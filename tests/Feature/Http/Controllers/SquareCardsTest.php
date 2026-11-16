<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\Support\FakesSquare;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * Saving a card for a pharmacy (web) and for a patient (mobile API), and paying a copay with the
 * saved card, with a stand-in for Square. Saving a card makes a customer, charges one dollar to
 * prove the card, stores the card and refunds the dollar.
 */
class SquareCardsTest extends TestCase
{
    use FakesSquare;
    use InsertsRows;

    private const FORM = ['type' => 'card', 'name' => 'Pat Payer', 'address' => '1 Main St, Apt 2, Brooklyn', 'zip' => '11201', 'card' => 'cnon:token'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        config(['app.SQUARE_LOCATION_ID' => 'LOC-1']);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        $this->row('users', ['id' => 1, 'email' => 'root@example.test', 'role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 2, 'email' => 'pat@example.test', 'phone' => '(555) 010-0002', 'role' => 'user', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('pharmacys', ['id' => 1, 'name' => 'Corner Pharmacy', 'email' => 'corner@example.test', 'phone' => '(555) 010-0001', 'isactive' => 1, 'isblocked' => 0]);
    }

    private function copayOrder(): void
    {
        $this->row('statuses', ['id' => 2]);
        $this->row('delivery_methods', ['id' => 1]);
        $this->row('delivery_times', ['id' => 1]);
        $this->row('orders', ['id' => 5, 'pharmacy_id' => 1, 'user_id' => 2, 'driver_id' => 1, 'statuse_id' => 2, 'delivery_method_id' => 1, 'delivery_time_id' => 1, 'copay' => 12.29, 'statuse_copay' => 2]);
    }

    private function asAdmin(): static
    {
        return $this->actingAs(User::findOrFail(1));
    }

    private function asPatient(): static
    {
        return $this->actingAs(User::findOrFail(2), 'api');
    }

    public function test_saving_a_pharmacy_card_makes_a_customer_proves_the_card_with_a_dollar_and_refunds_it(): void
    {
        $this->fakeSquare();

        $this->asAdmin()->post('/payment-method/1', self::FORM)->assertOk()->assertViewHas('success', 'Payment Method successfully added!')->assertViewHas('error', '');

        $customer = $this->square['createCustomer'][0];
        $this->assertSame(['Pat', 'Payer', 'corner@example.test', '5550100001', '1', 'Pharmacy #1'], [$customer->getGivenName(), $customer->getFamilyName(), $customer->getEmailAddress(), $customer->getPhoneNumber(), $customer->getReferenceId(), $customer->getNote()]);
        $address = $customer->getAddress();
        $this->assertSame(['1 Main St', ' Apt 2', ' Brooklyn', 'NY', '11201', 'US'], [$address->getAddressLine1(), $address->getAddressLine2(), $address->getLocality(), $address->getAdministrativeDistrictLevel1(), $address->getPostalCode(), $address->getCountry()]);
        $charge = $this->square['createPayment'][0];
        $this->assertSame(['cnon:token', 'cust_new', 100, 'USD', 'LOC-1', 'Authorization Card'], [$charge->getSourceId(), $charge->getCustomerId(), $charge->getAmountMoney()->getAmount(), $charge->getAmountMoney()->getCurrency(), $charge->getLocationId(), $charge->getNote()]);
        $card = $this->square['createCard'][0];
        $this->assertSame(['pay_123', 'Pat Payer', 'cust_new'], [$card->getSourceId(), $card->getCard()->getCardholderName(), $card->getCard()->getCustomerId()]);
        $refund = $this->square['refundPayment'][0];
        $this->assertSame(['pay_123', 100], [$refund->getPaymentId(), $refund->getAmountMoney()->getAmount()]);
        foreach (['createCustomer', 'createPayment', 'createCard', 'refundPayment'] as $call) {
            $this->assertCount(1, $this->square[$call], $call);
        }
        $this->assertSame(
            ['pharmacy_id' => 1, 'type' => 'card', 'profile_id' => 'cust_new', 'payment_id' => 'pay_123', 'payment_profile_id' => 'card_new', 'card' => '4242'],
            (array) DB::table('payment_pharmacy_accounts')->first(['pharmacy_id', 'type', 'profile_id', 'payment_id', 'payment_profile_id', 'card'])
        );
    }

    public function test_a_pharmacy_that_already_has_a_square_customer_keeps_it(): void
    {
        $this->row('payment_pharmacy_accounts', ['pharmacy_id' => 1, 'type' => 'bank', 'profile_id' => 'cust_old']);
        $this->fakeSquare();

        $this->asAdmin()->post('/payment-method/1', self::FORM)->assertViewHas('success', 'Payment Method successfully added!');

        $this->assertCount(0, $this->square['createCustomer']);
        $this->assertSame('cust_old', $this->square['createPayment'][0]->getCustomerId());
        $this->assertSame(['card', 'cust_old', 'card_new'], array_values((array) DB::table('payment_pharmacy_accounts')->first(['type', 'profile_id', 'payment_profile_id'])));
        $this->assertSame(1, DB::table('payment_pharmacy_accounts')->count());
    }

    public function test_switching_a_pharmacy_to_a_bank_account_only_needs_the_customer(): void
    {
        $this->fakeSquare();

        $this->asAdmin()->post('/payment-method/1', ['type' => 'bank'] + self::FORM)->assertOk()->assertViewHas('error', '');

        $this->assertCount(1, $this->square['createCustomer']);
        $this->assertCount(0, $this->square['createPayment']);
        $this->assertSame(['bank', 'cust_new', null], array_values((array) DB::table('payment_pharmacy_accounts')->first(['type', 'profile_id', 'payment_profile_id'])));
    }

    public function test_each_refusal_while_saving_a_pharmacy_card_stops_there_and_is_reported(): void
    {
        $declined = [['code' => 'DECLINED']];
        $cases = [
            'incomplete form' => [[], ['name' => ''], 'All Payment Method input is required.', [0, 0, 0, 0], null],
            'no card token' => [[], ['card' => ''], 'All Payment Method input is required.', [0, 0, 0, 0], null],
            'customer refused' => [['createCustomer' => $declined], [], '[{"code":"DECLINED"}]', [1, 0, 0, 0], null],
            'charge refused' => [['createPayment' => $declined], [], '[{"code":"DECLINED"}]', [1, 1, 0, 0], ['cust_new', null, null]],
            'charge not completed' => [['createPayment' => 'FAILED'], [], 'Payment not completed!', [1, 1, 0, 0], ['cust_new', null, null]],
            'card refused' => [['createCard' => $declined], [], '[{"code":"DECLINED"}]', [1, 1, 1, 0], ['cust_new', 'pay_123', null]],
            'refund refused' => [['refundPayment' => $declined], [], 'Payment Method successfully added, but refund payment not completed. [{"code":"DECLINED"}]', [1, 1, 1, 1], ['cust_new', 'pay_123', 'card_new']],
        ];
        foreach ($cases as $name => [$script, $form, $error, $calls, $stored]) {
            DB::table('payment_pharmacy_accounts')->delete();
            $this->fakeSquare($script);

            $this->asAdmin()->post('/payment-method/1', $form + self::FORM)->assertOk()->assertViewHas('error', $error)->assertViewHas('success', '');

            $this->assertSame($calls, array_map('count', [$this->square['createCustomer'], $this->square['createPayment'], $this->square['createCard'], $this->square['refundPayment']]), $name);
            $row = DB::table('payment_pharmacy_accounts')->first(['profile_id', 'payment_id', 'payment_profile_id']);
            $this->assertSame($stored, $row === null ? null : array_values((array) $row), $name);
        }
    }

    public function test_a_pending_dollar_charge_is_enough_to_save_a_pharmacy_card(): void
    {
        $this->fakeSquare(['createPayment' => 'PENDING']);

        $this->asAdmin()->post('/payment-method/1', self::FORM)->assertViewHas('success', 'Payment Method successfully added!');

        $this->assertSame('card_new', DB::table('payment_pharmacy_accounts')->value('payment_profile_id'));
    }

    public function test_a_patient_saves_a_card_from_the_app(): void
    {
        $this->fakeSquare();

        $this->asPatient()->postJson('/api/cards/add', self::FORM)->assertOk()->assertExactJson(['message' => 'Payment Method successfully added!']);

        $customer = $this->square['createCustomer'][0];
        $this->assertSame(['Pat', 'Payer', 'pat@example.test', '5550100002', '2', 'User #2'], [$customer->getGivenName(), $customer->getFamilyName(), $customer->getEmailAddress(), $customer->getPhoneNumber(), $customer->getReferenceId(), $customer->getNote()]);
        $this->assertSame(['cnon:token', 'cust_new', 100, 'Authorization Card'], [$this->square['createPayment'][0]->getSourceId(), $this->square['createPayment'][0]->getCustomerId(), $this->square['createPayment'][0]->getAmountMoney()->getAmount(), $this->square['createPayment'][0]->getNote()]);
        $this->assertSame(['pay_123', 100], [$this->square['refundPayment'][0]->getPaymentId(), $this->square['refundPayment'][0]->getAmountMoney()->getAmount()]);
        $this->assertSame(
            ['user_id' => 2, 'type' => 'card', 'profile_id' => 'cust_new', 'payment_id' => 'pay_123', 'payment_profile_id' => 'card_new', 'card' => '4242'],
            (array) DB::table('payment_accounts')->first(['user_id', 'type', 'profile_id', 'payment_id', 'payment_profile_id', 'card'])
        );
    }

    public function test_each_refusal_while_a_patient_saves_a_card_is_a_400_with_the_reason(): void
    {
        $declined = [['code' => 'DECLINED']];
        $cases = [
            'incomplete form' => [[], ['zip' => ''], 'All card input is required.', null],
            'no card token' => [[], ['card' => ''], 'All card input is required.', null],
            'customer refused' => [['createCustomer' => $declined], [], '[{"code":"DECLINED"}]', null],
            'charge refused' => [['createPayment' => $declined], [], '[{"code":"DECLINED"}]', ['cust_new', null, null]],
            'charge not completed' => [['createPayment' => 'CANCELED'], [], 'Payment not completed!', ['cust_new', null, null]],
            'card refused' => [['createCard' => $declined], [], '[{"code":"DECLINED"}]', ['cust_new', 'pay_123', null]],
            'refund refused' => [['refundPayment' => $declined], [], 'Payment Method successfully added, but refund payment not completed. [{"code":"DECLINED"}]', ['cust_new', 'pay_123', 'card_new']],
        ];
        foreach ($cases as $name => [$script, $form, $message, $stored]) {
            DB::table('payment_accounts')->delete();
            $this->fakeSquare($script);

            $this->asPatient()->postJson('/api/cards/add', $form + self::FORM)->assertStatus(400)->assertExactJson(['message' => $message, 'errors' => 'Bad Request']);

            $row = DB::table('payment_accounts')->first(['profile_id', 'payment_id', 'payment_profile_id']);
            $this->assertSame($stored, $row === null ? null : array_values((array) $row), $name);
        }
    }

    public function test_a_patient_pays_a_copay_with_the_saved_card(): void
    {
        $this->row('payment_accounts', ['user_id' => 2, 'type' => 'card', 'profile_id' => 'cust_2', 'payment_profile_id' => 'card_2']);
        $this->copayOrder();
        $this->fakeSquare();

        $this->asPatient()->postJson('/api/orders/copay/pay/5')->assertOk()->assertExactJson(['message' => 'OK']);

        $charge = $this->square['createPayment'][0];
        $this->assertSame(['card_2', 'cust_2', 1229, 'USD', 'LOC-1', 'Pay Copay #5', true], [$charge->getSourceId(), $charge->getCustomerId(), $charge->getAmountMoney()->getAmount(), $charge->getAmountMoney()->getCurrency(), $charge->getLocationId(), $charge->getNote(), $charge->getAutocomplete()]);
        $this->assertCount(1, $this->square['createPayment']);
        $this->assertSame(3, (int) DB::table('orders')->where('id', 5)->value('statuse_copay'));
        $this->assertSame(['order_id' => 5, 'transaction_id' => 'pay_123', 'type' => 'copay'], (array) DB::table('payments')->first(['order_id', 'transaction_id', 'type']));

        // A copay that is paid is not charged a second time.
        $this->fakeSquare();
        $this->asPatient()->postJson('/api/orders/copay/pay/5')->assertForbidden();
        $this->assertCount(0, $this->square['createPayment']);
    }

    public function test_a_copay_stays_unpaid_when_square_refuses_or_does_not_complete_the_charge(): void
    {
        $this->row('payment_accounts', ['user_id' => 2, 'type' => 'card', 'profile_id' => 'cust_2', 'payment_profile_id' => 'card_2']);
        $this->copayOrder();

        $this->fakeSquare(['createPayment' => [['code' => 'DECLINED']]]);
        $this->asPatient()->postJson('/api/orders/copay/pay/5')->assertStatus(400)->assertExactJson(['message' => 'Transaction Failed: [{"code":"DECLINED"}]', 'errors' => 'Error']);

        // Pending is not enough for a copay: only a completed or approved charge marks it paid.
        $this->fakeSquare(['createPayment' => 'PENDING']);
        $this->asPatient()->postJson('/api/orders/copay/pay/5')->assertStatus(400)->assertExactJson(['message' => 'Payment not completed!', 'errors' => 'Error']);

        $this->assertSame(2, (int) DB::table('orders')->where('id', 5)->value('statuse_copay'));
        $this->assertSame(0, DB::table('payments')->count());
    }

    public function test_a_copay_cannot_be_paid_without_a_saved_card(): void
    {
        $this->copayOrder();
        $this->fakeSquare();

        $this->asPatient()->postJson('/api/orders/copay/pay/5')->assertForbidden()->assertJsonPath('message', 'Card not added');

        $this->assertCount(0, $this->square['createPayment']);
    }

    public function test_the_billing_report_shows_each_payments_status_at_square(): void
    {
        $this->row('pharmacy_payments', ['pharmacy_id' => 1, 'amount' => 20, 'transaction_id' => 'pay_abc', 'type' => 'refill']);
        $this->row('pharmacy_payments', ['pharmacy_id' => 1, 'amount' => 30, 'transaction_id' => 'pay_def', 'type' => 'refill']);
        $this->row('pharmacy_payments', ['pharmacy_id' => 1, 'amount' => 40, 'transaction_id' => 'balance', 'type' => 'pay']);
        $this->fakeSquare(['getPayment' => 'COMPLETED']);

        $payments = $this->asAdmin()->get('/reports/billing')->assertOk()->viewData('payments');

        // Newest first; the one paid from the balance is not looked up.
        $this->assertEqualsCanonicalizing(['pay_abc', 'pay_def'], $this->square['getPayment']);
        $this->assertSame([null, 'COMPLETED', 'COMPLETED'], $payments->map(fn ($payment) => $payment->status ?? null)->all());
    }
}
