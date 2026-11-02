<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Controller\Component;

use AclManager\Controller\Component\AuthorizationManagerComponent;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;

class AuthorizationManagerComponentTest extends TestCase
{
    private string $originalLocale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalLocale = I18n::getLocale();
        $this->loadPlugins(['AclManager' => ['path' => ROOT . DS]]);
    }

    protected function tearDown(): void
    {
        I18n::setLocale($this->originalLocale);
        $this->removePlugins(['AclManager']);
        parent::tearDown();
    }

    public function testHandleUnauthorizedMessageUsesPluginDomain(): void
    {
        I18n::setLocale('es_ES');
        $session = new Session();
        $request = new ServerRequest(['session' => $session]);
        $controller = new Controller($request);
        $component = new AuthorizationManagerComponent(
            $controller->components(),
            ['unauthorizedRedirect' => '/users/login'],
        );

        $component->handleUnauthorized();

        $flash = $session->read('Flash.flash.0.message');
        $this->assertSame('No tienes autorización para acceder a esa ubicación.', $flash);
    }
}
