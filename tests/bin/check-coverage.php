<?php
declare(strict_types=1);

/**
 * Fails when the line coverage of a PHPUnit Clover report is below a minimum.
 *
 * Usage: php tests/bin/check-coverage.php <clover.xml> <min-line-percent>
 */
if ($argc !== 3 || !is_numeric($argv[2])) {
    fwrite(STDERR, "Usage: php check-coverage.php <clover.xml> <min-line-percent>\n");
    exit(2);
}

$report = is_file($argv[1]) ? simplexml_load_file($argv[1]) : false;
$metrics = $report ? $report->project->metrics : null;
if (!$metrics || (int)$metrics['statements'] === 0) {
    fwrite(STDERR, "Cannot read the coverage metrics from {$argv[1]}\n");
    exit(2);
}

$percent = (int)$metrics['coveredstatements'] / (int)$metrics['statements'] * 100;
$minimum = (float)$argv[2];
printf("Line coverage: %.2f%% (minimum %.2f%%)\n", $percent, $minimum);

if ($percent < $minimum) {
    fwrite(STDERR, "Coverage is below the minimum.\n");
    exit(1);
}
