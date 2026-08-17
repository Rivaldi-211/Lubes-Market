<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.ludes');

        View::composer('*', function ($view) {
            try {
                if (Schema::hasTable('users')) {
                    $admin = User::where('role', 'admin')->first();
                    $rawPhone = $admin?->no_hp ?: '081234500001';
                    $rawEmail = $admin?->email ?: 'admin@ludesmarket.id';
                    $name = $admin?->nama_lengkap ?: 'Admin LUDES-MARKET';

                    $digits = preg_replace('/[^0-9]/', '', $rawPhone);
                    if (str_starts_with($digits, '0')) {
                        $waPhone = '62' . substr($digits, 1);
                    } elseif (!str_starts_with($digits, '62') && !empty($digits)) {
                        $waPhone = '62' . $digits;
                    } else {
                        $waPhone = $digits ?: '6281234500001';
                    }

                    $view->with('adminContact', (object) [
                        'user' => $admin,
                        'nama' => $name,
                        'email' => $rawEmail,
                        'phone' => $rawPhone,
                        'phone_digits' => $digits,
                        'wa_phone' => $waPhone,
                        'wa_link' => 'https://wa.me/' . $waPhone,
                    ]);
                }
            } catch (\Throwable $e) {
                $view->with('adminContact', (object) [
                    'user' => null,
                    'nama' => 'Admin LUDES-MARKET',
                    'email' => 'admin@ludesmarket.id',
                    'phone' => '0812-3450-0001',
                    'phone_digits' => '081234500001',
                    'wa_phone' => '6281234500001',
                    'wa_link' => 'https://wa.me/6281234500001',
                ]);
            }
        });
    }
}
