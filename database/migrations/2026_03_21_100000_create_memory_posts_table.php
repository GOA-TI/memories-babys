<?php

use App\Models\MemoryMedia;
use App\Models\MemoryPost;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memory_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->foreignId('album_id')->nullable()->constrained('albums')->nullOnDelete();
            $table->text('caption')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->boolean('show_on_wall')->default(true);
            $table->timestamps();

            $table->index(['show_on_wall', 'created_at']);
            $table->index(['family_member_id', 'created_at']);
            $table->index(['album_id', 'created_at']);
        });

        Schema::table('memory_media', function (Blueprint $table) {
            $table->foreignId('memory_post_id')->nullable()->after('id')->constrained('memory_posts')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0)->after('memory_post_id');
        });

        MemoryMedia::query()->orderBy('id')->each(function (MemoryMedia $media) {
            $post = MemoryPost::query()->create([
                'family_member_id' => $media->family_member_id,
                'album_id' => $media->album_id,
                'caption' => $media->caption,
                'is_favorite' => $media->is_favorite,
                'show_on_wall' => $media->show_on_wall,
                'created_at' => $media->created_at,
                'updated_at' => $media->updated_at,
            ]);

            $media->update([
                'memory_post_id' => $post->id,
                'sort_order' => 0,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('memory_media', function (Blueprint $table) {
            $table->dropConstrainedForeignId('memory_post_id');
            $table->dropColumn('sort_order');
        });

        Schema::dropIfExists('memory_posts');
    }
};
