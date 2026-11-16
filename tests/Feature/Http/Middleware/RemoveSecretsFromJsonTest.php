<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\RemoveSecretsFromJson;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RemoveSecretsFromJsonTest extends TestCase
{
    private function through(mixed $response): \Symfony\Component\HttpFoundation\Response
    {
        return (new RemoveSecretsFromJson)->handle(Request::create('/x'), fn () => $response);
    }

    public function test_password_hashes_and_remember_tokens_are_removed_at_any_depth(): void
    {
        $driver = ['id' => 9, 'name' => 'Dee', 'password' => Hash::make('secret'), 'remember_token' => 'abc', 'phone' => '555'];
        $body = ['order_id' => 4, 'driver' => $driver, 'stops' => [['patient' => $driver + ['note' => null]], ['patient' => null]], 'empty' => new \stdClass, 'list' => []];

        $json = $this->through(response()->json($body))->getContent();
        $text = $this->through(new Response(json_encode($body)))->getContent();

        $expected = '{"order_id":4,"driver":{"id":9,"name":"Dee","phone":"555"},"stops":[{"patient":{"id":9,"name":"Dee","phone":"555","note":null}},{"patient":null}],"empty":{},"list":[]}';
        $this->assertSame($expected, $json);
        $this->assertSame($expected, $text);
    }

    public function test_a_message_about_the_password_field_and_other_answers_are_left_alone(): void
    {
        $errors = '{"message":"The password must be at least 8 characters.","errors":{"password":["The password must be at least 8 characters."],"email":["Taken."]}}';
        $page = '<html><body>{"password":"x"}</body></html>';
        $plain = '{"a":1.50,"b":"é","c":"x\/y"}';

        $this->assertSame($errors, $this->through(new Response($errors, 422))->getContent());
        $this->assertSame($page, $this->through(new Response($page))->getContent());
        $this->assertSame($plain, $this->through(new Response($plain))->getContent());
        $this->assertSame('{"password":"typed-by-the-person"}', $this->through(new Response('{"password":"typed-by-the-person"}'))->getContent());
    }

    public function test_it_runs_on_web_and_api_routes(): void
    {
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        foreach (['web', 'api'] as $group) {
            $this->assertContains(RemoveSecretsFromJson::class, Route::getMiddlewareGroups()[$group]);
        }
    }
}
