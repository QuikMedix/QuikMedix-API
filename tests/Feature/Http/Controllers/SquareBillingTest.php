<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\Support\FakesSquare;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * Paying an invoice and refilling the balance from a pharmacy's saved card, with a stand-in for
 * Square: what is asked of Square, and what the application records for each kind of answer.
 */
class SquareBillingTest extends TestCase
{
    use FakesSquare;
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        config(['app.SQUARE_LOCATION_ID' => 'LOC-1']);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        $this->row('users', ['id' => 1, 'email' => 'root@example.test', 'role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('pharmacys', ['id' => 1, 'name' => 'Corner Pharmacy', 'isactive' => 1, 'isblocked' => 0, 'balance' => -50]);
        $this->row('payment_pharmacy_accounts', ['pharmacy_id' => 1, 'type' => 'card', 'profile_id' => 'cust_1', 'payment_profile_id' => 'card_1']);
        $this->row('invoices', ['id' => 1, 'pharmacy_id' => 1, 'amount' => 100, 'corrections' => 0, 'copay' => 0, 'payed' => '0', 'date_from' => '2026-11-01', 'date_to' => '2026-11-07']);
        $this->actingAs(User::findOrFail(1));
    }

    public function test_paying_an_invoice_charges_the_saved_card_once_and_records_it(): void
    {
        $this->fakeSquare();

        $this->post('/billing/1', ['pay' => 1, 'invoice_id' => 1])->assertRedirect('/billing/1')->assertSessionHas('error', '');

        $this->assertCount(1, $this->square['createPayment']);
        $charge = $this->square['createPayment'][0];
        $this->assertSame(['card_1', 'cust_1', 'LOC-1', 'Invoice #1', 'USD', true], [$charge->getSourceId(), $charge->getCustomerId(), $charge->getLocationId(), $charge->getNote(), $charge->getAmountMoney()->getCurrency(), $charge->getAutocomplete()]);
        $cents = $charge->getAmountMoney()->getAmount();
        $this->assertSame(10000, $cents);
        $this->assertSame('1', (string) DB::table('invoices')->where('id', 1)->value('payed'));
        $payment = DB::table('pharmacy_payments')->where('invoice_id', 1)->first();
        $this->assertSame(['pay_123', 1], [$payment->transaction_id, (int) $payment->pharmacy_id]);
        $this->assertEqualsWithDelta($cents / 100, (float) $payment->amount, 0.001);
        $this->assertEqualsWithDelta(-50 + $cents / 100, (float) DB::table('pharmacys')->where('id', 1)->value('balance'), 0.001);
    }

    public function test_an_invoice_stays_unpaid_when_square_does_not_complete_or_refuses_the_charge(): void
    {
        $this->fakeSquare(['createPayment' => 'FAILED']);
        $this->post('/billing/1', ['pay' => 1, 'invoice_id' => 1])->assertSessionHas('error', 'Payment not completed!');
        $this->assertCount(1, $this->square['createPayment']);

        $this->fakeSquare(['createPayment' => [['code' => 'CARD_DECLINED']]]);
        $this->post('/billing/1', ['pay' => 1, 'invoice_id' => 1])->assertSessionHas('error', '[{"code":"CARD_DECLINED"}]');

        $this->assertCount(1, $this->square['createPayment']);
        $this->assertSame('0', (string) DB::table('invoices')->where('id', 1)->value('payed'));
        $this->assertSame(0, DB::table('pharmacy_payments')->count());
        $this->assertEqualsWithDelta(-50.0, (float) DB::table('pharmacys')->where('id', 1)->value('balance'), 0.001);
    }

    public function test_a_pharmacy_paying_by_bank_account_is_asked_for_a_card_and_that_card_is_charged(): void
    {
        DB::table('payment_pharmacy_accounts')->where('pharmacy_id', 1)->update(['type' => 'bank', 'payment_profile_id' => null]);
        $this->fakeSquare(['createPayment' => 'PENDING']);

        // Without a card the page asks for one and nothing is charged.
        $this->post('/billing/1', ['pay' => 1, 'invoice_id' => 1])->assertOk()->assertViewIs('card_pharmacy.pay');
        $this->assertCount(0, $this->square['createPayment']);

        $this->post('/billing/1', ['pay' => 1, 'invoice_id' => 1, 'card' => 'cnon:one-time', 'name' => 'Pat Payer'])->assertRedirect('/billing/1')->assertSessionHas('error', '');

        $charge = $this->square['createPayment'][0];
        $this->assertSame(['cnon:one-time', 'cust_1', 10000, 'Invoice #1'], [$charge->getSourceId(), $charge->getCustomerId(), $charge->getAmountMoney()->getAmount(), $charge->getNote()]);
        // A bank payment that is still pending counts as paid.
        $this->assertSame('1', (string) DB::table('invoices')->where('id', 1)->value('payed'));
        $this->assertSame(['pay_123'], DB::table('pharmacy_payments')->pluck('transaction_id')->all());
    }

    public function test_an_invoice_that_is_already_paid_is_not_charged_again(): void
    {
        DB::table('invoices')->where('id', 1)->update(['payed' => '1']);
        $this->fakeSquare();

        $this->post('/billing/1', ['pay' => 1, 'invoice_id' => 1])->assertSessionHas('error', 'This invoice is already paid.');

        $this->assertCount(0, $this->square['createPayment']);
    }

    public function test_a_refill_charges_the_amount_and_adds_it_to_the_balance(): void
    {
        $this->fakeSquare();

        $this->post('/billing/1', ['refill-amount' => '25.50'])->assertRedirect('/billing/1')->assertSessionHas('error', '');

        $this->assertCount(1, $this->square['createPayment']);
        $this->assertSame([2550, 'card_1', 'cust_1'], [$this->square['createPayment'][0]->getAmountMoney()->getAmount(), $this->square['createPayment'][0]->getSourceId(), $this->square['createPayment'][0]->getCustomerId()]);
        $this->assertEqualsWithDelta(-24.5, (float) DB::table('pharmacys')->where('id', 1)->value('balance'), 0.001);
        $this->assertSame(['pay_123'], DB::table('pharmacy_payments')->pluck('transaction_id')->all());
    }

    public function test_collected_copays_come_off_the_invoice_of_a_pharmacy_billed_for_them(): void
    {
        DB::table('pharmacys')->where('id', 1)->update(['copay_bill' => '1']);
        DB::table('invoices')->where('id', 1)->update(['copay' => 30.25, 'corrections' => -5]);
        $this->fakeSquare();

        $this->post('/billing/1', ['pay' => 1, 'invoice_id' => 1])->assertSessionHas('error', '');

        $this->assertSame(6475, $this->square['createPayment'][0]->getAmountMoney()->getAmount());
        $this->assertEqualsWithDelta(14.75, (float) DB::table('pharmacys')->where('id', 1)->value('balance'), 0.001);
    }

    public function test_a_refill_needs_a_saved_card(): void
    {
        $this->fakeSquare();
        DB::table('payment_pharmacy_accounts')->where('pharmacy_id', 1)->update(['type' => 'bank']);
        $this->post('/billing/1', ['refill-amount' => '25'])->assertSessionHas('error', 'PAYMENT METHOD BANK ACCOUNT CAN`T REFILL BALANCE.');

        DB::table('payment_pharmacy_accounts')->delete();
        $this->post('/billing/1', ['refill-amount' => '25'])->assertSessionHas('error', 'PAYMENT METHOD IS EMPTY! PLEASE, ADD YOUR PAYMENT METHOD.');
        $this->post('/billing/1', ['pay' => 1, 'invoice_id' => 1])->assertSessionHas('error', 'PAYMENT METHOD IS EMPTY! PLEASE, ADD YOUR PAYMENT METHOD.');
        $this->post('/billing/1', ['pay' => 1, 'invoice_id' => 99])->assertSessionHas('error', 'Invoice with this ID not found!');

        $this->assertCount(0, $this->square['createPayment']);
        $this->assertSame(0, DB::table('pharmacy_payments')->count());
    }

    public function test_a_refused_refill_changes_nothing_and_says_so(): void
    {
        $this->fakeSquare(['createPayment' => 'FAILED']);
        $this->post('/billing/1', ['refill-amount' => '25'])->assertRedirect('/billing/1')->assertSessionHas('error', 'Payment not completed!');
        $this->assertCount(1, $this->square['createPayment']);

        $this->fakeSquare(['createPayment' => [['code' => 'CARD_DECLINED']]]);
        $this->post('/billing/1', ['refill-amount' => '25'])->assertSessionHas('error', '[{"code":"CARD_DECLINED"}]');

        $this->assertCount(1, $this->square['createPayment']);

        $this->fakeSquare();
        $this->post('/billing/1', ['refill-amount' => '0.50'])->assertSessionHas('error', 'Enter a refill amount of at least $1.00.');
        $this->assertCount(0, $this->square['createPayment']);
        $this->assertSame(0, DB::table('pharmacy_payments')->count());
        $this->assertEqualsWithDelta(-50.0, (float) DB::table('pharmacys')->where('id', 1)->value('balance'), 0.001);
    }
}
