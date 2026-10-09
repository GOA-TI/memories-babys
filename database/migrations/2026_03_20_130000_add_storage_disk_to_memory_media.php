<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memory_media', function (Blueprint $table) {
            $table->string('storage_disk', 16)->default('local')->after('local_path');
            $table->string('public_url', 1024)->nullable()->after('storage_disk');
        });
    }

    public function down(): void
    {
        Schema::table('memory_media', function (Blueprint $table) {
            $table->dropColumn(['storage_disk', 'public_url']);
        });
    }
};
