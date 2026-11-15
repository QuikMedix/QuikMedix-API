<?php

namespace Tests\Feature;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * A class used by its short name must be imported or live in the same namespace; otherwise PHP
 * looks for it in the file's own namespace and the page fails when that line runs.
 */
class ClassReferencesTest extends TestCase
{
    private const NOT_CLASSES = ['self', 'static', 'parent', 'array', 'string', 'int', 'bool', 'float', 'mixed', 'object', 'callable', 'iterable', 'null', 'void', 'true', 'false', 'never'];

    public function test_application_code_names_no_class_it_has_not_imported(): void
    {
        $unresolved = [];
        foreach (Finder::create()->files()->name('*.php')->in(app_path()) as $file) {
            foreach ($this->unresolvedClassNames($file->getContents()) as $name => $line) {
                $unresolved[] = $file->getRelativePathname().":$line $name";
            }
        }

        $this->assertSame([], $unresolved);
    }

    /**
     * @return array<string, int> short class name => line of a use that cannot be resolved
     */
    private function unresolvedClassNames(string $source): array
    {
        $namespace = preg_match('/^namespace ([^;]+);/m', $source, $match) ? $match[1] : '';
        preg_match_all('/^use ([^;]+);/m', $source, $uses);
        $imported = [];
        foreach ($uses[1] as $import) {
            $parts = preg_split('/\s+as\s+/', $import);
            // PHP matches an imported name without regard to case: `use ...\Pdf` covers PDF::loadView().
            $imported[strtolower(basename(str_replace('\\', '/', end($parts))))] = true;
        }

        $tokens = array_values(array_filter(token_get_all($source), fn ($token): bool => ! is_array($token) || $token[0] !== T_WHITESPACE));
        $unresolved = [];
        foreach ($tokens as $i => $token) {
            if (! is_array($token) || $token[0] !== T_STRING) {
                continue;
            }
            $before = is_array($tokens[$i - 1] ?? null) ? $tokens[$i - 1][0] : ($tokens[$i - 1] ?? null);
            $after = is_array($tokens[$i + 1] ?? null) ? $tokens[$i + 1][0] : ($tokens[$i + 1] ?? null);
            if (in_array($before, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_CONST, T_NS_SEPARATOR, T_CLASS, T_USE, T_NAMESPACE, T_AS, T_ENUM, T_TRAIT, T_INTERFACE], true)) {
                continue;
            }
            $isClassUse = $after === T_DOUBLE_COLON || $after === T_VARIABLE || in_array($before, [T_NEW, T_INSTANCEOF, T_EXTENDS, T_IMPLEMENTS], true);
            $name = $token[1];
            if (! $isClassUse || in_array(strtolower($name), self::NOT_CLASSES, true) || isset($imported[strtolower($name)])) {
                continue;
            }
            $local = $namespace.'\\'.$name;
            if (! class_exists($local) && ! interface_exists($local) && ! trait_exists($local) && ! enum_exists($local)) {
                $unresolved[$name] ??= $token[2];
            }
        }

        return $unresolved;
    }
}
