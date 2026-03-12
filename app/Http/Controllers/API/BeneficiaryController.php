<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Beneficiary;

class BeneficiaryController extends Controller
{
    /**
     * Fetch user's beneficiaries sorted by most recently used.
     * This API serves as the Source of Truth for the mobile app recents/beneficiaries list.
     */
    public function index(Request $request)
    {
        // Consistent Auth resolution using verifyapptoken helper
        $authHeader = $request->header('Authorization');
        $deviceKey = config('app.habukhan_device_key');
        $userId = null;

        if (str_starts_with($authHeader ?? '', 'Bearer ')) {
            $token = str_replace('Bearer ', '', $authHeader);
            $userId = $this->verifyapptoken($token);
        } else if ($authHeader == $deviceKey || $request->header('X-Device-Key') == $deviceKey) {
            $userId = $this->verifyapptoken($request->user_id);
        }

        if (!$userId) {
            return response()->json(['status' => 'fail', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        $service_type = $request->query('service_type');

        // Fetch Favorites (All of them for this service type)
        $favoritesQuery = Beneficiary::where('user_id', $userId)->where('is_favorite', 1);
        if ($service_type) {
            $favoritesQuery->where('service_type', $service_type);
        }
        $favorites = $favoritesQuery->orderBy('last_used_at', 'desc')->get();

        // Fetch Recents (Limited Top 20)
        $recentsQuery = Beneficiary::where('user_id', $userId);
        if ($service_type) {
            $recentsQuery->where('service_type', $service_type);
        }
        $recents = $recentsQuery->orderBy('last_used_at', 'desc')
            ->limit(20)
            ->get();

        // Transformer for compatibility mapping
        $transformer = function ($b) {
            $b->id_str = (string) $b->id;
            $b->account_number = $b->identifier;
            $b->phone = $b->identifier;
            $b->bank_name = $b->network_or_provider;
            $b->network = $b->network_or_provider;
            $b->is_favorite = (bool) $b->is_favorite;

            // Flutter Model matching
            $b->networkName = $b->network_or_provider;
            $b->networkLogo = "";
            $b->createdAt = $b->created_at->toIso8601String();

            // Type mapping
            if (strpos($b->service_type, 'transfer_internal') !== false) {
                $b->type = 'internal';
            } elseif (strpos($b->service_type, 'transfer_external') !== false) {
                $b->type = 'external';
            } else {
                $b->type = $b->service_type;
            }

            return $b;
        };

        $recents->transform($transformer);
        $favorites->transform($transformer);

        return response()->json([
            'status' => 'success',
            'data' => $recents,
            'recents' => $recents,
            'favorites' => $favorites,
        ]);
    }

    /**
     * Delete a beneficiary.
     */
    public function destroy(Request $request, $id)
    {
        $deviceKey = config('app.habukhan_device_key');
        $authHeader = $request->header('Authorization');
        $userId = null;

        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = str_replace('Bearer ', '', $authHeader);
            $userId = $this->verifyapptoken($token);
        } else if ($authHeader == $deviceKey || $request->header('X-Device-Key') == $deviceKey) {
            $userId = $this->verifyapptoken($request->user_id);
        }

        if (!$userId) {
            return response()->json(['status' => 'fail', 'message' => 'Unauthorized'], 401);
        }

        $beneficiary = Beneficiary::where('user_id', $userId)->where('id', $id)->first();

        if (!$beneficiary) {
            return response()->json([
                'status' => 'error',
                'message' => 'Beneficiary not found'
            ], 404);
        }

        $beneficiary->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Beneficiary deleted'
        ]);
    }

    /**
     * Toggle the favorite status of a beneficiary.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleFavorite(Request $request, $id)
    {
        $deviceKey = config('app.habukhan_device_key');
        $authHeader = $request->header('Authorization');
        $userId = null;

        // Resolve UserID manually from headers (Symmetric to index logic)
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = str_replace('Bearer ', '', $authHeader);
            $userId = $this->verifyapptoken($token);
        } else if ($authHeader == $deviceKey || $request->header('X-Device-Key') == $deviceKey) {
            $userId = $this->verifyapptoken($request->user_id);
        }

        if (!$userId) {
            return response()->json(['status' => 'fail', 'message' => 'Unauthorized'], 401);
        }

        $beneficiary = Beneficiary::where('user_id', $userId)->where('id', $id)->first();

        if (!$beneficiary) {
            return response()->json([
                'status' => 'error',
                'message' => 'Beneficiary not found'
            ], 404);
        }

        $beneficiary->is_favorite = !$beneficiary->is_favorite;
        $beneficiary->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Favorite status updated',
            'is_favorite' => $beneficiary->is_favorite
        ]);
    }
}
