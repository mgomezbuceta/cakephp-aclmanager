<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Docs;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Keeps the English and Spanish guides structurally identical.
 */
class GuidesParityTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function pairs(): array
    {
        return [
            'README' => ['README.md', 'README.es.md'],
            'INSTALL' => ['INSTALL.md', 'INSTALL.es.md'],
        ];
    }

    private static function read(string $file): string
    {
        $path = dirname(__DIR__, 3) . '/' . $file;
        self::assertFileExists($path);

        return (string)file_get_contents($path);
    }

    /**
     * @return array{headings: array<int, array{0: int, 1: string}>, fences: array<int, string>, prose: string}
     */
    private static function parse(string $markdown): array
    {
        $headings = [];
        $fences = [];
        $prose = [];
        $inFence = false;
        foreach (explode("\n", $markdown) as $line) {
            if (preg_match('/^\s*```(\S*)/', $line, $m)) {
                if (!$inFence) {
                    $fences[] = $m[1];
                }
                $inFence = !$inFence;
                continue;
            }
            if ($inFence) {
                continue;
            }
            $prose[] = $line;
            if (preg_match('/^(#{1,4}) (.+)$/', $line, $m)) {
                $headings[] = [strlen($m[1]), trim($m[2])];
            }
        }

        return ['headings' => $headings, 'fences' => $fences, 'prose' => implode("\n", $prose)];
    }

    private static function slug(string $heading): string
    {
        $slug = mb_strtolower($heading);
        $slug = (string)preg_replace('/[^\p{L}\p{M}\p{N}_\- ]/u', '', $slug);

        return str_replace(' ', '-', $slug);
    }

    #[DataProvider('pairs')]
    public function testBothFilesExist(string $en, string $es): void
    {
        $this->assertFileExists(dirname(__DIR__, 3) . '/' . $en);
        $this->assertFileExists(dirname(__DIR__, 3) . '/' . $es);
    }

    #[DataProvider('pairs')]
    public function testSameHeadingCountPerLevel(string $en, string $es): void
    {
        $count = function (string $file): array {
            $levels = array_count_values(array_column(self::parse(self::read($file))['headings'], 0));
            ksort($levels);

            return $levels;
        };
        $this->assertSame($count($en), $count($es));
    }

    #[DataProvider('pairs')]
    public function testSameFencedBlocksAndLanguages(string $en, string $es): void
    {
        $this->assertSame(self::parse(self::read($en))['fences'], self::parse(self::read($es))['fences']);
    }

    #[DataProvider('pairs')]
    public function testSameConfigKeys(string $en, string $es): void
    {
        $keys = function (string $file): array {
            preg_match_all('/AclManager\.[A-Za-z]+(?:\.[A-Za-z]+)*/', self::read($file), $m);
            $found = array_values(array_unique($m[0]));
            sort($found);

            return $found;
        };
        $this->assertNotSame([], $keys($en));
        $this->assertSame($keys($en), $keys($es));
    }

    #[DataProvider('pairs')]
    public function testFilesLinkToTheirSibling(string $en, string $es): void
    {
        $this->assertStringContainsString("]({$es})", self::read($en));
        $this->assertStringContainsString("]({$en})", self::read($es));
    }

    #[DataProvider('pairs')]
    public function testInFileAnchorsResolve(string $en, string $es): void
    {
        foreach ([$en, $es] as $file) {
            $markdown = self::read($file);
            $slugs = [];
            foreach (self::parse($markdown)['headings'] as [, $text]) {
                $slugs[] = self::slug($text);
            }
            preg_match_all('/\sid="([^"]+)"/', $markdown, $ids);
            $targets = array_merge($slugs, $ids[1]);

            preg_match_all('/\]\(#([^)]+)\)/', self::parse($markdown)['prose'], $links);
            foreach ($links[1] as $anchor) {
                $this->assertContains($anchor, $targets, "{$file}: #{$anchor}");
            }
        }
    }

    #[DataProvider('pairs')]
    public function testSameNumberOfRelativeFileLinks(string $en, string $es): void
    {
        $links = function (string $file): array {
            preg_match_all('/\]\((?!https?:|#|mailto:)([^)#]+)/', self::parse(self::read($file))['prose'], $m);
            $found = array_values(array_unique(array_map(
                fn(string $l): string => str_replace(['.es.md'], ['.md'], $l),
                $m[1],
            )));
            sort($found);

            return $found;
        };
        $this->assertSame($links($en), $links($es));
    }
}
