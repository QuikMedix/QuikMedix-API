<?php

namespace App\Actions;

use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class RotateSignature
{
    public function handle(string $relativePath): string
    {
        $image = Image::decodePath(public_path($relativePath));

        // Intervention 4 uses clockwise angles; the old -90-degree rotation was clockwise.
        $image->rotate(90);

        $destination = '/images/signature/'.Str::uuid().'.'.pathinfo($relativePath, PATHINFO_EXTENSION);
        $image->save(public_path($destination));

        return $destination;
    }
}
