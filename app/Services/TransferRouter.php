<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Exception;

class TransferRouter
{
    protected $bankingService;

    public function __construct(\App\Services\Banking\BankingService $bankingService)
    {
        $this->bankingService = $bankingService;
    }

    /**
     * Process a transfer request.
     *
     * @param array $details
     * @return array
     */
    public function processTransfer(array $details)
    {
        // 1. Check Global Lock
        $settings = DB::table('settings')->first();
        if ($settings && $settings->transfer_lock_all) {
            throw new Exception("Transfer service is currently disabled by administrator.");
        }

        // 2. Delegate to BankingService (Unified logic)
        return $this->bankingService->transfer($details);
    }
}
