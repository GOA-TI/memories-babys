<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memory_media', function (Blueprint $table) {
            $table->string('poster_path', 512)->nullable()->after('local_path');
            $table->string('poster_public_url', 1024)->nullable()->after('poster_path');
        });
    }

    public function down(): void
    {
        Schema::table('memory_media', function (Blueprint $table) {
            $table->dropColumn(['poster_path', 'poster_public_url']);
        });
    }
};
