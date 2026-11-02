<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bills:generate-recurring')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->timezone(config('app.timezone', 'UTC'))
    ->description('Generate recurring monthly rent invoices for eligible active contracts');

Schedule::command('complaints:auto-close-resolved')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->timezone(config('app.timezone', 'UTC'))
    ->description('Otomatis menutup tiket pengaduan selesai yang telah melewati 7 hari (H+7)');

Schedule::command('bills:send-due-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->timezone(config('app.timezone', 'UTC'))
    ->description('Kirim pengingat jatuh tempo (H-3) dan tunggakan (H+1) secara otomatis');
