<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemLockMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $featureKey
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $featureKey)
    {
        // 1. Check if the feature is globally locked
        $lock = DB::table('system_locks')
            ->where('feature_key', $featureKey)
            ->first();

        // 2. If locked, return strict 403 response
        if ($lock && $lock->is_locked) {
            return response()->json([
                'status' => 'error',
                'message' => 'Feature temporarily unavailable',
                'code' => 'FEATURE_LOCKED'
            ], 403);
        }

        // 3. Proceed if not locked
        return $next($request);
    }
}
