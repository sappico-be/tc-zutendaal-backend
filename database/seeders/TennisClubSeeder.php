<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\NewsArticle;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TennisClubSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@tennisclub.be',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'membership_type' => 'senior',
            'member_since' => now()->subYears(5),
            'is_active' => true,
            'can_book_courts' => true,
            'phone' => '089 12 34 56',
            'mobile' => '0470 12 34 56',
            'street' => 'Tennislaan',
            'house_number' => '1',
            'postal_code' => '3600',
            'city' => 'Genk',
            'tennis_level' => 5.5,
        ]);

        // Create board member
        $boardMember = User::create([
            'name' => 'Board Member',
            'first_name' => 'Jan',
            'last_name' => 'Janssens',
            'email' => 'board@tennisclub.be',
            'password' => Hash::make('password'),
            'role' => 'board_member',
            'membership_type' => 'senior',
            'member_since' => now()->subYears(3),
            'is_active' => true,
            'can_book_courts' => true,
            'tennis_level' => 6.0,
            'city' => 'Genk',
        ]);

        // Create trainer
        $trainer = User::create([
            'name' => 'Trainer User',
            'first_name' => 'Tom',
            'last_name' => 'Trainers',
            'email' => 'trainer@tennisclub.be',
            'password' => Hash::make('password'),
            'role' => 'trainer',
            'membership_type' => 'senior',
            'member_since' => now()->subYears(2),
            'is_active' => true,
            'can_book_courts' => true,
            'tennis_level' => 3.0,
            'vta_number' => 'VTA123456',
            'city' => 'Genk',
        ]);

        // Create regular members
        $members = [];
        for ($i = 1; $i <= 10; $i++) {
            $memberType = $i <= 2 ? 'junior' : 'senior';
            $members[] = User::create([
                'name' => "Member $i",
                'first_name' => "Voornaam$i",
                'last_name' => "Achternaam$i",
                'email' => "member$i@example.com",
                'password' => Hash::make('password'),
                'role' => 'member',
                'membership_type' => $memberType,
                'member_since' => now()->subMonths(rand(1, 36)),
                'membership_expires_at' => now()->addMonths(rand(1, 12)),
                'is_active' => true,
                'can_book_courts' => true,
                'tennis_level' => rand(65, 90) / 10, // 6.5 tot 9.0
                'phone' => '089 ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                'city' => 'Genk',
                'postal_code' => '3600',
            ]);
        }

        // Create news articles
        $newsArticles = [
            [
                'title' => 'Welkom bij het nieuwe seizoen 2025!',
                'excerpt' => 'Het nieuwe tennisseizoen is begonnen met veel enthousiasme.',
                'content' => 'We zijn verheugd om het nieuwe tennisseizoen 2025 te openen. Met nieuwe banen, verbeterde faciliteiten en een spannend toernooischema belooft dit een geweldig jaar te worden.',
                'status' => 'published',
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'Clubkampioenschap 2025 - Inschrijvingen Open',
                'excerpt' => 'Schrijf je nu in voor het jaarlijkse clubkampioenschap.',
                'content' => 'De inschrijvingen voor het clubkampioenschap 2025 zijn geopend. Het toernooi vindt plaats van 15 tot 30 juni. Alle categorieën zijn beschikbaar.',
                'status' => 'published',
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Nieuwe Trainingsschema\'s Beschikbaar',
                'excerpt' => 'Check de nieuwe trainingstijden voor het voorjaar.',
                'content' => 'De nieuwe trainingsschema\'s voor het voorjaarsseizoen zijn nu beschikbaar. Groepslessen voor alle niveaus.',
                'status' => 'published',
                'published_at' => now()->subDays(1),
            ],
        ];

        foreach ($newsArticles as $articleData) {
            NewsArticle::create([
                ...$articleData,
                'author_id' => $admin->id,
                'slug' => Str::slug($articleData['title']),
                'tags' => ['nieuws', 'club'],
            ]);
        }

        // Create events
        $events = [
            [
                'title' => 'Clubkampioenschap Enkelspel 2025',
                'description' => 'Het jaarlijkse clubkampioenschap enkelspel voor alle categorieën.',
                'type' => 'tournament',
                'location' => 'Tennisclub Genk',
                'start_date' => now()->addWeeks(4),
                'end_date' => now()->addWeeks(6),
                'registration_deadline' => now()->addWeeks(3),
                'max_participants' => 32,
                'price_members' => 15.00,
                'price_non_members' => 25.00,
                'status' => 'published',
            ],
            [
                'title' => 'Start2Tennis - Beginnerscursus',
                'description' => '10-weken durende tenniscursus voor absolute beginners.',
                'type' => 'training',
                'location' => 'Tennisclub Genk - Baan 4',
                'start_date' => now()->addDays(7),
                'end_date' => now()->addWeeks(10),
                'registration_deadline' => now()->addDays(5),
                'max_participants' => 12,
                'price_members' => 120.00,
                'price_non_members' => 150.00,
                'status' => 'published',
            ],
            [
                'title' => 'Nieuwjaarsreceptie 2025',
                'description' => 'Gezellige nieuwjaarsreceptie voor alle leden en hun familie.',
                'type' => 'social',
                'location' => 'Clubhuis',
                'start_date' => now()->addDays(14),
                'end_date' => now()->addDays(14)->addHours(4),
                'max_participants' => 100,
                'price_members' => 0,
                'members_only' => true,
                'status' => 'published',
            ],
            [
                'title' => 'Jeugdtoernooi Pasen 2025',
                'description' => 'Paastoernooi voor alle jeugdcategorieën.',
                'type' => 'tournament',
                'location' => 'Tennisclub Genk',
                'start_date' => now()->addMonths(3),
                'end_date' => now()->addMonths(3)->addDays(3),
                'registration_deadline' => now()->addMonths(2)->addWeeks(2),
                'max_participants' => 48,
                'price_members' => 10.00,
                'price_non_members' => 20.00,
                'status' => 'published',
            ],
        ];

        foreach ($events as $eventData) {
            $event = Event::create([
                ...$eventData,
                'slug' => Str::slug($eventData['title']),
                'created_by' => $admin->id,
                'content' => $eventData['description'] . ' Meer gedetailleerde informatie volgt binnenkort.',
            ]);

            // Add some registrations for the events
            if ($event->type === 'tournament' || $event->type === 'training') {
                $numberOfRegistrations = rand(3, min(8, count($members)));
                $registeredMembers = array_rand($members, $numberOfRegistrations);
                
                if (!is_array($registeredMembers)) {
                    $registeredMembers = [$registeredMembers];
                }

                foreach ($registeredMembers as $memberIndex) {
                    EventRegistration::create([
                        'event_id' => $event->id,
                        'user_id' => $members[$memberIndex]->id,
                        'status' => rand(0, 10) > 2 ? 'confirmed' : 'pending',
                        'payment_status' => rand(0, 10) > 3 ? 'paid' : 'unpaid',
                        'amount_paid' => rand(0, 10) > 3 ? $event->price_members : 0,
                        'payment_method' => rand(0, 10) > 5 ? 'mollie' : 'bank_transfer',
                    ]);
                }
            }
        }

        $this->command->info('Tennis Club seeding completed!');
        $this->command->info('Admin login: admin@tennisclub.be / password');
        $this->command->info('Board login: board@tennisclub.be / password');
        $this->command->info('Trainer login: trainer@tennisclub.be / password');
    }
}
