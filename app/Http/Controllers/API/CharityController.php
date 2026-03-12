<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class CharityController extends Controller
{
    /**
     * Admin: Search users for onboarding (Auto-complete)
     */
    public function searchUsers(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $user_id = $this->verifytoken($request->id);
            if ($user_id) {
                $check_admin = DB::table('user')->where(['id' => $user_id, 'type' => 'ADMIN'])->first();
                if ($check_admin) {
                    $search = $request->search;
                    $users = DB::table('user')
                        ->where('username', 'LIKE', '%' . $search . '%')
                        ->orWhere('name', 'LIKE', '%' . $search . '%')
                        ->select('id', 'username', 'name', 'profile_image')
                        ->limit(10)
                        ->get();
                    return response()->json([
                        'status' => 200,
                        'users' => $users
                    ]);
                }
            }
            return response()->json(['status' => 403, 'message' => 'Unauthorized Access'], 403);
        }
        return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
    }

    /**
     * Admin: Onboard a new charity
     */
    public function addCharity(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $user_id = $this->verifytoken($request->id);
            if ($user_id) {
                $check_admin = DB::table('user')->where(['id' => $user_id, 'type' => 'ADMIN'])->first();
                if ($check_admin) {
                    $validate = Validator::make($request->all(), [
                        'name' => 'required|string|max:255',
                        'username' => 'required|string|exists:user,username',
                        'category' => 'required',
                        'cac_number' => 'nullable|string|max:50',
                        'description' => 'required|string',
                        'logo' => 'nullable|string' // base64 or path
                    ]);

                    if ($validate->fails()) {
                        return response()->json(['status' => 400, 'message' => $validate->errors()->first()], 400);
                    }

                    // Look for existing user
                    $target_user = DB::table('user')->where('username', $request->username)->first();

                    $logo_path = null;
                    if ($request->logo && strpos($request->logo, 'data:image') !== false) {
                        // Handle base64 upload
                        $folderPath = public_path('uploads/charity/');
                        if (!file_exists($folderPath)) {
                            mkdir($folderPath, 0777, true);
                        }
                        $image_parts = explode(";base64,", $request->logo);
                        $image_type_aux = explode("image/", $image_parts[0]);
                        $image_type = $image_type_aux[1];
                        $image_base64 = base64_decode($image_parts[1]);
                        $fileName = $request->username . '_' . time() . '.' . $image_type;
                        $file = $folderPath . $fileName;
                        file_put_contents($file, $image_base64);
                        $logo_path = 'uploads/charity/' . $fileName;
                    }

                    $charityId = DB::table('charities')->insertGetId([
                        'user_id' => $target_user->id,
                        'name' => $request->name,
                        'username' => $request->username,
                        'category' => $request->category,
                        'cac_number' => $request->cac_number,
                        'description' => $request->description,
                        'logo' => $logo_path,
                        'verification_status' => $request->verification_status ?? 'verified',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    return response()->json([
                        'status' => 200,
                        'message' => 'Charity Onboarded Successfully',
                        'charity_id' => $charityId
                    ]);
                }
            }
            return response()->json(['status' => 403, 'message' => 'Unauthorized Access'], 403);
        }
        return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
    }

    /**
     * Admin: Update organization details
     */
    public function updateCharity(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $user_id = $this->verifytoken($request->id);
            if ($user_id) {
                $check_admin = DB::table('user')->where(['id' => $user_id, 'type' => 'ADMIN'])->first();
                if ($check_admin) {
                    $validate = Validator::make($request->all(), [
                        'charity_id' => 'required|exists:charities,id',
                        'name' => 'required|string|max:255',
                        'category' => 'required',
                        'cac_number' => 'nullable|string|max:50',
                        'description' => 'required|string',
                        'logo' => 'nullable|string' // base64 or ignored if null
                    ]);

                    if ($validate->fails()) {
                        return response()->json(['status' => 400, 'message' => $validate->errors()->first()], 400);
                    }

                    $charity = DB::table('charities')->where('id', $request->charity_id)->first();
                    $logo_path = $charity->logo;

                    if ($request->logo && strpos($request->logo, 'data:image') !== false) {
                        // Handle base64 upload
                        $folderPath = public_path('uploads/charity/');
                        if (!file_exists($folderPath)) {
                            mkdir($folderPath, 0777, true);
                        }
                        $image_parts = explode(";base64,", $request->logo);
                        $image_type_aux = explode("image/", $image_parts[0]);
                        $image_type = $image_type_aux[1];
                        $image_base64 = base64_decode($image_parts[1]);
                        $fileName = $charity->username . '_' . time() . '.' . $image_type;
                        $file = $folderPath . $fileName;
                        file_put_contents($file, $image_base64);
                        $logo_path = 'uploads/charity/' . $fileName;
                    }

                    DB::table('charities')->where('id', $request->charity_id)->update([
                        'name' => $request->name,
                        'category' => $request->category,
                        'cac_number' => $request->cac_number,
                        'description' => $request->description,
                        'logo' => $logo_path,
                        'verification_status' => $request->verification_status ?? $charity->verification_status,
                        'updated_at' => now(),
                    ]);

                    return response()->json([
                        'status' => 200,
                        'message' => 'Charity Updated Successfully'
                    ]);
                }
            }
            return response()->json(['status' => 403, 'message' => 'Unauthorized Access'], 403);
        }
        return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
    }

    /**
     * Admin: Create a new campaign for a charity
     */
    public function addCampaign(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $user_id = $this->verifytoken($request->id);
            if ($user_id) {
                $check_admin = DB::table('user')->where(['id' => $user_id, 'type' => 'ADMIN'])->first();
                if ($check_admin) {
                    $validate = Validator::make($request->all(), [
                        'charity_id' => 'required|exists:charities,id',
                        'title' => 'required|string|max:255',
                        'description' => 'required|string',
                        'target_amount' => 'required|numeric',
                        'state' => 'required|string',
                        'lga' => 'required|string',
                        'image' => 'nullable|string' // base64
                    ]);

                    if ($validate->fails()) {
                        return response()->json(['status' => 400, 'message' => $validate->errors()->first()], 400);
                    }

                    // Get charity and linked user for contact info
                    $charity = DB::table('charities')->where('id', $request->charity_id)->first();
                    $target_user = DB::table('user')->where('id', $charity->user_id)->first();

                    $image_path = null;
                    if ($request->image && strpos($request->image, 'data:image') !== false) {
                        $folderPath = public_path('uploads/campaign/');
                        if (!file_exists($folderPath)) {
                            mkdir($folderPath, 0777, true);
                        }
                        $image_parts = explode(";base64,", $request->image);
                        $image_type_aux = explode("image/", $image_parts[0]);
                        $image_type = $image_type_aux[1];
                        $image_base64 = base64_decode($image_parts[1]);
                        $fileName = 'campaign_' . time() . '_' . str_replace(' ', '_', $request->title) . '.' . $image_type;
                        $file = $folderPath . $fileName;
                        file_put_contents($file, $image_base64);
                        $image_path = 'uploads/campaign/' . $fileName;
                    }

                    $insert = DB::table('campaigns')->insert([
                        'charity_id' => $request->charity_id,
                        'title' => $request->title,
                        'description' => $request->description,
                        'image' => $image_path,
                        'state' => $request->state,
                        'lga' => $request->lga,
                        'phone' => $target_user->phone ?? null,
                        'target_amount' => $request->target_amount,
                        'start_date' => Carbon::now(),
                        'end_date' => null,
                        'status' => $request->status ?? 'active',
                        'payout_status' => 'pending',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);

                    if ($insert) {
                        return response()->json(['status' => 200, 'message' => 'Campaign Created Successfully']);
                    }
                }
            }
            return response()->json(['status' => 403, 'message' => 'Unauthorized Access'], 403);
        }
        return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
    }

    /**
     * Get all campaigns (Admin/User)
     */
    public function getCampaigns(Request $request)
    {
        $query = DB::table('campaigns')
            ->join('charities', 'campaigns.charity_id', '=', 'charities.id')
            ->where('campaigns.status', 'active') // Default to active only for feed
            ->select(
            'campaigns.*',
            'charities.name as charity_name',
            'charities.username as charity_username',
            'charities.logo',
            'charities.verification_status'
        );

        if ($request->status) {
            // Override default if specific status requested
            $query->where('campaigns.status', $request->status);
        }

        if ($request->charity_id) {
            $query->where('campaigns.charity_id', $request->charity_id);
        }

        if ($request->category && $request->category !== 'All') {
            $query->where('charities.category', $request->category);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('campaigns.title', 'LIKE', "%{$search}%")
                    ->orWhere('charities.name', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('random') && $request->random == 'true') {
            $query->inRandomOrder();
        }
        else {
            $query->orderBy('campaigns.id', 'desc');
        }

        $campaigns = $query->paginate($request->limit ?? 10)
            ->through(function ($campaign) {
            $campaign->logo_url = $campaign->logo ? url($campaign->logo) : null;
            $campaign->image_url = $campaign->image ? url($campaign->image) : null;
            // Calculate percentage
            $campaign->percentage = $campaign->target_amount > 0
                ? min(100, round(($campaign->current_amount / $campaign->target_amount) * 100))
                : 0;
            return $campaign;
        });

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'success',
            'campaigns' => $campaigns
        ]);
    }

    /**
     * Get Charity Categories (Hardcoded for UI Consistency)
     */
    public function getCategories(Request $request)
    {
        $categories = [
            ['id' => '1', 'name' => 'All', 'icon' => 'grid_view_rounded'], // Material Icon names
            ['id' => '2', 'name' => 'Medical', 'icon' => 'medical_services_rounded'],
            ['id' => '3', 'name' => 'Education', 'icon' => 'school_rounded'],
            ['id' => '4', 'name' => 'Orphanage', 'icon' => 'child_care_rounded'],
            ['id' => '5', 'name' => 'Religion', 'icon' => 'mosque_rounded'],
            ['id' => '6', 'name' => 'Emergency', 'icon' => 'emergency_rounded'],
            ['id' => '7', 'name' => 'Prison', 'icon' => 'lock_open_rounded'],
        ];

        return response()->json([
            'status' => 200,
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Admin: Get all charities
     */
    public function getCharities(Request $request)
    {
        $id = $request->id;
        $user_id = $this->verifyapptoken($id); // Support Bearer or ID

        if (!$user_id) {
            return response()->json(['status' => 403, 'message' => 'Unauthorized Access'], 403);
        }

        $check_admin = DB::table('user')->where(['id' => $user_id, 'type' => 'ADMIN'])->first();
        if (!$check_admin) {
            return response()->json(['status' => 403, 'message' => 'Admin Access Required'], 403);
        }

        $query = DB::table('charities');
        if ($request->search) {
            $query->where('name', 'LIKE', '%' . $request->search . '%')
                ->orWhere('username', 'LIKE', '%' . $request->search . '%');
        }
        $charities = $query->orderBy('id', 'desc')->paginate($request->limit ?? 10)
            ->through(function ($charity) {
            $charity->logo_url = $charity->logo ? url($charity->logo) : null;
            return $charity;
        });

        return response()->json([
            'status' => 200,
            'charities' => $charities
        ]);
    }

    /**
     * User: Get charity details by ID
     */
    public function getCharityDetails(Request $request, $id)
    {
        $charity = DB::table('charities')->where('id', $id)->first();
        if (!$charity) {
            return response()->json(['status' => 404, 'message' => 'Charity not found'], 404);
        }

        $charity->logo_url = $charity->logo ? url($charity->logo) : null;

        // Add stats
        $charity->campaigns_count = DB::table('campaigns')->where('charity_id', $id)->count();
        $charity->donors_count = DB::table('donations')->where('charity_id', $id)->distinct('user_id')->count();
        $charity->total_raised = DB::table('donations')->where('charity_id', $id)->sum('amount');

        return response()->json([
            'status' => 200,
            'charity' => $charity
        ]);
    }

    /**
     * User: Get my donations (optionally filtered by charity)
     */
    public function getUserDonations(Request $request)
    {
        $headerToken = $request->bearerToken() ?? $request->id;
        $user_id = $this->verifyapptoken($headerToken);

        if ($user_id) {
            $query = DB::table('donations')
                ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
                ->where('donations.user_id', $user_id);

            if ($request->charity_id) {
                $query->where('donations.charity_id', $request->charity_id);
            }

            $donations = $query->select('donations.*', 'campaigns.title as campaign_title')
                ->orderBy('donations.id', 'desc')
                ->paginate($request->limit ?? 10)
                ->through(function ($donation) {
                $donation->date_formatted = Carbon::parse($donation->created_at)->format('d M Y, h:i A');
                return $donation;
            });

            return response()->json([
                'status' => 200,
                'success' => true,
                'donations' => $donations
            ]);
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized Access'], 403);
    }


    /**
     * User: Donate to a campaign (Escrow Model)
     */
    public function donate(Request $request)
    {
        $headerToken = $request->bearerToken() ?? $request->id;
        $user_id = $this->verifyapptoken($headerToken);

        if ($user_id) {
            $user = DB::table('user')->where('id', $user_id)->first();
            $campaign = DB::table('campaigns')->where('id', $request->campaign_id)->first();

            if (!$campaign || $campaign->status !== 'active') {
                return response()->json(['status' => 400, 'message' => 'Campaign is not active']);
            }

            $amount = $request->amount;
            if ($user->bal < $amount) {
                return response()->json(['status' => 400, 'message' => 'Insufficient Balance']);
            }

            // 1. Debit User
            $transid = 'DON-' . strtoupper(bin2hex(random_bytes(6)));
            DB::table('user')->where('id', $user_id)->decrement('bal', $amount);

            // 2. Log Transaction (Unified History)
            DB::table('message')->insert([
                'username' => $user->username,
                'message' => "Donation for " . $campaign->title,
                'amount' => $amount,
                'oldbal' => $user->bal,
                'newbal' => $user->bal - $amount,
                'habukhan_date' => Carbon::now(),
                'transid' => $transid,
                'plan_status' => 1, // Success
                'role' => 'charity_donation'
            ]);

            // 3. Update Campaign Progress
            DB::table('campaigns')->where('id', $campaign->id)->increment('current_amount', $amount);

            // 4. Record Donation
            DB::table('donations')->insert([
                'user_id' => $user_id,
                'campaign_id' => $campaign->id,
                'charity_id' => $campaign->charity_id,
                'amount' => $amount,
                'transid' => $transid,
                'status' => 'confirmed',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // 5. Update Charity Pending Balance (Escrow)
            DB::table('charities')->where('id', $campaign->charity_id)->increment('pending_balance', $amount);

            (new \App\Services\NotificationService())->sendCharityDonationNotification($user, $campaign->title, $amount);

            // 6. Return Success
            return response()->json(['status' => 'success', 'message' => 'Donation Successful! Thank you for your kindness.', 'transid' => $transid]);
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized Access'], 403);
    }

    /**
     * Release funds from Pending to Available for closed campaigns
     * This can be run by cron or manually
     */
    public function processPayouts(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $user_id = $this->verifytoken($request->id);
            if ($user_id) {
                // Check if admin or just a valid call
                $query = DB::table('campaigns')->where('payout_status', 'pending');

                // If specific ID is provided, we can force payout even if active (incomplete)
                if ($request->has('campaign_id')) {
                    $query->where('id', $request->campaign_id);
                }
                else {
                    // Bulk Mode: Only closed campaigns for safety
                    $query->where('status', 'closed');
                }

                $campaigns_to_process = $query->get();
                $count = 0;

                if ($campaigns_to_process->isEmpty()) {
                    return response()->json(['status' => 200, 'message' => "0 Campaigns Payouts Processed (Ensure campaigns are Closed or use Manual Payout)"]);
                }

                foreach ($campaigns_to_process as $campaign) {
                    // Start Transaction
                    DB::beginTransaction();
                    try {
                        // 1. Mark campaign as released AND closed (if not already)
                        // forcing 'closed' ensures consistency as this is a one-time payout system
                        DB::table('campaigns')->where('id', $campaign->id)->update([
                            'payout_status' => 'released',
                            'status' => 'closed'
                        ]);

                        if ($campaign->current_amount > 0) {
                            // 2. Move funds: Pending -> User Wallet (Direct Payout)
                            DB::table('charities')->where('id', $campaign->charity_id)->decrement('pending_balance', $campaign->current_amount);
                            // We don't increment available_balance anymore if we paying out directly to user wallet

                            $charity = DB::table('charities')->where('id', $campaign->charity_id)->first();
                            $charityUser = DB::table('user')->where('id', $charity->user_id)->first();

                            // Credit User Wallet
                            DB::table('user')->where('id', $charityUser->id)->increment('bal', $campaign->current_amount);

                            // Log Transaction
                            $transid = 'PAY-' . strtoupper(bin2hex(random_bytes(6)));
                            DB::table('message')->insert([
                                'username' => $charityUser->username,
                                'message' => "Payout for Campaign: " . $campaign->title,
                                'amount' => $campaign->current_amount,
                                'oldbal' => $charityUser->bal,
                                'newbal' => $charityUser->bal + $campaign->current_amount,
                                'habukhan_date' => Carbon::now(),
                                'transid' => $transid,
                                'plan_status' => 1,
                                'role' => 'charity_payout'
                            ]);

                            (new \App\Services\NotificationService())->sendCharityPayoutNotification($charityUser, $charity->name, $campaign->current_amount);
                        }

                        DB::commit();
                        $count++;
                    }
                    catch (\Exception $e) {
                        DB::rollBack();
                    }
                }

                return response()->json(['status' => 200, 'message' => "$count Campaigns Payouts Processed"]);
            }
            return response()->json(['status' => 403, 'message' => 'Unauthorized Access'], 403);
        }
        return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
    }

    /**
     * Admin: Approve charity withdrawal request
     */
    public function approveWithdrawal(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $user_id = $this->verifytoken($request->id);
            if ($user_id) {
                $check_admin = DB::table('user')->where(['id' => $user_id, 'type' => 'ADMIN'])->first();
                if ($check_admin) {
                    $charity = DB::table('charities')->where('id', $request->charity_id)->first();
                    $amount = $request->amount;

                    if ($charity->available_balance < $amount) {
                        return response()->json(['status' => 400, 'message' => 'Insufficient Available Balance']);
                    }

                    // 1. Deduct from available balance
                    DB::table('charities')->where('id', $request->charity_id)->decrement('available_balance', $amount);

                    // 2. Log withdrawal
                    DB::table('donations')->insert([ // Reusing donations table for logs or create a new one
                        'user_id' => $user_id,
                        'campaign_id' => 0, // 0 for withdrawal
                        'charity_id' => $request->charity_id,
                        'amount' => $amount,
                        'transid' => 'WIT-' . strtoupper(bin2hex(random_bytes(6))),
                        'status' => 'withdrawn',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);

                    return response()->json(['status' => 200, 'message' => 'Withdrawal Approved & Processed']);
                }
            }
            return response()->json(['status' => 403, 'message' => 'Unauthorized Access'], 403);
        }
        return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
    }

    /**
     * Admin: Delete a charity organization
     */
    public function deleteCharity(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $user_id = $this->verifytoken($request->id);
            if ($user_id) {
                $check_admin = DB::table('user')->where(['id' => $user_id, 'type' => 'ADMIN'])->first();
                if ($check_admin) {
                    $charity = DB::table('charities')->where('id', $request->charity_id)->first();
                    if (!$charity)
                        return response()->json(['status' => 404, 'message' => 'Charity not found'], 404);

                    if ($charity->pending_balance > 0 || $charity->available_balance > 0) {
                        return response()->json(['status' => 400, 'message' => 'Cannot delete charity with active balances'], 400);
                    }

                    DB::table('charities')->where('id', $request->charity_id)->delete();
                    DB::table('campaigns')->where('charity_id', $request->charity_id)->delete(); // Cleanup campaigns

                    return response()->json(['status' => 200, 'message' => 'Charity Deleted Successfully']);
                }
            }
            return response()->json(['status' => 403, 'message' => 'Unauthorized Access'], 403);
        }
        return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
    }

    /**
     * Admin: Delete a campaign
     */
    public function deleteCampaign(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $user_id = $this->verifytoken($request->id);
            if ($user_id) {
                $check_admin = DB::table('user')->where(['id' => $user_id, 'type' => 'ADMIN'])->first();
                if ($check_admin) {
                    $campaign = DB::table('campaigns')->where('id', $request->campaign_id)->first();
                    if (!$campaign)
                        return response()->json(['status' => 404, 'message' => 'Campaign not found'], 404);

                    if ($campaign->current_amount > 0 && $campaign->payout_status !== 'released') {
                        return response()->json(['status' => 400, 'message' => 'Cannot delete campaign with escrowed funds'], 400);
                    }

                    DB::table('campaigns')->where('id', $request->campaign_id)->delete();

                    return response()->json(['status' => 200, 'message' => 'Campaign Deleted Successfully']);
                }
            }
            return response()->json(['status' => 403, 'message' => 'Unauthorized Access'], 403);
        }
        return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
    }
}