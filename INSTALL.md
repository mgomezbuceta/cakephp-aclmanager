# Guía de instalación - AclManager

Guía paso a paso partiendo de una aplicación CakePHP 5 vacía. La versión en inglés está en [README.md](README.md).

> **Haz una copia de seguridad de la base de datos antes de ejecutar cualquier migración o SQL de esta guía.** Nada se ejecuta automáticamente: el plugin no modifica tu esquema hasta que tú ejecutas una migración o un script.

## Requisitos

| Requisito | Versión |
|-----------|---------|
| PHP | >= 8.1 (con `ext-intl`) |
| CakePHP | ^5.0 (ver `composer.json`) |
| CakePHP Authentication | ^3.0 (obligatorio salvo que lo desactives, ver Paso 2) |
| CakePHP Authorization | ^3.0 |
| CakePHP Migrations | ^4.5 o ^5.0 (solo para los caminos A y B de base de datos) |
| Base de datos | MySQL/MariaDB, PostgreSQL o SQLite (scripts en `docs/sql/`) |

## Paso 1: Instalar los paquetes

```bash
composer require mgomezbuceta/cakephp-aclmanager
composer require cakephp/authentication cakephp/authorization
composer require "cakephp/migrations:^4.5 || ^5.0"   # solo para los caminos A y B de base de datos
```

`cakephp/migrations` figura en `suggest` (no en `require`) porque el camino C (SQL manual) no lo necesita. Las migraciones del plugin extienden `Migrations\BaseMigration`, así que requieren migrations ^4.5 (que ya incluye `BaseMigration`) o ^5.0 (que solo tiene `BaseMigration`); las versiones anteriores a la 4.5 no son compatibles. `cakephp/authentication` también es solo sugerido: sin él, toda petición al gestor recibe un 403 salvo que lo desactives (ver Paso 2).

## Paso 2: Autenticación

El gestor solo deja entrar a administradores, así que debe saber quién es el usuario actual. Tu tabla `users` necesita una columna `role_id` (se añade en el Paso 6). Configura Authentication en `src/Application.php`; sigue la aplicación de pruebas del plugin (`tests/TestApp/Application.php`, autenticador de sesión con el identificador Password):

```php
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;
use Psr\Http\Message\ServerRequestInterface;

class Application extends BaseApplication implements AuthenticationServiceProviderInterface
{
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue
            ->add(new RoutingMiddleware($this))
            ->add(new AuthenticationMiddleware($this));
    }

    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService();
        $identifier = ['Authentication.Password' => ['fields' => ['username' => 'email', 'password' => 'password']]];
        $service->loadAuthenticator('Authentication.Session', ['identifier' => $identifier]);
        $service->loadAuthenticator('Authentication.Form', [
            'identifier' => $identifier,
            'loginUrl' => '/users/login',
        ]);

        return $service;
    }
}
```

