<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memory_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memory_media_id')->constrained('memory_media')->cascadeOnDelete();
            $table->foreignId('family_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->string('emotion', 32);
            $table->timestamps();

            $table->unique(['memory_media_id', 'family_member_id']);
            $table->index(['memory_media_id', 'emotion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memory_reactions');
    }
};
