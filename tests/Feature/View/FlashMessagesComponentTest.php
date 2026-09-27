<?php

namespace Tests\Feature\View;

use Tests\TestCase;

class FlashMessagesComponentTest extends TestCase
{
    public function test_flash_messages_are_escaped(): void
    {
        session()->flash('error', '<script>alert(1)</script>');

        $html = (string) $this->blade('<x-flash-messages />');

        $this->assertStringContainsString('alert-danger', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_dismissible_messages_get_a_close_button(): void
    {
        session()->flash('success', 'Route saved');

        $this->blade('<x-flash-messages dismissible />')
            ->assertSee('Route saved')
            ->assertSee('alert-dismissible', false)
            ->assertSee('data-bs-dismiss="alert"', false);
    }

    public function test_renders_nothing_without_messages(): void
    {
        $this->assertSame('', trim((string) $this->blade('<x-flash-messages />')));
    }
}
