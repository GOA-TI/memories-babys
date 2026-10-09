<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('samuel_profiles', function (Blueprint $table) {
            $table->string('theme_primary', 32)->nullable()->after('birth_at');
            $table->string('theme_secondary', 32)->nullable()->after('theme_primary');
            $table->string('theme_background', 32)->nullable()->after('theme_secondary');
        });

        DB::table('samuel_profiles')->update([
            'theme_primary' => '#5EAD65',
            'theme_secondary' => '#A8D08D',
            'theme_background' => '#F3FAF0',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('samuel_profiles', function (Blueprint $table) {
            $table->dropColumn(['theme_primary', 'theme_secondary', 'theme_background']);
        });
    }
};
