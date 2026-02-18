# Migration Plan: silverstripe-twig

## Summary

- **Package**: azt3k/silverstripe-twig
- **Type**: B (Silverstripe module)
- **Tier**: 3
- **Risk Level**: Medium-High
- **Estimated Scope**: 8 source files, 5 classes + 2 traits + 1 standalone class, 0 existing tests

## Change Inventory

### Namespace Renames Required

| Old Namespace | New Namespace | Files Affected |
|---|---|---|
| `SilverStripe\View\ViewableData` | `SilverStripe\Model\ModelData` | TwigViewableData.php, TwigRenderer.php, TwigEmail.php, TwigSSGlobals.php |
| `SilverStripe\View\ArrayData` | `SilverStripe\Model\ArrayData` | TwigEmail.php |
| `SilverStripe\ORM\FieldType\DBField` | `SilverStripe\Model\DBField` | TwigEmail.php |
| `SilverStripe\View\SSViewer` | `SilverStripe\View\SSTemplateEngine` (or alternatives) | TwigEmail.php, TwigContainer.php |

### Composer Dependency Changes

| Package | Current Version | Target Version |
|---|---|---|
| `php` | (not specified) | `^8.5` |
| `silverstripe/framework` | `^5` | `^6.0` |
| `silverstripe/admin` | `^2` | `^3.0` |
| `twig/twig` | `>=2` | `^3.14` |
| `pimple/pimple` | `~3` | `~3` (keep) |
| `phpunit/phpunit` | `^9` | `^11.0` |
| `squizlabs/php_codesniffer` | `^3.0` | `^3.0` (keep) |
| `silverstripe/recipe-cms` | (new) | `^6.0` (require-dev) |
| `silverstripe/vendor-plugin` | (new) | `^3.0` (require) |

### API Changes Required

| Pattern | Migration | Files Affected |
|---|---|---|
| `SSViewer::get_templates_by_class()` | Replace with SS6 template resolution API (SSTemplateEngine or ThemeResourceLoader) | TwigEmail.php |
| `SSViewer::get_themes()` | Replace with SS6 theme API | TwigEmail.php |
| `Config::inst()->get('SilverStripe\View\SSViewer', 'theme')` | Update config key to SS6 equivalent (SSTemplateEngine) | TwigContainer.php |
| `ViewableData::config()->uninherited('default_cast')` | Change to `ModelData::config()->uninherited('default_cast')` | TwigSSGlobals.php |
| `extends ViewableData` | `extends ModelData` | TwigViewableData.php |
| `instanceof ViewableData` | `instanceof ModelData` | TwigRenderer.php |
| `ViewableData` type hints | `ModelData` type hints | TwigEmail.php |
| Dead import `IOD\Util\Debug` | Remove unused import | TwigViewableData.php |

### PHP 8.5 Compatibility Fixes

