<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->foreignId('actor_family_member_id')->nullable()->constrained('family_members')->nullOnDelete();
            $table->foreignId('memory_post_id')->nullable()->constrained('memory_posts')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('title');
            $table->string('body', 500);
            $table->string('url', 500);
            $table->json('meta')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['family_member_id', 'read_at', 'created_at'], 'family_notif_member_read_created_idx');
        });

        Schema::create('family_push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->string('endpoint', 500)->unique();
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding', 32)->default('aesgcm');
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index('family_member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_push_subscriptions');
        Schema::dropIfExists('family_notifications');
    }
};
