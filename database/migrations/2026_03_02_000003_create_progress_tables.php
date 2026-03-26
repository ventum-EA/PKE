<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->boolean('completed')->default(false);
            $table->integer('puzzles_solved')->default(0);
            $table->integer('puzzles_total')->default(0);
            $table->integer('best_score')->default(0); // best % accuracy
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'lesson_id']);
        });

        Schema::create('user_opening_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('opening_id')->constrained()->onDelete('cascade');
            $table->integer('times_practiced')->default(0);
            $table->boolean('practiced_as_white')->default(false);
            $table->boolean('practiced_as_black')->default(false);
            $table->boolean('completed')->default(false);
            $table->timestamp('last_practiced_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'opening_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_opening_progress');
        Schema::dropIfExists('user_lesson_progress');
    }
};
