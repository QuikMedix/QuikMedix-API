<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class ChatSupportContactsTest extends TestCase
{
    use CreatesAuthenticationSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user');
            $table->integer('pharmacy_id')->nullable();
        });
    }

    public function test_support_contacts_are_the_superadmins_whatever_their_ids(): void
    {
        User::factory()->create(['role' => 'admin']);
        $first = User::factory()->create(['role' => 'superadmin']);
        User::factory()->create(['role' => 'medic', 'pharmacy_id' => 1]);
        $second = User::factory()->create(['role' => 'superadmin']);

        $this->assertSame([$first->id, $second->id], User::supportContactIds());
    }

    public function test_there_are_no_support_contacts_without_a_superadmin(): void
    {
        User::factory()->create(['role' => 'user']);

        $this->assertSame([], User::supportContactIds());
    }
}
