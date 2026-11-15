<?php

namespace Tests\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Answers the application's HERE routing and Google geocoding requests locally and records them.
 * Unless told otherwise HERE finds a route with one leg per stop; geocoding has no default answer.
 */
class FakeMapServices
{
    /** @var list<array{url: string, header: string|null}> */
    public static array $requests = [];

    /** Metres and seconds reported for every leg of a route. */
    public static int $legLength = 1609;

    public static int $legDuration = 360;

    /** Requests starting from this "lat,lng" fail, as when HERE cannot be reached. */
    public static ?string $unreachableOrigin = null;

    /** HERE answers without any route, as it does for stops it cannot connect. */
    public static bool $withoutRoutes = false;

    /** @var array<string, array<mixed>> JSON answers for URLs containing the key, set with respondWith() */
    private static array $answers = [];

    public static function install(): void
    {
        self::$requests = [];
        self::$legLength = 1609;
        self::$legDuration = 360;
        self::$unreachableOrigin = null;
        self::$withoutRoutes = false;
        self::$answers = [];

        Http::fake(function (Request $request) {
            $url = $request->url();
            $routing = str_contains($url, 'router.hereapi.com/');
            $answer = null;
            foreach (self::$answers as $urlPart => $json) {
                if (str_contains($url, $urlPart)) {
                    $answer = $json;
                    break;
                }
            }
            if (! $routing && $answer === null) {
                return null; // not ours: left to the test's other fakes
            }

            self::$requests[] = ['url' => $url, 'header' => $request->hasHeader('Authorization') ? 'Authorization: '.$request->header('Authorization')[0] : null];
            if (self::$unreachableOrigin !== null && str_contains($url, '&origin='.self::$unreachableOrigin.'&')) {
                throw new ConnectionException('HERE cannot be reached');
            }
            if ($answer !== null) {
                return Http::response($answer);
            }

            // HERE answers with one section per leg: origin to the first stop, then stop to stop.
            $legs = substr_count($url, '&via=') + 1;
            $section = ['summary' => ['length' => self::$legLength, 'duration' => self::$legDuration]];

            return Http::response(['routes' => self::$withoutRoutes ? [] : [['sections' => array_fill(0, $legs, $section)]]]);
        });
    }

    /**
     * Answers requests whose URL contains $urlPart with this JSON.
     *
     * @param array<mixed> $json
     */
    public static function respondWith(string $urlPart, array $json): void
    {
        self::$answers[$urlPart] = $json;
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
}
