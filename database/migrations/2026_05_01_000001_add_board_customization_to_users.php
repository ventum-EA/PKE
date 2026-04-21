<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'board_theme'))
                $table->string('board_theme', 20)->default('classic')->after('sound_enabled');
            if (!Schema::hasColumn('users', 'piece_style'))
                $table->string('piece_style', 20)->default('standard')->after('board_theme');
            if (!Schema::hasColumn('users', 'email_friend_requests'))
                $table->boolean('email_friend_requests')->default(true)->after('piece_style');
            if (!Schema::hasColumn('users', 'email_game_invites'))
                $table->boolean('email_game_invites')->default(true)->after('email_friend_requests');
            if (!Schema::hasColumn('users', 'email_weekly_digest'))
                $table->boolean('email_weekly_digest')->default(true)->after('email_game_invites');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['board_theme', 'piece_style', 'email_friend_requests', 'email_game_invites', 'email_weekly_digest'] as $col) {
                if (Schema::hasColumn('users', $col)) $table->dropColumn($col);
            }
        });
    }
};
