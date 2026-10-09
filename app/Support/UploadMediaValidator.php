<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class UploadMediaValidator
{
    /** @return list<string> */
    public static function allowedMimeTypes(): array
    {
        return config('samuel.upload.allowed_mimes', []);
    }

    public static function maxKilobytes(): int
    {
        return max(1, (int) config('samuel.upload.max_mb')) * 1024;
    }

    public static function isAllowedMedia(UploadedFile $file): bool
    {
        $mime = strtolower((string) $file->getMimeType());
        $clientMime = strtolower((string) $file->getClientMimeType());
        $allowed = array_map('strtolower', self::allowedMimeTypes());

        foreach ([$mime, $clientMime] as $candidate) {
            if ($candidate !== '' && in_array($candidate, $allowed, true)) {
                return true;
            }
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        $videoExt = ['mp4', 'mov', 'webm', '3gp', '3gpp', 'm4v', 'avi', 'mkv'];
        $imageExt = ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'];

        if (in_array($ext, $videoExt, true)) {
            return true;
        }

        if (in_array($ext, $imageExt, true)) {
            return true;
        }

        if ($mime === 'application/octet-stream' && $ext === '') {
            $name = strtolower((string) $file->getClientOriginalName());
            if (preg_match('/\.(heic|heif|jpe?g|png|webp|mov|mp4|m4v)$/i', $name)) {
                return true;
            }
        }

        return str_starts_with($mime, 'video/') || str_starts_with($mime, 'image/');
    }

    public static function maxFilesPerPost(): int
    {
        return max(1, (int) config('samuel.upload.max_files_per_post', 20));
    }

    /** @param  list<UploadedFile>  $files */
    public static function validateFiles(array $files, string $errorField): array
    {
        $errors = [];
        $files = array_values(array_filter($files, fn ($f) => $f instanceof UploadedFile));

        if ($files === []) {
            $errors[$errorField] = 'Elige al menos una foto o un video.';

            return $errors;
        }

        if (count($files) > self::maxFilesPerPost()) {
            $errors[$errorField] = 'Máximo '.self::maxFilesPerPost().' archivos por publicación.';

            return $errors;
        }

        foreach ($files as $index => $file) {
            $label = count($files) > 1 ? 'Archivo '.($index + 1).': ' : '';

            if (! $file->isValid()) {
                $errors[$errorField] = $label.self::uploadErrorMessage($file);

                return $errors;
            }

            if ($file->getSize() > self::maxKilobytes() * 1024) {
                $maxMin = (int) config('samuel.upload.max_video_minutes', 3);
                $errors[$errorField] = $label.'El archivo es muy pesado. Máximo '
                    .config('samuel.upload.max_mb').' MB por archivo (videos de hasta ~'.$maxMin.' min).';

                return $errors;
            }

            if (! self::isAllowedMedia($file)) {
                $errors[$errorField] = $label.'Formato no permitido. Usa foto (JPG, PNG, HEIC del iPhone) o video (MP4, MOV).';

                return $errors;
            }
        }

        return $errors;
    }

    public static function attachMultipleMediaRules(Validator $validator, string $field, bool $required): void
    {
        $validator->after(function (Validator $v) use ($field, $required) {
            $raw = $v->getValue($field);
            $files = is_array($raw) ? $raw : ($raw instanceof UploadedFile ? [$raw] : []);

            if ($files === [] && ! $required) {
                return;
            }

            foreach (self::validateFiles($files, $field) as $key => $message) {
                $v->errors()->add($key, $message);
            }
        });
    }

    public static function attachMediaRules(Validator $validator, string $field, bool $required): void
    {
        $validator->after(function (Validator $v) use ($field, $required) {
            $file = $v->getValue($field);
            if (! $file instanceof UploadedFile) {
                if ($required) {
                    $v->errors()->add($field, 'Elige una foto o un video.');
                }

                return;
            }

            if (! $file->isValid()) {
                $v->errors()->add($field, self::uploadErrorMessage($file));

                return;
            }

            if ($file->getSize() > self::maxKilobytes() * 1024) {
                $v->errors()->add($field, 'El archivo es muy pesado. Máximo '.config('samuel.upload.max_mb').' MB.');

                return;
            }

            if (! self::isAllowedMedia($file)) {
                $v->errors()->add($field, 'Formato no permitido. Usa foto (JPG, PNG, HEIC del iPhone) o video (MP4, MOV).');
            }
        });
    }

    private static function uploadErrorMessage(UploadedFile $file): string
    {
        $maxMb = (int) config('samuel.upload.max_mb', 300);
        $maxMin = (int) config('samuel.upload.max_video_minutes', 3);
        $sizeHint = 'Máximo '.$maxMb.' MB por archivo (videos de hasta ~'.$maxMin.' min).';

        return match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El video pesa demasiado para subir. '.$sizeHint.' Si sigue fallando, expórtalo en calidad media desde el teléfono.',
            UPLOAD_ERR_PARTIAL => 'La subida se interrumpió. Intenta de nuevo.',
            UPLOAD_ERR_NO_FILE => 'No se recibió ningún archivo.',
            default => 'No se pudo subir el archivo. Intenta de nuevo.',
        };
    }
}
