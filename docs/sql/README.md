# SQL scripts / Scripts SQL

**EN.** Reference SQL for AclManager. Review every script and take a backup before running it.
Only `sqlite.sql` (and the SQLite-compatible part of `cleanup-duplicates.sql`) is executed by the test suite; the MySQL and PostgreSQL variants are **not executed in CI**.

| File | Purpose |
|------|---------|
| `mysql.sql`, `postgres.sql`, `sqlite.sql` | Full schema (`roles`, `permissions`, `resources`) for a fresh install, equivalent to migration 0001. |
| `cleanup-duplicates.sql` | Detects and removes the duplicates that make migration 0004 abort (lowest id wins). |
| `adopt-existing-roles.sql` | `ALTER TABLE` statements to bring an existing `roles` table to the required shape. |

Guides: [INSTALL.md](../../INSTALL.md), [README.md](../../README.md).

**ES.** SQL de referencia para AclManager. Revisa cada script y haz una copia de seguridad antes de ejecutarlo.
La suite de pruebas solo ejecuta `sqlite.sql` (y la parte compatible con SQLite de `cleanup-duplicates.sql`); las variantes de MySQL y PostgreSQL **no se ejecutan en CI**.

| Archivo | Finalidad |
|---------|-----------|
| `mysql.sql`, `postgres.sql`, `sqlite.sql` | Esquema completo (`roles`, `permissions`, `resources`) para una instalación nueva, equivalente a la migración 0001. |
| `cleanup-duplicates.sql` | Detecta y elimina los duplicados que hacen abortar la migración 0004 (se conserva el id más bajo). |
| `adopt-existing-roles.sql` | Sentencias `ALTER TABLE` para dejar una tabla `roles` existente con la forma necesaria. |

Guías: [INSTALL.es.md](../../INSTALL.es.md), [README.es.md](../../README.es.md).
