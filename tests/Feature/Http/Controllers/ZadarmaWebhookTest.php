<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The phone provider's "call answered" notice is recorded only when it carries Zadarma's signature.
 */
class ZadarmaWebhookTest extends TestCase
{
    private const NOTICE = ['event' => 'NOTIFY_ANSWER', 'caller_id' => '15550001111', 'destination' => '15550002222', 'call_start' => '2026-11-15 09:00:00', 'pbx_call_id' => 'in_abc123'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        config(['services.zadarma.secret' => 'zadarma-secret']);
    }

    public function test_a_signed_notice_is_recorded(): void
    {
        $signature = base64_encode(hash_hmac('sha1', '15550001111'.'15550002222'.'2026-11-15 09:00:00', 'zadarma-secret'));

        $this->post('/zadarma', self::NOTICE, ['Signature' => $signature])->assertOk();

        $this->assertSame(['in_abc123'], DB::table('calls')->pluck('call_id')->all());
        $this->assertSame('15550002222', DB::table('calls')->value('to'));
    }

    public function test_an_unsigned_or_wrongly_signed_notice_is_refused(): void
    {
        $this->post('/zadarma', self::NOTICE)->assertForbidden();
        $this->post('/zadarma', self::NOTICE, ['Signature' => base64_encode(hash_hmac('sha1', 'something else', 'zadarma-secret'))])->assertForbidden();
        $this->post('/zadarma', self::NOTICE, ['Signature' => base64_encode(hash_hmac('sha1', '15550001111'.'15550002222'.'2026-11-15 09:00:00', 'another-secret'))])->assertForbidden();

        $this->assertSame(0, DB::table('calls')->count());
    }

    public function test_without_a_configured_secret_nothing_is_recorded(): void
    {
        config(['services.zadarma.secret' => null]);

        $this->post('/zadarma', self::NOTICE, ['Signature' => base64_encode(hash_hmac('sha1', '15550001111'.'15550002222'.'2026-11-15 09:00:00', ''))])->assertStatus(503);
        $this->assertSame(0, DB::table('calls')->count());
    }

    public function test_the_setup_echo_and_other_events_still_answer(): void
    {
        $this->get('/zadarma?zd_echo=12345')->assertOk()->assertSee('12345');
        $this->post('/zadarma', ['event' => 'NOTIFY_START'])->assertOk();
    }
}
