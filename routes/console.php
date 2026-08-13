<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment('LUDES-MARKET — produk lokal, ekonomi tumbuh.');
});

Schedule::command('payments:expire-stale')->everyMinute()->withoutOverlapping();
