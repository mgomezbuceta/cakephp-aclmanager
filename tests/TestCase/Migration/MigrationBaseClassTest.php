<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Migration;

use Cake\TestSuite\TestCase;

/**
 * Mirrors cakephp/migrations 5.x Manager::checkMigrationClass(), which rejects legacy AbstractMigration.
 */
class MigrationBaseClassTest extends TestCase
{
    public function testEveryMigrationExtendsBaseMigration(): void
    {
        $files = glob(dirname(__DIR__, 3) . '/config/Migrations/*.php') ?: [];
        $this->assertCount(4, $files);

        foreach ($files as $file) {
            $source = (string)file_get_contents($file);
            $name = basename($file);
            $this->assertStringNotContainsString('use Migrations\AbstractMigration;', $source, $name);
            $this->assertStringNotContainsString('extends AbstractMigration', $source, $name);
            $this->assertStringNotContainsString('extends \Migrations\AbstractMigration', $source, $name);
            $this->assertStringContainsString('use Migrations\BaseMigration;', $source, $name);
            $this->assertStringContainsString('extends BaseMigration', $source, $name);
        }
    }
}
