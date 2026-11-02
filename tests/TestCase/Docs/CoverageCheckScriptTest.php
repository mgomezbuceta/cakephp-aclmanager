<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Docs;

use Cake\TestSuite\TestCase;

/**
 * The CI coverage gate (tests/bin/check-coverage.php) must fail below the threshold.
 */
class CoverageCheckScriptTest extends TestCase
{
    /**
     * @return array{int, string} Exit code and combined output
     */
    private function runScript(string $reportPath, string $minimum): array
    {
        $script = ROOT . DS . 'tests' . DS . 'bin' . DS . 'check-coverage.php';
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($reportPath) . ' ' . $minimum . ' 2>&1';
        exec($command, $output, $code);

        return [$code, implode("\n", $output)];
    }

    /**
     * @return array{int, string} Exit code and combined output
     */
    private function runWithMetrics(int $statements, int $covered, string $minimum): array
    {
        $file = TMP . 'clover_' . uniqid() . '.xml';
        file_put_contents(
            $file,
            "<coverage><project><metrics statements=\"{$statements}\" coveredstatements=\"{$covered}\"/></project></coverage>",
        );
        try {
            return $this->runScript($file, $minimum);
        } finally {
            unlink($file);
        }
    }

    public function testPassesAtOrAboveTheMinimum(): void
    {
        $this->assertSame(0, $this->runWithMetrics(200, 190, '95')[0]);
        $this->assertSame(0, $this->runWithMetrics(200, 200, '100')[0]);
    }

    public function testFailsBelowTheMinimum(): void
    {
        [$code, $output] = $this->runWithMetrics(200, 189, '95');

        $this->assertSame(1, $code);
        $this->assertStringContainsString('94.50%', $output);
    }

    public function testFailsOnUnreadableReports(): void
    {
        $this->assertSame(2, $this->runScript(TMP . 'missing.xml', '90')[0]);
        $this->assertSame(2, $this->runWithMetrics(0, 0, '90')[0]);
    }
}
