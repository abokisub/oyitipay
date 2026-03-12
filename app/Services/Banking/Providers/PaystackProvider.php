<?php

namespace App\Services\Banking\Providers;

use App\Services\Banking\BankingProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackProvider implements BankingProviderInterface
{
    protected $secretKey;
    protected $publicKey;

    public function __construct()
    {
        $key = DB::table('habukhan_key')->first();
        // Map 'psk' (Secret) and 'plive' (Public) from habukhan_key table
        $this->secretKey = $key->psk ?? config('app.paystack_secret_key');
        $this->publicKey = $key->plive ?? config('app.paystack_public_key');
    }

    public function getProviderSlug(): string
    {
        return 'paystack';
    }

    public function getBanks(): array
    {
        if (!$this->secretKey) {
            throw new \Exception('Paystack API key not configured');
        }

        $allBanks = [];
        $page = 1;
        $perPage = 100;

        do {
            $response = Http::timeout(120) // Increased for slow connections
                ->withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json'
            ])
                ->get('https://api.paystack.co/bank', [
                'country' => 'nigeria',
                'perPage' => $perPage,
                'page' => $page
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch banks from Paystack (Page ' . $page . '): ' . $response->body());
            }

            $data = $response->json();
            $banksChunk = $data['data'] ?? [];

            if (empty($banksChunk)) {
                break;
            }

            $allBanks = array_merge($allBanks, $banksChunk);
            $page++;

        } while (count($banksChunk) >= $perPage);

        return collect($allBanks)->map(function ($bank) {
            return [
                'name' => $bank['name'],
                'code' => $bank['code'], // Paystack code (usually NUBAN consistent)
                'slug' => $bank['slug'] ?? strtolower(str_replace(' ', '-', $bank['name'])),
                'active' => $bank['active'] ?? true,
                'paystack_code' => $bank['code'] // Store explicitly for Paystack
            ];
        })
            ->filter(function ($bank) {
            return $bank['active'] === true;
        })
            ->values()
            ->toArray();
    }

    public function verifyAccount(string $accountNumber, string $bankCode): array
    {
        if (!$this->secretKey) {
            throw new \Exception('Paystack API key not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json'
        ])->get("https://api.paystack.co/bank/resolve", [
            'account_number' => $accountNumber,
            'bank_code' => $bankCode
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'status' => 'success',
                'data' => [
                    'account_name' => $data['data']['account_name'],
                    'account_number' => $data['data']['account_number'],
                    'bank_code' => $bankCode
                ]
            ];
        }

        throw new \Exception('Paystack verification failed: ' . $response->body());
    }

    public function transfer(array $details): array
    {
        // 1. Create Transfer Recipient
        // 2. Initiate Transfer
        // For simplicity, adapting existing TransferRouter logic which might just be "Initiate" if recipient exists?
        // TransferRouter.php processPaystack implies direct transfer endpoint usage?
        // Actually Paystack requires creating a recipient first usually.
        // Let's stick to the TransferRouter logic for now to ensure compatibility.

        $url = "https://api.paystack.co/transfer";
        $recipientUrl = "https://api.paystack.co/transferrecipient";

        // Step 1: Create/Resolve Recipient
        $recipientResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json'
        ])->post($recipientUrl, [
            "type" => "nuban",
            "name" => $details['account_name'],
            "account_number" => $details['account_number'],
            "bank_code" => $details['bank_code'],
            "currency" => "NGN"
        ]);

        if (!$recipientResponse->successful()) {
            return [
                'status' => 'fail',
                'message' => 'Failed to create transfer recipient: ' . $recipientResponse->json()['message'] ?? 'Unknown error'
            ];
        }

        $recipientCode = $recipientResponse->json()['data']['recipient_code'];

        // Step 2: Initiate Transfer
        $transferResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json'
        ])->post($url, [
            "source" => "balance",
            "reason" => $details['narration'] ?? 'Transfer',
            "amount" => $details['amount'] * 100, // Paystack is in Kobo
            "recipient" => $recipientCode,
            "reference" => $details['reference']
        ]);

        if ($transferResponse->successful()) {
            return [
                'status' => 'pending', // Paystack transfers are async usually
                'message' => 'Transfer initiated successfully',
                'reference' => $details['reference'],
                'provider_reference' => $transferResponse->json()['data']['reference'] ?? null
            ];
        }

        $responseData = $transferResponse->json();
        $errorMessage = $responseData['message'] ?? $transferResponse->body();

        // Handle specific balance/liquidity errors with friendly messages
        if (str_contains($errorMessage, 'Insufficient Funds') ||
        str_contains($errorMessage, 'Low Liquidity') ||
        str_contains($errorMessage, 'balance') ||
        $transferResponse->status() === 400 && str_contains($errorMessage, 'Transfer failed')) {
            $errorMessage = "Service temporarily unavailable due to low provider liquidity. Please try again later.";
        }

        return [
            'status' => 'fail',
            'message' => 'Transfer failed: ' . $errorMessage
        ];
    }
    public function getBalance(): float
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json'
        ])->get('https://api.paystack.co/balance');

        if ($response->successful()) {
            $data = $response->json();
            // Paystack balance is in Kobo!
            return ($data['data'][0]['balance'] ?? 0) / 100;
        }

        return 0.0;
    }

    public function queryTransfer(string $reference): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json'
        ])->get("https://api.paystack.co/transfer/verify/" . $reference);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'status' => $data['data']['status'] ?? 'unknown', // success, failed, pending
                'message' => $data['message'] ?? 'Status retrieved',
                'data' => $data['data']
            ];
        }

        return [
            'status' => 'failed',
            'message' => 'Query failed: ' . $response->body()
        ];
    }
}