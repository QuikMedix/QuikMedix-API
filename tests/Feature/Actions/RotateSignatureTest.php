<?php

namespace Tests\Feature\Actions;

use App\Actions\RotateSignature;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RotateSignatureTest extends TestCase
{
    public function test_rotation_preserves_clockwise_direction_and_leaves_the_original_unchanged(): void
    {
        $directory = sys_get_temp_dir().'/quikmedix-signatures-'.bin2hex(random_bytes(8));
        File::makeDirectory($directory.'/images/signature', 0700, true);
        $this->app->usePublicPath($directory);
        $image = imagecreatetruecolor(2, 3);
        $red = imagecolorallocate($image, 255, 0, 0);
        $blue = imagecolorallocate($image, 0, 0, 255);
        imagesetpixel($image, 0, 0, $red);
        imagesetpixel($image, 0, 2, $blue);
        imagepng($image, $directory.'/images/signature/original.png');
        $original = file_get_contents($directory.'/images/signature/original.png');

        try {
            $rotatedPath = app(RotateSignature::class)->handle('/images/signature/original.png');

            $rotated = imagecreatefrompng($directory.$rotatedPath);
            $this->assertSame(3, imagesx($rotated));
            $this->assertSame(2, imagesy($rotated));
            $this->assertSame($red, imagecolorat($rotated, 2, 0) & 0xffffff);
            $this->assertSame($blue, imagecolorat($rotated, 0, 0) & 0xffffff);
            $this->assertSame($original, file_get_contents($directory.'/images/signature/original.png'));
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
