<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_puzzles', function (Blueprint $table) {
            $table->id();
            $table->date('puzzle_date')->unique();
            $table->string('fen');
            $table->string('correct_move', 10); // UCI format
            $table->string('theme', 64)->nullable();
            $table->string('theme_lv', 64)->nullable();
            $table->text('explanation')->nullable();
            $table->text('explanation_lv')->nullable();
            $table->unsignedSmallInteger('difficulty')->default(2); // 1-3
            $table->timestamps();

            $table->index('puzzle_date');
        });

        Schema::create('daily_puzzle_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_puzzle_id')->constrained('daily_puzzles')->cascadeOnDelete();
            $table->boolean('solved')->default(false);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedInteger('solve_time_seconds')->nullable();
            $table->timestamp('created_at');

            $table->unique(['user_id', 'daily_puzzle_id']);
            $table->index(['daily_puzzle_id', 'solved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_puzzle_attempts');
        Schema::dropIfExists('daily_puzzles');
    }
};
