<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\ReactionCatalog;
use Illuminate\Support\Collection;

class MemoryPost extends Model
{
    protected $fillable = [
        'family_member_id',
        'album_id',
        'caption',
        'memory_at',
        'is_favorite',
        'show_on_wall',
    ];

    protected function casts(): array
    {
        return [
            'memory_at' => 'datetime',
            'is_favorite' => 'boolean',
            'show_on_wall' => 'boolean',
        ];
    }

    public function memoryMoment(): \Carbon\Carbon
    {
        return ($this->memory_at ?? $this->created_at)->copy();
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'family_member_id');
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function mediaItems(): HasMany
    {
        return $this->hasMany(MemoryMedia::class, 'memory_post_id')->orderBy('sort_order')->orderBy('id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MemoryReaction::class, 'memory_post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(MemoryPostComment::class, 'memory_post_id')->orderBy('created_at');
    }

    /** @return list<array{id: int, body: string, created_at_human: string, author: array{name: string, emoji: string, photo_url: ?string}, can_delete: bool}> */
    public function commentsPayloadFor(?int $viewerMemberId): array
    {
        return $this->comments
            ->map(fn (MemoryPostComment $comment) => $comment->toWallPayload($viewerMemberId))
            ->values()
            ->all();
    }

    public function coverMedia(): ?MemoryMedia
    {
        $items = $this->relationLoaded('mediaItems')
            ? $this->mediaItems
            : $this->mediaItems()->orderBy('sort_order')->orderBy('id')->get();

        if ($items->isEmpty()) {
            return null;
        }

        $firstImage = $items->first(fn (MemoryMedia $m) => $m->isImage());

        return $firstImage ?? $items->first();
    }

    /** @return Collection<string, int> */
    public function reactionCounts(): Collection
    {
        return $this->reactions
            ->groupBy('emotion')
            ->map(fn ($group) => $group->count())
            ->sortDesc();
    }

    public function reactionForMember(?int $familyMemberId): ?MemoryReaction
    {
        if (! $familyMemberId) {
            return null;
        }

        return $this->reactions->firstWhere('family_member_id', $familyMemberId);
    }

    /** @return list<array{name: string, emotion: string, emoji: string, label: string, relationship: ?string}> */
    public function reactionsPeoplePayload(): array
    {
        return $this->reactions
            ->sortBy(fn ($reaction) => mb_strtolower($reaction->member?->name ?? ''))
            ->values()
            ->map(function ($reaction) {
                $emotion = (string) $reaction->emotion;

                return [
                    'name' => $reaction->member?->name ?? 'Familia',
                    'emotion' => $emotion,
                    'emoji' => ReactionCatalog::emoji($emotion),
                    'label' => ReactionCatalog::label($emotion),
                    'relationship' => $reaction->member?->relationshipLabel(),
                ];
            })
            ->all();
    }

    /** @return array{mine: ?string, counts: list<array{emotion: string, emoji: string, count: int, label: string}>, people: list<array{name: string, emotion: string, emoji: string, label: string, relationship: ?string}>} */
    public function reactionsStateFor(?int $memberId): array
    {
        $counts = $this->reactionCounts();

        return [
            'mine' => $this->reactionForMember($memberId)?->emotion,
            'counts' => $counts->map(fn ($count, $emotion) => [
                'emotion' => (string) $emotion,
                'emoji' => ReactionCatalog::emoji((string) $emotion),
                'label' => ReactionCatalog::label((string) $emotion),
                'count' => (int) $count,
            ])->values()->all(),
            'people' => $this->reactionsPeoplePayload(),
        ];
    }
}
