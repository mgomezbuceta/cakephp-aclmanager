<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Layout;

use Cake\TestSuite\TestCase;

/**
 * Every asset the layout loads from a CDN must be pinned with Subresource Integrity.
 */
class LayoutIntegrityTest extends TestCase
{
    private const LAYOUT = __DIR__ . '/../../../templates/layout/default.php';

    /**
     * @return array<int, string> Raw tags that load an external stylesheet or script
     */
    private function externalTags(): array
    {
        $html = (string)file_get_contents(self::LAYOUT);
        preg_match_all('/<(?:link|script)\b[^>]*(?:href|src)="https?:\/\/[^>]*>/i', $html, $matches);

        return $matches[0];
    }

    public function testLayoutLoadsExternalAssets(): void
    {
        $this->assertCount(3, $this->externalTags());
    }

    public function testEveryExternalAssetHasIntegrityAndCrossorigin(): void
    {
        foreach ($this->externalTags() as $tag) {
            $this->assertMatchesRegularExpression('/\bintegrity="sha384-[A-Za-z0-9+\/]{64}"/', $tag, $tag);
            $this->assertMatchesRegularExpression('/\bcrossorigin="anonymous"/', $tag, $tag);
        }
    }

    public function testNoBootstrapFourBadgeClassesRemain(): void
    {
        $templates = glob(__DIR__ . '/../../../templates/{,*/}*.php', GLOB_BRACE) ?: [];
        $this->assertNotEmpty($templates);
        foreach ($templates as $file) {
            $this->assertDoesNotMatchRegularExpression(
                '/\bbadge-(?:primary|secondary|success|danger|warning|info|light|dark)\b/',
                (string)file_get_contents($file),
                $file,
            );
        }
    }
}
