<?php

namespace Tests\Feature\Http\Controllers;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\Support\StubsDashboardLayout;
use Tests\TestCase;

class FaqControllerTest extends TestCase
{
    use CreatesAuthenticationSchema;
    use StubsDashboardLayout;

    private const BLOCKED_MESSAGE = "We're sorry for the inconvenience, but it seems we have a little hiccup here. Our team is already on the case, working hard to get things back on track as quickly as possible.";

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
        });
        Schema::create('faqs', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('title');
            $table->text('text');
        });
        $this->stubDashboardLayout();
    }

    private function account(string $role, array $attributes = []): User
    {
        return User::factory()->create($attributes + ['role' => $role, 'isactive' => 1, 'isblocked' => 0]);
    }

    private function actingAsAdmin(): User
    {
        $admin = $this->account('admin');
        $this->actingAs($admin)->withSession(['user_2fa' => $admin->id]);

        return $admin;
    }

    private function createFaqs(int $count): void
    {
        foreach (range(1, $count) as $number) {
            DB::table('faqs')->insert(['user_id' => 1, 'title' => "Question $number", 'text' => "<p>Answer $number</p>"]);
        }
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/faq')->assertRedirectToRoute('login');
    }

    public function test_any_signed_in_user_reads_the_faq_twenty_per_page(): void
    {
        $this->createFaqs(45);

        $response = $this->actingAs($this->account('medic'))->get('/faq')->assertOk()->assertViewIs('faq.index');

        $response->assertViewHas('title', 'Faqs')->assertViewHas('br1', 'FAQ')->assertViewHas('br2', 'List');
        $this->assertSame(range(1, 20), $response->viewData('faqs')->pluck('id')->all());
        $response->assertSee('Question 20')->assertDontSee('Question 21')->assertSee('<p>Answer 1</p>', false)
            ->assertDontSee('Add FAQ');

        $this->assertSame(range(41, 45), $this->get('/faq?page=3')->viewData('faqs')->pluck('id')->all());
    }

    /**
     * @return array<string, array{int, string, list<array{id: int, class: string}>}>
     */
    public static function pageLinks(): array
    {
        $link = fn (int $id, bool $current = false): array => ['id' => $id, 'class' => $current ? 'btn-outline-primary' : 'btn-primary'];

        return [
            'first of three' => [45, '', [$link(1, true), $link(2), $link(3)]],
            'middle of five' => [100, '?page=3', [$link(1), $link(2), $link(3, true), $link(4), $link(5)]],
            'last of three' => [45, '?page=3', [$link(1), $link(2), $link(3, true)]],
            'only page' => [3, '?page=1', [$link(1, true)]],
            'no questions' => [0, '', [$link(1, true)]],
            'beyond the last page keeps earlier links' => [45, '?page=9', [$link(7), $link(8), $link(9, true)]],
            'page zero is page one' => [45, '?page=0', [$link(1, true), $link(2), $link(3)]],
        ];
    }

    #[DataProvider('pageLinks')]
    public function test_page_links_surround_the_current_page(int $count, string $query, array $expected): void
    {
        if ($count > 0) {
            $this->createFaqs($count);
        }

        $this->actingAs($this->account('medic'))->get('/faq'.$query)->assertOk()->assertViewHas('pages', $expected);
    }

    public function test_dashboard_ajax_navigation_receives_the_page_sections(): void
    {
        $this->createFaqs(1);

        $sections = $this->actingAs($this->account('medic'))->get('/faq?ajax')->assertOk()->json();

        $this->assertSame(['title', 'headerCss', 'content', 'footerScript'], array_keys($sections));
        $this->assertStringContainsString('Question 1', $sections['content']);
    }

    public function test_admin_sees_management_controls_and_the_add_form(): void
    {
        $this->createFaqs(1);
        $this->actingAsAdmin();

        $this->get('/faq')->assertOk()->assertSee('Add FAQ')->assertSee('Remove');
        $this->get('/faq/add')->assertOk()->assertViewIs('faq.add')
            ->assertViewHas('input', ['title' => '', 'text' => ''])->assertViewHas('alert', '')
            ->assertViewHas('title', 'FAQ Add')->assertViewHas('br3', 'FAQ Add');
        $this->assertSame(['title', 'headerCss', 'content', 'footerScript'], array_keys($this->get('/faq/add?ajax')->assertOk()->json()));
    }

    public function test_admin_adds_a_question(): void
    {
        $admin = $this->actingAsAdmin();

        $this->post('/faq/add', ['save' => 1, 'title' => 'How do I print labels?', 'text' => '<p>Open the order.</p>'])
            ->assertRedirect('/faq');

        $this->assertDatabaseHas('faqs', ['user_id' => $admin->id, 'title' => 'How do I print labels?', 'text' => '<p>Open the order.</p>']);
    }

    public function test_incomplete_question_is_not_saved(): void
    {
        $this->actingAsAdmin();

        $this->post('/faq/add', ['save' => 1, 'title' => '', 'text' => '<p>Answer</p>'])->assertRedirect('/faq');
        $this->post('/faq/add', ['save' => 1, 'title' => 'Question', 'text' => ''])->assertRedirect('/faq');
        $this->post('/faq/add', ['title' => 'Question', 'text' => '<p>Answer</p>'])->assertRedirect('/faq');

        $this->assertDatabaseCount('faqs', 0);
    }

    public function test_admin_removes_a_question(): void
    {
        $this->createFaqs(2);
        $this->actingAsAdmin();

        $this->post('/faq', ['remove' => 1, 'faq_id' => 1])->assertRedirect('/faq');
        $this->post('/faq', ['faq_id' => 2])->assertRedirect('/faq');

        $this->assertDatabaseMissing('faqs', ['id' => 1]);
        $this->assertDatabaseHas('faqs', ['id' => 2]);
    }

    public function test_only_admin_staff_manage_the_faq(): void
    {
        $this->createFaqs(1);
        $this->actingAs($this->account('medic'));

        $this->get('/faq/add')->assertForbidden()->assertSee('Permision error.');
        $this->post('/faq/add', ['save' => 1, 'title' => 'Question', 'text' => 'Answer'])->assertForbidden();
        $this->post('/faq', ['remove' => 1, 'faq_id' => 1])->assertForbidden();

        $this->assertDatabaseCount('faqs', 1);
    }

    public function test_blocked_admin_cannot_manage_but_the_list_stays_readable(): void
    {
        $this->createFaqs(1);
        $blocked = $this->account('superadmin', ['isblocked' => 1]);
        $this->actingAs($blocked);

        $this->get('/faq/add')->assertForbidden()->assertSee(self::BLOCKED_MESSAGE);
        $this->post('/faq/add', ['save' => 1, 'title' => 'Question', 'text' => 'Answer'])->assertForbidden();
        $this->post('/faq', ['remove' => 1, 'faq_id' => 1])->assertForbidden();
        $this->get('/faq')->assertOk();

        $this->assertDatabaseCount('faqs', 1);
    }

    public function test_admin_without_second_factor_is_sent_to_verification(): void
    {
        Schema::create('white_list_ip', function (Blueprint $table): void {
            $table->string('ip');
        });

        $this->actingAs($this->account('admin'))->get('/faq')->assertRedirectToRoute('2fa.index');
    }
}
