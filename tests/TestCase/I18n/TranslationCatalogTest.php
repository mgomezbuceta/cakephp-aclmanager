<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\I18n;

use Cake\TestSuite\TestCase;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Keeps the source code and the three translation catalogs in sync.
 */
class TranslationCatalogTest extends TestCase
{
    private const LOCALES = ['en_US', 'es_ES', 'gl_ES'];

    /**
     * msgid => msgstr of one catalog (header entry excluded).
     *
     * @return array<string, string>
     */
    private function catalog(string $locale): array
    {
        $path = ROOT . DS . 'resources' . DS . 'locales' . DS . $locale . DS . 'acl_manager.po';
        $entries = [];
        $id = null;
        $target = null;
        foreach ((array)file($path, FILE_IGNORE_NEW_LINES) as $line) {
            $line = (string)$line;
            if (preg_match('/^(msgid|msgstr) "(.*)"$/', $line, $m)) {
                $target = $m[1];
                $text = stripcslashes($m[2]);
                if ($target === 'msgid') {
                    $id = $text;
                    $entries[$id] = '';
                } elseif ($id !== null) {
                    $entries[$id] = $text;
                }
            } elseif (preg_match('/^"(.*)"$/', $line, $m) && $id !== null) {
                if ($target === 'msgid') {
                    unset($entries[$id]);
                    $id .= stripcslashes($m[1]);
                    $entries[$id] = '';
                } else {
                    $entries[$id] .= stripcslashes($m[1]);
                }
            }
        }
        unset($entries['']);

        return $entries;
    }

    /**
     * Message ids passed to __d('acl_manager', ...) in the plugin code and templates.
     *
     * @return array{ids: array<int, string>, calls: int}
     */
    private function usedMessages(): array
    {
        $ids = [];
        $calls = 0;
        $files = [];
        foreach (['src', 'templates'] as $dir) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ROOT . DS . $dir, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }
        foreach ($files as $path) {
            $code = (string)file_get_contents($path);
            $calls += preg_match_all('/\b__d[a-z]*\(/', $code);
            $literal = "'(?:[^'\\\\\\\\]|\\\\\\\\.)*'";
            preg_match_all("/\\b__d\\(\\s*'acl_manager'\\s*,\\s*({$literal}(?:\\s*\\.\\s*{$literal})*)/s", $code, $matches);
            foreach ($matches[1] as $expression) {
                preg_match_all("/{$literal}/", $expression, $parts);
                $text = '';
                foreach ($parts[0] as $part) {
                    $text .= str_replace(["\\'", '\\\\'], ["'", '\\'], substr($part, 1, -1));
                }
                $ids[] = $text;
            }
        }

        return ['ids' => $ids, 'calls' => $calls];
    }

    public function testEveryTranslationCallIsAnAnalyzableAclManagerLiteral(): void
    {
        $used = $this->usedMessages();

        $this->assertNotEmpty($used['ids']);
        $this->assertSame($used['calls'], count($used['ids']), 'a __d() call is not a plain acl_manager literal');
    }

    public function testEveryUsedMessageExistsInEveryCatalog(): void
    {
        $used = array_unique($this->usedMessages()['ids']);

        foreach (self::LOCALES as $locale) {
            $this->assertSame([], array_values(array_diff($used, array_keys($this->catalog($locale)))), $locale);
        }
    }

    public function testNoCatalogEntryIsStale(): void
    {
        $used = array_unique($this->usedMessages()['ids']);

        foreach (self::LOCALES as $locale) {
            $this->assertSame([], array_values(array_diff(array_keys($this->catalog($locale)), $used)), $locale);
        }
    }

    public function testCatalogsContainTheSameMessages(): void
    {
        $reference = array_keys($this->catalog('en_US'));
        sort($reference);

        foreach (['es_ES', 'gl_ES'] as $locale) {
            $ids = array_keys($this->catalog($locale));
            sort($ids);
            $this->assertSame($reference, $ids, $locale);
        }
    }

    public function testSpanishAndGalicianTranslateEveryMessage(): void
    {
        foreach (['es_ES', 'gl_ES'] as $locale) {
            $empty = array_keys(array_filter($this->catalog($locale), fn(string $t): bool => trim($t) === ''));
            $this->assertSame([], $empty, $locale);
        }
    }

    public function testTranslationsKeepTheMessagePlaceholders(): void
    {
        foreach (self::LOCALES as $locale) {
            foreach ($this->catalog($locale) as $id => $text) {
                if ($text === '') {
                    continue;
                }
                preg_match_all('/\{\d+\}/', $id, $expected);
                preg_match_all('/\{\d+\}/', $text, $actual);
                sort($expected[0]);
                sort($actual[0]);
                $this->assertSame($expected[0], $actual[0], "$locale: $id");
            }
        }
    }
}
