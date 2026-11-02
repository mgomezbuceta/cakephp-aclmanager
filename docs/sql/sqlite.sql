-- AclManager full schema for a fresh install (SQLite).
-- Equivalent to migration 20250101000001_CreatePermissionsTables.
-- Executed in CI by tests/TestCase/Migration/SqlDocsTest.php.
-- The plugin column is nullable as in the migration; the application stores 'App' for
-- application-level resources (see migration 20250101000004_NormalizeAppPlugin).

CREATE TABLE roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    priority INTEGER NOT NULL DEFAULT 0,
    active BOOLEAN NOT NULL DEFAULT 1,
    created DATETIME NOT NULL,
    modified DATETIME NOT NULL
);
CREATE UNIQUE INDEX idx_roles_name_unique ON roles (name);

CREATE TABLE permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INTEGER NOT NULL,
    controller VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    plugin VARCHAR(100) NULL,
    allowed BOOLEAN NOT NULL DEFAULT 1,
    created DATETIME NOT NULL,
    modified DATETIME NOT NULL,
    FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE ON UPDATE NO ACTION
);
CREATE INDEX idx_permissions_role_id ON permissions (role_id);
CREATE INDEX idx_permissions_controller_action ON permissions (controller, action);
CREATE UNIQUE INDEX idx_permissions_unique ON permissions (role_id, controller, action, plugin);

CREATE TABLE resources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    controller VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    plugin VARCHAR(100) NULL,
    description TEXT NULL,
    active BOOLEAN NOT NULL DEFAULT 1,
    created DATETIME NOT NULL,
    modified DATETIME NOT NULL
);
CREATE UNIQUE INDEX idx_resources_unique ON resources (controller, action, plugin);
