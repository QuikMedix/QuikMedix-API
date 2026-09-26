<?php

namespace App\Http\Controllers\Concerns;

use App\Support\PublicUpload;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

/**
 * Conventions the existing dashboard views rely on: AJAX section loading, page links and public uploads.
 */
trait RendersLegacyPages
{
    /**
     * Pages are also loaded into the dashboard over AJAX, which wants only the sections.
     */
    protected function page(Request $request, View $view): View|array
    {
        return $request->query->has('ajax') ? $view->renderSections() : $view;
    }

    /**
     * Up to two pages either side of the current one, in the shape the list views expect.
     *
     * @return list<array{id: int, class: string}>
     */
    protected function pageLinks(int $page, int $maxPages): array
    {
        $links = [];
        foreach (range($page - 2, $page + 2) as $number) {
            if ($number === $page || ($number >= 1 && $number <= $maxPages)) {
                $links[] = ['id' => $number, 'class' => $number === $page ? 'btn-outline-primary' : 'btn-primary'];
            }
        }

        return $links;
    }

    /**
     * Stores an uploaded image under public/images/{directory} and returns its public path.
     * The name is random with an extension guessed from the content, never the client's filename.
     */
    protected function storeUpload(?UploadedFile $file, string $directory): ?string
    {
        if ($file === null) {
            return null;
        }
        $name = PublicUpload::name($file);
        $file->move(public_path('images/'.$directory), $name);

        return '/images/'.$directory.'/'.$name;
    }
}
