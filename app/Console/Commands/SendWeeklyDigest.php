<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\WeeklyDigestNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SendWeeklyDigest extends Command
{
    protected $signature = 'digest:send';
    protected $description = 'Send weekly activity digest emails to all users';

    public function handle(): int
    {
        $weekAgo = Carbon::now()->subWeek();

        $users = User::whereNotNull('email')
            ->where('role', '!=', 'admin')
            ->get();

        $count = 0;

        foreach ($users as $user) {
            $gamesPlayed = DB::table('games')
                ->where('user_id', $user->id)
                ->where('created_at', '>=', $weekAgo)
                ->whereNull('deleted_at')
                ->count();

            $gamesWon = DB::table('games')
                ->where('user_id', $user->id)
                ->where('created_at', '>=', $weekAgo)
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where(fn($q2) => $q2->where('result', '1-0')->where('user_color', 'white'))
                      ->orWhere(fn($q2) => $q2->where('result', '0-1')->where('user_color', 'black'));
                })->count();

            $puzzlesSolved = DB::table('training_sessions')
                ->where('user_id', $user->id)
                ->where('created_at', '>=', $weekAgo)
                ->where('is_correct', true)
                ->count();

            $eloHistory = DB::table('elo_history')
                ->where('user_id', $user->id)
                ->where('created_at', '>=', $weekAgo)
                ->get();

            $eloChange = $eloHistory->sum('change');

            $streak = DB::table('daily_puzzle_attempts')
                ->join('daily_puzzles', 'daily_puzzles.id', '=', 'daily_puzzle_attempts.daily_puzzle_id')
                ->where('daily_puzzle_attempts.user_id', $user->id)
                ->where('daily_puzzle_attempts.solved', true)
                ->where('daily_puzzles.puzzle_date', '>=', $weekAgo->toDateString())
                ->count();

            // Generate suggestion based on activity
            $suggestion = match (true) {
                $gamesPlayed === 0 => 'Nospēlē savu pirmo partiju šonedēļ!',
                $puzzlesSolved < 5 => 'Pamēģini atrisināt vairāk dienas uzdevumus — tie uzlabo taktisko redzējumu.',
                $eloChange < 0     => 'Fokusējies uz analīzi — apskatī savas pēdējās kļūdas un trenējies.',
                default            => 'Turpini tādā pat garā — tavs progress ir stabils!',
            };

            $user->notify(new WeeklyDigestNotification([
                'games_played'  => $gamesPlayed,
                'games_won'     => $gamesWon,
                'puzzles_solved' => $puzzlesSolved,
                'elo_change'    => $eloChange,
                'current_elo'   => $user->elo_rating,
                'streak'        => $streak,
                'suggestion'    => $suggestion,
            ]));

            $count++;
        }

        $this->info("Sent {$count} weekly digest emails.");
        return self::SUCCESS;
    }
}
