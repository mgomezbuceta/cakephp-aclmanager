<?php
declare(strict_types=1);

namespace AclManager\Test\TestApp;

use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Policy\OrmResolver;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Minimal host application used by the integration tests.
 *
 * Authentication is intentionally not configured: the plugin's AppController then
 * skips its admin check, while the Authorization component still needs a service.
 */
class Application extends BaseApplication implements AuthorizationServiceProviderInterface
{
    public function bootstrap(): void
    {
        $this->addPlugin('AclManager', ['path' => ROOT . DS]);
    }

    public function routes(RouteBuilder $routes): void
    {
        // Only plugin routes are needed; they are loaded by RoutingMiddleware.
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue
            ->add(new RoutingMiddleware($this))
            ->add(new BodyParserMiddleware())
            ->add(new AuthorizationMiddleware($this));
    }

    public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
    {
        return new AuthorizationService(new OrmResolver());
    }
}
