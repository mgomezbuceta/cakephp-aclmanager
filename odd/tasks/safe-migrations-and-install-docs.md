# Safe migrations and step-by-step installation docs (extends PR #5)

## Objective
The plugin must never silently alter or delete data it does not own. Integrators control destructive/ownership decisions. Installation and configuration are documented step by step from scratch.

## Problem
- Migrations 0002/0003 add columns to a pre-existing host `roles` table without consent.
- Migration 0004 (PR #5) DELETEs duplicate rows before converting NULL -> 'App'.
- Installation docs are scattered, partly stale, and some documented config keys are not read by any code (audit M6).

## Decisions (user approved)
- Keep CakePHP migrations as the standard install path (`bin/cake migrations migrate -p AclManager`); nothing runs automatically.
- 0004 becomes SAFE: only `UPDATE ... SET plugin='App' WHERE plugin IS NULL`; never deletes. If converting would collide with an existing row (duplicates), it aborts BEFORE changing anything, with a message that points to the documented cleanup SQL. The cleanup SQL (the former DELETE statements) is documented, not executed.
- New config `AclManager.migrations.adoptExistingTables` (default `false`, set in config/bootstrap.php defaults, read with Configure inside the migrations). When a host table (`roles`, `permissions`, `resources`) already exists and the flag is false, 0001/0002/0003 do NOT alter it: they only verify the schema is compatible and throw an explicit exception listing missing columns, the flag name, and where the SQL is documented. When the flag is true they add missing columns as today. Fresh installs (tables absent) are unaffected.
- Publish per-engine SQL in `docs/sql/` (mysql.sql, postgres.sql, sqlite.sql: full schema for a fresh install; `cleanup-duplicates-*.sql` or sections: duplicate detection + cleanup; `adopt-existing-roles-*.sql`: ALTERs for an existing roles table).
- Docs: README.md (English) and INSTALL.md (Spanish) get a complete, ordered, step-by-step guide FROM THE BASE (see Tasks M4). Document ONLY config keys that code actually reads (verify with rg `Configure::read`); keys that are not implemented must not appear as working features.

## TDD
Mode: strict. Runner: `PATH=<php8.3 shim>:$PATH composer exec phpunit`.

## Tasks
- [x] M1 0004 safe: no deletes, abort-before-change on collisions; AppPluginNormalization updated; tests (duplicates abort and leave data intact; clean data converts; down() still works) — delegated writer
- [x] M2 adoptExistingTables flag: default in bootstrap.php; 0001/0002/0003 behavior per flag; compatibility check helper (testable class, like AppPluginNormalization) with tests — delegated writer
- [x] M3 docs/sql/* per engine (+ test that executes the SQLite full-schema script and asserts tables/columns/unique indexes) — delegated writer
- [x] M4 README.md + INSTALL.md step-by-step guide + CHANGELOG — delegated writer

## Acceptance criteria
- 0004 with duplicate NULL/'App' rows: throws, no row deleted or updated.
- Existing incompatible `roles` + flag false: exception names missing columns and the flag; schema untouched. Flag true: columns added.
- Fresh DB: migrations create everything as before.
- docs/sql/sqlite.sql applied to an empty SQLite DB yields a schema the tests' queries work against.
- Docs mention only keys read by code; every code snippet in the install guide is consistent with the TestApp setup used in tests (Authentication middleware, plugin load).

## Progress / evidence
- Continues on branch `fix/plugin-normalization-data-integrity` (PR #5 still open).
- M1 RED: `phpunit tests/TestCase/Migration` -> 2 errors + 1 failure (findCollisions/up(Connection)/statements() undefined, DELETE still in class). GREEN: 4 tests, 20 assertions (duplicates abort leaving ids 1-5 and NULL plugins untouched; clean data converts; down() works; no DELETE in source/statements).
- M2 RED: SchemaCompatibilityTest -> 8 tests, 7 errors, 1 failure (class missing). GREEN: `composer exec phpunit -- tests/TestCase/Migration` -> OK (12 tests, 35 assertions). Migrations 0001-0003 call `SchemaCompatibility::decide()`; bootstrap default `migrations.adoptExistingTables=false`. Migration files only syntax-checked (`php -l`): cakephp/migrations is not installed.
- M3 RED: SqlDocsTest -> 3 failures (docs/sql/sqlite.sql missing). GREEN after creating docs/sql/*: schema, required columns (SchemaCompatibility), unique indexes, duplicate rejection, and cleanup-duplicates.sql removing every collision detected by `findCollisions` all pass on SQLite. MySQL/Postgres SQL NOT executed.
- M4: README.md, INSTALL.md rewritten; CHANGELOG [Unreleased] updated; composer.json `suggest` added. Added AclManagerPolicyTest (MapResolver wiring documented in README) -> 2 tests pass.
- Full suite: `composer exec phpunit` -> OK (53 tests, 142 assertions). phpcs clean on new/changed code (remaining violations are pre-existing lines in config/Migrations and config/bootstrap.php).
- No commit made (per instruction).
- Follow-up (BaseMigration): RED `MigrationBaseClassTest` -> 1 failure (migrations extended AbstractMigration); after the switch OK. Refactor RED: AppPluginNormalizationTest with injected fetchAll/execute callables -> 2 errors (still Connection-typed), then GREEN. 0004 no longer uses getAdapter()/ConnectionManager. 0001-0003 use only table(), hasTable(), Table::getColumns/hasColumn/hasIndex/hasForeignKey/addColumn/addIndex/addForeignKey/create/update, execute().
- Ran the 4 real migrations (copied to a scratch dir outside the repo) via `Migrations\Migrations` on SQLite: cakephp/migrations 5.x and 4.6.6: migrate + rollback OK; duplicate case throws the 0004 RuntimeException leaving rows untouched; existing incompatible `roles`: flag false throws, flag true adds columns and keeps data (5.x). Full suite OK (54 tests, 163 assertions); `php -l` OK on all four.
- [x] M5 Bilingual guides: README.md/README.es.md and INSTALL.md/INSTALL.es.md (INSTALL.md moved with `git mv` to INSTALL.es.md, English INSTALL.md added), `docs/sql/README.md` (EN/ES), CHANGELOG entry. RED: `GuidesParityTest` (14 tests) all failed with files missing. GREEN: 14 tests, 62 assertions (headings per level, fence count/languages, `AclManager.*` keys, sibling links, relative-file links, in-file anchors). Heading emoji in "Upgrading" changed to one without a variation selector so the GitHub slug is stable.
- [x] M6 Review fixes. F1: 0001 now has explicit `up()`/`down()`; `down()` drops permissions/resources/roles only when the table carries the unique index 0001 creates (`hasIndexByName()` exists in migrations 4.6.6 Phinx-based Table and 5.x Db\Table). Because 0002 used to add the same-named index to adopted host tables, adoption now adds COLUMNS only (no indexes/FKs), otherwise the marker would be ambiguous. 0002/0003 now `up()` + documented no-op `down()`. F2: `cakephp/migrations` added to require-dev (5.2.6 installed), `MigrationsRunTest` (in-process, temp SQLite, loads the plugin to use `plugin => AclManager`). RED: fresh rollback left tables, adopt rollback left permissions/resources, '' collision undetected; GREEN after fixes. F3: ScannerService dedupes found entries and keeps a list of rows per key (reactivate one preferring 'App', deactivate every active row); RED 3 failures, GREEN. F4: '' treated like NULL in AppPluginNormalization and cleanup-duplicates.sql (RED 2 failures, then GREEN). F5: `SchemaCompatibility::decideFor()`/`decideForMigration()` single entry point; removed double normalization in PermissionService::isAuthorized and scanAndSync.
- Real rollback runs (scratch installs outside repo, `Migrations\Migrations`, SQLite): migrations 5.x and 4.6.6: fresh migrate -> `target => 0` leaves no plugin tables; adopt mode (host roles + flag on) -> full rollback leaves `roles` with the host row intact.
- Not changing: raw SQL in AppPluginNormalization ignores table prefixes/quoting (CakePHP 4+ has no datasource prefixes; `action` is not reserved on MySQL/Postgres/SQLite). Documented as "Table prefixes are not supported" in README/INSTALL (EN/ES) and CHANGELOG.
- Full suite: OK (79 tests, 254 assertions); GuidesParityTest green.
