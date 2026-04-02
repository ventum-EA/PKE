<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->integer('move_number');
            $table->enum('color', ['white', 'black']);
            $table->string('move_san', 10);
            $table->string('move_uci', 10)->nullable();
            $table->string('fen_before')->nullable();
            $table->string('fen_after')->nullable();
            $table->float('eval_before')->nullable();
            $table->float('eval_after')->nullable();
            $table->float('eval_diff')->nullable();
            $table->string('best_move', 10)->nullable();
            $table->enum('classification', ['best', 'excellent', 'good', 'inaccuracy', 'mistake', 'blunder'])->nullable();
            $table->enum('error_category', ['tactical', 'positional', 'opening', 'endgame'])->nullable();
            $table->text('explanation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_moves');
    }
};
