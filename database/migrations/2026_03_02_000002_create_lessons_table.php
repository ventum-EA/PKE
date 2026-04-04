<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('category', 30)->index(); // fork, pin, skewer, etc.
            $table->string('title');
            $table->string('title_lv');
            $table->text('description_lv');
            $table->tinyInteger('difficulty')->default(1); // 1=beginner, 2=intermediate, 3=advanced
            $table->text('theory_lv'); // full theory text in Latvian
            $table->string('icon', 10)->default('♟');
            $table->string('color', 20)->default('amber');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('lesson_puzzles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->string('fen');
            $table->string('correct_move', 10); // UCI format
            $table->text('explanation_lv');
            $table->json('hints_lv')->nullable(); // ["hint1", "hint2"]
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_puzzles');
        Schema::dropIfExists('lessons');
    }
};
