<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ProfileController extends Controller
{
    /**
     * Get Account Limits based on KYC Tier
     * GET /api/profile/limits
     */
    public function getLimits(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');

        if (!$origin || in_array($origin, $explode_url)) {
            $user = DB::table('user')->where('id', $request->user()->id)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            // Determine tier based on KYC tier from user table
            $tier = $this->getUserTier($user);
            
            // Use actual limits from user table (set during KYC verification)
            $singleLimit = $user->single_limit ?? 0;
            $dailyLimit = $user->daily_limit ?? 0;
            
            // If limits are not set, use tier defaults
            if ($singleLimit == 0 && $dailyLimit == 0) {
                $limits = $this->getTierLimits($tier);
                $singleLimit = $limits['single'];
                $dailyLimit = $limits['daily'];
            }

            // Calculate daily usage
            $dailyUsed = $this->calculateDailyUsage($user->username);

            // Get next tier limits
            $nextTier = $tier < 2 ? $tier + 1 : null;
            $nextTierLimits = $nextTier ? $this->getTierLimits($nextTier) : null;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'tier' => $tier,
                    'tier_name' => $this->getTierName($tier),
                    'single_limit' => $singleLimit,
                    'daily_limit' => $dailyLimit,
                    'daily_used' => $dailyUsed,
                    'daily_remaining' => max(0, $dailyLimit - $dailyUsed),
                    'usage_percentage' => $dailyLimit > 0 ? min(100, round(($dailyUsed / $dailyLimit) * 100, 2)) : 0,
                    'next_tier_single' => $nextTierLimits ? $nextTierLimits['single'] : null,
                    'next_tier_daily' => $nextTierLimits ? $nextTierLimits['daily'] : null,
                    'kyc_status' => $user->kyc_status ?? 'pending',
                    'can_upgrade' => $tier < 2,
                    'upgrade_message' => $this->getUpgradeMessage($tier, $user->kyc_status),
                    'theme' => $this->getUserTheme($user->id)
                ]
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to Authenticate System'
            ], 403);
        }
    }

    /**
     * Generate Account Statement
     * POST /api/profile/statement
     */
    public function generateStatement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|in:email,download'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()->first()
            ], 400);
        }

        $user = $request->user();

        $type = $request->input('type', 'email');

        // Get transactions for date range
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $transactions = DB::table('message')
            ->where('username', $user->username)
            ->whereBetween('habukhan_date', [$startDate, $endDate])
            ->orderBy('habukhan_date', 'desc')
            ->get();

        $general = $this->general();
        $emailData = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'username' => $user->username,
            'app_name' => config('app.name'),
            'start_date' => $startDate->format('d M Y'),
            'end_date' => $endDate->format('d M Y'),
            'transactions' => $transactions,
            'total_debit' => $transactions->sum('amount'),
            'opening_balance' => $user->bal, // In a real app, this would be calculated
            'closing_balance' => $user->bal
        ];

        if ($type === 'download') {
            $pdf = \PDF::loadView('pdf.statement', $emailData);
            return $pdf->download('Statement_' . $user->username . '_' . date('Ymd') . '.pdf');
        }

        // Send statement via email with PDF attachment
        $this->sendStatementEmailWithPdf($user, $emailData);

        return response()->json([
            'status' => 'success',
            'message' => 'Statement has been sent to your email address',
            'transactions_count' => count($transactions)
        ]);
    }
    public function updateTheme(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme' => 'required|in:light,dark,system',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()->first()
            ], 400);
        }

        $user = $request->user();


        DB::table('user_settings')->updateOrInsert(
            ['user_id' => $user->id],
            ['theme' => $request->theme, 'updated_at' => now()]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Theme updated successfully',
            'theme' => $request->theme
        ]);
    }

    /**
     * Get user theme from settings
     */
    private function getUserTheme($userId): string
    {
        $setting = DB::table('user_settings')->where('user_id', $userId)->first();
        return $setting->theme ?? 'light';
    }

    /**
     * Determine user tier based on KYC tier from database
     */
    private function getUserTier($user): int
    {
        // Read actual kyc_tier from user table (tier_0, tier_1, tier_2)
        $kycTier = $user->kyc_tier ?? 'tier_0';
        
        // Map tier strings to tier numbers for display
        switch ($kycTier) {
            case 'tier_2':
                return 2; // Silver (BVN verified)
            case 'tier_1':
                return 1; // Bronze (NIN verified)
            case 'tier_0':
            default:
                return 0; // No KYC
        }
    }

    /**
     * Get tier limits - use actual limits from user table
     */
    private function getTierLimits(int $tier): array
    {
        // These are fallback defaults if user table doesn't have limits set
        $limits = [
            0 => ['single' => 0, 'daily' => 0],               // Tier 0: No transfers
            1 => ['single' => 50000, 'daily' => 200000],      // Tier 1: NIN verified
            2 => ['single' => 500000, 'daily' => 2000000],    // Tier 2: BVN verified
        ];

        return $limits[$tier] ?? $limits[0];
    }

    /**
     * Get tier name
     */
    private function getTierName(int $tier): string
    {
        $names = [
            0 => 'No KYC',
            1 => 'Bronze',
            2 => 'Silver',
        ];

        return $names[$tier] ?? 'No KYC';
    }

    /**
     * Calculate daily usage
     */
    private function calculateDailyUsage(string $username): float
    {
        $today = Carbon::today('Africa/Lagos');

        $transactions = DB::table('message')
            ->where('username', $username)
            ->whereDate('habukhan_date', $today)
            ->whereIn('role', ['DATA', 'AIRTIME', 'CABLE', 'BILL', 'TRANSFER', 'EXAM', 'BULKSMS', 'transfer_sent'])
            ->where('plan_status', 1)
            ->sum('amount');

        return (float) $transactions;
    }

    /**
     * Get upgrade message based on tier and KYC status
     */
    private function getUpgradeMessage(int $tier, ?string $kycStatus): ?string
    {
        if ($tier >= 2) {
            return null; // Already at max tier (BVN verified)
        }

        if ($tier === 0) {
            return 'Verify your NIN to upgrade to Tier 1 (Bronze) and unlock bank transfers';
        }

        if ($tier === 1) {
            return 'Verify your BVN to upgrade to Tier 2 (Silver) and increase your limits';
        }

        return 'Complete KYC to unlock higher limits';
    }

    /**
     * Send statement email with PDF attachment
     */
    private function sendStatementEmailWithPdf($user, $emailData)
    {
        try {
            $pdf = \PDF::loadView('pdf.statement', $emailData);
            $attachment = [
                'data' => $pdf->output(),
                'name' => 'Account_Statement_' . date('Ymd') . '.pdf',
                'mime' => 'application/pdf'
            ];

            $emailData['title'] = 'Account Statement - ' . config('app.name');
            \App\Http\Controllers\MailController::send_mail($emailData, 'email.statement', $attachment);
        } catch (\Exception $e) {
            \Log::error('Statement Email Error: ' . $e->getMessage());
        }
    }
}
