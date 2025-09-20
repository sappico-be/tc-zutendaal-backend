<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ========================================
// SCHEDULED TASKS
// ========================================

// Stuur lesson reminders elke 5 minuten
// (checkt intern of het de juiste tijd is volgens de settings)
// Schedule::command('lessons:send-reminders')
//     ->everyFiveMinutes()
//     ->withoutOverlapping()
//     ->runInBackground();

// Of als je het liever op specifieke tijden doet (efficiënter):
// Schedule::command('lessons:send-reminders')
//     ->dailyAt('19:00')  // Default reminder time
//     ->runInBackground();
// 
Schedule::command('lessons:send-reminders')
    ->dailyAt('09:00')  // Morning reminders
    ->runInBackground();

// Optioneel: Extra scheduled tasks voor de tennis club
// Bijvoorbeeld: Check voor verlopen lidmaatschappen
// Schedule::call(function () {
//     \App\Models\User::where('membership_expires_at', '<', now())
//         ->where('is_active', true)
//         ->update(['is_active' => false]);
// })->daily()->at('00:01');

// Bijvoorbeeld: Markeer oude events als 'completed'
Schedule::call(function () {
    \App\Models\Event::where('end_date', '<', now())
        ->where('status', 'published')
        ->update(['status' => 'completed']);
})->daily()->at('00:30');

// Bijvoorbeeld: Markeer oude lessen als 'completed'
Schedule::call(function () {
    \App\Models\LessonSchedule::where('lesson_date', '<', now()->subDays(1))
        ->where('status', 'scheduled')
        ->update(['status' => 'completed']);
})->daily()->at('01:00');
