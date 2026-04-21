<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Large puzzle bank imported from Lichess (separate from daily_puzzles)
        Schema::create('puzzle_bank', function (Blueprint $table) {
            $table->id();
            $table->string('source_id', 10)->nullable()->unique(); // Lichess puzzle ID
            $table->string('fen');
            $table->string('solution', 100);       // UCI moves space-separated (e.g. "e2e4 d7d5 e4d5")
            $table->unsignedSmallInteger('rating')->default(1500);
            $table->string('themes', 200)->nullable(); // comma-separated: fork,pin,mateIn2
            $table->string('opening_tags', 200)->nullable();
            $table->unsignedSmallInteger('difficulty')->default(2); // 1-3
            $table->unsignedInteger('popularity')->default(0);
            $table->timestamps();

            $table->index('rating');
            $table->index('difficulty');
        });

        // Track which puzzles each user has attempted from the bank
        Schema::create('puzzle_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('puzzle_id')->constrained('puzzle_bank')->cascadeOnDelete();
            $table->boolean('solved')->default(false);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('created_at');

            $table->unique(['user_id', 'puzzle_id']);
            $table->index(['user_id', 'solved']);
        });

        // Detected weakness patterns across multiple games
        Schema::create('user_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('pattern_type', 40);      // hanging_piece, missed_tactic, weak_endgame, opening_mistake, time_trouble
            $table->string('description');             // "You frequently hang knights on moves 8-15"
            $table->string('description_lv')->nullable();
            $table->unsignedSmallInteger('occurrences')->default(0);
            $table->unsignedSmallInteger('severity')->default(1); // 1-3
            $table->string('suggestion')->nullable();  // "Practice knight safety puzzles"
            $table->string('suggestion_lv')->nullable();
            $table->json('evidence')->nullable();      // [{game_id, move_index, fen}]
            $table->timestamp('detected_at');
            $table->timestamps();

            $table->index(['user_id', 'severity']);
        });

        // Track imported games to avoid duplicates
        Schema::create('game_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20);              // lichess, chesscom
            $table->string('source_username', 40);
            $table->string('source_game_id', 30);
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('imported_at');

            $table->unique(['user_id', 'source', 'source_game_id']);
            $table->index(['user_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_imports');
        Schema::dropIfExists('user_patterns');
        Schema::dropIfExists('puzzle_attempts');
        Schema::dropIfExists('puzzle_bank');
    }
};
