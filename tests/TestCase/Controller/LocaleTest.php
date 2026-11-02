<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Controller;

use AclManager\Test\TestApp\Application;
use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * The plugin must not change the host locale unless AclManager.locale is configured.
 */
class LocaleTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'plugin.AclManager.Roles',
        'plugin.AclManager.Resources',
        'plugin.AclManager.Permissions',
    ];

    private string $originalLocale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalLocale = I18n::getLocale();
        Configure::write('AclManager.redirects.login', '/users/login');
        $this->configApplication(Application::class, []);
        $this->disableErrorHandlerMiddleware();
    }

    protected function tearDown(): void
    {
        I18n::setLocale($this->originalLocale);
        Configure::delete('AclManager.locale');
        Configure::delete('AclManager.redirects');
        parent::tearDown();
    }

    public function testDefaultLeavesHostLocaleUntouched(): void
    {
        I18n::setLocale('fr_FR');

        $this->get('/authorization-manager');

        $this->assertSame('fr_FR', I18n::getLocale());
        $this->assertNull(Configure::read('AclManager.locale'));
    }

    public function testConfiguredLocaleTranslatesDeniedAccessFlash(): void
    {
        I18n::setLocale('en_US');
        Configure::write('AclManager.locale', 'gl_ES');

        $this->get('/authorization-manager');

        $this->assertFlashMessage('Debes iniciar sesión para acceder ao Xestor de Autorizacións.');
    }

    public function testConfiguredLocaleTranslatesAdminPages(): void
    {
        Configure::write('AclManager.locale', 'es_ES');
        $this->session(['Auth' => ['id' => 10, 'role_id' => 1]]);

        $this->get('/authorization-manager/roles');

        $this->assertResponseOk();
        $this->assertResponseContains('Gestor de Autorizaciones');
    }

    public function testEmptyConfiguredLocaleIsIgnored(): void
    {
        I18n::setLocale('fr_FR');
        Configure::write('AclManager.locale', '');

        $this->get('/authorization-manager');

        $this->assertSame('fr_FR', I18n::getLocale());
    }

    public function testPageLanguageAttributeFollowsTheActiveLocale(): void
    {
        $this->session(['Auth' => ['id' => 10, 'role_id' => 1]]);
        I18n::setLocale('en_US');

        $this->get('/authorization-manager');

        $this->assertResponseOk();
        $this->assertResponseContains('<html lang="en">');
    }
}
