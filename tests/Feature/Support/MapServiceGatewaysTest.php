<?php

namespace Tests\Feature\Support;

use App\Actions\GeocodeAddress;
use App\Support\HereMaps;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * The two outside map services are each reached from one place, with a time limit.
 */
class MapServiceGatewaysTest extends TestCase
{
    public function test_here_route_is_requested_with_the_token_and_returns_one_leg_per_stop(): void
    {
        Redis::shouldReceive('get')->with('here_access_token')->andReturn('here-token');
        Http::fake(['router.hereapi.com/*' => Http::response(['routes' => [['sections' => [
            ['summary' => ['length' => 1000, 'duration' => 120]], ['summary' => ['length' => 2000, 'duration' => 240]],
        ]]]])]);

        $legs = HereMaps::route('bicycle', '40.7,-74', '40.72,-74.02', ['40.71,-74.01']);

        $this->assertSame([[1000, 120], [2000, 240]], array_map(fn (object $leg): array => [$leg->summary->length, $leg->summary->duration], $legs));
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://router.hereapi.com/v8/routes?transportMode=bicycle&origin=40.7,-74&destination=40.72,-74.02&return=summary&via=40.71,-74.01'
            && $request->hasHeader('Authorization', 'Bearer here-token'));
    }

    public function test_here_without_a_route_is_null_whatever_the_status(): void
    {
        Redis::shouldReceive('get')->with('here_access_token')->andReturn('here-token');
        Http::fakeSequence('router.hereapi.com/*')
            ->push(['routes' => []])
            ->push(['title' => 'Malformed request'], 400)
            ->push('not json', 502);

        $this->assertNull(HereMaps::route('car', '40.7,-74', '40.72,-74.02'));
        $this->assertNull(HereMaps::route('car', '40.7,-74', '40.72,-74.02'));
        $this->assertNull(HereMaps::route('car', '40.7,-74', '40.72,-74.02'));
    }

    public function test_here_that_cannot_be_reached_throws(): void
    {
        Redis::shouldReceive('get')->with('here_access_token')->andReturn('here-token');
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->expectException(ConnectionException::class);
        HereMaps::route('car', '40.7,-74', '40.72,-74.02');
    }

    public function test_geocoding_returns_googles_answer_as_the_older_pages_read_it(): void
    {
        config(['app.googlemaps_apikey' => 'test-key']);
        Http::fake(['maps.googleapis.com/*' => Http::response(['status' => 'OK', 'results' => [[
            'formatted_address' => '9 New Ave, New York, NY 10002, USA', 'geometry' => ['location' => ['lat' => 40.72, 'lng' => -73.99]],
        ]]])]);

        $data = app(GeocodeAddress::class)->response('9 New Ave #5 & Co');

        $this->assertSame('9 New Ave, New York, NY 10002, USA', $data->results[0]->formatted_address);
        $this->assertSame('40.72,-73.99', $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng);
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_starts_with($request->url(), 'https://maps.googleapis.com/maps/api/geocode/json?')
                && $query === ['address' => '9 New Ave #5 & Co', 'key' => 'test-key'];
        });
    }

    public function test_geocoding_without_a_match_or_with_an_unreadable_answer_has_no_results(): void
    {
        Http::fakeSequence('maps.googleapis.com/*')->push(['status' => 'ZERO_RESULTS', 'results' => []])->push('<html>busy</html>', 503);

        $none = app(GeocodeAddress::class)->response('nowhere');
        $this->assertSame(['ZERO_RESULTS', []], [$none->status, $none->results]);
        $unreadable = app(GeocodeAddress::class)->response('nowhere');
        $this->assertSame(['UNAVAILABLE', []], [$unreadable->status, $unreadable->results]);
    }

    public function test_geocoding_that_cannot_be_reached_has_no_results(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->assertSame([], app(GeocodeAddress::class)->response('nowhere')->results);
    }
}
