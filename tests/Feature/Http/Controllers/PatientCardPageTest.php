<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\Support\FakesSquare;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * The page where a patient saves a card. The app asks the API for a signed link and opens it in a
 * browser sheet; the card is typed into Square's form and only Square's token is posted back.
 */
class PatientCardPageTest extends TestCase
{
    use FakesSquare;
    use InsertsRows;

    private const FORM = ['name' => 'Pat Payer', 'address' => '1 Main St, Brooklyn', 'zip' => '11201', 'card' => 'cnon:token'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        config(['app.SQUARE_LOCATION_ID' => 'LOC-1', 'app.SQUARE_APPLICATION_ID' => 'sq0idp-app', 'app.SQUARE_ENVIRONMENT' => 'production']);
        $this->row('users', ['id' => 2, 'name' => 'Pat', 'last_name' => 'Payer', 'email' => 'pat@example.test', 'phone' => '(555) 010-0002', 'address' => '9 Elm St', 'zip' => '11201', 'role' => 'user', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 3, 'email' => 'dee@example.test', 'phone' => '(555) 010-0003', 'role' => 'driver', 'isactive' => 1, 'isblocked' => 0]);
    }

    /** The link the app is given for a patient, as a path and query. */
    private function link(int $userId = 2): string
    {
        Passport::actingAs(User::findOrFail($userId));
        $url = $this->getJson('/api/cards/add_link')->assertOk()->json('url');
        $this->app['auth']->forgetGuards();

        return substr($url, strlen(url('/')));
    }

    public function test_the_link_opens_squares_card_form_for_that_patient_without_signing_in(): void
    {
        $link = $this->link();
        $this->assertStringStartsWith('/patient/card/2?expires=', $link);

        $this->get($link)->assertOk()
            ->assertSee('https://web.squarecdn.com/v1/square.js', false)
            ->assertSee('"sq0idp-app"', false)->assertSee('"LOC-1"', false)
            ->assertSee('name="card" id="card-token"', false)
            ->assertSee('value="Pat Payer"', false)->assertSee('value="9 Elm St"', false)
            ->assertSee('Save card')
            ->assertDontSee('Card on file');
    }

    public function test_the_sandbox_uses_squares_sandbox_form(): void
    {
        config(['app.SQUARE_ENVIRONMENT' => 'sandbox']);

        $this->get($this->link())->assertOk()->assertSee('https://sandbox.web.squarecdn.com/v1/square.js', false);
    }

    public function test_a_link_that_was_changed_or_has_expired_opens_nothing(): void
    {
        $link = $this->link();

        $this->get(str_replace('/patient/card/2?', '/patient/card/3?', $link))->assertForbidden();
        $this->get('/patient/card/2')->assertForbidden();
        $this->post($link.'x', self::FORM)->assertForbidden();
        $this->travel(16)->minutes();
        $this->get($link)->assertForbidden();
        $this->assertSame(0, DB::table('payment_accounts')->count());
    }

    public function test_only_a_patient_is_given_a_link(): void
    {
        Passport::actingAs(User::findOrFail(3));

        $this->getJson('/api/cards/add_link')->assertForbidden();
    }

    public function test_posting_squares_token_saves_the_card(): void
    {
        $this->fakeSquare();
        $link = $this->link();

        $this->post($link, self::FORM)->assertOk()
            ->assertSee('Card saved. You can close this page and go back to the app.')
            ->assertSee('Card on file: •••• 4242', false)
            ->assertDontSee('id="save-card"', false);

        $this->assertSame(['cnon:token', 'cust_new'], [$this->square['createPayment'][0]->getSourceId(), $this->square['createPayment'][0]->getCustomerId()]);
        $this->assertSame(
            ['user_id' => 2, 'type' => 'card', 'profile_id' => 'cust_new', 'payment_profile_id' => 'card_new', 'card' => '4242'],
            (array) DB::table('payment_accounts')->first(['user_id', 'type', 'profile_id', 'payment_profile_id', 'card'])
        );

        // The app then lists the card through the API it already uses.
        Passport::actingAs(User::findOrFail(2));
        $this->getJson('/api/cards')->assertOk()->assertExactJson(['card' => 'XXXX4242']);
    }

    public function test_a_refused_card_or_an_incomplete_form_says_so_and_saves_no_card(): void
    {
        $link = $this->link();

        $this->fakeSquare(['createPayment' => [['code' => 'CARD_DECLINED']]]);
        $this->post($link, self::FORM)->assertOk()
            ->assertSee('The card could not be saved. Check the details, or try another card.')
            ->assertDontSee('CARD_DECLINED')->assertSee('id="save-card"', false);

        $this->fakeSquare();
        $this->post($link, ['card' => ''] + self::FORM)->assertOk()->assertSee('Enter the card, the name on it and the billing address and ZIP code.');
        $this->assertSame([], $this->square['createPayment']);
        $this->assertNull(DB::table('payment_accounts')->value('payment_profile_id'));
    }

    public function test_a_card_whose_confirming_dollar_could_not_be_refunded_is_still_saved(): void
    {
        $this->fakeSquare(['refundPayment' => [['code' => 'REFUND_DECLINED']]]);

        $this->post($this->link(), self::FORM)->assertOk()->assertSee('Card saved.');

        $this->assertSame('card_new', DB::table('payment_accounts')->value('payment_profile_id'));
    }
}
