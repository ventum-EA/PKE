<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'font_size')) {
                $table->string('font_size', 10)->default('medium')->after('sound_enabled');
            }
            if (!Schema::hasColumn('users', 'high_contrast')) {
                $table->boolean('high_contrast')->default(false)->after('font_size');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'high_contrast')) $table->dropColumn('high_contrast');
            if (Schema::hasColumn('users', 'font_size')) $table->dropColumn('font_size');
        });
    }
};
