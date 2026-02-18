# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Changed
- Upgraded to Silverstripe 6 compatibility (`silverstripe/framework: ^6.0`, `silverstripe/admin: ^3.0`)
- Updated PHP requirement to `^8.5`
- Migrated test suite to PHPUnit 11
- `ViewableData` references updated to `ModelData` (SS6 rename)
- `ArrayData` import updated to `SilverStripe\Model\ArrayData`
- `SSViewer` theme config updated from singular `theme` to `SSViewer::get_themes()`
- `TEMP_FOLDER` constant replaced with `TEMP_PATH` (SS6 rename)
- `renderWith()` now returns `DBHTMLText` to match SS6 `ModelData` parent signature
- Twig compilation cache path uses `TEMP_PATH` instead of `TEMP_FOLDER`
- Added `twig/cache-extra: ^3.23` dependency for `CacheExtension`/`CacheRuntime`
- Added `silverstripe/vendor-plugin: ^3.0` dependency (required by SS6)

### Added
- Complete test suite: 96 tests, 122 assertions, 82% line coverage
- `phpunit.xml.dist` with SS6 framework bootstrap
- Test fixtures for Twig template rendering
- MIGRATION-PLAN.md documenting all SS6 changes

### Fixed
- PHP 8.5 compatibility: implicit nullable parameter in `TwigEmail::setBody()`
- Added return type declarations across all source files
- Added parameter type declarations to untyped methods
- Removed dead `IOD\Util\Debug` import from `TwigViewableData`
- Removed leading backslash from use statements in `TwigRenderer`
- Fixed `getTemplateList()` to properly wrap string templates in arrays
- Fixed `buildTemplatesFromClassName()` to handle null `ClassName` property
