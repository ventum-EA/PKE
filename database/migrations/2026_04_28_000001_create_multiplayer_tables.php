<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('white_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('black_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('waiting');
            // waiting → active → completed | abandoned | draw_offered
            $table->text('pgn')->nullable();
            $table->string('fen', 100)->default('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1');
            $table->string('result', 10)->nullable();           // 1-0, 0-1, 1/2-1/2
            $table->string('result_reason', 30)->nullable();    // checkmate, resignation, timeout, draw_agreement, stalemate, abandon
            $table->string('invite_token', 32)->nullable()->unique();
            $table->boolean('rated')->default(true);
            $table->string('opening_name')->nullable();
            $table->string('opening_eco', 5)->nullable();
            $table->unsignedSmallInteger('total_moves')->default(0);
            $table->unsignedSmallInteger('time_control')->default(600); // seconds per player
            $table->integer('white_time_remaining')->nullable(); // ms
            $table->integer('black_time_remaining')->nullable(); // ms

            // ELO snapshots
            $table->unsignedSmallInteger('white_elo_before')->nullable();
            $table->unsignedSmallInteger('black_elo_before')->nullable();
            $table->smallInteger('white_elo_change')->nullable();
            $table->smallInteger('black_elo_change')->nullable();

            $table->string('draw_offered_by', 10)->nullable(); // 'white' or 'black'
            $table->timestamp('last_move_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['white_id', 'status']);
            $table->index(['black_id', 'status']);
        });

        Schema::create('online_game_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('online_game_id')->constrained('online_games')->cascadeOnDelete();
            $table->unsignedSmallInteger('move_number');
            $table->string('color', 5);     // white / black
            $table->string('move_san', 10);
            $table->string('move_uci', 6);
            $table->string('fen_after', 100);
            $table->timestamp('created_at');

            $table->index(['online_game_id', 'move_number']);
        });

        Schema::create('matchmaking_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('elo_rating');
            $table->unsignedSmallInteger('time_control')->default(600);
            $table->timestamp('created_at');

            $table->unique('user_id');
            $table->index('created_at');
        });

        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('friend_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 10)->default('pending'); // pending, accepted
            $table->timestamps();

            $table->unique(['user_id', 'friend_id']);
            $table->index(['friend_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friendships');
        Schema::dropIfExists('matchmaking_queue');
        Schema::dropIfExists('online_game_moves');
        Schema::dropIfExists('online_games');
    }
};
