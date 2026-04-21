<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            // Games
            ['slug' => 'first_game',     'name' => 'First Steps',        'name_lv' => 'Pirmie soļi',           'description' => 'Play your first game',                  'description_lv' => 'Nospēlē pirmo partiju',                 'icon' => '♟',  'category' => 'games',    'tier' => 'bronze', 'threshold' => 1,   'sort_order' => 1],
            ['slug' => 'games_10',       'name' => 'Getting Started',    'name_lv' => 'Sākums',                'description' => 'Play 10 games',                         'description_lv' => 'Nospēlē 10 partijas',                   'icon' => '♟',  'category' => 'games',    'tier' => 'silver', 'threshold' => 10,  'sort_order' => 2],
            ['slug' => 'games_50',       'name' => 'Dedicated Player',   'name_lv' => 'Uzticīgs spēlētājs',    'description' => 'Play 50 games',                         'description_lv' => 'Nospēlē 50 partijas',                   'icon' => '♟',  'category' => 'games',    'tier' => 'gold',   'threshold' => 50,  'sort_order' => 3],
            ['slug' => 'games_100',      'name' => 'Century',            'name_lv' => 'Simtgade',               'description' => 'Play 100 games',                        'description_lv' => 'Nospēlē 100 partijas',                  'icon' => '♚',  'category' => 'games',    'tier' => 'gold',   'threshold' => 100, 'sort_order' => 4],

            // Wins
            ['slug' => 'first_win',      'name' => 'First Victory',      'name_lv' => 'Pirmā uzvara',          'description' => 'Win your first game',                   'description_lv' => 'Izcīni pirmo uzvaru',                   'icon' => '🏆', 'category' => 'games',    'tier' => 'bronze', 'threshold' => 1,   'sort_order' => 5],
            ['slug' => 'wins_10',        'name' => 'Winning Streak',     'name_lv' => 'Uzvaru sērija',         'description' => 'Win 10 games',                          'description_lv' => 'Izcīni 10 uzvaras',                     'icon' => '🏆', 'category' => 'games',    'tier' => 'silver', 'threshold' => 10,  'sort_order' => 6],
            ['slug' => 'wins_25',        'name' => 'Champion',           'name_lv' => 'Čempions',               'description' => 'Win 25 games',                          'description_lv' => 'Izcīni 25 uzvaras',                     'icon' => '🏆', 'category' => 'games',    'tier' => 'gold',   'threshold' => 25,  'sort_order' => 7],

            // Analysis
            ['slug' => 'first_analysis', 'name' => 'Self-Aware',         'name_lv' => 'Pašapzināšanās',        'description' => 'Analyze your first game',               'description_lv' => 'Analizē pirmo partiju',                 'icon' => '🔍', 'category' => 'analysis', 'tier' => 'bronze', 'threshold' => 1,   'sort_order' => 8],
            ['slug' => 'analyzed_10',    'name' => 'Studious',           'name_lv' => 'Čakls students',        'description' => 'Analyze 10 games',                      'description_lv' => 'Analizē 10 partijas',                   'icon' => '🔍', 'category' => 'analysis', 'tier' => 'silver', 'threshold' => 10,  'sort_order' => 9],
            ['slug' => 'analyzed_25',    'name' => 'Deep Thinker',       'name_lv' => 'Dziļais domātājs',      'description' => 'Analyze 25 games',                      'description_lv' => 'Analizē 25 partijas',                   'icon' => '🔍', 'category' => 'analysis', 'tier' => 'gold',   'threshold' => 25,  'sort_order' => 10],

            // Training
            ['slug' => 'puzzles_10',     'name' => 'Puzzle Solver',      'name_lv' => 'Uzdevumu risinātājs',   'description' => 'Solve 10 training puzzles',             'description_lv' => 'Atrisini 10 treniņu uzdevumus',         'icon' => '⚡', 'category' => 'training', 'tier' => 'bronze', 'threshold' => 10,  'sort_order' => 11],
            ['slug' => 'puzzles_50',     'name' => 'Tactician',          'name_lv' => 'Taktiķis',              'description' => 'Solve 50 training puzzles',             'description_lv' => 'Atrisini 50 treniņu uzdevumus',         'icon' => '⚡', 'category' => 'training', 'tier' => 'silver', 'threshold' => 50,  'sort_order' => 12],
            ['slug' => 'puzzles_100',    'name' => 'Puzzle Master',      'name_lv' => 'Uzdevumu meistars',     'description' => 'Solve 100 training puzzles',            'description_lv' => 'Atrisini 100 treniņu uzdevumus',        'icon' => '⚡', 'category' => 'training', 'tier' => 'gold',   'threshold' => 100, 'sort_order' => 13],

            // Streaks
            ['slug' => 'streak_3',       'name' => 'Getting Consistent', 'name_lv' => 'Konsekvence',           'description' => 'Practice 3 days in a row',              'description_lv' => 'Trenējies 3 dienas pēc kārtas',         'icon' => '🔥', 'category' => 'streaks',  'tier' => 'bronze', 'threshold' => 3,   'sort_order' => 14],
            ['slug' => 'streak_7',       'name' => 'Weekly Warrior',     'name_lv' => 'Nedēļas karotājs',      'description' => 'Practice 7 days in a row',              'description_lv' => 'Trenējies 7 dienas pēc kārtas',         'icon' => '🔥', 'category' => 'streaks',  'tier' => 'silver', 'threshold' => 7,   'sort_order' => 15],
            ['slug' => 'streak_30',      'name' => 'Monthly Master',     'name_lv' => 'Mēneša meistars',       'description' => 'Practice 30 days in a row',             'description_lv' => 'Trenējies 30 dienas pēc kārtas',        'icon' => '🔥', 'category' => 'streaks',  'tier' => 'gold',   'threshold' => 30,  'sort_order' => 16],

            // Openings
            ['slug' => 'openings_5',     'name' => 'Opening Explorer',   'name_lv' => 'Atklātņu pētnieks',    'description' => 'Master 5 openings',                     'description_lv' => 'Apgūsti 5 atklātnes',                   'icon' => '📖', 'category' => 'openings', 'tier' => 'silver', 'threshold' => 5,   'sort_order' => 17],
            ['slug' => 'openings_20',    'name' => 'Repertoire Builder', 'name_lv' => 'Repertuāra veidotājs',  'description' => 'Master 20 openings',                    'description_lv' => 'Apgūsti 20 atklātnes',                  'icon' => '📖', 'category' => 'openings', 'tier' => 'gold',   'threshold' => 20,  'sort_order' => 18],

            // Accuracy
            ['slug' => 'accuracy_90',    'name' => 'Precision Play',     'name_lv' => 'Precīza spēle',         'description' => 'Achieve 90%+ accuracy in a game',       'description_lv' => 'Sasniedz 90%+ precizitāti partijā',     'icon' => '🎯', 'category' => 'analysis', 'tier' => 'gold',   'threshold' => 1,   'sort_order' => 19],

            // Daily puzzle
            ['slug' => 'daily_first',    'name' => 'Daily Solver',       'name_lv' => 'Dienas risinātājs',     'description' => 'Solve your first daily puzzle',         'description_lv' => 'Atrisini pirmo dienas uzdevumu',        'icon' => '📅', 'category' => 'training', 'tier' => 'bronze', 'threshold' => 1,   'sort_order' => 20],
            ['slug' => 'daily_10',       'name' => 'Daily Devotee',      'name_lv' => 'Dienas entuziasts',     'description' => 'Solve 10 daily puzzles',                'description_lv' => 'Atrisini 10 dienas uzdevumus',          'icon' => '📅', 'category' => 'training', 'tier' => 'silver', 'threshold' => 10,  'sort_order' => 21],
        ];

        foreach ($achievements as $a) {
            Achievement::updateOrCreate(
                ['slug' => $a['slug']],
                $a,
            );
        }
    }
}
