<?php
declare(strict_types=1);

use AclManager\Migration\AppPluginNormalization;
use Migrations\AbstractMigration;

/**
 * NormalizeAppPlugin migration
 *
 * Application-level resources used a NULL plugin while permissions used 'App', so
 * lookups by the policy never matched resource rows and the nullable column let
 * duplicates slip past the unique indexes. 'App' is now the single canonical value.
 */
class NormalizeAppPlugin extends AbstractMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        foreach (AppPluginNormalization::up() as $sql) {
            $this->execute($sql);
        }
    }

    /**
     * @return void
     */
    public function down(): void
    {
        foreach (AppPluginNormalization::down() as $sql) {
            $this->execute($sql);
        }
    }
}
