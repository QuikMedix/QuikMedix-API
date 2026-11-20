<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Support\ChatViewer;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakesChatConversations;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * The chat page inside the mobile apps runs on Twilio Conversations: the token it signs in with,
 * and what the server prepares at Twilio before the page opens.
 */
class ChatConversationsTest extends TestCase
{
    use FakesChatConversations;
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        foreach ([5 => 'Pat', 6 => 'Dee'] as $id => $name) {
            $this->row('users', ['id' => $id, 'name' => $name, 'last_name' => 'Test', 'email' => "$name@example.test", 'phone' => "(555) 000-000$id", 'role' => 'user', 'isactive' => 1, 'isblocked' => 0]);
        }
    }

    private function asPat(): static
    {
        return $this->actingAs(User::findOrFail(5), 'api');
    }

    public function test_the_chat_token_lets_its_holder_into_the_conversations_service_as_themselves_for_an_hour(): void
    {
        $this->fakeChatConversations();

        $token = $this->postJson('/api/chat-token', ['identity' => 5, 'viewer' => 5, 'signature' => ChatViewer::sign(5)])
            ->assertOk()->assertJsonPath('identity', 5)->json('token');

        [$header, $payload] = array_map(fn (string $part) => json_decode(base64_decode(strtr($part, '-_', '+/')), true), array_slice(explode('.', $token), 0, 2));
        $this->assertSame('twilio-fpa;v=1', $header['cty']);
        $this->assertSame(['identity' => '5', 'chat' => ['service_sid' => 'IStest']], $payload['grants']);
        $this->assertSame(['SKtest', 'ACtest'], [$payload['iss'], $payload['sub']]);
        $this->assertEqualsWithDelta(time() + 3600, $payload['exp'], 5);
        $this->assertSame(hash_hmac('sha256', implode('.', array_slice(explode('.', $token), 0, 2)), 'secret', true), base64_decode(strtr(explode('.', $token)[2], '-_', '+/')));
    }

    public function test_no_token_is_issued_and_no_chat_page_opens_while_chat_is_not_set_up(): void
    {
        config(['app.twilio_sid' => 'ACtest', 'app.twilio_apiKey' => null, 'app.twilio_apiSecret' => null, 'app.twilio_chatServiceSid' => null]);

        $this->postJson('/api/chat-token', ['identity' => 5, 'viewer' => 5, 'signature' => ChatViewer::sign(5)])->assertStatus(503);
        $this->asPat()->get('/api/chats/user/6')->assertStatus(503);

        $this->assertSame(0, DB::table('chats')->count());
    }

    public function test_opening_a_chat_for_the_first_time_creates_the_conversation_with_both_people_in_it(): void
    {
        $this->fakeChatConversations();

        $page = $this->asPat()->get('/api/chats/user/6')->assertOk()->assertViewIs('chats.chat_api');

        $this->assertSame([
            'GET /Conversations/5_6',
            'POST /Conversations {"FriendlyName":"5_6","UniqueName":"5_6"}',
            'GET /Conversations/CH0/Participants',
            'POST /Conversations/CH0/Participants {"Identity":"5"}',
            'POST /Conversations/CH0/Participants {"Identity":"6"}',
            'GET /Conversations/CH0/Messages',
        ], $this->chatRequests);
        $this->assertSame(['name' => '5_6', 'user1' => 5, 'user2' => 6], (array) DB::table('chats')->first(['name', 'user1', 'user2']));

        // The page loads the Conversations SDK, pinned and hash-checked, and the script written for it.
        $page->assertSee('https://sdk.twilio.com/js/conversations/releases/3.0.1/twilio-conversations.min.js', false)
            ->assertSee('integrity="sha256-', false)
            ->assertSee('js/chat-conversation.js', false)
            ->assertSee('conversation: "5_6"', false)
            ->assertDontSee('twilio-chat.min.js', false)
            ->assertDontSee('twilio-common', false);
    }

    public function test_opening_a_chat_with_history_shows_it_and_marks_it_read_for_the_viewer(): void
    {
        $this->row('chats', ['name' => '6_5', 'user1' => 6, 'user2' => 5, 'unread_user1' => 0, 'unread_user2' => 2]);
        $this->fakeChatConversations(['6_5' => [
            'participants' => [['sid' => 'MB0', 'identity' => '6', 'last_read_message_index' => 0]],
            'messages' => [
                ['sid' => 'IM0', 'index' => 0, 'author' => '5', 'body' => 'Is my order ready?', 'date_created' => '2026-11-19T15:00:00Z'],
                ['sid' => 'IM1', 'index' => 1, 'author' => '5', 'body' => 'Hello?', 'date_created' => '2026-11-19T15:05:00Z'],
                ['sid' => 'IM2', 'index' => 2, 'author' => '6', 'body' => 'On its way <b>now</b>', 'date_created' => '2026-11-19T16:30:00Z'],
            ],
        ]]);

        $page = $this->asPat()->get('/api/chats/user/6')->assertOk();

        $this->assertSame([
            'GET /Conversations/6_5',
            'GET /Conversations/CH0/Participants',
            'POST /Conversations/CH0/Participants {"Identity":"5"}',
            'GET /Conversations/CH0/Messages',
            'POST /Conversations/CH0/Participants/MB1 {"LastReadMessageIndex":2}',
        ], $this->chatRequests);
        $this->assertSame(
            ['unread_user1' => 0, 'unread_user2' => 0, 'last_message_date' => '2026-11-19 11:30:00', 'last_message_body' => 'On its way <b>now</b>'],
            (array) DB::table('chats')->where('name', '6_5')->first(['unread_user1', 'unread_user2', 'last_message_date', 'last_message_body'])
        );

        $page->assertSeeInOrder(['Is my order ready?', 'Hello?', 'On its way &lt;b&gt;now&lt;/b&gt;'], false);
        $html = $page->getContent();
        $history = substr($html, 0, strpos($html, '<template'));
        // Pat wrote two of the three; Dee has read the first of them only.
        $this->assertSame(2, substr_count($history, 'row no-margin own-message'));
        $this->assertSame(1, substr_count($history, 'class="check2 viewed"'));
        $this->assertSame(2, substr_count($history, 'class="check2"'));
    }

    public function test_a_chat_with_someone_who_does_not_exist_is_not_found(): void
    {
        $this->fakeChatConversations();

        $this->asPat()->get('/api/chats/user/404')->assertNotFound();

        $this->assertSame([], $this->chatRequests);
    }
}
