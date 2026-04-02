<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('pgn');
            $table->string('white_player')->nullable();
            $table->string('black_player')->nullable();
            $table->enum('result', ['1-0', '0-1', '1/2-1/2', '*'])->default('*');
            $table->string('opening_name')->nullable();
            $table->string('opening_eco')->nullable();
            $table->integer('total_moves')->default(0);
            $table->enum('user_color', ['white', 'black'])->default('white');
            $table->boolean('is_analyzed')->default(false);
            $table->string('share_token')->nullable()->unique();
            $table->date('played_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
