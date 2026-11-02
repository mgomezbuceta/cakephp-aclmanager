# Code quality: phpcs, phpstan, types, templates (audit phase 4b)

## Objective
The repository passes `phpcs` (CakePHP standard) and `phpstan` with no baseline, has declared types where it matters, no static `TableRegistry` usage in services, and templates that use valid Bootstrap 5 classes and subresource integrity for CDN assets. No behavior change.

## Baseline (measured on this branch before any change)
- phpcs `--standard=CakePHP src config templates tests`: 140 errors, 30 warnings in 18 files (138 auto-fixable).
- `TableRegistry::getTableLocator()` used in `PermissionService` (3x) and `ResourceScannerService` (1x).
- `templates/layout/default.php` loads Bootstrap 5.3.0 CSS+JS and Font Awesome 6.4.0 from jsDelivr/cdnjs without `integrity`.
- 10 Bootstrap 4 classes (`badge-success|secondary|danger|...`) in templates.
- phpstan is not installed.

## Decisions
- Add `phpcs.xml.dist` (standard `CakePHP`; paths `src`, `config`, `templates`, `tests`; exclude `tmp`, `logs`; keep the migrations' arrays style from the standard) so `composer exec phpcs` needs no arguments. Add composer scripts: `cs-check` (phpcs), `cs-fix` (phpcbf), `stan` (phpstan), `test` (phpunit), `check` (all of them).
- Fix violations: run `phpcbf` first, then fix the rest by hand. Do NOT disable sniffs globally to hide debt; a targeted inline `phpcs:ignore` with a reason is acceptable only for generated/third-party-shaped code and must be reported.
- Add `phpstan/phpstan` to `require-dev` with `phpstan.neon.dist` (paths `src`; `tests` only if clean; `treatPhpDocCommentsAsPhpDoc`). Use the highest level that passes with NO baseline file (aim for 6+; report the level reached and what blocked higher). A Cake-aware extension (`cakedc/cakephp-phpstan`) is allowed if it installs cleanly and meaningfully reduces false positives; otherwise use `ignoreErrors` entries with a precise regex + comment for Cake magic (e.g. dynamic table properties) and report them.
- Types: add `declare(strict_types=1);` to every PHP file lacking it (src, config, tests); type the policy (`AclManagerPolicy`), `isUserAdmin($identity)` (accept `\ArrayAccess|object|null` as appropriate; keep behavior), property/param/return types flagged by phpcs/phpstan; replace `\Cake\Http\Response|null` with `?Response` + `use`; fix `DNFTypeHintFormat` findings.
- Services: replace `TableRegistry::getTableLocator()->get()` with `Cake\ORM\Locator\LocatorAwareTrait` (or constructor injection of the tables with defaults) so tests can swap tables; existing tests that rely on the global locator must still pass; keep the public constructors compatible (`new PermissionService()` / `new ResourceScannerService()` still work).
- composer.json: `cakephp/cakephp` constraint `^5.1` (the code uses `Query::orderBy()`, which does not exist in 5.0); add `ext-intl` and `ext-mbstring` to `require`; keep PHP `>=8.1`. Mention in CHANGELOG (Changed) and in the requirements table of the four guides (parity test must stay green).
- Templates: map Bootstrap 4 badge classes to Bootstrap 5 (`badge-success` -> `text-bg-success`, etc.; check each usage visually in markup: a `.badge` keeps its class); add Subresource Integrity (`integrity="sha384-..." crossorigin="anonymous"`) to every CDN `<link>`/`<script>`. Compute each hash from the real file at the exact pinned URL (download into its OWN new empty directory under the scratchpad, never execute it; `openssl dgst -sha384 -binary | openssl base64 -A`; verify the hash against the official published value when one exists, e.g. the Bootstrap docs hash for 5.3.0 CSS/JS bundle, and say if you could not). Keep versions pinned exactly as now. Add a test that asserts every external stylesheet/script in the layout has an `integrity` attribute. Inline `<style>`/`<script>` blocks stay inline (plugin ships no published assets; deliberate) — add a one-line note in the guides' troubleshooting or customization section only if a CSP is mentioned; otherwise skip.

## TDD
Strict where there is behavior: write the SRI test and a service-injection test first (RED). For mechanical lint fixes the existing 150 tests are the safety net: run the full suite after phpcbf and after each manual batch.

## Tasks
- [x] Q1 phpcs.xml.dist + composer scripts; phpcbf; manual fixes until `phpcs` exits 0 — delegated writer
- [x] Q2 strict_types + type declarations; policy and isUserAdmin typing — delegated writer
- [x] Q3 Locator-aware services (RED test first) — delegated writer
- [x] Q4 phpstan install/config, reach the highest passing level without a baseline — delegated writer
- [x] Q5 composer constraints + guides requirements table (EN/ES) + CHANGELOG — delegated writer
- [x] Q6 Templates: badge classes, SRI + test (RED first) — delegated writer

## Acceptance criteria
- `composer exec phpcs` exits 0; `composer exec phpstan analyse` exits 0 (level reported); `composer exec phpunit` green (>= 150 tests + new).
- No `TableRegistry::getTableLocator` in `src/`.
- Every CDN tag in `templates/layout/default.php` has `integrity` + `crossorigin`.

## Progress / evidence
- Branch `chore/code-quality` stacked on `fix/security-hardening` (PR #8, stacked on #7, stacked on #6).
- Q1: `phpcs.xml.dist` + composer scripts (`cs-check`, `cs-fix`, `stan`, `test`, `check`). Baseline 140 errors / 30 warnings -> `phpcbf` fixed 138; remaining 2 errors and 28 warnings (long lines) fixed by hand. `composer exec phpcs`: 0 errors, 0 warnings, exit 0. No `phpcs:ignore` added.
- Q3 RED: `testServiceUsesTheInjectedTableLocator` and `testScannerUsesTheInjectedTableLocator` failed ("Failed asserting that exception of type RuntimeException is thrown") because the constructors ignored a locator. GREEN: both services use `LocatorAwareTrait` with an optional `?LocatorInterface` constructor argument; no `TableRegistry` left in `src/`.
- Q2: `strict_types` was already present everywhere except `config/app_local.example.php` (added); templates intentionally untouched. `AclManagerPolicy` typed property + docs, reads `role_id` through ArrayAccess and casts request params (no TypeError under strict_types). Controller methods that return `$this->redirect()` are now `?Response`.
- Q6 RED: `LayoutIntegrityTest` failed on the missing `integrity` attribute and on remaining `badge-*` classes. GREEN after adding SRI + `crossorigin="anonymous"` to the 3 CDN tags and mapping badges to `text-bg-*` (layout CSS rules re-targeted to `.badge.text-bg-success|secondary` with `!important`, keeping brand colors).
- Q4: phpstan 2.3.4 + `cakedc/cakephp-phpstan` 4.3.0, level 8, no baseline, `composer exec phpstan -- analyse` exits 0. Level 9 not reached: ~17 `mixed` cast/argument errors from `Configure::read()` and `getParam()`. `ignoreErrors` (2 entries): entity magic properties on `EntityInterface` (scoped to `src/Model/Table/*`, `src/Service/*`) and the `APP` constant.
- Q5: `cakephp/cakephp ^5.1`, `ext-intl`, `ext-mbstring` in `require`; requirements tables of README/INSTALL (EN/ES) and CHANGELOG updated; parity test green.
- Final: phpunit 155 tests / 541 assertions OK (150 before, 5 new), `composer validate` valid, `php -l` clean.
