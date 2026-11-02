-- AclManager full schema for a fresh install (PostgreSQL).
-- Equivalent to migration 20250101000001_CreatePermissionsTables.
-- NOT EXECUTED IN CI: only the SQLite script is tested. Review before running and take a backup.
-- The plugin column is nullable as in the migration; the application stores 'App' for
-- application-level resources (see migration 20250101000004_NormalizeAppPlugin).

CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    priority INTEGER NOT NULL DEFAULT 0,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created TIMESTAMP NOT NULL,
    modified TIMESTAMP NOT NULL
);
COMMENT ON COLUMN roles.priority IS 'Higher priority = more permissions';
CREATE UNIQUE INDEX idx_roles_name_unique ON roles (name);

CREATE TABLE permissions (
    id SERIAL PRIMARY KEY,
    role_id INTEGER NOT NULL REFERENCES roles (id) ON DELETE CASCADE ON UPDATE NO ACTION,
    controller VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    plugin VARCHAR(100) NULL,
    allowed BOOLEAN NOT NULL DEFAULT TRUE,
    created TIMESTAMP NOT NULL,
    modified TIMESTAMP NOT NULL
);
CREATE INDEX idx_permissions_role_id ON permissions (role_id);
CREATE INDEX idx_permissions_controller_action ON permissions (controller, action);
CREATE UNIQUE INDEX idx_permissions_unique ON permissions (role_id, controller, action, plugin);

CREATE TABLE resources (
    id SERIAL PRIMARY KEY,
    controller VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    plugin VARCHAR(100) NULL,
    description TEXT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created TIMESTAMP NOT NULL,
    modified TIMESTAMP NOT NULL
);
CREATE UNIQUE INDEX idx_resources_unique ON resources (controller, action, plugin);
