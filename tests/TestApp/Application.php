<?php
declare(strict_types=1);

namespace AclManager\Test\TestApp;

use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
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
 * Authentication uses the session authenticator, so tests log in with
 * `$this->session(['Auth' => [...]])`. `/no-auth` hits a controller that does not load
 * the Authentication component (a host app without cakephp/authentication).
 */
class Application extends BaseApplication implements
    AuthorizationServiceProviderInterface,
    AuthenticationServiceProviderInterface
{
    public function bootstrap(): void
    {
        $this->addPlugin('AclManager', ['path' => ROOT . DS]);
    }

    public function routes(RouteBuilder $routes): void
    {
        $routes->connect('/no-auth', ['controller' => 'NoAuth', 'action' => 'index']);
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue
            ->add(new RoutingMiddleware($this))
            ->add(new BodyParserMiddleware())
            ->add(new AuthenticationMiddleware($this))
            ->add(new AuthorizationMiddleware($this, ['requireAuthorizationCheck' => false]));
    }

    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService();
        $service->loadAuthenticator('Authentication.Session', [
            'identifier' => ['Authentication.Password'],
        ]);

        return $service;
    }

    public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
    {
        return new AuthorizationService(new OrmResolver());
    }
}
