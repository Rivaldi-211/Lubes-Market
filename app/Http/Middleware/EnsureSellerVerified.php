<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSellerVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->isSeller()) {
            $umkm = $user->umkm;
            if (!$umkm || $umkm->status_verifikasi === 'disetujui') {
                return $next($request);
            }

            if (!$umkm->sellerOnboarding()->exists()) {
                if (!$request->routeIs('seller.onboarding*')) {
                    return redirect()->route('seller.onboarding');
                }
            } elseif ($umkm->status_verifikasi === 'menunggu') {
                if (!$request->routeIs('seller.onboarding.waiting')) {
                    return redirect()->route('seller.onboarding.waiting');
                }
            } elseif ($umkm->status_verifikasi === 'ditolak') {
                if (!$request->routeIs('seller.onboarding.rejected')) {
                    return redirect()->route('seller.onboarding.rejected');
                }
            }
        }

        return $next($request);
    }
}
