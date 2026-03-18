<?php
declare(strict_types=1);

namespace AclManager;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;

/**
 * Plugin class for AclManager
 *
 * This class provides the plugin configuration and bootstrapping
 * for the AclManager authorization plugin.
 */
class Plugin extends BasePlugin
{
    /**
     * Plugin name
     *
     * @var ?string
     */
    protected ?string $name = 'AclManager';

    /**
     * Load routes for the plugin
     *
     * @param \Cake\Routing\RouteBuilder $routes The route builder to update
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin(
            'AclManager',
            ['path' => '/acl-manager'],
            function (RouteBuilder $builder): void {
                $builder->fallbacks();
            }
        );

        parent::routes($routes);
    }

    /**
     * Add middleware for the plugin
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to update
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue;
    }

    /**
     * Bootstrap hook for the plugin
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);
    }
}
