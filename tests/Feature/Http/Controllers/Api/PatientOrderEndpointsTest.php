<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

/**
 * What a patient can do to their own order from the app: leave a note, rate a delivery, pick a delivery time.
 */
class PatientOrderEndpointsTest extends TestCase
{
    use CreatesAuthenticationSchema;

    private const FORBIDDEN = ['message' => 'You cannot open this page', 'errors' => 'Forbidden'];

    private const BAD_REQUEST = ['message' => 'Something is wrong with this field!', 'errors' => 'Bad Request'];

    private User $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
            $table->integer('pharmacy_id')->nullable();
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('statuse_id')->default(1);
            $table->integer('rating')->nullable();
            $table->integer('delivery_time_id')->nullable();
        });
        Schema::create('notes', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('order_id');
            $table->integer('user_id');
            $table->string('type');
            $table->text('note');
        });
        Schema::create('delivery_times', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
        });

        $this->patient = $this->account('user');
        DB::table('orders')->insert([
            ['id' => 1, 'user_id' => $this->patient->id, 'statuse_id' => 4, 'delivery_time_id' => 1],
            ['id' => 2, 'user_id' => $this->patient->id, 'statuse_id' => 1, 'delivery_time_id' => 1],
        ]);
    }

    private function account(string $role): User
    {
        return User::factory()->create(['role' => $role, 'isactive' => 1, 'isblocked' => 0]);
    }

    public function test_patient_adds_a_note_to_their_order(): void
    {
        $this->actingAs($this->patient, 'api')->postJson('/api/orders/1/add_note', ['note' => "Ring the bell, it's loud"])
            ->assertOk()->assertExactJson(['message' => 'OK']);

        $this->assertDatabaseHas('notes', ['order_id' => 1, 'user_id' => $this->patient->id, 'type' => '2', 'note' => "Ring the bell, it\\'s loud"]);
    }

    public function test_note_text_is_required(): void
    {
        $this->actingAs($this->patient, 'api')->postJson('/api/orders/1/add_note', ['note' => ''])
            ->assertBadRequest()->assertExactJson(self::BAD_REQUEST);

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_only_the_orders_patient_may_add_a_note(): void
    {
        $this->actingAs($this->account('user'), 'api')->postJson('/api/orders/1/add_note', ['note' => 'Hello'])
            ->assertForbidden()->assertExactJson(self::FORBIDDEN);
        $this->actingAs($this->account('driver'), 'api')->postJson('/api/orders/1/add_note', ['note' => 'Hello'])
            ->assertForbidden()->assertExactJson(self::FORBIDDEN);

        $this->assertDatabaseCount('notes', 0);
    }

    /**
     * @return array<string, array{mixed, int}>
     */
    public static function ratings(): array
    {
        return ['in range' => [4, 4], 'above five is capped' => [9, 5], 'negative becomes one' => [-3, 1], 'text counts as zero' => ['great', 0]];
    }

    #[DataProvider('ratings')]
    public function test_patient_rates_a_delivered_order_once(mixed $submitted, int $stored): void
    {
        $this->actingAs($this->patient, 'api')->postJson('/api/orders/1/rating', ['rating' => $submitted])
            ->assertOk()->assertExactJson(['message' => 'OK']);

        $this->assertDatabaseHas('orders', ['id' => 1, 'rating' => $stored]);
    }

    public function test_rating_is_required(): void
    {
        $this->actingAs($this->patient, 'api')->postJson('/api/orders/1/rating', [])
            ->assertBadRequest()->assertExactJson(self::BAD_REQUEST);

        $this->assertDatabaseHas('orders', ['id' => 1, 'rating' => null]);
    }

    public function test_rating_is_refused_for_undelivered_already_rated_or_foreign_orders(): void
    {
        $this->actingAs($this->patient, 'api')->postJson('/api/orders/2/rating', ['rating' => 5])
            ->assertForbidden()->assertExactJson(self::FORBIDDEN);

        DB::table('orders')->where('id', 1)->update(['rating' => 3]);
        $this->postJson('/api/orders/1/rating', ['rating' => 5])->assertForbidden()->assertExactJson(self::FORBIDDEN);

        DB::table('orders')->where('id', 1)->update(['rating' => null]);
        $this->actingAs($this->account('user'), 'api')->postJson('/api/orders/1/rating', ['rating' => 5])
            ->assertForbidden()->assertExactJson(self::FORBIDDEN);

        $this->assertDatabaseHas('orders', ['id' => 1, 'rating' => null]);
        $this->assertDatabaseHas('orders', ['id' => 2, 'rating' => null]);
    }

    public function test_delivery_times_are_listed_for_any_active_account(): void
    {
        DB::table('delivery_times')->insert([['id' => 1, 'name' => 'Next day'], ['id' => 2, 'name' => 'Same day']]);

        $this->actingAs($this->account('driver'), 'api')->getJson('/api/orders/delivery_times')
            ->assertOk()->assertExactJson(['delivery_times' => [['id' => 1, 'name' => 'Next day'], ['id' => 2, 'name' => 'Same day']]]);
    }

    public function test_patient_changes_the_delivery_time_of_their_order(): void
    {
        $this->actingAs($this->patient, 'api')->postJson('/api/orders/delivery_times', ['order_id' => 2, 'time_id' => '3'])
            ->assertOk()->assertExactJson(['message' => 'OK']);

        $this->assertDatabaseHas('orders', ['id' => 2, 'delivery_time_id' => 3]);
        $this->assertDatabaseHas('orders', ['id' => 1, 'delivery_time_id' => 1]);
    }

    public function test_delivery_time_change_reports_missing_fields_foreign_orders_and_roles(): void
    {
        $this->actingAs($this->patient, 'api')->postJson('/api/orders/delivery_times', ['order_id' => 2])
            ->assertForbidden()->assertExactJson(['message' => 'Fields is not be empty.', 'errors' => 'Forbidden']);
        $this->postJson('/api/orders/delivery_times', ['order_id' => 99, 'time_id' => 2])
            ->assertNotFound()->assertExactJson(['message' => 'Order not found', 'errors' => 'Not Found']);

        $this->actingAs($this->account('user'), 'api')->postJson('/api/orders/delivery_times', ['order_id' => 2, 'time_id' => 2])
            ->assertNotFound()->assertExactJson(['message' => 'Order not found', 'errors' => 'Not Found']);
        $this->actingAs($this->account('driver'), 'api')->postJson('/api/orders/delivery_times', ['order_id' => 2, 'time_id' => 2])
            ->assertForbidden()->assertExactJson(self::FORBIDDEN);

        $this->assertDatabaseHas('orders', ['id' => 2, 'delivery_time_id' => 1]);
    }
}
