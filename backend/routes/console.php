<?php

use App\Services\AlertRules;
use App\Services\SessionStatus;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Look for stalled onboardings every 5 minutes, even when no manager has the
// dashboard open. Needs `php artisan schedule:work` running (locally) or the
// `schedule:run` cron job (on a server). Opening Overview still checks too.
Schedule::call(function () {
    SessionStatus::reconcile();
    AlertRules::checkStalled();
})->everyFiveMinutes()->name('check-stalled-onboardings');
