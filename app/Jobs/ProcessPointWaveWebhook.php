<?php

namespace App\Jobs;

use App\Models\PointWaveVirtualAccount;
use App\Models\PointWaveTransaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPointWaveWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * Webhook data
     *
     * @var array
     */
    protected $data;

    /**
     * Event ID
     *
     * @var string
     */
    protected $eventId;

    /**
     * Create a new job instance.
     *
     * @param array $data
     * @param string $eventId
     * @return void
     */
    public function __construct(array $data, string $eventId)
    {
        $this->data = $data;
        $this->eventId = $eventId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $eventType = $this->data['event'];

        Log::info('Processing PointWave webhook', [
            'event_id' => $this->eventId,
            'event_type' => $eventType
        ]);

        try {
            switch ($eventType) {
                case 'payment.success':
                case 'transaction.success':
                    $this->handlePaymentSuccess();
                    break;

                case 'payment.failed':
                case 'transaction.failed':
                    $this->handlePaymentFailed();
                    break;

                case 'transfer.success':
                    $this->handleTransferSuccess();
                    break;

                case 'transfer.failed':
                    $this->handleTransferFailed();
                    break;

                default:
                    Log::warning('Unknown PointWave event type', [
                        'event_type' => $eventType
                    ]);
            }

            // Mark as processed
            DB::table('webhook_events')
                ->where('event_id', $this->eventId)
                ->update(['processed' => true, 'updated_at' => now()]);

        } catch (\Exception $e) {
            Log::error('Error processing PointWave webhook', [
                'event_id' => $this->eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to trigger job retry
        }
    }

    /**
     * Handle payment success
     *
     * @return void
     */
    private function handlePaymentSuccess()
    {
        $transactionData = $this->data['data'];

        // Extract transaction details
        $transactionId = $transactionData['transaction_id'];
        $amount = floatval($transactionData['amount']);
        $fee = floatval($transactionData['fee'] ?? 0);
        
        // Calculate net_amount if not provided
        $netAmount = isset($transactionData['net_amount']) && $transactionData['net_amount'] !== null
            ? floatval($transactionData['net_amount'])
            : ($amount - $fee);
            
        $reference = $transactionData['reference'];
        
        // Get virtual account number from customer data
        $accountNumber = $transactionData['customer']['account_number'] ?? null;
        
        if (!$accountNumber) {
            // Try virtual_account data
            $accountNumber = $transactionData['virtual_account']['account_number'] ?? null;
        }

        if (!$accountNumber) {
            throw new \Exception('Account number not found in webhook data');
        }

        // Find user by virtual account number
        $virtualAccount = PointWaveVirtualAccount::where('account_number', $accountNumber)->first();
        $customerId = null;
        $user = null;

        if (!$virtualAccount) {
            // Fallback: Check user table directly (for accounts created before bank_code fix)
            Log::info('Virtual account not in pointwave_virtual_accounts table, checking user table', [
                'account_number' => $accountNumber
            ]);
            
            $userRecord = \DB::table('user')
                ->where('pointwave_account_number', $accountNumber)
                ->first();
                
            if (!$userRecord) {
                throw new \Exception('Virtual account not found for account_number: ' . $accountNumber);
            }
            
            // Convert stdClass to Eloquent model
            $user = \App\Models\User::find($userRecord->id);
            
            if (!$user) {
                throw new \Exception('User model not found for user_id: ' . $userRecord->id);
            }
            
            // Get customer_id from user record or webhook data
            $customerId = $user->pointwave_customer_id 
                       ?? $transactionData['customer']['customer_id'] 
                       ?? $transactionData['customer_id']
                       ?? null;
            
            Log::info('Found user via fallback lookup', [
                'user_id' => $user->id,
                'username' => $user->username,
                'customer_id' => $customerId
            ]);
        } else {
            $user = $virtualAccount->user;
            $customerId = $virtualAccount->customer_id;
        }

        if (!$user) {
            throw new \Exception('User not found for virtual account');
        }

        // Get platform charge settings
        $settings = DB::table('settings')->first();
        $kobopointFee = 0;
        
        if ($settings) {
            $chargeType = strtoupper($settings->pointwave_charge_type ?? '');
            $chargeValue = floatval($settings->pointwave_charge_value ?? 0);
            
            // Only apply platform charges if admin has set a charge > 0
            if ($chargeValue > 0) {
                if ($chargeType === 'PERCENTAGE') {
                    // Calculate percentage fee on the original amount (before PointWave fee)
                    $feeCap = floatval($settings->pointwave_charge_cap ?? 0);
                    $kobopointFee = ($amount * $chargeValue) / 100;
                    
                    // Apply cap if set
                    if ($feeCap > 0 && $kobopointFee > $feeCap) {
                        $kobopointFee = $feeCap;
                    }
                } elseif ($chargeType === 'FLAT') {
                    // Flat fee
                    $kobopointFee = $chargeValue;
                }
            }
            // If admin sets charge to 0.0, kobopointFee remains 0
        }
        
        // Final amount calculation:
        // - If admin charge is 0.0: Credit full net_amount (amount - pointwave_fee)
        // - If admin charge > 0: Credit net_amount - platform_fee
        $finalAmount = $netAmount - $kobopointFee;
        
        // Ensure we don't credit negative amounts
        if ($finalAmount < 0) {
            $finalAmount = 0;
        }

        DB::beginTransaction();

        try {
            // Credit user wallet with final amount (after Kobopoint's fee)
            $user->increment('bal', $finalAmount);

            // Create transaction record in pointwave_transactions table
            PointWaveTransaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $amount,
                'fee' => $fee + $kobopointFee, // Total fee (PointWave + Platform)
                'status' => 'completed',
                'reference' => $reference,
                'pointwave_transaction_id' => $transactionId,
                'pointwave_customer_id' => $customerId,
                'description' => 'Deposit via PointWave',
                'metadata' => json_encode($transactionData),
            ]);

            // Create message for transaction history with clear fee breakdown
            $feeMessage = '';
            if ($kobopointFee > 0) {
                $feeMessage = sprintf(' (PointWave Fee: ₦%.2f, Platform Fee: ₦%.2f)', $fee, $kobopointFee);
            } else {
                $feeMessage = sprintf(' (PointWave Fee: ₦%.2f)', $fee);
            }

            // Also create transaction in message table for transaction history
            DB::table('message')->insert([
                'username' => $user->username,
                'amount' => $finalAmount,
                'message' => 'Wallet Funded via PointWave' . $feeMessage,
                'oldbal' => $user->bal - $finalAmount,
                'newbal' => $user->bal,
                'habukhan_date' => now(),
                'plan_status' => 1,
                'transid' => $transactionId,
                'role' => 'credit' // Changed from 'pointwave_deposit' to 'credit' for mobile app Money IN display
            ]);

            DB::commit();

            // Send push notification to user
            if ($user->app_token) {
                try {
                    $firebase = new \App\Services\FirebaseService();
                    $firebase->sendNotification(
                        $user->app_token,
                        'Wallet Funded',
                        sprintf('Your wallet has been credited with ₦%s', number_format($finalAmount, 2)),
                        [
                            'type' => 'wallet_credit',
                            'amount' => (string)$finalAmount,
                            'transaction_id' => $transactionId,
                            'channel_id' => 'transaction_channel',
                        ],
                        null, // no image
                        false // NOT data-only, show notification
                    );
                    Log::info('Push notification sent for PointWave deposit', ['user_id' => $user->id]);
                } catch (\Exception $e) {
                    // Don't fail the transaction if notification fails
                    Log::error('Failed to send push notification for PointWave deposit', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('User wallet credited from PointWave', [
                'user_id' => $user->id,
                'gross_amount' => $amount,
                'pointwave_fee' => $fee,
                'platform_fee' => $kobopointFee,
                'net_from_pointwave' => $netAmount,
                'final_credited_amount' => $finalAmount,
                'transaction_id' => $transactionId,
                'new_balance' => $user->bal,
                'admin_charge_setting' => $settings->pointwave_charge_value ?? 0
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle payment failed
     *
     * @return void
     */
    private function handlePaymentFailed()
    {
        Log::warning('PointWave payment failed', $this->data);
        // Handle failed payment if needed
    }

    /**
     * Handle transfer success
     *
     * @return void
     */
    private function handleTransferSuccess()
    {
        $transactionData = $this->data['data'];
        $reference = $transactionData['reference'] ?? null;
        $transactionId = $transactionData['transaction_id'] ?? null;

        if (!$reference) {
            throw new \Exception('Missing reference in transfer.success webhook');
        }

        // Find transaction by reference
        $transaction = PointWaveTransaction::where('reference', $reference)->first();

        if (!$transaction) {
            Log::warning('Transaction not found for reference', ['reference' => $reference]);
            return;
        }

        // Update transaction status
        $transaction->update([
            'status' => 'successful',
            'pointwave_transaction_id' => $transactionId,
        ]);

        Log::info('Transfer success processed', [
            'reference' => $reference,
            'transaction_id' => $transactionId
        ]);
    }

    /**
     * Handle transfer failed
     *
     * @return void
     */
    private function handleTransferFailed()
    {
        $transactionData = $this->data['data'];
        $reference = $transactionData['reference'] ?? null;
        $transactionId = $transactionData['transaction_id'] ?? null;
        $reason = $transactionData['reason'] ?? $transactionData['message'] ?? 'Transfer failed';

        if (!$reference) {
            throw new \Exception('Missing reference in transfer.failed webhook');
        }

        // Find transaction by reference
        $transaction = PointWaveTransaction::where('reference', $reference)->first();

        if (!$transaction) {
            Log::warning('Transaction not found for reference', ['reference' => $reference]);
            return;
        }

        $user = $transaction->user;

        DB::beginTransaction();

        try {
            // Refund user wallet (amount + fee)
            $refundAmount = $transaction->amount + $transaction->fee;
            $user->increment('bal', $refundAmount);

            // Update transaction status
            $transaction->update([
                'status' => 'failed',
                'pointwave_transaction_id' => $transactionId,
                'narration' => 'Transfer failed: ' . $reason,
            ]);

            DB::commit();

            Log::info('Transfer failed processed and refunded', [
                'reference' => $reference,
                'transaction_id' => $transactionId,
                'refund_amount' => $refundAmount,
                'reason' => $reason
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Webhook processing job failed permanently', [
            'event_id' => $this->eventId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
