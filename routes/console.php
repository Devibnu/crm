<?php

use App\Jobs\RefreshSlaTimersJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => RefreshSlaTimersJob::dispatchSync())
    ->name('sla:refresh-timers')
    ->everyFiveMinutes();
