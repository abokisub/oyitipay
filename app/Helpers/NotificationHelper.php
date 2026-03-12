<?php

namespace App\Helpers;

use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class NotificationHelper
{
    /**
     * Send transaction notification to user
     * 
     * @param object $user User object with app_token
     * @param string $type Transaction type (credit/debit)
     * @param float $amount Transaction amount
     * @param string $description Transaction description
     * @param string $transactionId Transaction ID
     * @return bool Success status
     */
    public static function sendTransactionNotification($user, $type, $amount, $description, $transactionId = null)
    {
        // Check if user has FCM token
        if (!$user || !$user->app_token) {
            return false;
        }

        try {
            $firebase = new FirebaseService();
            
            // Determine title and body based on transaction type
            if ($type === 'credit' || $type === 'deposit' || $type === 'funding') {
                $title = 'Wallet Funded';
                $body = sprintf('Your wallet has been credited with ₦%s', number_format($amount, 2));
                $notifType = 'wallet_credit';
            } else {
                // Debit transactions
                $title = 'Transaction Successful';
                $body = sprintf('%s - ₦%s', $description, number_format($amount, 2));
                $notifType = 'transaction_debit';
            }
            
            $firebase->sendNotification(
                $user->app_token,
                $title,
                $body,
                [
                    'type' => $notifType,
                    'amount' => (string)$amount,
                    'description' => $description,
                    'transaction_id' => (string)($transactionId ?? ''),
                    'channel_id' => 'transaction_channel',
                ],
                null, // no image
                false // NOT data-only, show notification
            );
            
            Log::info('Transaction notification sent', [
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount
            ]);
            
            return true;
        } catch (\Exception $e) {
            // Don't fail the transaction if notification fails
            Log::error('Failed to send transaction notification', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Send airtime purchase notification
     */
    public static function sendAirtimeNotification($user, $amount, $phone, $network)
    {
        return self::sendTransactionNotification(
            $user,
            'debit',
            $amount,
            sprintf('Airtime purchase - %s to %s', $network, $phone)
        );
    }
    
    /**
     * Send data purchase notification
     */
    public static function sendDataNotification($user, $amount, $phone, $network, $plan)
    {
        return self::sendTransactionNotification(
            $user,
            'debit',
            $amount,
            sprintf('Data purchase - %s %s', $network, $plan)
        );
    }
    
    /**
     * Send cable TV notification
     */
    public static function sendCableNotification($user, $amount, $provider, $smartcard)
    {
        return self::sendTransactionNotification(
            $user,
            'debit',
            $amount,
            sprintf('Cable TV - %s subscription', $provider)
        );
    }
    
    /**
     * Send electricity notification
     */
    public static function sendElectricityNotification($user, $amount, $disco, $meterNumber)
    {
        return self::sendTransactionNotification(
            $user,
            'debit',
            $amount,
            sprintf('Electricity - %s', $disco)
        );
    }
    
    /**
     * Send bank transfer notification
     */
    public static function sendTransferNotification($user, $amount, $recipientName, $bank)
    {
        return self::sendTransactionNotification(
            $user,
            'debit',
            $amount,
            sprintf('Transfer to %s - %s', $recipientName, $bank)
        );
    }
    
    /**
     * Send wallet funding notification (deposits)
     */
    public static function sendDepositNotification($user, $amount, $source = 'Virtual Account')
    {
        return self::sendTransactionNotification(
            $user,
            'credit',
            $amount,
            sprintf('Wallet funded via %s', $source)
        );
    }
}

