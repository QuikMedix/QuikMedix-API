<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Support\ChatViewer;
use Illuminate\Support\Facades\DB;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * The chat endpoints used to answer anyone. They now need a bearer token or the signature the
 * chat page carries, and act only for the person that names.
 */
class ChatAccessTest extends TestCase
{
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        config(['app.twilio_sid' => 'ACtest', 'app.twilio_apiKey' => 'SKtest', 'app.twilio_apiSecret' => 'secret', 'app.twilio_chatServiceSid' => 'IStest']);
        foreach ([5 => 'Pat', 6 => 'Dee', 7 => 'Eve'] as $id => $name) {
            $this->row('users', ['id' => $id, 'name' => $name, 'last_name' => 'Test', 'email' => "$name@example.test", 'phone' => "(555) 000-000$id", 'image' => "$name.png"]);
        }
        $this->row('chats', ['name' => 'chat_5_6', 'user1' => 5, 'user2' => 6, 'unread_user1' => 0, 'unread_user2' => 0]);
    }

    public function test_nobody_is_answered_without_a_token_or_a_signature(): void
    {
        $this->postJson('/api/chat-token', ['identity' => 5])->assertUnauthorized();
        $this->postJson('/api/users/get', ['identity' => 5])->assertUnauthorized();
        $this->postJson('/api/chats/new_message', ['chat_name' => 'chat_5_6', 'created' => 'now', 'body' => 'hi', 'user' => 5])->assertUnauthorized();
        $this->postJson('/api/users/get', ['identity' => 5, 'viewer' => 5, 'signature' => ChatViewer::sign(6)])->assertUnauthorized();
        $this->postJson('/api/users/get', ['identity' => 5, 'viewer' => 5, 'signature' => 'x'])->assertUnauthorized();
    }

    public function test_the_chat_pages_signature_works_until_it_expires(): void
    {
        $signed = ['viewer' => 5, 'signature' => ChatViewer::sign(5)];

        $this->postJson('/api/users/get', ['identity' => 6] + $signed)->assertOk()->assertExactJson(['name' => 'Dee Test', 'image' => 'Dee.png']);

        $this->travel(13)->hours();
        $this->postJson('/api/users/get', ['identity' => 6] + $signed)->assertUnauthorized();
    }

    public function test_a_chat_token_is_only_issued_for_the_callers_own_identity(): void
    {
        $signed = ['viewer' => 5, 'signature' => ChatViewer::sign(5)];

        $this->postJson('/api/chat-token', ['identity' => 6] + $signed)->assertForbidden();
        $this->postJson('/api/chat-token', ['identity' => 5] + $signed)->assertOk()->assertJsonPath('identity', 5)->assertJsonStructure(['token']);
    }

    public function test_only_a_member_can_report_a_message_and_only_as_themselves(): void
    {
        $message = ['chat_name' => 'chat_5_6', 'created' => '2026-11-14 10:00:00', 'body' => 'hi'];

        $this->postJson('/api/chats/new_message', $message + ['user' => 7, 'viewer' => 7, 'signature' => ChatViewer::sign(7)])->assertForbidden();
        $this->postJson('/api/chats/new_message', $message + ['user' => 6, 'viewer' => 5, 'signature' => ChatViewer::sign(5)])->assertForbidden();
        $this->assertSame(0, (int) DB::table('chats')->where('name', 'chat_5_6')->value('unread_user2'));

        $this->postJson('/api/chats/new_message', $message + ['user' => 5, 'viewer' => 5, 'signature' => ChatViewer::sign(5)])->assertOk()->assertExactJson(['result' => 'true']);
        $this->assertSame(1, (int) DB::table('chats')->where('name', 'chat_5_6')->value('unread_user2'));
        $this->assertSame('hi', DB::table('chats')->where('name', 'chat_5_6')->value('last_message_body'));
    }

    public function test_a_bearer_token_names_the_caller_without_a_signature(): void
    {
        $this->actingAs(User::findOrFail(5), 'api');

        $this->postJson('/api/users/get', ['identity' => 6])->assertOk()->assertJsonPath('name', 'Dee Test');
        $this->postJson('/api/chat-token', ['identity' => 6])->assertForbidden();
    }

    public function test_password_resets_for_one_phone_are_limited(): void
    {
        foreach (range(1, 3) as $attempt) {
            $this->postJson('/api/user/reset', ['phone' => '(555) 999-0000'])->assertNotFound();
        }

        $this->postJson('/api/user/reset', ['phone' => '(555) 999-0000'])->assertTooManyRequests();
        $this->postJson('/api/user/reset', ['phone' => '(555) 999-0001'])->assertNotFound();
    }
}
