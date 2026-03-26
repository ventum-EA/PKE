<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_id')->nullable()->constrained()->onDelete('set null');
            $table->string('fen');
            $table->string('correct_move', 10);
            $table->string('user_move', 10)->nullable();
            $table->boolean('is_correct')->nullable();
            $table->enum('category', ['tactical', 'positional', 'opening', 'endgame']);
            $table->text('hint')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
