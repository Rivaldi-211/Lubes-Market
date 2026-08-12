<?php
namespace App\Services;
use App\Models\LogAktivitas;
use App\Models\User;
class ActivityLogger
{
    public function log(string $activity, ?User $user = null, ?string $ip = null): LogAktivitas
    {
        return LogAktivitas::create(['user_id'=>$user?->id,'aktivitas'=>$activity,'ip_address'=>$ip]);
    }
}
