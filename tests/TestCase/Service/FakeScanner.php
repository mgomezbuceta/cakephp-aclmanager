<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Service;

use AclManager\Service\ResourceScannerService;

/**
 * Scanner with a controllable "found" list instead of real controller reflection.
 */
class FakeScanner extends ResourceScannerService
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $found = [];

    protected function scanAllControllers(): array
    {
        return $this->found;
    }

    /**
     * Scan the TestApp controllers as application (non-plugin) controllers.
     */
    public function scanTestApp(): array
    {
        return $this->scanControllersInPath(APP . 'Controller', null);
    }

    protected function getControllerClassName(string $controllerName, ?string $plugin = null): string
    {
        return 'AclManager\\Test\\TestApp\\Controller\\' . $controllerName . 'Controller';
    }
}
