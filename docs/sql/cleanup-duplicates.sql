-- Duplicate cleanup before migration 20250101000004_NormalizeAppPlugin.
--
-- REVIEW BEFORE RUNNING AND TAKE A BACKUP. The DELETE statements remove rows permanently.
-- The migration itself never deletes anything: it aborts when the SELECTs below return rows.
-- Tested only against SQLite semantics; the MySQL/PostgreSQL variants are NOT EXECUTED IN CI.
--
-- An empty-plugin row (plugin NULL or '', both mean the application) collides when an 'App' row has the same key, or another empty-plugin row with a
-- lower id has the same key (resources: controller+action; permissions: role_id+controller+action).
-- The lowest id wins.

-- ---------------------------------------------------------------------------
-- 1. Detection (all engines). Both counts must be 0 for the migration to run.
-- ---------------------------------------------------------------------------
SELECT COUNT(*) AS colliding_resources FROM resources r
WHERE (r.plugin IS NULL OR r.plugin = '') AND (
    EXISTS (SELECT 1 FROM resources x WHERE x.plugin = 'App' AND x.controller = r.controller AND x.action = r.action)
    OR EXISTS (SELECT 1 FROM resources x WHERE (x.plugin IS NULL OR x.plugin = '') AND x.id < r.id AND x.controller = r.controller AND x.action = r.action)
);

SELECT COUNT(*) AS colliding_permissions FROM permissions r
WHERE (r.plugin IS NULL OR r.plugin = '') AND (
    EXISTS (SELECT 1 FROM permissions x WHERE x.plugin = 'App' AND x.role_id = r.role_id AND x.controller = r.controller AND x.action = r.action)
    OR EXISTS (SELECT 1 FROM permissions x WHERE (x.plugin IS NULL OR x.plugin = '') AND x.id < r.id AND x.role_id = r.role_id AND x.controller = r.controller AND x.action = r.action)
);

-- ---------------------------------------------------------------------------
-- 2. Cleanup: SQLite and PostgreSQL (the DELETE can reference its own table in a subquery).
-- ---------------------------------------------------------------------------
DELETE FROM resources WHERE (plugin IS NULL OR plugin = '') AND (
    EXISTS (SELECT 1 FROM resources x WHERE x.plugin = 'App' AND x.controller = resources.controller AND x.action = resources.action)
    OR EXISTS (SELECT 1 FROM resources x WHERE (x.plugin IS NULL OR x.plugin = '') AND x.id < resources.id AND x.controller = resources.controller AND x.action = resources.action)
);

DELETE FROM permissions WHERE (plugin IS NULL OR plugin = '') AND (
    EXISTS (SELECT 1 FROM permissions x WHERE x.plugin = 'App' AND x.role_id = permissions.role_id AND x.controller = permissions.controller AND x.action = permissions.action)
    OR EXISTS (SELECT 1 FROM permissions x WHERE (x.plugin IS NULL OR x.plugin = '') AND x.id < permissions.id AND x.role_id = permissions.role_id AND x.controller = permissions.controller AND x.action = permissions.action)
);

-- ---------------------------------------------------------------------------
-- 3. Cleanup: MySQL / MariaDB (error 1093 forbids a subquery on the target table, so the
--    keys are materialised in derived tables). Use this section INSTEAD of section 2.
-- ---------------------------------------------------------------------------
-- DELETE FROM resources WHERE (plugin IS NULL OR plugin = '') AND EXISTS (
--     SELECT 1 FROM (SELECT DISTINCT controller, action FROM resources WHERE plugin = 'App') AS a
--     WHERE a.controller = resources.controller AND a.action = resources.action
-- );
-- DELETE FROM resources WHERE (plugin IS NULL OR plugin = '') AND id NOT IN (
--     SELECT keep_id FROM (SELECT MIN(id) AS keep_id FROM resources WHERE (plugin IS NULL OR plugin = '') GROUP BY controller, action) AS k
-- );
-- DELETE FROM permissions WHERE (plugin IS NULL OR plugin = '') AND EXISTS (
--     SELECT 1 FROM (SELECT DISTINCT role_id, controller, action FROM permissions WHERE plugin = 'App') AS a
--     WHERE a.role_id = permissions.role_id AND a.controller = permissions.controller AND a.action = permissions.action
-- );
-- DELETE FROM permissions WHERE (plugin IS NULL OR plugin = '') AND id NOT IN (
--     SELECT keep_id FROM (SELECT MIN(id) AS keep_id FROM permissions WHERE (plugin IS NULL OR plugin = '') GROUP BY role_id, controller, action) AS k
-- );
