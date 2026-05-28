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

class ProcessKobopointWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    protected $data;
    protected $transactionId;

    public function __construct(array $data, string $transactionId)
    {
        $this->data = $data;
        $this->transactionId = $transactionId;
    }

    public function handle()
    {
        Log::info('Processing Kobopoint webhook', [
            'transaction_id' => $this->transactionId
        ]);

        try {
            // Kobopoint sends PalmPay-style webhooks with orderStatus (1 = success)
            // Support both formats
            $orderStatus = $this->data['orderStatus'] ?? null;
            $notificationStatus = $this->data['notification_status'] ?? 'success';

            $isSuccess = ($orderStatus !== null)
                ? intval($orderStatus) === 1
                : in_array(strtolower($notificationStatus), ['successful', 'success']);

            if ($isSuccess) {
                $this->handleDeposit();
            } else {
                Log::warning('Kobopoint webhook: Unhandled/failed status', [
                    'orderStatus'        => $orderStatus,
                    'notificationStatus' => $notificationStatus,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Kobopoint webhook processing failed', [
                'transaction_id' => $this->transactionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } finally {
            DB::table('webhook_events')
                ->where('event_id', $this->transactionId)
                ->update([
                    'processed' => true,
                    'updated_at' => now()
                ]);
        }
    }

    private function handleDeposit()
    {
        // Real Kobopoint PalmPay webhook field names:
        // orderNo          = transaction ID
        // virtualAccountNo = the account number that was funded
        // orderAmount      = amount in KOBO (divide by 100 for Naira)
        $transactionId = $this->data['orderNo'] ?? ($this->data['transaction_id'] ?? $this->transactionId);
        $amountKobo    = floatval($this->data['orderAmount'] ?? ($this->data['amount_paid'] ?? 0));
        $amount        = $amountKobo / 100; // convert kobo → naira
        $kobopointFee  = floatval($this->data['settlement_fee'] ?? 0);

        // Account number - support both formats
        $accountNumber = $this->data['virtualAccountNo']
            ?? ($this->data['receiver']['account_number']
                ?? ($this->data['receiver.account_number'] ?? null));

        if (!$accountNumber) {
            Log::error('Kobopoint Webhook: Missing receiver account_number in payload', ['payload' => $this->data]);
            throw new \Exception('Account number not found in webhook data');
        }

        $virtualAccount = PointWaveVirtualAccount::where('account_number', $accountNumber)->first();

        if (!$virtualAccount) {
            Log::error('Kobopoint Webhook: Virtual account not found in database', ['account_number' => $accountNumber]);
            throw new \Exception('Virtual account not found: ' . $accountNumber);
        }

        $user = $virtualAccount->user;

        if (!$user) {
            Log::error('Kobopoint Webhook: User not found for virtual account', ['account_number' => $accountNumber]);
            throw new \Exception('User not found for virtual account');
        }

        $exists = PointWaveTransaction::where('pointwave_transaction_id', $transactionId)->exists();
        if ($exists) {
            Log::info('Kobopoint Webhook: Transaction already processed', ['transaction_id' => $transactionId]);
            return;
        }

        $settings = DB::table('settings')->first();
        $platformFee = 0;

        if ($settings && floatval($settings->pointwave_charge_value ?? 0) > 0) {
            $chargeType = $settings->pointwave_charge_type ?? 'PERCENTAGE';
            $chargeValue = floatval($settings->pointwave_charge_value);
            $feeCap = floatval($settings->pointwave_charge_cap ?? 0);

            if ($chargeType === 'PERCENTAGE') {
                $platformFee = ($amount * $chargeValue) / 100;
                if ($feeCap > 0 && $platformFee > $feeCap) {
                    $platformFee = $feeCap;
                }
            } elseif ($chargeType === 'FLAT') {
                $platformFee = $chargeValue;
            }
        }

        $finalAmount = $amount - $platformFee;
        if ($finalAmount < 0) {
            $finalAmount = 0;
        }

        DB::beginTransaction();

        try {
            $user->increment('bal', $finalAmount);

            PointWaveTransaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $amount,
                'fee' => $platformFee,
                'status' => 'completed',
                'reference' => 'KBP_' . uniqid(),
                'pointwave_transaction_id' => $transactionId,
                'pointwave_customer_id' => $virtualAccount->customer_id,
                'account_number' => $accountNumber,
                'description' => 'Deposit via Kobopoint',
                'metadata' => json_encode(array_merge($this->data, [
                    'kobopoint_fee_charged' => $kobopointFee
                ])),
            ]);

            $feeMessage = $platformFee > 0 ? sprintf(' (Fee: ₦%.2f)', $platformFee) : ' (Free Deposit)';

            DB::table('message')->insert([
                'username' => $user->username,
                'amount' => $finalAmount,
                'message' => 'Wallet Funded via Kobopoint' . $feeMessage,
                'oldbal' => $user->bal - $finalAmount,
                'newbal' => $user->bal,
                'habukhan_date' => now(),
                'plan_status' => 1,
                'transid' => $transactionId,
                'role' => 'credit'
            ]);

            DB::commit();

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
                        null,
                        false
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to send push notification for Kobopoint deposit', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Kobopoint deposit processed successfully', [
                'user_id' => $user->id,
                'customer_deposit' => $amount,
                'customer_credited' => $finalAmount,
                'transaction_id' => $transactionId,
                'new_balance' => $user->bal
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Kobopoint Webhook: Failed to save transaction to database', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'transaction_id' => $transactionId
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Kobopoint Webhook processing job failed permanently', [
            'event_id' => $this->transactionId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
