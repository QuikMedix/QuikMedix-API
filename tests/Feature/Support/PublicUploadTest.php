<?php

namespace Tests\Feature\Support;

use App\Support\PublicUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PublicUploadTest extends TestCase
{
    /**
     * A real upload, whose type is detected from its content (fake files report a type from their name).
     */
    private function upload(string $clientName, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, $content);

        return new UploadedFile($path, $clientName, null, null, true);
    }

    public function test_names_ignore_the_client_filename_and_use_the_real_type(): void
    {
        $jpeg = UploadedFile::fake()->image('photo.jpg');
        $renamed = new UploadedFile($jpeg->getPathname(), 'page.html', 'text/html', null, true);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{40}\.jpg$/', PublicUpload::name($renamed));
    }

    public function test_the_same_upload_keeps_one_name(): void
    {
        $file = UploadedFile::fake()->image('photo.png');

        $this->assertSame(PublicUpload::name($file), PublicUpload::name($file));
        $this->assertNotSame(PublicUpload::name($file), PublicUpload::name(UploadedFile::fake()->image('photo.png')));
    }

    public function test_scripts_are_refused_even_with_an_image_name(): void
    {
        $script = $this->upload('photo.jpg', "<?php echo 'owned';");

        $this->expectException(ValidationException::class);
        PublicUpload::name($script);
    }

    public function test_html_content_is_refused(): void
    {
        $page = $this->upload('photo.png', '<!DOCTYPE html><html><body><script>alert(1)</script></body></html>');

        $this->expectException(ValidationException::class);
        PublicUpload::name($page);
    }
}
