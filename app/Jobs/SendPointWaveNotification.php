<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPointWaveNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * User to notify
     *
     * @var User
     */
    protected $user;

    /**
     * Notification type
     *
     * @var string
     */
    protected $notificationType;

    /**
     * Notification data
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
     *
     * @param User $user
     * @param string $notificationType
     * @param array $data
     * @return void
     */
    public function __construct(User $user, string $notificationType, array $data)
    {
        $this->user = $user;
        $this->notificationType = $notificationType;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            switch ($this->notificationType) {
                case 'payment_received':
                    $this->sendPaymentReceivedNotification();
                    break;

                case 'transfer_success':
                    $this->sendTransferSuccessNotification();
                    break;

                case 'transfer_failed':
                    $this->sendTransferFailedNotification();
                    break;

                case 'transfer_refunded':
                    $this->sendTransferRefundedNotification();
                    break;

                default:
                    Log::channel('pointwave')->warning('Unknown notification type', [
                        'type' => $this->notificationType,
                        'user_id' => $this->user->id,
                    ]);
            }

        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Notification sending failed', [
                'user_id' => $this->user->id,
                'type' => $this->notificationType,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Send payment received notification
     *
     * @return void
     */
    private function sendPaymentReceivedNotification()
    {
        $amount = $this->data['amount'] ?? 0;
        $reference = $this->data['reference'] ?? 'N/A';
        $newBalance = $this->data['new_balance'] ?? 0;

        $message = sprintf(
            "Your PointWave account has been credited with ₦%s. Reference: %s. New balance: ₦%s",
            number_format($amount, 2),
            $reference,
            number_format($newBalance, 2)
        );

        // Send email notification
        if ($this->user->email) {
            try {
                Mail::raw($message, function ($mail) use ($amount) {
                    $mail->to($this->user->email)
                        ->subject('Payment Received - ₦' . number_format($amount, 2));
                });

                Log::channel('pointwave')->info('Payment received email sent', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                ]);
            } catch (\Exception $e) {
                Log::channel('pointwave')->error('Failed to send payment email', [
                    'user_id' => $this->user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // TODO: Add SMS notification if phone number exists
        // TODO: Add in-app notification using Laravel notification system

        Log::channel('pointwave')->info('Payment received notification sent', [
            'user_id' => $this->user->id,
            'amount' => $amount,
        ]);
    }

    /**
     * Send transfer success notification
     *
     * @return void
     */
    private function sendTransferSuccessNotification()
    {
        $amount = $this->data['amount'] ?? 0;
        $reference = $this->data['reference'] ?? 'N/A';
        $accountNumber = $this->data['account_number'] ?? 'N/A';
        $accountName = $this->data['account_name'] ?? 'N/A';

        $message = sprintf(
            "Your transfer of ₦%s to %s (%s) was successful. Reference: %s",
            number_format($amount, 2),
            $accountName,
            $accountNumber,
            $reference
        );

        // Send email notification
        if ($this->user->email) {
            try {
                Mail::raw($message, function ($mail) use ($amount) {
                    $mail->to($this->user->email)
                        ->subject('Transfer Successful - ₦' . number_format($amount, 2));
                });

                Log::channel('pointwave')->info('Transfer success email sent', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                ]);
            } catch (\Exception $e) {
                Log::channel('pointwave')->error('Failed to send transfer success email', [
                    'user_id' => $this->user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::channel('pointwave')->info('Transfer success notification sent', [
            'user_id' => $this->user->id,
            'amount' => $amount,
        ]);
    }

    /**
     * Send transfer failed notification
     *
     * @return void
     */
    private function sendTransferFailedNotification()
    {
        $amount = $this->data['amount'] ?? 0;
        $reference = $this->data['reference'] ?? 'N/A';
        $reason = $this->data['reason'] ?? 'Unknown error';
        $refundAmount = $this->data['refund_amount'] ?? 0;

        $message = sprintf(
            "Your transfer of ₦%s failed. Reason: %s. ₦%s has been refunded to your wallet. Reference: %s",
            number_format($amount, 2),
            $reason,
            number_format($refundAmount, 2),
            $reference
        );

        // Send email notification
        if ($this->user->email) {
            try {
                Mail::raw($message, function ($mail) use ($amount) {
                    $mail->to($this->user->email)
                        ->subject('Transfer Failed - ₦' . number_format($amount, 2));
                });

                Log::channel('pointwave')->info('Transfer failed email sent', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                ]);
            } catch (\Exception $e) {
                Log::channel('pointwave')->error('Failed to send transfer failed email', [
                    'user_id' => $this->user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::channel('pointwave')->info('Transfer failed notification sent', [
            'user_id' => $this->user->id,
            'amount' => $amount,
            'reason' => $reason,
        ]);
    }

    /**
     * Send transfer refunded notification
     *
     * @return void
     */
    private function sendTransferRefundedNotification()
    {
        $amount = $this->data['amount'] ?? 0;
        $fee = $this->data['fee'] ?? 0;
        $refundAmount = $this->data['refund_amount'] ?? 0;
        $reference = $this->data['reference'] ?? 'N/A';
        $reason = $this->data['reason'] ?? 'Admin refund';
        $accountNumber = $this->data['account_number'] ?? 'N/A';
        $accountName = $this->data['account_name'] ?? 'N/A';

        $message = sprintf(
            "Your transfer of ₦%s to %s (%s) has been refunded by an administrator. Reason: %s. ₦%s (including ₦%s fee) has been credited to your wallet. Reference: %s",
            number_format($amount, 2),
            $accountName,
            $accountNumber,
            $reason,
            number_format($refundAmount, 2),
            number_format($fee, 2),
            $reference
        );

        // Send email notification
        if ($this->user->email) {
            try {
                Mail::raw($message, function ($mail) use ($refundAmount) {
                    $mail->to($this->user->email)
                        ->subject('Transfer Refunded - ₦' . number_format($refundAmount, 2));
                });

                Log::channel('pointwave')->info('Transfer refunded email sent', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                ]);
            } catch (\Exception $e) {
                Log::channel('pointwave')->error('Failed to send transfer refunded email', [
                    'user_id' => $this->user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::channel('pointwave')->info('Transfer refunded notification sent', [
            'user_id' => $this->user->id,
            'refund_amount' => $refundAmount,
            'reason' => $reason,
        ]);
    }
}
