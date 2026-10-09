<?php

return [
    'app_name' => env('SAMUEL_APP_NAME', 'Samuel'),
    /** Nombre largo para PWA e «Instalar …» en el menú */
    'pwa_name' => env('SAMUEL_PWA_NAME', 'Memorias de Samuel'),
    'tagline' => env('SAMUEL_TAGLINE', 'Nuestro álbum de recuerdos'),

    /** Texto de la bienvenida la primera vez que entra cada familiar */
    'welcome' => [
        'gift_line' => env(
            'SAMUEL_WELCOME_GIFT_LINE',
            'Este álbum es un regalo hecho con mucho amor por tu madrina, para que la familia guarde y celebre juntos los recuerdos de Samuel.'
        ),
    ],

    /** Parentesco respecto al bebé (clave => cómo se muestra) */
    'relationships' => [
        'mama' => 'Mamá',
        'papa' => 'Papá',
        'hermano' => 'Hermano',
        'hermana' => 'Hermana',
        'madrina' => 'Madrina',
        'padrino' => 'Padrino',
        'abuela' => 'Abuela',
        'abuelo' => 'Abuelo',
        'tia' => 'Tía',
        'tio' => 'Tío',
        'prima' => 'Prima',
        'primo' => 'Primo',
        'familia' => 'Familia',
        'amigo' => 'Amigo',
    ],

    'theme' => [
        'primary' => env('SAMUEL_THEME_PRIMARY', '#5EAD65'),
        'secondary' => env('SAMUEL_THEME_SECONDARY', '#A8D08D'),
        'background' => env('SAMUEL_THEME_BACKGROUND', '#F3FAF0'),
    ],

    /** Clave para panel /admin (además de miembros con is_admin). */
    'admin_secret' => env('SAMUEL_ADMIN_SECRET'),

    /** Binario ffmpeg en el servidor (posters de video en el muro). */
    'ffmpeg_bin' => env('SAMUEL_FFMPEG_BIN', 'ffmpeg'),

    'upload' => [
        /** Por archivo (foto o video). ~3 min en 4K en iPhone puede superar 300 MB. */
        'max_mb' => (int) env('SAMUEL_UPLOAD_MAX_MB', 512),
        'max_video_minutes' => (int) env('SAMUEL_UPLOAD_MAX_VIDEO_MINUTES', 3),
        /** Máximo de fotos/videos en una sola publicación */
        'max_files_per_post' => (int) env('SAMUEL_UPLOAD_MAX_FILES', 20),
        'allowed_mimes' => [
            'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif',
            'video/mp4', 'video/quicktime', 'video/webm', 'video/3gpp', 'video/3gpp2',
            'video/x-m4v', 'application/mp4',
        ],
    ],

    /**
     * Cuentas Google Drive (JSON en .env). Ver README para OAuth.
     *
     * @see SAMUEL_DRIVE_ACCOUNTS_JSON
     */
    'drive_accounts_json' => env('SAMUEL_DRIVE_ACCOUNTS_JSON', '[]'),

    /** auto | r2 | drive | local — auto: R2 si está configurado, si no Drive, si no local */
    'storage_driver' => env('SAMUEL_STORAGE_DRIVER', 'auto'),
];
