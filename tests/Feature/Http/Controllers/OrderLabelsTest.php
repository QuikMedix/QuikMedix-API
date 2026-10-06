<?php

namespace Tests\Feature\Http\Controllers;

use App\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class OrderLabelsTest extends TestCase
{
    use CreatesAuthenticationSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
            $table->integer('pharmacy_id')->nullable();
            foreach (['last_name', 'address', 'zip', 'apartment'] as $column) {
                $table->string($column)->nullable();
            }
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('pharmacy_id');
            $table->integer('count_bags')->nullable();
            $table->integer('statuse_id')->default(1);
            $table->integer('fridge')->default(0);
            $table->integer('signature')->default(0);
        });
        Schema::create('rxs', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('order_id');
            $table->string('rx_id');
        });
        Schema::create('wishes', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('category_id');
            $table->string('text');
        });
        Schema::create('wishes_category', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('status');
        });
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
    }

    public static function bags(): array
    {
        return [['/orders/ticket/print?order_id=20', null, 1], ['/orders/ticket/print?order_id=20', 0, 1], ['/orders/ticket/print?order_id=20', 3, 3], ['/orders/ticket/print?order_id=20&print=1', 2, 2], ['/orders/tickets/print', null, 1]];
    }

    #[DataProvider('bags')]
    public function test_labels_contain_one_distinct_embedded_qr_per_bag_including_legacy_orders(string $path, ?int $bags, int $expectedCount): void
    {
        DB::table('pharmacys')->insert(['id' => 2, 'name' => 'Test Pharmacy']);
        $patient = User::factory()->create(['role' => 'user', 'pharmacy_id' => 2]);
        DB::table('orders')->insert(['id' => 20, 'pharmacy_id' => 2, 'user_id' => $patient->id, 'count_bags' => $bags]);
        $staff = User::factory()->create(['role' => 'medic', 'pharmacy_id' => 2, 'isactive' => 1, 'isblocked' => 0]);

        $response = $this->actingAs($staff)->get($path)->assertOk();

        preg_match_all('/src="data:image\/png;base64,([^"]+)" alt="qrcode"/', $response->getContent(), $matches);
        $this->assertCount($expectedCount, array_unique($matches[1]));
        foreach ($matches[1] as $png) {
            $this->assertSame('image/png', getimagesizefromstring(base64_decode($png, true))['mime']);
        }
        for ($bag = 1; $bag <= $expectedCount; $bag++) {
            $response->assertSee('PACKAGE '.$bag.' OF '.$expectedCount);
        }
    }

    public function test_ticket_requires_authentication_and_rejects_missing_orders(): void
    {
        $this->get('/orders/ticket/print?order_id=20')->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->create(['role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0]))
            ->get('/orders/ticket/print?order_id=999')->assertNotFound();
    }

    public function test_pdf_ticket_renders_a_qr_when_legacy_bag_count_is_missing(): void
    {
        $html = view('orders.ticket_pdf', [
            'order' => (object) ['id' => 20, 'count_bags' => null, 'rxs' => [], 'fridge' => 0, 'signature' => 0],
            'pharmacy' => (object) ['name' => 'Test Pharmacy', 'phone' => '5550000100'],
            'patient' => (object) ['name' => 'Test', 'last_name' => 'Patient', 'address' => '12 Test St', 'zip' => '00001', 'apartment' => ''],
            'wish' => (object) ['text' => 'Test'],
        ])->render();

        $this->assertStringContainsString('alt="qrcode"', $html);
        $this->assertStringContainsString('PACKAGE 1 OF 1', $html);
    }

    public static function labelPaths(): array
    {
        return [
            'individual fragment' => ['/orders/ticket/print?order_id=20'],
            'individual print page' => ['/orders/ticket/print?order_id=20&print=1'],
            'batch print page' => ['/orders/tickets/print'],
        ];
    }

    #[DataProvider('labelPaths')]
    public function test_labels_show_delivery_details_and_omit_pharmacy_details(string $path): void
    {
        DB::table('pharmacys')->insert(['id' => 2, 'name' => 'Private Pharmacy Name', 'phone' => '5550000100']);
        $patient = User::factory()->create([
            'role' => 'user', 'pharmacy_id' => 2, 'name' => 'Alex', 'last_name' => 'Sample',
            'address' => '123 Example Street', 'apartment' => '4B', 'zip' => '11201',
        ]);
        DB::table('orders')->insert(['id' => 20, 'pharmacy_id' => 2, 'user_id' => $patient->id, 'count_bags' => 2]);
        $staff = User::factory()->create(['role' => 'medic', 'pharmacy_id' => 2, 'isactive' => 1, 'isblocked' => 0]);

        $response = $this->actingAs($staff)->get($path);

        $response->assertSee('DELIVER TO')->assertSee('Alex Sample')->assertSee('123 Example Street, Apt 4B, 11201')
            ->assertSee('QM-000020')->assertSee('PACKAGE 1 OF 2')->assertSee('PACKAGE 2 OF 2')
            ->assertDontSee('Private Pharmacy Name')->assertDontSee('5550000100')
            ->assertDontSee($staff->name)->assertDontSee('RX qty:')->assertDontSee('Have a Nice Day!');
    }

    public static function handlingWarnings(): array
    {
        return [
            'standard delivery' => [0, 0, 1, false, false],
            'refrigerated delivery with signature' => [1, 1, 3, true, true],
            'other signature delivery method' => [0, 1, 4, false, true],
            'signature flag on unsupported method' => [0, 1, 1, false, false],
        ];
    }

    #[DataProvider('handlingWarnings')]
    public function test_handling_warnings_remain_on_the_first_package(int $fridge, int $signature, int $deliveryMethod, bool $expectFridge, bool $expectSignature): void
    {
        $html = view('orders.label', [
            'order' => (object) ['id' => 20, 'count_bags' => 2, 'fridge' => $fridge, 'signature' => $signature, 'delivery_method_id' => $deliveryMethod],
            'patient' => (object) ['name' => 'Alex', 'last_name' => 'Sample', 'address' => '123 Example Street'],
        ])->render();

        $this->assertSame($expectFridge ? 1 : 0, substr_count($html, 'KEEP REFRIGERATED'));
        $this->assertSame($expectSignature ? 1 : 0, substr_count($html, 'SIGNATURE REQUIRED'));
        $secondPackage = substr($html, strpos($html, 'aria-label="Order QM-000020, package 2 of 2"'));
        $this->assertStringNotContainsString('KEEP REFRIGERATED', $secondPackage);
        $this->assertStringNotContainsString('SIGNATURE REQUIRED', $secondPackage);
    }

    public function test_recipient_details_are_escaped_in_labels(): void
    {
        $html = view('orders.label', [
            'order' => (object) ['id' => 20, 'count_bags' => 1, 'fridge' => 0, 'signature' => 0],
            'patient' => (object) ['name' => '<script>Name</script>', 'last_name' => 'Sample', 'address' => '<script>Address</script>', 'apartment' => '<script>Apt</script>', 'zip' => '<script>Zip</script>'],
        ])->render();

        foreach (['Name', 'Address', 'Apt', 'Zip'] as $field) {
            $this->assertStringContainsString('&lt;script&gt;'.$field.'&lt;/script&gt;', $html);
        }
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_pdf_labels_have_one_compact_page_per_package(): void
    {
        $pdf = Pdf::loadView('orders.ticket_pdf', [
            'order' => (object) ['id' => 20, 'count_bags' => 2, 'fridge' => 1, 'signature' => 1, 'delivery_method_id' => 3],
            'patient' => (object) ['name' => 'Alex', 'last_name' => 'Sample', 'address' => '123 Example Street', 'apartment' => '4B', 'zip' => '11201'],
        ]);

        $pdf->render();
        $canvas = $pdf->getDomPDF()->getCanvas();

        $this->assertSame(2, $canvas->get_page_count());
        $this->assertSame(229.5, $canvas->get_width());
        $this->assertSame(304.5, $canvas->get_height());
    }
}
