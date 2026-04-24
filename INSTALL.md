# Guía de Instalación - AclManager

## Escenario 1: Instalación limpia (sin tablas existentes)

```bash
# 1. Instalar el plugin
composer require mgomezbuceta/cakephp-aclmanager

# 2. Cargar el plugin en src/Application.php
# Añadir: $this->addPlugin('AclManager', ['bootstrap' => true, 'routes' => true]);

# 3. Ejecutar las migraciones
bin/cake migrations migrate -p AclManager

# 4. Listo! Accede a /authorization-manager
```

## Escenario 2: Ya tienes la tabla `roles` pero te faltan `permissions` y `resources`

```bash
# 1. Actualizar el plugin
composer update mgomezbuceta/cakephp-aclmanager

# 2. Verificar la estructura de tu tabla roles
mysql -u usuario -p nombre_bd -e "DESCRIBE roles;"

# 3. Si la tabla roles existe pero es diferente, necesitas ajustarla
# Ejecuta este script SQL para verificar qué columnas tienes:
```

```sql
-- Verificar columnas en roles
SHOW COLUMNS FROM roles;

-- Las columnas necesarias son:
-- id, name, description, priority, active, created, modified
```

### Si tu tabla `roles` tiene una estructura diferente:

**Opción A: Backup y recrear (RECOMENDADO si tienes pocos datos)**

```bash
# 1. Hacer backup de los datos
mysqldump -u usuario -p nombre_bd roles > backup_roles.sql

# 2. Eliminar todas las tablas relacionadas
mysql -u usuario -p nombre_bd -e "
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS resources;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS phinxlog WHERE migration_name LIKE '%AclManager%';
"

# 3. Ejecutar las migraciones desde cero
bin/cake migrations migrate -p AclManager

# 4. Restaurar tus datos adaptados (necesitarás ajustar el SQL manualmente)
```

**Opción B: Migrar datos existentes (si tienes muchos datos importantes)**

Necesito ver tu estructura actual. Ejecuta esto y envíame el resultado:

```bash
mysql -u usuario -p nombre_bd -e "SHOW CREATE TABLE roles\G"
mysql -u usuario -p nombre_bd -e "SELECT * FROM roles LIMIT 5\G"
```

## Escenario 3: Tienes las 3 tablas (roles, permissions, resources)

```bash
# 1. Actualizar el plugin
composer update mgomezbuceta/cakephp-aclmanager

# 2. Marcar migraciones como ejecutadas
bin/cake migrations mark_migrated -p AclManager

# 3. Verificar que todo funciona
# Accede a /authorization-manager
```

## Verificación de la instalación

Para verificar que tu base de datos está correcta, ejecuta:

```sql
-- Deben existir estas 3 tablas con esta estructura:

-- Tabla: roles
DESCRIBE roles;
-- Columnas esperadas: id, name, description, priority, active, created, modified

-- Tabla: permissions
DESCRIBE permissions;
-- Columnas esperadas: id, role_id, controller, action, plugin, allowed, created, modified

-- Tabla: resources
DESCRIBE resources;
-- Columnas esperadas: id, controller, action, plugin, description, active, created, modified
```

## Solución rápida para tu caso actual

Basándome en tu error, parece que:
- ✅ Tienes la tabla `roles`
- ❌ NO tienes la tabla `permissions`
- ❌ NO tienes la tabla `resources`

**Solución:**

```bash
cd /var/www/depurapp5

# Crear un script SQL temporal
cat > /tmp/create_missing_tables.sql << 'EOF'
-- Crear tabla permissions
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `controller` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL,
  `plugin` varchar(100) DEFAULT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT 1,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_permissions_unique` (`role_id`,`controller`,`action`,`plugin`),
  KEY `idx_permissions_role_id` (`role_id`),
  KEY `idx_permissions_controller_action` (`controller`,`action`),
  CONSTRAINT `permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla resources
CREATE TABLE IF NOT EXISTS `resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `controller` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL,
  `plugin` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_resources_unique` (`controller`,`action`,`plugin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
EOF

# Ejecutar el script SQL
mysql -u tu_usuario -p tu_base_datos < /tmp/create_missing_tables.sql

# Marcar migraciones como ejecutadas
bin/cake migrations mark_migrated -p AclManager

# Probar acceso
# Ir a: http://tu-app/authorization-manager
```

## ¿Necesitas ayuda?

Envíame la salida de estos comandos:

```bash
# Ver qué tablas existen
mysql -u usuario -p bd -e "SHOW TABLES LIKE '%role%'; SHOW TABLES LIKE '%permission%'; SHOW TABLES LIKE '%resource%';"

# Ver estructura de roles
mysql -u usuario -p bd -e "DESCRIBE roles;"

# Ver datos en roles
mysql -u usuario -p bd -e "SELECT * FROM roles;"
```
