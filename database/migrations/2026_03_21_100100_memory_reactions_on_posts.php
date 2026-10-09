<?php

use App\Models\MemoryMedia;
use App\Models\MemoryReaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('memory_reactions', 'memory_post_id')) {
            Schema::table('memory_reactions', function (Blueprint $table) {
                $table->foreignId('memory_post_id')->nullable()->after('id')->constrained('memory_posts')->cascadeOnDelete();
            });
        }

        MemoryReaction::query()->whereNull('memory_post_id')->each(function (MemoryReaction $reaction) {
            $media = MemoryMedia::query()->find($reaction->memory_media_id);
            if ($media?->memory_post_id) {
                $reaction->update(['memory_post_id' => $media->memory_post_id]);
            }
        });

        if (Schema::hasColumn('memory_reactions', 'memory_media_id')) {
            Schema::table('memory_reactions', function (Blueprint $table) {
                $table->dropForeign(['memory_media_id']);
            });

            if ($this->indexExists('memory_reactions', 'memory_reactions_memory_media_id_family_member_id_unique')) {
                Schema::table('memory_reactions', function (Blueprint $table) {
                    $table->dropUnique(['memory_media_id', 'family_member_id']);
                });
            }

            if ($this->indexExists('memory_reactions', 'memory_reactions_memory_media_id_emotion_index')) {
                Schema::table('memory_reactions', function (Blueprint $table) {
                    $table->dropIndex(['memory_media_id', 'emotion']);
                });
            }

            Schema::table('memory_reactions', function (Blueprint $table) {
                $table->dropColumn('memory_media_id');
            });
        }

        Schema::table('memory_reactions', function (Blueprint $table) {
            if (! $this->indexExists('memory_reactions', 'memory_reactions_memory_post_id_family_member_id_unique')) {
                $table->unique(['memory_post_id', 'family_member_id']);
            }
            if (! $this->indexExists('memory_reactions', 'memory_reactions_memory_post_id_emotion_index')) {
                $table->index(['memory_post_id', 'emotion']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('memory_reactions', function (Blueprint $table) {
            $table->dropUnique(['memory_post_id', 'family_member_id']);
            $table->dropIndex(['memory_post_id', 'emotion']);
            $table->foreignId('memory_media_id')->nullable()->constrained('memory_media')->cascadeOnDelete();
            $table->dropForeign(['memory_post_id']);
            $table->dropColumn('memory_post_id');
            $table->unique(['memory_media_id', 'family_member_id']);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $index) {
            if (($index['name'] ?? '') === $indexName) {
                return true;
            }
        }

        return false;
    }
};
