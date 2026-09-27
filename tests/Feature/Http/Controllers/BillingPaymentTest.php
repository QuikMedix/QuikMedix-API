<?php

namespace Tests\Feature\Http\Controllers;

use App\Support\Money;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

/**
 * The paths here never reach Square: they pay from the pharmacy balance or are refused before charging.
 */
class BillingPaymentTest extends TestCase
{
    use CreatesAuthenticationSchema;

    private User $pharmacyAdmin;

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
            $table->decimal('balance', 10, 2)->default(0);
            $table->string('copay_bill')->default('0');
            $table->string('balance_ban')->default('0');
        });
        Schema::create('invoices', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('pharmacy_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('corrections', 10, 2)->default(0);
            $table->decimal('copay', 10, 2)->default(0);
            $table->string('payed')->default('0');
            $table->date('date_from');
            $table->date('date_to');
        });
        Schema::create('pharmacy_payments', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('pharmacy_id');
            $table->integer('invoice_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('transaction_id')->nullable();
            $table->string('type');
        });
        Schema::create('payment_pharmacy_accounts', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('pharmacy_id');
            $table->string('type');
        });
        Schema::create('invoice_exclusion', function (Blueprint $table): void {
            $table->integer('invoice_id');
            $table->integer('order_id');
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('pharmacy_id');
            $table->integer('statuse_id');
            $table->dateTime('finish')->nullable();
            $table->string('invoice_payed')->default('0');
        });
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);

        $this->pharmacyAdmin = User::factory()->create(['role' => 'medic', 'pharmacy_id' => 2, 'isactive' => 1, 'isblocked' => 0]);
    }

    private function pharmacyWithUnpaidInvoice(float $balanceAfterInvoice, float $amount = 100): int
    {
        DB::table('pharmacys')->insert(['id' => 2, 'balance' => $balanceAfterInvoice]);

        return DB::table('invoices')->insertGetId(['pharmacy_id' => 2, 'amount' => $amount, 'date_from' => '2026-09-01', 'date_to' => '2026-09-07']);
    }

    public function test_paying_from_balance_marks_the_invoice_paid_without_debiting_the_balance_again(): void
    {
        // $50 before a $100 invoice (now -$50), then a $200 refill: $150 left covers the invoice.
        $invoiceId = $this->pharmacyWithUnpaidInvoice(150);

        $this->actingAs($this->pharmacyAdmin)->post('/billing/2', ['pay' => 1, 'invoice_id' => $invoiceId])->assertRedirect('/billing/2');

        $this->assertDatabaseHas('invoices', ['id' => $invoiceId, 'payed' => '1']);
        $this->assertSame(150.0, (float) DB::table('pharmacys')->where('id', 2)->value('balance'));
        $this->assertSame(1, DB::table('pharmacy_payments')->where('invoice_id', $invoiceId)->count());
    }

    public function test_an_invoice_is_never_paid_twice(): void
    {
        $invoiceId = $this->pharmacyWithUnpaidInvoice(150);

        $this->actingAs($this->pharmacyAdmin)->post('/billing/2', ['pay' => 1, 'invoice_id' => $invoiceId]);
        $this->actingAs($this->pharmacyAdmin)->post('/billing/2', ['pay' => 1, 'invoice_id' => $invoiceId])
            ->assertSessionHas('error', 'This invoice is already paid.');

        $this->assertSame(1, DB::table('pharmacy_payments')->where('invoice_id', $invoiceId)->count());
    }

    public function test_a_payment_already_in_progress_blocks_a_second_one(): void
    {
        $invoiceId = $this->pharmacyWithUnpaidInvoice(150);
        $inProgress = Cache::lock("pay-invoice:2:$invoiceId", 120);
        $inProgress->get();

        $this->actingAs($this->pharmacyAdmin)->post('/billing/2', ['pay' => 1, 'invoice_id' => $invoiceId])
            ->assertSessionHas('error', 'This invoice is already being paid. Please wait a moment and refresh.');

        $this->assertDatabaseHas('invoices', ['id' => $invoiceId, 'payed' => '0']);
        $inProgress->release();
    }

    public function test_an_unpaid_balance_needs_a_payment_method_instead(): void
    {
        $invoiceId = $this->pharmacyWithUnpaidInvoice(-50);

        $this->actingAs($this->pharmacyAdmin)->post('/billing/2', ['pay' => 1, 'invoice_id' => $invoiceId])
            ->assertSessionHas('error', 'PAYMENT METHOD IS EMPTY! PLEASE, ADD YOUR PAYMENT METHOD.');

        $this->assertDatabaseHas('invoices', ['id' => $invoiceId, 'payed' => '0']);
        $this->assertSame(-50.0, (float) DB::table('pharmacys')->where('id', 2)->value('balance'));
    }

    public function test_refills_must_be_a_positive_amount(): void
    {
        DB::table('pharmacys')->insert(['id' => 2, 'balance' => 0]);
        DB::table('payment_pharmacy_accounts')->insert(['pharmacy_id' => 2, 'type' => 'card']);

        foreach (['-50', 'abc', '0.50'] as $amount) {
            $this->actingAs($this->pharmacyAdmin)->post('/billing/2', ['refill-amount' => $amount])
                ->assertSessionHas('error', 'Enter a refill amount of at least $1.00.');
        }
        $this->assertSame(0, DB::table('pharmacy_payments')->count());
    }

    public function test_dollar_amounts_become_exact_cents(): void
    {
        $this->assertSame(29, Money::cents(0.29));
        $this->assertSame(1015, Money::cents('10.15'));
        $this->assertSame(100, Money::cents(1));
    }
}
