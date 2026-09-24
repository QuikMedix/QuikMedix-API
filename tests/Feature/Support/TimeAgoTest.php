<?php

namespace Tests\Feature\Support;

use App\Support\TimeAgo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TimeAgoTest extends TestCase
{
    public static function intervals(): array
    {
        return [
            'just now' => ['now', false, 'just now'],
            'seconds' => ['-45 seconds', false, '45 seconds ago'],
            'one minute' => ['-1 minute', false, '1 minute ago'],
            'weeks from days' => ['-15 days', false, '2 weeks ago'],
            'full breakdown' => ['-9 days -3 hours', true, '1 week, 2 days, 3 hours ago'],
            'null is now' => [null, false, 'just now'],
        ];
    }

    #[DataProvider('intervals')]
    public function test_formats_elapsed_time_like_the_old_view_helper(?string $relative, bool $full, string $expected): void
    {
        $datetime = $relative === null ? null : (new \DateTime($relative))->format('Y-m-d H:i:s');

        $this->assertSame($expected, TimeAgo::format($datetime, $full));
    }
}
