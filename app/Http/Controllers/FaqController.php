<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\Support\PageLinks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The help page every signed-in user can read, and its management by admin staff.
 * Formerly the faq* actions of LexaAdmin.
 */
class FaqController extends Controller
{
    use RendersLegacyPages;

    private const PER_PAGE = 20;

    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware('active')->except('index');
    }

    public function index(Request $request): View|array
    {
        $page = empty($request->query('page')) ? 1 : intval($request->query('page'));
        $maxPages = ceil(DB::table('faqs')->count() / self::PER_PAGE);

        return $this->page($request, view('faq.index', [
            'faqs' => DB::table('faqs')->orderBy('id', 'asc')->offset(($page - 1) * self::PER_PAGE)->limit(self::PER_PAGE)->get(),
            'pages' => PageLinks::around($page, $maxPages),
            'title' => 'Faqs', 'br1' => 'FAQ', 'br2' => 'List',
        ]));
    }

    public function create(Request $request): View|array
    {
        $this->authorizeAdmin($request);

        return $this->page($request, view('faq.add', [
            'title' => 'FAQ Add', 'br1' => 'FAQ', 'br2' => 'FAQS', 'br3' => 'FAQ Add',
            'alert' => '',
            'input' => ['title' => '', 'text' => ''],
        ]));
    }

    /**
     * An incomplete question is dropped without a message, as the form always behaved.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        if ($request->input('save') > 0 && ! empty($request->input('title')) && ! empty($request->input('text'))) {
            DB::table('faqs')->insert([
                'user_id' => $request->user()->id,
                'title' => $request->input('title'),
                'text' => $request->input('text'),
            ]);
        }

        return redirect('faq');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        if ($request->input('remove') > 0 && ! empty($request->input('faq_id'))) {
            DB::table('faqs')->where('id', $request->input('faq_id'))->delete();
        }

        return redirect('faq');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->can('admin'), 403, LexaAdmin::$err_perm);
    }
}
