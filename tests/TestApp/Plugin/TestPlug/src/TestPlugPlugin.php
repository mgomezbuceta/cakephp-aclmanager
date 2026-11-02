<?php
declare(strict_types=1);

namespace TestPlug;

use Cake\Core\BasePlugin;

/**
 * Test-only plugin used to exercise plugin controller scanning.
 */
class TestPlugPlugin extends BasePlugin
{
    protected ?string $name = 'TestPlug';
    protected bool $bootstrapEnabled = false;
    protected bool $routesEnabled = false;
    protected bool $middlewareEnabled = false;
    protected bool $consoleEnabled = false;
}
