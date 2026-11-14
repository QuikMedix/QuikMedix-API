<?php

namespace Tests\Feature\View;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Partner products are shown with the partner's own logo file when one has been added to
 * public/images, and by name until then, so the page never shows a broken image.
 */
class PartnerLogoComponentTest extends TestCase
{
    public function test_partner_is_named_in_text_while_its_logo_file_is_absent(): void
    {
        $html = (string) $this->blade('<x-partner-logo image="images/no-such-partner.png" name="Example Rx" class="mr-3" />');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertMatchesRegularExpression('#<span class="text-black mr-3" style="[^"]*line-height: 40px;[^"]*">Example Rx</span>#', $html);
    }

    public function test_logo_file_is_shown_once_it_exists(): void
    {
        // Any existing image stands in for a supplied logo file.
        $html = (string) $this->blade('<x-partner-logo image="images/logo-sm.png" name="Example Rx" height="28" class="mt-2" />');

        $this->assertStringNotContainsString('<span', $html);
        $this->assertStringContainsString('src="'.url('/images/logo-sm.png').'"', $html);
        $this->assertStringContainsString('alt="Example Rx"', $html);
        $this->assertStringContainsString('height="28"', $html);
        $this->assertStringContainsString('class="mt-2"', $html);
    }

    public function test_partner_name_is_escaped(): void
    {
        $html = (string) $this->blade('<x-partner-logo image="images/no-such-partner.png" :name="$name" />', ['name' => '<b>Rx</b>']);

        $this->assertStringContainsString('&lt;b&gt;Rx&lt;/b&gt;', $html);
    }

    /**
     * @return array<string, array{string, list<array{string, string}>}>
     */
    public static function pages(): array
    {
        return [
            'dashboard' => ['dashboard/index', [
                ['images/micromerchantsystem.png', 'Micro Merchant Systems'], ['images/PioneerRx-0.png', 'PioneerRx'], ['images/bestrx-0.png', 'BestRx'],
            ]],
            'orders list' => ['orders/list', [['images/mms-logo-v2.png', 'Micro Merchant Systems']]],
        ];
    }

    /**
     * @param list<array{string, string}> $partners
     */
    #[DataProvider('pages')]
    public function test_page_shows_its_partners_through_the_component(string $view, array $partners): void
    {
        $source = file_get_contents(resource_path("views/$view.blade.php"));

        foreach ($partners as [$image, $name]) {
            $this->assertStringContainsString('<x-partner-logo image="'.$image.'" name="'.$name.'"', $source);
            $this->assertStringNotContainsString("URL::asset('/$image')", $source);
        }
    }
}
