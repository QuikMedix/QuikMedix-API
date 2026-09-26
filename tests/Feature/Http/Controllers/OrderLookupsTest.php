<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\Concerns\LoadsOrderLookups;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class OrderLookupsTest extends TestCase
{
    use CreatesAuthenticationSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user');
            $table->integer('pharmacy_id')->nullable();
            foreach (['last_name', 'address', 'zip', 'apartment'] as $column) {
                $table->string($column)->nullable();
            }
        });
    }

    private function lookups(): object
    {
        return new class
        {
            use LoadsOrderLookups;
        };
    }

    public function test_an_empty_page_loads_no_patients_or_drivers(): void
    {
        User::factory()->create(['role' => 'user']);
        User::factory()->create(['role' => 'driver']);

        $this->assertCount(0, $this->lookups()::get_patients([]));
        $this->assertCount(0, $this->lookups()::get_drivers([null]));
    }

    public function test_only_the_requested_people_of_the_right_role_are_loaded(): void
    {
        $patient = User::factory()->create(['role' => 'user']);
        $driver = User::factory()->create(['role' => 'driver']);
        User::factory()->create(['role' => 'user']);

        $this->assertSame([$patient->id], $this->lookups()::get_patients([$patient->id, $driver->id])->keys()->all());
        $this->assertSame([$driver->id], $this->lookups()::get_drivers([$patient->id, $driver->id, $driver->id])->keys()->all());
    }
}
