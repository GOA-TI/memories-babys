<?php

namespace App\Support;

class AvatarPhotoRules
{
    /** @return list<string> */
    public static function validation(): array
    {
        return [
            'nullable',
            'file',
            'max:8192',
            'mimes:jpg,jpeg,png,webp,gif,heic,heif',
        ];
    }
}
