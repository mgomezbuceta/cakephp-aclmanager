-- AclManager full schema for a fresh install (MySQL / MariaDB).
-- Equivalent to migration 20250101000001_CreatePermissionsTables.
-- Not executed in CI (only the SQLite script is). Checked by hand against MySQL 8.0: the resulting
-- columns, indexes and foreign key match the migration. Take a backup before running it.
-- The plugin column is nullable as in the migration; the application stores 'App' for
-- application-level resources (see migration 20250101000004_NormalizeAppPlugin).

CREATE TABLE roles (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    priority INT NOT NULL DEFAULT 0 COMMENT 'Higher priority = more permissions',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created DATETIME NOT NULL,
    modified DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_roles_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE permissions (
    id INT NOT NULL AUTO_INCREMENT,
    role_id INT NOT NULL,
    controller VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    plugin VARCHAR(100) NULL,
    allowed TINYINT(1) NOT NULL DEFAULT 1,
    created DATETIME NOT NULL,
    modified DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_permissions_role_id (role_id),
    KEY idx_permissions_controller_action (controller, action),
    UNIQUE KEY idx_permissions_unique (role_id, controller, action, plugin),
    CONSTRAINT permissions_role_id FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE resources (
    id INT NOT NULL AUTO_INCREMENT,
    controller VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    plugin VARCHAR(100) NULL,
    description TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created DATETIME NOT NULL,
    modified DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_resources_unique (controller, action, plugin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
