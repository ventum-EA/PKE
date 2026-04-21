<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optimize frequent queries
        Schema::table('game_annotations', function (Blueprint $table) {
            $table->index(['game_id', 'move_index'], 'ga_game_move_idx');
        });

        Schema::table('daily_puzzle_attempts', function (Blueprint $table) {
            $table->index(['user_id', 'solved'], 'dpa_user_solved_idx');
        });

        Schema::table('elo_history', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'eh_user_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('game_annotations', fn(Blueprint $t) => $t->dropIndex('ga_game_move_idx'));
        Schema::table('daily_puzzle_attempts', fn(Blueprint $t) => $t->dropIndex('dpa_user_solved_idx'));
        Schema::table('elo_history', fn(Blueprint $t) => $t->dropIndex('eh_user_date_idx'));
    }
};
