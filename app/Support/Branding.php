<?php

namespace App\Support;

class Branding
{
    public static function supportContact(): string
    {
        return config('branding.support_phone') ?: config('branding.support_email') ?: 'your pharmacy';
    }

    public static function appAccessMessage(): string
    {
        $downloadUrl = config('branding.download_url');

        return $downloadUrl
            ? 'Download QuikMedix: '.$downloadUrl
            : 'Open QuikMedix: '.rtrim(config('app.url'), '/');
    }

    public static function documentPath(string $name): ?string
    {
        $path = config('branding.documents.'.$name);
        if (!$path || !is_file(public_path($path))) {
            return null;
        }

        return $path;
    }
}
