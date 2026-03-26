<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('openings', function (Blueprint $table) {
            $table->id();
            $table->string('eco', 10)->index();
            $table->char('category', 1)->index(); // A, B, C, D, E
            $table->string('name');
            $table->string('name_lv');
            $table->text('moves'); // SAN move sequence: "e4 c5 Nf3 d6"
            $table->text('summary_lv')->nullable();
            $table->json('ideas_lv')->nullable(); // ["idea1", "idea2"]
            $table->json('move_explanations_lv')->nullable(); // [{"move":"e4","text":"..."},...]
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['eco', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('openings');
    }
};
