<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('samuel_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('display_name')->nullable();
            $table->string('emoji', 8)->default('👶');
            $table->dateTime('birth_at')->nullable();
            $table->timestamps();
        });

        DB::table('samuel_profiles')->insert([
            'display_name' => null,
            'emoji' => '👶',
            'birth_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('memory_posts', function (Blueprint $table) {
            $table->dateTime('memory_at')->nullable()->after('caption');
            $table->index('memory_at');
        });

        DB::table('memory_posts')->update([
            'memory_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('memory_posts', function (Blueprint $table) {
            $table->dropIndex(['memory_at']);
            $table->dropColumn('memory_at');
        });

        Schema::dropIfExists('samuel_profiles');
    }
};
