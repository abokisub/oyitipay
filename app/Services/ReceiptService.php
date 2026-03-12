<?php

namespace App\Services;

use Exception;

class ReceiptService
{
    /**
     * Strict service type templates
     * NEVER modify these without explicit approval
     */
    private const TEMPLATES = [
        'AIRTIME' => [
            'title' => 'Airtime Purchase Successful',
            'message' => 'You have successfully purchased ₦{amount} airtime for {recipient}.',
            'icon' => '📱'
        ],
        'DATA' => [
            'title' => 'Data Purchase Successful',
            'message' => 'You have successfully purchased {plan} data for {recipient}.',
            'icon' => '📶'
        ],
        'ELECTRICITY' => [
            'title' => 'Electricity Bill Payment Successful',
            'message' => 'Meter Number: {meter_no}\nToken: {token}\nAmount: ₦{amount}',
            'icon' => '⚡'
        ],
        'TV' => [
            'title' => 'TV Subscription Successful',
            'message' => 'Package: {package}\nSmart Card Number: {recipient}\nAmount: ₦{amount}',
            'icon' => '📺'
        ],
        'EDU_PIN' => [
            'title' => 'Education PIN Purchase Successful',
            'message' => 'Exam Type: {exam}\nPIN: {pin}\nSerial: {serial}\nAmount: ₦{amount}',
            'icon' => '📝'
        ],
        'AIRTIME_TO_CASH' => [
            'title' => 'Airtime to Cash Successful',
            'message' => 'Airtime worth ₦{amount} has been converted to wallet balance.',
            'icon' => '💸'
        ],
        'CHARITY' => [
            'title' => 'Charity Donation Successful',
            'message' => 'You donated ₦{amount} to {charity_name}.\nCampaign: {campaign}',
            'icon' => '❤️'
        ],
        'AIRTIME_PIN' => [
            'title' => 'Airtime PIN Generated Successfully',
            'message' => 'Network: {network}\nPIN: {pin}\nAmount: ₦{amount}',
            'icon' => '🔢'
        ],
        'BANK_TRANSFER' => [
            'title' => 'Bank Transfer Successful',
            'message' => 'Amount: ₦{amount}\nBeneficiary: {account_name}\nAccount Number: {account_number}\nBank: {bank_name}',
            'icon' => '🏦'
        ],
        'INTERNAL_TRANSFER' => [
            'title' => 'Internal Transfer Successful',
            'message' => 'Amount: ₦{amount}\nRecipient: {recipient_username}',
            'icon' => '💰'
        ],
        'DATA_CARD' => [
            'title' => 'Data Card Printing Successful',
            'message' => 'Network: {network}\nPlan: {plan}\nQuantity: {quantity}\nAmount: ₦{amount}',
            'icon' => '📇'
        ],
        'RECHARGE_CARD' => [
            'title' => 'Recharge Card Printing Successful',
            'message' => 'Network: {network}\nQuantity: {quantity}\nAmount: ₦{amount}',
            'icon' => '🎫'
        ],
    ];

    /**
     * Generate receipt message based on service type
     * 
     * @param string $serviceType Service type (AIRTIME, DATA, etc.)
     * @param array $data Transaction data
     * @return array Receipt with title, message, and metadata
     * @throws Exception if service type is invalid
     */
    public function generate(string $serviceType, array $data): array
    {
        // STRICT VALIDATION - NO FALLBACK
        if (!isset(self::TEMPLATES[$serviceType])) {
            throw new Exception("Invalid service type: {$serviceType}. Receipt generation requires valid service_type.");
        }

        $template = self::TEMPLATES[$serviceType];

        return [
            'title' => $template['title'],
            'message' => $this->interpolate($template['message'], $data),
            'icon' => $template['icon'],
            'meta' => [
                'service_type' => $serviceType,
                'reference' => $data['reference'] ?? '',
                'status' => $data['status'] ?? 'SUCCESS',
                'transaction_channel' => $data['transaction_channel'] ?? 'EXTERNAL',
                'provider' => $data['provider'] ?? null,
            ]
        ];
    }

    /**
     * Interpolate template variables with actual data
     * 
     * @param string $template Template string with {variables}
     * @param array $data Data to interpolate
     * @return string Interpolated message
     */
    private function interpolate(string $template, array $data): string
    {
        return preg_replace_callback('/{(\w+)}/', function ($matches) use ($data) {
            $key = $matches[1];
            return $data[$key] ?? $matches[0]; // Keep placeholder if data missing
        }, $template);
    }

    /**
     * Get full receipt message for database storage
     * Combines title and message
     * 
     * @param string $serviceType Service type
     * @param array $data Transaction data
     * @return string Full receipt message
     */
    public function getFullMessage(string $serviceType, array $data): string
    {
        $receipt = $this->generate($serviceType, $data);
        return $receipt['icon'] . ' ' . $receipt['title'] . "\n\n" . $receipt['message'];
    }

    /**
     * Get available service types
     * 
     * @return array List of valid service types
     */
    public static function getServiceTypes(): array
    {
        return array_keys(self::TEMPLATES);
    }

    /**
     * Validate service type
     * 
     * @param string $serviceType Service type to validate
     * @return bool True if valid
     */
    public static function isValidServiceType(string $serviceType): bool
    {
        return isset(self::TEMPLATES[$serviceType]);
    }
}