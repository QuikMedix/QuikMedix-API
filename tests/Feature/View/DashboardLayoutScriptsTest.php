<?php

namespace Tests\Feature\View;

use Tests\TestCase;

class DashboardLayoutScriptsTest extends TestCase
{
    public function test_the_page_ready_script_is_a_file_loaded_last_in_the_layout(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/master.blade.php'));

        // Loaded without defer or async at the end of the body, where the inline script ran.
        $this->assertMatchesRegularExpression('#<script src="\{\{ URL::asset\(\'/js/layout-ready\.js\?v=1\'\) \}\}"></script>\s*</body>#', $layout);
        $this->assertFileExists(public_path('js/layout-ready.js'));
        $this->assertStringContainsString("\$('.select-state').selectize(", file_get_contents(public_path('js/layout-ready.js')));
        $this->assertStringNotContainsString('.selectize(', $layout);
        $this->assertStringNotContainsString('@if(0)', $layout, 'Script that is never rendered does not belong in the layout.');
    }
}
