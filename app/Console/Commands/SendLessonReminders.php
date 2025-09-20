<?php
// app/Console/Commands/SendLessonReminders.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LessonSchedule;
use App\Models\LessonNotification;
use App\Mail\LessonReminder;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendLessonReminders extends Command
{
    protected $signature = 'lessons:send-reminders';
    protected $description = 'Send automatic lesson reminders based on settings';

    public function handle()
    {
        $this->info('Starting to send lesson reminders...');
        
        // Get all packages with reminder settings enabled
        $settings = \DB::table('lesson_reminder_settings')
            ->where('enabled', true)
            ->get();
        
        foreach ($settings as $setting) {
            $this->processPackageReminders($setting);
        }
        
        $this->info('Finished sending reminders.');
    }
    
    private function processPackageReminders($setting)
    {
        // Calculate target date (e.g., if days_before = 1, we look for lessons tomorrow)
        $targetDate = Carbon::now()->addDays($setting->days_before)->format('Y-m-d');
        
        // Check if it's the right time to send
        $currentTime = Carbon::now()->format('H:i');
        $sendTime = $setting->send_time;
        
        // Only send within 5 minutes of the configured time
        if (abs(Carbon::parse($currentTime)->diffInMinutes(Carbon::parse($sendTime))) > 5) {
            return;
        }
        
        // Get all lessons for the target date
        $lessons = LessonSchedule::whereHas('group', function($q) use ($setting) {
                $q->where('lesson_package_id', $setting->lesson_package_id);
            })
            ->whereDate('lesson_date', $targetDate)
            ->where('status', 'scheduled')
            ->with(['group.registrations.user', 'location'])
            ->get();
        
        foreach ($lessons as $lesson) {
            $this->sendLessonReminders($lesson, $setting);
        }
    }
    
    private function sendLessonReminders($lesson, $setting)
    {
        // Check if reminder already sent
        $existingReminder = LessonNotification::where('lesson_schedule_id', $lesson->id)
            ->where('type', 'reminder')
            ->whereDate('created_at', Carbon::today())
            ->first();
        
        if ($existingReminder) {
            return; // Already sent today
        }
        
        $users = $lesson->group->registrations->pluck('user');
        $recipientIds = $users->pluck('id')->toArray();
        
        // Create notification record
        $notification = LessonNotification::create([
            'lesson_schedule_id' => $lesson->id,
            'type' => 'reminder',
            'channel' => $setting->channel,
            'message' => $setting->email_template ?? 'Automatische herinnering',
            'recipients_count' => count($recipientIds),
            'recipients' => $recipientIds,
            'status' => 'sending',
        ]);
        
        // Send emails
        if (in_array($setting->channel, ['email', 'both'])) {
            foreach ($users as $user) {
                try {
                    Mail::to($user->email)->queue(
                        new LessonReminder($lesson, $user, $setting->email_template)
                    );
                } catch (\Exception $e) {
                    $this->error("Failed to send to {$user->email}: " . $e->getMessage());
                }
            }
        }
        
        // TODO: Implement SMS if needed
        
        $notification->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);
        
        $this->info("Sent reminders for lesson: {$lesson->group->name} on {$lesson->lesson_date}");
    }
}