| Issue | Fix | Files Affected |
|---|---|---|
| Implicit nullable: `AbstractPart\|string $body = null` | Change to `AbstractPart\|string\|null $body = null` | TwigEmail.php:109 |
| Missing return type declarations | Add return types to all public/protected methods | TwigContainer.php, TwigController.php, TwigRenderer.php, TwigSSGlobals.php |
| Missing parameter type declarations | Add parameter types to untyped methods | TwigRenderer.php, TwigSSGlobals.php |
| Leading backslash on use statements | Remove `\` prefix from `\SilverStripe\View\...` imports | TwigRenderer.php |

### PHPUnit Migration

| Issue | Fix | Files Affected |
|---|---|---|
| No test suite exists | Write tests from scratch | (new tests/) |
| No phpunit.xml.dist | Create with SS6 bootstrap | (new phpunit.xml.dist) |

### Config Changes

| File | Change Required |
|---|---|
| `_config.php` | No changes needed (conditional Haml integration, no deprecated APIs) |
| `_config/injector.yml` | No changes needed (Injector config is still valid) |
| `composer.json` | Add `allow-plugins`, `autoload-dev`, PHP requirement, SS6 deps |
| `.gitignore` | Add `app/`, `public/`, `.htaccess`, `index.php`, `web.config` |

## Risk Assessment

| Area | Risk | Notes |
|---|---|---|
| Namespace renames | Medium | ViewableData→ModelData affects 4 files; straightforward but pervasive |
| SSViewer API changes | High | SSViewer removed in SS6, replaced by SSTemplateEngine. TwigEmail template resolution and TwigContainer theme config both rely on SSViewer APIs that need SS6 equivalents |
| PHP 8.5 compat | Low | One implicit nullable, some missing type declarations |
| Test creation | High | No existing tests — must write from scratch to reach 80% coverage across 8 files |
| Email class compat | Medium | TwigEmail extends Email — need to verify SS6 Email API compatibility |
| Config changes | Low | _config.php and injector.yml are simple; no deprecated patterns |
| Pimple/Twig compat | Low | Pimple 3 and Twig 3 are already the current versions |

## Migration Steps (Ordered)

### Phase 1: composer.json
- [ ] Add `"php": "^8.5"` to require
- [ ] Update `silverstripe/framework` to `^6.0`
- [ ] Update `silverstripe/admin` to `^3.0`
- [ ] Pin `twig/twig` to `^3.14`
- [ ] Add `silverstripe/vendor-plugin: ^3.0` to require
- [ ] Update `phpunit/phpunit` to `^11.0`
- [ ] Add `silverstripe/recipe-cms: ^6.0` to require-dev
- [ ] Add `allow-plugins` config for `composer/installers`, `silverstripe/vendor-plugin`, `silverstripe/recipe-plugin`
- [ ] Add `autoload-dev` with `classmap` for `app/src/Page.php` and `app/src/PageController.php`
- [ ] Run `composer validate`

### Phase 2: Namespace Renames (4 files)
- [ ] `SilverStripe\View\ViewableData` → `SilverStripe\Model\ModelData` (4 files: TwigViewableData, TwigRenderer, TwigEmail, TwigSSGlobals)
- [ ] `SilverStripe\View\ArrayData` → `SilverStripe\Model\ArrayData` (1 file: TwigEmail)
- [ ] `SilverStripe\ORM\FieldType\DBField` → `SilverStripe\Model\DBField` (1 file: TwigEmail)
- [ ] Remove leading backslash from use statements in TwigRenderer.php
- [ ] Remove dead `use IOD\Util\Debug` import from TwigViewableData.php

### Phase 3: API Changes (2 files, highest risk)
- [ ] Replace `SSViewer::get_templates_by_class()` with SS6 template resolution in TwigEmail.php
- [ ] Replace `SSViewer::get_themes()` with SS6 theme API in TwigEmail.php
- [ ] Update `Config::inst()->get('SilverStripe\View\SSViewer', 'theme')` to SS6 equivalent in TwigContainer.php
- [ ] Update `ViewableData::config()` → `ModelData::config()` in TwigSSGlobals.php
- [ ] Verify Email parent class API compatibility (setBody, send, sendPlain)
- [ ] Remove `use SilverStripe\View\SSViewer` import from TwigEmail.php

### Phase 4: PHP 8.5 Compatibility (all files)
- [ ] Fix implicit nullable: `AbstractPart|string $body = null` → `AbstractPart|string|null $body = null` in TwigEmail.php:109
- [ ] Add return type declarations to methods in TwigContainer.php (extendConfig, addExtension, addShared)
- [ ] Add return type declarations to methods in TwigRenderer.php (renderWith, render, renderTwig, customise, getTwigTemplate, buildTemplatesFromClassName, getTemplateList)
- [ ] Add return type declarations to methods in TwigController.php (handleAction)
- [ ] Add return/param types to TwigSSGlobals.php (__construct, __isset, __get)
- [ ] Add return types to TwigEmail.php methods missing them (removeData, setData)

### Phase 5: Logging Integration
- [ ] Assess if this module needs its own logging (unlikely — it's a templating bridge)
- [ ] If needed, add Monolog 3.2+ with Catch format via YAML config
- [ ] Ensure any error handling uses PSR-3 logging

### Phase 6: Config Updates
- [ ] Update `.gitignore` to add: `app/`, `public/`, `.htaccess`, `index.php`, `web.config`
- [ ] No changes needed to `_config.php` or `_config/injector.yml`

### Phase 7: Test Suite (Silverstripe Best Practices)
- [ ] Create `phpunit.xml.dist` with bootstrap `vendor/silverstripe/framework/tests/bootstrap.php`
- [ ] Add `silverstripe/recipe-cms: ^6.0` to require-dev (provides Page/PageController)
- [ ] Add `silverstripe/recipe-plugin: true` to allow-plugins
- [ ] Add `autoload-dev.classmap` for `app/src/Page.php` and `app/src/PageController.php`
- [ ] Add recipe-generated files to `.gitignore`
- [ ] Create test classes extending `SapphireTest`:
  - `tests/TwigContainerTest.php` — test container setup, config, template paths
  - `tests/TwigRendererTest.php` — test renderWith, render, getTwigTemplate, buildTemplatesFromClassName
  - `tests/TwigEmailTest.php` — test setData, addData, getData, removeData, template rendering
  - `tests/TwigSSGlobalsTest.php` — test global variable loading, __isset, __get
  - `tests/TwigViewableDataTest.php` — test AbsoluteLink, TwigRenderer trait integration
  - `tests/TwigControllerExtensionTest.php` — basic extension wiring test
  - `tests/TwigRendererExtensionTest.php` — basic extension wiring test
- [ ] Use `$usesDatabase = false` for tests that don't need ORM
- [ ] Use `Injector::inst()->registerService()` for mock injection
- [ ] Use `::create()` instead of `new` for SS classes in tests
- [ ] Migrate to PHPUnit 11 syntax (attributes, removed methods)
- [ ] Add `allow-plugins` config to composer.json
- [ ] Regenerate `composer.lock` via `composer update`
- [ ] Achieve 80% line coverage target

## Dependencies

- **Depends on**: No direct catch-oss dependencies (Tier 2 repos are not composer dependencies of this module)
- **Blocks**: ss-twig-elemental (Tier 4), abc-silverstripe (Tier 4), ss-seo (Tier 7)
