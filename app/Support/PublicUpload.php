<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use WeakMap;

/**
 * Safe filenames for uploads saved under public/: never the client's name, and never a type
 * the web server or browser would execute or render as a page.
 */
final class PublicUpload
{
    /** @var list<string> */
    private const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps', 'pht',
        'html', 'htm', 'xhtml', 'shtml', 'svg', 'svgz', 'js', 'mjs', 'xml', 'xsl',
        'cgi', 'pl', 'py', 'sh', 'asp', 'aspx', 'jsp', 'exe',
    ];

    /** @var WeakMap<UploadedFile, string>|null */
    private static ?WeakMap $names = null;

    /**
     * A random filename with the extension guessed from the file's content. The same upload
     * gets the same name for the rest of the request, so a move() and its stored path agree.
     *
     * @throws ValidationException when the content is an executable or renderable type
     */
    public static function name(UploadedFile $file): string
    {
        self::$names ??= new WeakMap;

        return self::$names[$file] ??= self::generate($file);
    }

    private static function generate(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->guessExtension());

        if ($extension === '' || in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            throw ValidationException::withMessages(['file' => 'This type of file cannot be uploaded.']);
        }

        return Str::random(40).'.'.$extension;
    }
}
