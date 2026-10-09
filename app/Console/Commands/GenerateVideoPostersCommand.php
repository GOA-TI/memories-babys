<?php

namespace App\Console\Commands;

use App\Models\MemoryMedia;
use App\Services\MediaVideoPosterService;
use Illuminate\Console\Command;

class GenerateVideoPostersCommand extends Command
{
    protected $signature = 'samuel:video-posters {--missing : Solo videos sin poster}';

    protected $description = 'Genera miniaturas JPEG para videos (muro e inicio más rápidos)';

    public function handle(MediaVideoPosterService $posters): int
    {
        $query = MemoryMedia::query()->where('mime_type', 'like', 'video/%');
        if ($this->option('missing')) {
            $query->whereNull('poster_path');
        }

        $items = $query->orderBy('id')->get();
        if ($items->isEmpty()) {
            $this->info('No hay videos pendientes.');

            return self::SUCCESS;
        }

        $ok = 0;
        foreach ($items as $media) {
            if ($posters->ensurePoster($media)) {
                $ok++;
                $this->line('OK #'.$media->id);
            } else {
                $this->warn('Falló #'.$media->id);
            }
        }

        $this->info("Posters listos: {$ok}/".$items->count());

        return self::SUCCESS;
    }
}
