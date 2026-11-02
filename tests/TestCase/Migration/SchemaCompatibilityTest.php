<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Migration;

use AclManager\Migration\SchemaCompatibility;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use RuntimeException;

class SchemaCompatibilityTest extends TestCase
{
    public function testRequiredColumnsPerTable(): void
    {
        $this->assertSame(
            ['name', 'description', 'priority', 'active', 'created', 'modified'],
            SchemaCompatibility::REQUIRED['roles'],
        );
        $this->assertSame(
            ['role_id', 'controller', 'action', 'plugin', 'allowed', 'created', 'modified'],
            SchemaCompatibility::REQUIRED['permissions'],
        );
        $this->assertSame(
            ['controller', 'action', 'plugin', 'description', 'active', 'created', 'modified'],
            SchemaCompatibility::REQUIRED['resources'],
        );
    }

    public function testMissingColumnsListsWhatIsAbsent(): void
    {
        $this->assertSame(
            ['priority', 'active', 'created', 'modified'],
            SchemaCompatibility::missingColumns('roles', ['id', 'name', 'description', 'extra']),
        );
        $this->assertSame([], SchemaCompatibility::missingColumns('roles', ['id', 'name', 'description', 'priority', 'active', 'created', 'modified', 'extra']));
    }

    public function testUnknownTableIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SchemaCompatibility::missingColumns('users', []);
    }

    public function testAbsentTableIsCreated(): void
    {
        $this->assertSame(SchemaCompatibility::CREATE, SchemaCompatibility::decide('roles', false, [], false));
        $this->assertSame(SchemaCompatibility::CREATE, SchemaCompatibility::decide('roles', false, [], true));
    }

    public function testCompatibleExistingTableIsLeftAlone(): void
    {
        $columns = ['id', ...SchemaCompatibility::REQUIRED['roles']];
        $this->assertSame(SchemaCompatibility::SKIP, SchemaCompatibility::decide('roles', true, $columns, false));
    }

    public function testIncompatibleTableWithoutFlagThrowsWithDetails(): void
    {
        try {
            SchemaCompatibility::decide('roles', true, ['id', 'name'], false);
            $this->fail('Expected a RuntimeException');
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('roles', $message);
            $this->assertStringContainsString('description, priority, active, created, modified', $message);
            $this->assertStringContainsString('AclManager.migrations.adoptExistingTables', $message);
            $this->assertStringContainsString('docs/sql/', $message);
        }
    }

    public function testIncompatibleTableWithFlagIsAdopted(): void
    {
        $this->assertSame(SchemaCompatibility::ADOPT, SchemaCompatibility::decide('roles', true, ['id', 'name'], true));
    }

    public function testCompatibleTableWithFlagStillNeedsNoChanges(): void
    {
        $columns = ['id', ...SchemaCompatibility::REQUIRED['resources']];
        $this->assertSame(SchemaCompatibility::ADOPT, SchemaCompatibility::decide('resources', true, $columns, true));
    }
}
