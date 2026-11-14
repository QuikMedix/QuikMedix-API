<?php

namespace Tests\Support;

/**
 * Stands in for https:// while a test runs, so the ETA code's file_get_contents() routing
 * requests are recorded and answered locally instead of reaching HERE.
 */
class FakeHereRouting
{
    /** @var list<array{url: string, header: string|null}> */
    public static array $requests = [];

    /** Metres and seconds reported for every leg of a route. */
    public static int $legLength = 1609;

    public static int $legDuration = 360;

    /** Requests starting from this "lat,lng" fail, as when HERE cannot be reached. */
    public static ?string $unreachableOrigin = null;

    /** @var resource|null */
    public $context;

    private string $body = '';

    private int $position = 0;

    public static function install(): void
    {
        self::$requests = [];
        self::$legLength = 1609;
        self::$legDuration = 360;
        self::$unreachableOrigin = null;
        stream_wrapper_unregister('https');
        stream_wrapper_register('https', self::class, STREAM_IS_URL);
    }

    public static function uninstall(): void
    {
        stream_wrapper_restore('https');
    }

    /**
     * The query of a recorded request: scalar parameters as strings, the repeated "via" as a list.
     *
     * @return array{transportMode: string, origin: string, destination: string, via: list<string>}
     */
    public static function query(int $index): array
    {
        $query = ['via' => []];
        foreach (explode('&', (string) parse_url(self::$requests[$index]['url'], PHP_URL_QUERY)) as $pair) {
            [$name, $value] = explode('=', $pair, 2);
            if ($name === 'via') {
                $query['via'][] = $value;
            } else {
                $query[$name] = $value;
            }
        }

        return $query;
    }

    public function stream_open(string $path): bool
    {
        $options = $this->context ? stream_context_get_options($this->context) : [];
        self::$requests[] = ['url' => $path, 'header' => $options['http']['header'] ?? null];
        if (self::$unreachableOrigin !== null && str_contains($path, '&origin='.self::$unreachableOrigin.'&')) {
            return false;
        }

        // HERE answers with one section per leg: origin to the first stop, then stop to stop.
        $legs = substr_count($path, '&via=') + 1;
        $section = ['summary' => ['length' => self::$legLength, 'duration' => self::$legDuration]];
        $this->body = json_encode(['routes' => [['sections' => array_fill(0, $legs, $section)]]]);

        return true;
    }

    public function stream_read(int $count): string
    {
        $chunk = substr($this->body, $this->position, $count);
        $this->position += strlen($chunk);

        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen($this->body);
    }

    /**
     * @return array<string, mixed>
     */
    public function stream_stat(): array
    {
        return [];
    }
}
