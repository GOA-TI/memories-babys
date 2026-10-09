<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MemoryMedia extends Model
{
    protected $table = 'memory_media';

    protected $fillable = [
        'memory_post_id',
        'sort_order',
        'family_member_id',
        'album_id',
        'drive_account_id',
        'drive_file_id',
        'drive_web_view_link',
        'drive_thumbnail_link',
        'local_path',
        'poster_path',
        'poster_public_url',
        'storage_disk',
        'public_url',
        'original_name',
        'mime_type',
        'caption',
        'is_favorite',
        'show_on_wall',
        'bytes',
    ];

    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'show_on_wall' => 'boolean',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(MemoryPost::class, 'memory_post_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'family_member_id');
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    /** Fotos/videos que pertenecen a la carpeta (directo o vía publicación). */
    public function scopeInAlbum(Builder $query, Album|int $album): Builder
    {
        $albumId = $album instanceof Album ? $album->id : $album;

        return $query->where(function (Builder $inner) use ($albumId) {
            $inner->where('album_id', $albumId)
                ->orWhereHas('post', fn (Builder $post) => $post->where('album_id', $albumId));
        });
    }

    public function driveAccount(): BelongsTo
    {
        return $this->belongsTo(DriveAccount::class);
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function isImage(): bool
    {
        if ($this->isVideo()) {
            return false;
        }

        if (str_starts_with((string) $this->mime_type, 'image/')) {
            return true;
        }

        $mime = strtolower((string) $this->mime_type);
        if (str_contains($mime, 'heic') || str_contains($mime, 'heif')) {
            return true;
        }

        $ext = strtolower(pathinfo((string) $this->original_name, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif', 'avif'], true);
    }

    public function displayUrl(): ?string
    {
        if ($this->storage_disk === 'r2') {
            if (filled($this->public_url)) {
                return $this->public_url;
            }
            if ($this->local_path) {
                return route('media.serve', $this);
            }
        }

        if ($this->drive_thumbnail_link && $this->isImage()) {
            return $this->drive_thumbnail_link;
        }

        if ($this->storage_disk === 'local' && $this->local_path && Storage::disk('local')->exists($this->local_path)) {
            return route('media.serve', $this);
        }

        if ($this->storage_disk === 'drive' || filled($this->drive_file_id)) {
            return $this->drive_web_view_link;
        }

        return $this->public_url ?: $this->drive_web_view_link;
    }

    /** URL ligera para muro, inicio y carrusel (menos peso que el original). */
    public function feedDisplayUrl(): ?string
    {
        if ($this->isVideo()) {
            return $this->feedPosterUrl();
        }

        if ($this->isImage()) {
            if (in_array($this->storage_disk, ['local', 'r2'], true) && filled($this->local_path)) {
                return route('media.serve', ['media' => $this, 'feed' => 1]);
            }
            if (filled($this->drive_thumbnail_link)) {
                return $this->drive_thumbnail_link;
            }
        }

        return $this->displayUrl();
    }

    /** Miniatura del video en el muro (no descarga el MP4). */
    public function feedPosterUrl(): ?string
    {
        if (filled($this->poster_public_url)) {
            return $this->poster_public_url;
        }

        if (filled($this->poster_path)) {
            return route('media.serve', ['media' => $this, 'poster' => 1]);
        }

        if ($this->canStreamViaApp()) {
            return route('media.serve', ['media' => $this, 'poster' => 1]);
        }

        if (filled($this->drive_thumbnail_link)) {
            return $this->drive_thumbnail_link;
        }

        return null;
    }

    public function canStreamViaApp(): bool
    {
        return in_array($this->storage_disk, ['local', 'r2'], true) && filled($this->local_path);
    }

    /** URL a tamaño completo para visor modal. */
    public function viewerUrl(): ?string
    {
        if ($this->storage_disk === 'r2' && filled($this->public_url)) {
            return $this->public_url;
        }

        if ($this->canStreamViaApp()) {
            return route('media.serve', $this);
        }

        return $this->displayUrl();
    }

    /** Descarga directa (app) o enlace externo (Drive). */
    public function appDownloadUrl(): ?string
    {
        if ($this->canStreamViaApp()) {
            return route('media.download', $this);
        }

        if (filled($this->drive_file_id)) {
            return 'https://drive.google.com/uc?export=download&id='.rawurlencode($this->drive_file_id);
        }

        return $this->drive_web_view_link ?: $this->public_url;
    }
}
