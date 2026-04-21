<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'board_coordinates'))
                $table->boolean('board_coordinates')->default(true)->after('high_contrast');
            if (!Schema::hasColumn('users', 'move_confirmation'))
                $table->boolean('move_confirmation')->default(false)->after('board_coordinates');
            if (!Schema::hasColumn('users', 'auto_queen'))
                $table->boolean('auto_queen')->default(true)->after('move_confirmation');
            if (!Schema::hasColumn('users', 'default_difficulty'))
                $table->unsignedTinyInteger('default_difficulty')->default(5)->after('auto_queen');
            if (!Schema::hasColumn('users', 'show_elo_opponent'))
                $table->boolean('show_elo_opponent')->default(true)->after('default_difficulty');
            if (!Schema::hasColumn('users', 'animation_speed'))
                $table->string('animation_speed', 10)->default('normal')->after('show_elo_opponent');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['board_coordinates', 'move_confirmation', 'auto_queen', 'default_difficulty', 'show_elo_opponent', 'animation_speed'] as $col) {
                if (Schema::hasColumn('users', $col)) $table->dropColumn($col);
            }
        });
    }
};