Mantén el resto de tu middleware habitual (manejo de errores, assets, CSRF...); este fragmento solo muestra lo que necesita el plugin. Crea la acción de login según la [documentación de Authentication](https://book.cakephp.org/authentication/3/en/index.html). Por defecto el gestor redirige a los anónimos a `Users::login` (`AclManager.redirects.login`).

**Cómo decide el gestor quién es administrador** (`AclManager\Controller\AppController::isUserAdmin()`). Decide la primera regla que aplique, en este orden:

1. `AclManager.adminAccess.customCheck`: si es un callable, recibe la identidad y su resultado es definitivo.
2. La identidad tiene `role_name` no nulo: es admin si coincide (sin distinguir mayúsculas) con `AclManager.adminAccess.adminRoleNames` (por defecto `admin`, `administrator`, `superadmin`).
3. La identidad tiene `role_id` no nulo: es admin si está en `AclManager.adminAccess.adminRoleIds` (por defecto `[1]`).
4. La identidad tiene `is_admin` no nulo: es admin si es verdadero.
5. La identidad tiene `email` y `AclManager.adminAccess.adminEmails` no está vacío: es admin si el email figura en la lista.
6. En otro caso: acceso denegado.

Un campo que coincide corta la evaluación (un usuario con `role_id` nunca se comprueba por email). Los no administradores se redirigen a `AclManager.redirects.unauthorized`.

**¿Sin `cakephp/authentication`?** Sin el componente Authentication el plugin falla en cerrado (HTTP 403). Solo si proteges el gestor por otro medio (por ejemplo, autenticación HTTP en el servidor web), pon `AclManager.adminAccess.allowWithoutAuthentication` a `true`, o define un `customCheck` (en este caso se invoca con `null`).

## Paso 3: Autorización, para aplicar los permisos en tu aplicación

Instalar el gestor solo te da las pantallas para editar permisos. Tu aplicación decide qué hacer cumplir, y el plugin ofrece dos piezas. Ambas usan el `role_id` y el controlador/acción/plugin de la petición, y ambas **deniegan por defecto**: una acción sin permiso concedido para el rol se rechaza. Los controladores de la aplicación se guardan con plugin `'App'`.

**Opción 1: política (`AclManager\Policy\AclManagerPolicy`).** Es una política de petición para `cakephp/authorization`. Lee `role_id` de los datos originales de la identidad (`$identity->getOriginalData()->role_id`), así que tu identificador debe devolver una entidad u objeto, no un array. Regístrala para `ServerRequest`:

```php
use AclManager\Policy\AclManagerPolicy;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Policy\MapResolver;
use Cake\Http\ServerRequest;

// class Application ... implements AuthorizationServiceProviderInterface
public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
{
    return new AuthorizationService(new MapResolver([ServerRequest::class => AclManagerPolicy::class]));
}

// en middleware(), después de AuthenticationMiddleware:
$middlewareQueue->add(new AuthorizationMiddleware($this, ['requireAuthorizationCheck' => false]));
```

Después comprueba la petición en tu propio `src/Controller/AppController.php`. No apliques la comprobación a las páginas del propio gestor: están protegidas por las reglas de administrador y, por defecto, se excluyen de la sincronización de recursos, así que ningún rol podría recibir permisos sobre ellas.

```php
use Cake\Http\Exception\ForbiddenException;

public function beforeFilter(\Cake\Event\EventInterface $event): void
{
    parent::beforeFilter($event);

    if ($this->request->getParam('plugin') === 'AclManager') {
        return;
    }
    $identity = $this->request->getAttribute('identity');
    if (!$identity || !$identity->can('access', $this->request)) {
        throw new ForbiddenException();
    }
}
```

La decisión de la política (`can('access', $request)` con `MapResolver`) está cubierta por `tests/TestCase/Policy/AclManagerPolicyTest.php`. El fragmento de `beforeFilter` es uso normal de `cakephp/authorization` y la suite de pruebas no lo ejecuta. `RequestAuthorizationMiddleware` también serviría, pero comprueba además las páginas del gestor, por lo que tendrías que excluirlas tú.

**Opción 2: componente (`AclManager.AuthorizationManager`).** Es una utilidad, no un gancho. CakePHP 5 no tiene un callback `isAuthorized()` automático, así que el componente no hace nada hasta que lo llamas:

```php
// AppController::initialize()
$this->loadComponent('AclManager.AuthorizationManager', ['roleField' => 'role_id']);

// donde quieras aplicarlo, p. ej. beforeFilter()
$identity = $this->request->getAttribute('identity');
$user = $identity?->getOriginalData()?->toArray();
if (!$this->AuthorizationManager->isAuthorized($user)) {
    return $this->AuthorizationManager->handleUnauthorized();
}

// comprobar un rol y recurso concretos
$this->AuthorizationManager->checkPermission($roleId, 'Articles', 'edit', 'Blog');
```

`isAuthorized(?array $user)` recibe un array y devuelve `false` si es `null` o si falta `roleField`. Opciones del componente que se leen: `roleField` (por defecto `role_id`), `unauthorizedRedirect` (por defecto `Users::login`) y `cachePermissions` (por defecto `true`; cachea resultados por instancia del componente, usa `clearCache()` para reiniciar). `userModel`, `cacheConfig` y `cacheDuration` están declaradas pero no se usan.

## Paso 4: Cargar el plugin

En `src/Application.php`:

```php
public function bootstrap(): void
{
    parent::bootstrap();

    $this->addPlugin('AclManager', ['bootstrap' => true, 'routes' => true]);
}
```

El `config/bootstrap.php` del plugin escribe los valores por defecto descritos en la [referencia de configuración](#referencia-de-configuración) (sin sobrescribir los que ya hayas definido en `config/app.php`).

## Paso 5: Crear las tablas

Elige un camino. **Haz copia de seguridad primero.**

**Camino A: base de datos nueva, con migraciones (recomendado).** Ninguna de las tablas `roles`, `permissions`, `resources` existe todavía:

```bash
bin/cake migrations migrate -p AclManager
```

Las migraciones crean las tres tablas con sus índices y clave foránea. Consulta "La migración 0004 nunca borra datos" más abajo.

**Camino B: ya tienes una tabla `roles` (o alguna de las tres).** Por defecto las migraciones nunca modifican una tabla existente.

- Si la tabla ya tiene todas las columnas necesarias, las migraciones no la tocan y continúan.
- Si faltan columnas, `bin/cake migrations migrate -p AclManager` se detiene con un error que indica la tabla, las columnas que faltan, el parámetro de configuración y `docs/sql/`. Elige:
  - **B1, que las migraciones añadan las columnas:** define `Configure::write('AclManager.migrations.adoptExistingTables', true);` en `config/app.php` (o `config/bootstrap.php`) **antes** de ejecutar la migración. Se añaden las columnas que faltan con valores por defecto seguros; los datos existentes se conservan.
  - **B2, aplicar el SQL tú mismo:** ejecuta las sentencias de [`docs/sql/adopt-existing-roles.sql`](docs/sql/adopt-existing-roles.sql) para tu motor (compara `permissions` y `resources` con los scripts de esquema) y después ejecuta las migraciones; encontrarán una tabla compatible y no la modificarán.

Columnas necesarias (además de `id`): `roles`: `name, description, priority, active, created, modified`; `permissions`: `role_id, controller, action, plugin, allowed, created, modified`; `resources`: `controller, action, plugin, description, active, created, modified`.

**Camino C: sin el plugin de migraciones.** Ejecuta una sola vez el esquema completo de tu motor: [`docs/sql/mysql.sql`](docs/sql/mysql.sql), [`docs/sql/postgres.sql`](docs/sql/postgres.sql) o [`docs/sql/sqlite.sql`](docs/sql/sqlite.sql). Solo el script de SQLite se ejecuta en las pruebas; los de MySQL y PostgreSQL no se han ejecutado en CI, así que revísalos antes de usarlos.

### La migración 0004 nunca borra datos

`20250101000004_NormalizeAppPlugin` convierte `plugin IS NULL` en `'App'` en `resources` y `permissions` (para que la política reconozca los controladores de la aplicación). Solo ejecuta `UPDATE`. Si la conversión crearía un duplicado, **se aborta antes de cambiar ninguna fila** e indica cuántas filas colisionan en cada tabla:

```
Migration NormalizeAppPlugin aborted, nothing was changed. ... (resources: 2, permissions: 0). Review and clean the duplicates with docs/sql/cleanup-duplicates.sql ...
```

Los duplicados son una fila con plugin NULL para un controlador/acción que ya existe como `'App'` (o que aparece dos veces con NULL). Para resolverlo: haz copia de seguridad, abre [`docs/sql/cleanup-duplicates.sql`](docs/sql/cleanup-duplicates.sql), ejecuta los `SELECT` de detección, revisa qué se eliminaría, ejecuta los `DELETE` de limpieza de tu motor (se conserva el id más bajo) y vuelve a ejecutar la migración. Al revertir la migración, `resources.plugin` vuelve a NULL; los permisos siguen en `'App'`.

## Paso 6: Añadir `role_id` a la tabla de usuarios

Usa el mismo tipo que `roles.id` (`INT` con signo si lo crearon las migraciones). Déjala nula al principio para poder crear al primer administrador (Paso 7).

```sql
-- MySQL / MariaDB
ALTER TABLE users ADD COLUMN role_id INT NULL;
ALTER TABLE users ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE SET NULL;

-- PostgreSQL
ALTER TABLE users ADD COLUMN role_id INTEGER NULL REFERENCES roles (id) ON DELETE SET NULL;

-- SQLite (no admite ALTER ... ADD CONSTRAINT; REFERENCES en línea se acepta en columnas nuevas)
ALTER TABLE users ADD COLUMN role_id INTEGER NULL REFERENCES roles (id) ON DELETE SET NULL;
```

## Paso 7: Primer uso

1. Crea un rol de administrador directamente en la base de datos (aún no se puede entrar al gestor porque nadie es admin):

   ```sql
   INSERT INTO roles (name, description, priority, active, created, modified)
   VALUES ('Admin', 'Administradores', 100, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
   -- PostgreSQL: usa TRUE en lugar de 1 para active.
   ```
2. Asígnalo a tu usuario y anota el id del rol: `UPDATE users SET role_id = <id del rol> WHERE email = 'tu@ejemplo.com';`
3. Si el id del rol no es `1`, indícaselo al gestor: `'AclManager' => ['adminAccess' => ['adminRoleIds' => [<id del rol>]]]` en `config/app.php`. Los roles de esa lista no se pueden eliminar ni desactivar desde la interfaz.
4. Inicia sesión y abre `/authorization-manager`.
5. Pulsa **Sync Resources** (POST) para descubrir tus controladores y acciones.
6. Crea el resto de roles en **Manage Roles**, abre **Manage Permissions** de cada uno, marca las acciones permitidas y guarda.
7. Asigna un `role_id` al resto de usuarios.

La comprobación de administrador (Paso 2) es independiente de la matriz de permisos: los administradores usan el gestor sin necesidad de permisos concedidos.

Alternativa a los pasos 1-3 para un arranque puntual: mientras tu `users.role_id` sea NULL, incluye tu email en `adminEmails`. Deja de funcionar en cuanto la identidad tenga un `role_id` no nulo, porque ese campo se evalúa antes.

## Referencia de configuración

Solo se listan las claves que el código lee. Defínelas en el array `AclManager` de `config/app.php` o con `Configure::write()` antes de cargar el plugin; los valores que definas no se sobrescriben con los de por defecto.

| Clave | Por defecto | Significado |
|-------|-------------|-------------|
| `AclManager.adminAccess.adminRoleIds` | `[1]` | Ids de rol considerados administradores (se comparan con `role_id`). Estos roles no se pueden eliminar ni desactivar en la interfaz. |
| `AclManager.adminAccess.adminRoleNames` | `['admin', 'administrator', 'superadmin']` | Se comparan sin distinguir mayúsculas con `role_name`. |
| `AclManager.adminAccess.adminEmails` | `[]` | Emails considerados administradores (solo si la identidad no tiene `role_name`, `role_id` ni `is_admin`). |
| `AclManager.adminAccess.customCheck` | sin definir | `callable(?IdentityInterface $identity): bool`. Máxima prioridad. También se invoca con `null` si falta el componente Authentication. |
| `AclManager.adminAccess.allowWithoutAuthentication` | `false` | Permite el acceso si `cakephp/authentication` no está disponible. Solo se acepta `true`. |
| `AclManager.redirects.login` | `['plugin' => false, 'controller' => 'Users', 'action' => 'login']` | Destino de los usuarios anónimos (se añade `?redirect=`). URL como array o cadena. |
| `AclManager.redirects.unauthorized` | `['plugin' => false, 'controller' => 'Pages', 'action' => 'display', 'home']` | Destino de los no administradores. |
| `AclManager.ignoreActions` | lista en `config/bootstrap.php` | Métodos, controladores y patrones `Plugin.*` que omite Sync Resources. **Definirla sustituye toda la lista por defecto**: copia los valores que sigas necesitando. |
| `AclManager.admin` | `false` | `true` sirve el gestor bajo el prefijo `Admin` en `/admin/authorization-manager`. |
| `AclManager.migrations.adoptExistingTables` | `false` | `true` permite que las migraciones 0001-0003 añadan las columnas que falten a tablas `roles`/`permissions`/`resources` ya existentes. |
| `AclManager.version` | `'3.2.0'` | Informativa (se muestra en el pie). |

Ejemplo:

```php
// config/app.php
return [
    // ...
    'AclManager' => [
        'adminAccess' => [
            'adminRoleIds' => [1, 2],
            'adminRoleNames' => ['admin'],
            'customCheck' => null, // o fn($identity): bool => ...
        ],
        'redirects' => [
            'login' => ['plugin' => false, 'controller' => 'Users', 'action' => 'login'],
            'unauthorized' => '/dashboard',
        ],
        'migrations' => ['adoptExistingTables' => false],
    ],
];
```

**Previsto / no implementado todavía.** El bootstrap del plugin aún escribe `permissionMode`, `cachePermissions`, `cacheDuration`, `defaultRoleId`, `paginationLimits` y `{roles,permissions,resources}.limit`, y documentación anterior los describía, pero ningún código los lee, así que cambiarlos no tiene efecto. La comprobación de permisos siempre deniega por defecto.

## Idioma (i18n)

Hay traducciones (dominio `acl_manager`) para `es_ES`, `gl_ES` y `en_US`.

Comportamiento actual, tal como está implementado: el `config/bootstrap.php` del plugin llama a `I18n::setLocale('es_ES')` sin condiciones cuando el plugin se carga con `'bootstrap' => true`. Ese ajuste es **global para la petición**, por lo que también cambia el idioma del resto de tu aplicación, y no se puede configurar con `Configure`. Para usar otro idioma, establécelo después de que el plugin haya hecho su bootstrap, por petición. Por ejemplo, con `LocaleSelectorMiddleware` de CakePHP, añadido a la cola después de `RoutingMiddleware`:

```php
use Cake\I18n\Middleware\LocaleSelectorMiddleware;

$middlewareQueue->add(new LocaleSelectorMiddleware(['en_US', 'es_ES', 'gl_ES']));
```

Para añadir tu propia traducción, copia `resources/locales/es_ES/acl_manager.po` a `resources/locales/{locale}/acl_manager.po` y traduce los `msgstr`.

## Solución de problemas

**Toda petición a `/authorization-manager` devuelve 403.** `cakephp/authentication` no está instalado o `AuthenticationMiddleware` no está en la cola, así que nadie queda identificado y el plugin falla en cerrado. Instálalo y configúralo (Paso 2) o, si proteges el gestor por otro medio, pon `AclManager.adminAccess.allowWithoutAuthentication` a `true`.

**Te redirige al login o a "sin permiso".** No eres administrador según las reglas del Paso 2. Revisa `role_id` frente a `adminRoleIds` (o los demás campos) y recuerda que un `role_id` no nulo tiene prioridad sobre `adminEmails`.

**La migración aborta con "NormalizeAppPlugin aborted, nothing was changed".** Hay filas duplicadas que colisionarían al convertir `NULL` en `'App'`. No se modificó nada. Sigue "La migración 0004 nunca borra datos" (copia de seguridad, revisa y ejecuta `docs/sql/cleanup-duplicates.sql`, vuelve a ejecutar).

**La migración falla con "Table 'roles' already exists and is missing required columns".** Camino B: pon `AclManager.migrations.adoptExistingTables` a `true` o aplica `docs/sql/adopt-existing-roles.sql` y vuelve a ejecutar.

**La migración falla con un mensaje sobre el `AbstractMigration` heredado.** Es el error que lanza `cakephp/migrations` 5.x cuando un archivo de migración sigue extendiendo `AbstractMigration`, es decir, tienes una versión antigua de este plugin. Actualiza el plugin (sus migraciones extienden `BaseMigration`) y usa `cakephp/migrations` ^4.5 o ^5.0. En versiones anteriores a la 4.5 `BaseMigration` no existe: actualiza el plugin de migraciones.

**Guardar permisos con muchos controladores.** `max_input_vars` ya no es un problema: el formulario envía un valor `resource_ids[]` por cada acción concedida y un POST truncado se detecta y se rechaza sin tocar los permisos existentes.

**Sync Resources o Clear Permissions devuelven 405.** Solo aceptan POST (los botones usan `postLink`); un enlace GET o un marcador no funcionará, y tu layout debe incluir el token CSRF si usas `CsrfProtectionMiddleware`.

**No aparecen recursos.** Abre `/authorization-manager` y usa *Sync Resources*. Las acciones que coinciden con `AclManager.ignoreActions` se omiten.

**Acceso denegado tras activar la política.** El usuario necesita un `role_id` y el rol debe tener la acción marcada en *Manage Permissions*. Todo lo no concedido se deniega.

## Actualizar desde la 3.2.0

1. Haz copia de seguridad de la base de datos y ejecuta `composer update mgomezbuceta/cakephp-aclmanager`.
2. Ejecuta `bin/cake migrations migrate -p AclManager`. La nueva migración `20250101000004_NormalizeAppPlugin` convierte `plugin IS NULL` en `'App'`. Nunca borra filas: si hubiera duplicados que colisionan, se aborta mostrando los recuentos y sin cambiar nada (ver "La migración 0004 nunca borra datos").
3. Las migraciones 0001-0003 ya no modifican tablas preexistentes salvo que `AclManager.migrations.adoptExistingTables` sea `true`. Si ya tenías una tabla `roles` propia y dependías de la adición automática de columnas, activa el parámetro o añade las columnas con `docs/sql/adopt-existing-roles.sql`.
4. **Cambio incompatible:** sin `cakephp/authentication` el gestor ahora devuelve 403 en lugar de dar acceso a todos. Instálalo, o pon `AclManager.adminAccess.allowWithoutAuthentication` a `true` (o define un `customCheck`).
5. `clearPermissions` y `syncResources` solo aceptan POST.
