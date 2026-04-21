<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('move_index');
            $table->text('comment')->nullable();
            $table->json('arrows')->nullable();   // [{ from: "e2", to: "e4", color: "green" }]
            $table->json('highlights')->nullable(); // [{ square: "e4", color: "yellow" }]
            $table->timestamps();

            $table->unique(['game_id', 'user_id', 'move_index']);
            $table->index(['game_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_annotations');
    }
};
