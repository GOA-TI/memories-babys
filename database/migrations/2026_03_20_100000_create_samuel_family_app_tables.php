<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->string('cedula', 20)->unique();
            $table->string('name');
            $table->string('pin_hash');
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('avatar_emoji', 8)->default('🙂');
            $table->timestamps();
        });

        Schema::create('drive_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('google_client_id')->nullable();
            $table->text('google_client_secret')->nullable();
            $table->text('refresh_token')->nullable();
            $table->string('root_folder_id')->nullable();
            $table->string('owner_email')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('emoji', 8)->default('📁');
            $table->string('color', 32)->default('#f9a8d4');
            $table->string('description')->nullable();
            $table->foreignId('drive_account_id')->nullable()->constrained('drive_accounts')->nullOnDelete();
            $table->string('drive_folder_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('memory_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->foreignId('album_id')->nullable()->constrained('albums')->nullOnDelete();
            $table->foreignId('drive_account_id')->nullable()->constrained('drive_accounts')->nullOnDelete();
            $table->string('drive_file_id')->nullable();
            $table->string('drive_web_view_link', 1024)->nullable();
            $table->string('drive_thumbnail_link', 1024)->nullable();
            $table->string('local_path')->nullable();
            $table->string('original_name');
            $table->string('mime_type', 128);
            $table->text('caption')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->boolean('show_on_wall')->default(true);
            $table->unsignedBigInteger('bytes')->default(0);
            $table->timestamps();

            $table->index(['show_on_wall', 'created_at']);
            $table->index(['is_favorite', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memory_media');
        Schema::dropIfExists('albums');
        Schema::dropIfExists('drive_accounts');
        Schema::dropIfExists('family_members');
    }
};
