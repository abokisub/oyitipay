<?php

namespace App\Services\Banking;

interface BankingProviderInterface
{
    /**
     * Get the unique slug of the provider (e.g., 'paystack', 'xixapay')
     */
    public function getProviderSlug(): string;

    /**
     * Fetch the list of supported banks from the provider.
     * Should return a standardized array of banks.
     * 
     * @return array [ ['name' => 'Bank Name', 'code' => '000', 'active' => true] ]
     */
    public function getBanks(): array;

    /**
     * Verify an account number.
     * 
     * @param string $accountNumber
     * @param string $bankCode The provider-specific bank code
     * @return array [ 'status' => 'success', 'data' => [ 'account_name' => '...', 'account_number' => '...' ] ]
     */
    public function verifyAccount(string $accountNumber, string $bankCode): array;

    /**
     * Initiate a transfer.
     * 
     * @param array $details [ 'amount', 'bank_code', 'account_number', 'account_name', 'narration', 'reference' ]
     * @return array [ 'status' => 'success/pending/fail', 'message' => '...', 'reference' => '...' ]
     */
    public function transfer(array $details): array;
    /**
     * Get provider balance (for Balance Guard).
     * @return float
     */
    public function getBalance(): float;

    /**
     * Query transfer status by reference.
     * @param string $reference
     * @return array [ 'status' => 'success/pending/failed', 'message' => '...' ]
     */
    public function queryTransfer(string $reference): array;
}
