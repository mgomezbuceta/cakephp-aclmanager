-- Bring an EXISTING host `roles` table to the shape AclManager requires.
-- Alternative to setting AclManager.migrations.adoptExistingTables = true.
--
-- REVIEW BEFORE RUNNING AND TAKE A BACKUP. Run only the statements for columns that are
-- missing (the engines fail on a duplicate column). Required columns besides id:
--   name, description, priority, active, created, modified
-- NOT EXECUTED IN CI. The MySQL block was run by hand against MySQL 8.0 on a roles table holding
-- only `id`; the PostgreSQL and SQLite blocks were not executed.
-- The same approach applies to `permissions` and `resources`: compare with the CREATE TABLE
-- statements in mysql.sql / postgres.sql / sqlite.sql and add what is missing.

-- ---------------------------------------------------------------------------
-- MySQL / MariaDB
-- ---------------------------------------------------------------------------
ALTER TABLE roles ADD COLUMN name VARCHAR(100) NOT NULL DEFAULT '' AFTER id;
ALTER TABLE roles ADD COLUMN description TEXT NULL;
ALTER TABLE roles ADD COLUMN priority INT NOT NULL DEFAULT 0;
ALTER TABLE roles ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE roles ADD COLUMN created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE roles ADD COLUMN modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
-- Only if role names are already unique:
ALTER TABLE roles ADD UNIQUE KEY idx_roles_name_unique (name);

-- ---------------------------------------------------------------------------
-- PostgreSQL
-- ---------------------------------------------------------------------------
-- ALTER TABLE roles ADD COLUMN name VARCHAR(100) NOT NULL DEFAULT '';
-- ALTER TABLE roles ADD COLUMN description TEXT NULL;
-- ALTER TABLE roles ADD COLUMN priority INTEGER NOT NULL DEFAULT 0;
-- ALTER TABLE roles ADD COLUMN active BOOLEAN NOT NULL DEFAULT TRUE;
-- ALTER TABLE roles ADD COLUMN created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
-- ALTER TABLE roles ADD COLUMN modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
-- CREATE UNIQUE INDEX idx_roles_name_unique ON roles (name);

-- ---------------------------------------------------------------------------
-- SQLite (ADD COLUMN with NOT NULL needs a constant default; no ON UPDATE support)
-- ---------------------------------------------------------------------------
-- ALTER TABLE roles ADD COLUMN name VARCHAR(100) NOT NULL DEFAULT '';
-- ALTER TABLE roles ADD COLUMN description TEXT NULL;
-- ALTER TABLE roles ADD COLUMN priority INTEGER NOT NULL DEFAULT 0;
-- ALTER TABLE roles ADD COLUMN active BOOLEAN NOT NULL DEFAULT 1;
-- ALTER TABLE roles ADD COLUMN created DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00';
-- ALTER TABLE roles ADD COLUMN modified DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00';
-- CREATE UNIQUE INDEX idx_roles_name_unique ON roles (name);
