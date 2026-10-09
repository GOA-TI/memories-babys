<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

final class PreparedUploadBinary
{
    public function __construct(
        public readonly string $contents,
        public readonly string $mimeType,
        public readonly string $filename,
        public readonly bool $normalized = false,
    ) {}

    public static function fromUploadedFile(UploadedFile $file): self
    {
        $path = $file->getRealPath();
        $contents = ($path && is_readable($path)) ? (string) file_get_contents($path) : '';

        return new self(
            $contents,
            $file->getMimeType() ?: 'application/octet-stream',
            $file->getClientOriginalName() ?: 'archivo',
            false,
        );
    }
}
