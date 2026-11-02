<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Service;

use AclManager\Service\ResourceScannerService;

/**
 * Exposes the real (reflection based) scanning entry points for tests.
 */
class PluginProbeScanner extends ResourceScannerService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function scanLoaded(): array
    {
        return $this->scanAllControllers();
    }

    /**
     * @return array<int, string>
     */
    public function filesIn(string $path): array
    {
        return $this->findControllerFiles($path);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function scanPath(string $path): array
    {
        return $this->scanControllersInPath($path);
    }
}
