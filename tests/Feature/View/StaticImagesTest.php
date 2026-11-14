<?php

namespace Tests\Feature\View;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Every image a view or stylesheet names under /images must be in public/images.
 */
class StaticImagesTest extends TestCase
{
    /**
     * Other companies' logos. They must come from those companies and are not in the repository.
     */
    private const THIRD_PARTY_LOGOS = [
        'images/PioneerRx-0.png',
        'images/bestrx-0.png',
        'images/micromerchantsystem.png',
        'images/mms-logo-v2.png',
    ];

    public function test_every_named_image_exists_except_third_party_logos(): void
    {
        $files = Finder::create()->files()->name(['*.php', '*.css', '*.js'])
            ->in([resource_path('views'), public_path('css'), public_path('js'), app_path()]);
        $named = [];
        foreach ($files as $file) {
            preg_match_all('#(?<![A-Za-z0-9_])/?(images/[A-Za-z0-9_\-./]+\.(?:svg|png|jpe?g|gif|ico|webp))#', $file->getContents(), $matches);
            foreach ($matches[1] as $image) {
                $named[$image] = true;
            }
        }
        $this->assertGreaterThan(40, count($named), 'The scan finds the image references.');

        $missing = array_values(array_filter(array_keys($named), fn (string $image): bool => ! is_file(public_path($image))));
        sort($missing);

        $this->assertSame(self::THIRD_PARTY_LOGOS, $missing);
    }
}
