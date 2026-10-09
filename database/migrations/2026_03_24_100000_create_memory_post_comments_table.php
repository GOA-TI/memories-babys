<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memory_post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memory_post_id')->constrained('memory_posts')->cascadeOnDelete();
            $table->foreignId('family_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->string('body', 500);
            $table->timestamps();

            $table->index(['memory_post_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memory_post_comments');
    }
};
