<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

/**
 * Every signed-in mobile API endpoint refuses blocked or unapproved accounts with the same response,
 * and keeps an active account's token alive.
 */
class ActiveAccountGuardTest extends TestCase
{
    use CreatesAuthenticationSchema;

    private const FORBIDDEN = ['message' => 'You cannot open this page', 'errors' => 'Forbidden'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
            $table->integer('pharmacy_id')->nullable();
            $table->integer('work_now')->default(0);
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function guardedEndpoints(): array
    {
        $endpoints = [
            ['GET', 'profile'], ['GET', 'banner'], ['POST', 'profile'],
            ['GET', 'profile/family_members'], ['POST', 'profile/family_members/add'], ['POST', 'profile/family_members/remove'],
            ['GET', 'patient/home'], ['GET', 'orders'], ['GET', 'orders/show/1'], ['POST', 'orders/show/1'],
            ['GET', 'cards'], ['POST', 'cards/add'], ['GET', 'routes'], ['GET', 'routes-logs'], ['GET', 'routes/show/1'],
            ['GET', 'orders/delivery_times'], ['POST', 'orders/delivery_times'], ['POST', 'orders/qr_scaned'],
            ['POST', 'orders/drop_off_qr'], ['POST', 'orders/drop_off_qr2'], ['POST', 'orders/drop_off'],
            ['POST', 'orders/signature'], ['POST', 'orders/update_status'], ['POST', 'orders/1/add_note'],
            ['POST', 'orders/1/rating'], ['POST', 'orders/copay/pay/1'], ['POST', 'orders/copay/pay_cash/1'],
            ['POST', 'orders/copay/not_pay/1'], ['POST', 'driver/add_location'], ['GET', 'driver/qr'],
            ['POST', 'update/device'], ['GET', 'driver/status'], ['GET', 'driver/status/start'],
            ['GET', 'driver/status/finish'], ['GET', 'test/notification/1'], ['GET', 'user/countunread'],
            ['GET', 'chats'], ['GET', 'chats/user/1'], ['GET', 'routes/start'], ['GET', 'user/count_noread'],
            ['GET', 'driver/payouts'], ['GET', 'news'],
        ];

        return array_combine(array_map(fn (array $endpoint): string => implode(' ', $endpoint), $endpoints), $endpoints);
    }

    #[DataProvider('guardedEndpoints')]
    public function test_blocked_account_is_refused_before_the_endpoint_runs(string $method, string $uri): void
    {
        $blocked = User::factory()->create(['role' => 'driver', 'isactive' => 1, 'isblocked' => 1]);

        $this->actingAs($blocked, 'api')->json($method, '/api/'.$uri)
            ->assertForbidden()->assertExactJson(self::FORBIDDEN);
    }

    #[DataProvider('guardedEndpoints')]
    public function test_account_awaiting_approval_is_refused(string $method, string $uri): void
    {
        $pending = User::factory()->create(['role' => 'user', 'isactive' => 0, 'isblocked' => 0]);

        $this->actingAs($pending, 'api')->json($method, '/api/'.$uri)
            ->assertForbidden()->assertExactJson(self::FORBIDDEN);
    }

    public function test_staff_of_a_blocked_pharmacy_are_refused(): void
    {
        DB::table('pharmacys')->insert(['id' => 2, 'isactive' => 1, 'isblocked' => 1]);
        $staff = User::factory()->create(['role' => 'medic', 'pharmacy_id' => 2, 'isactive' => 1, 'isblocked' => 0]);

        $this->actingAs($staff, 'api')->getJson('/api/driver/status')
            ->assertForbidden()->assertExactJson(self::FORBIDDEN);
    }

    public function test_guest_is_still_rejected_as_unauthenticated(): void
    {
        $this->getJson('/api/driver/status')->assertUnauthorized();
    }

    public function test_active_account_passes_and_its_token_expiry_is_extended(): void
    {
        Carbon::setTestNow('2026-11-13 12:00:00');
        $driver = User::factory()->create(['role' => 'driver', 'isactive' => 1, 'isblocked' => 0, 'work_now' => 1]);
        [$token, $tokenId] = $this->issueToken($driver);

        $this->withToken($token)->getJson('/api/driver/status')->assertOk()->assertExactJson(['work_now' => 1]);

        $this->assertEquals(now()->addDays(7), Passport::token()->newQuery()->find($tokenId)->expires_at);
    }

    public function test_refused_account_does_not_get_a_longer_token(): void
    {
        Carbon::setTestNow('2026-11-13 12:00:00');
        $blocked = User::factory()->create(['role' => 'driver', 'isactive' => 1, 'isblocked' => 1]);
        [$token, $tokenId] = $this->issueToken($blocked);

        $this->withToken($token)->getJson('/api/driver/status')->assertForbidden()->assertExactJson(self::FORBIDDEN);

        $this->assertEquals(now()->addDay(), Passport::token()->newQuery()->find($tokenId)->expires_at);
    }

    /**
     * @return array{string, string}
     */
    private function issueToken(User $user): array
    {
        app(ClientRepository::class)->createPersonalAccessGrantClient('Test client', 'users');
        $result = $user->createToken('test');
        $tokenId = $result->getToken()->getKey();
        Passport::token()->newQuery()->whereKey($tokenId)->update(['expires_at' => now()->addDay()]);

        return [$result->accessToken, $tokenId];
    }
}
