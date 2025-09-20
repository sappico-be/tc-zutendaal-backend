<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LessonPackage;
use App\Models\LessonRegistration;
use App\Models\LessonLocation;
use App\Models\User;

class LessonTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Maak locaties
        $locations = [
            ['name' => 'Baan 1', 'type' => 'outdoor', 'capacity' => 4],
            ['name' => 'Baan 2', 'type' => 'outdoor', 'capacity' => 4],
            ['name' => 'Baan 3', 'type' => 'outdoor', 'capacity' => 4],
            ['name' => 'Indoor Hal', 'type' => 'indoor', 'capacity' => 8],
        ];
        
        foreach ($locations as $loc) {
            LessonLocation::firstOrCreate(['name' => $loc['name']], $loc);
        }
        
        // Vind of maak een lessenpakket
        $package = LessonPackage::first();
        
        if (!$package) {
            $package = LessonPackage::create([
                'name' => 'Zomerlessen 2025',
                'description' => 'Intensieve zomer tennislessen voor alle niveaus',
                'total_lessons' => 20,
                'start_date' => now()->addMonths(2),
                'end_date' => now()->addMonths(4),
                'registration_deadline' => now()->addWeeks(6),
                'price_members' => 150,
                'price_non_members' => 200,
                'status' => 'open',
                'min_participants' => 10,
                'max_participants' => 40,
                'available_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            ]);
        }
        
        // Maak test inschrijvingen
        $testRegistrations = [
            ['name' => 'Anna Janssen', 'level' => 'beginner', 'days' => ['monday', 'wednesday'], 'partners' => ['Bert Peters']],
            ['name' => 'Bert Peters', 'level' => 'beginner', 'days' => ['monday', 'wednesday'], 'partners' => ['Anna Janssen']],
            ['name' => 'Carla De Vries', 'level' => 'intermediate', 'days' => ['tuesday', 'thursday'], 'partners' => []],
            ['name' => 'David Bakker', 'level' => 'intermediate', 'days' => ['monday', 'tuesday', 'thursday'], 'partners' => ['Emma Smit']],
            ['name' => 'Emma Smit', 'level' => 'intermediate', 'days' => ['monday', 'thursday'], 'partners' => ['David Bakker']],
            ['name' => 'Frank Visser', 'level' => 'advanced', 'days' => ['wednesday', 'friday'], 'partners' => []],
            ['name' => 'Greta Mulder', 'level' => 'beginner', 'days' => ['tuesday', 'thursday'], 'partners' => ['Hans Brouwer']],
            ['name' => 'Hans Brouwer', 'level' => 'beginner', 'days' => ['tuesday', 'thursday'], 'partners' => ['Greta Mulder']],
            ['name' => 'Iris Hendriks', 'level' => 'advanced', 'days' => ['monday', 'wednesday', 'friday'], 'partners' => []],
            ['name' => 'Johan Dekker', 'level' => 'intermediate', 'days' => ['tuesday', 'friday'], 'partners' => []],
            ['name' => 'Karen Jansen', 'level' => 'beginner', 'days' => ['monday', 'wednesday'], 'partners' => ['Lisa Vermeer']],
            ['name' => 'Lisa Vermeer', 'level' => 'beginner', 'days' => ['monday', 'wednesday', 'friday'], 'partners' => ['Karen Jansen']],
        ];
        
        foreach ($testRegistrations as $reg) {
            // Maak of vind user
            $user = User::firstOrCreate(
                ['email' => strtolower(str_replace(' ', '.', $reg['name'])) . '@example.com'],
                [
                    'name' => $reg['name'],
                    'password' => bcrypt('password'),
                    'membership_type' => 'senior',
                    'is_active' => true,
                ]
            );
            
            // Check if already registered
            $existing = LessonRegistration::where('lesson_package_id', $package->id)
                ->where('user_id', $user->id)
                ->first();
                
            if (!$existing) {
                LessonRegistration::create([
                    'lesson_package_id' => $package->id,
                    'user_id' => $user->id,
                    'available_days' => $reg['days'],
                    'preferred_partners' => $reg['partners'],
                    'level' => $reg['level'],
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'amount_paid' => 150,
                ]);
            }
        }
        
        $this->command->info('Lesson test data created successfully!');
        $this->command->info('Created ' . count($testRegistrations) . ' test registrations');
    }
}
