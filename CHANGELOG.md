# Changelog

## 2.0.0 (2026-07-08)

First stable release of the 2.0.x line.

No new changes since 2.0.0-rc4. See the 2.0.0-rc4 entry below for the full
changelog of features, bug fixes, and improvements included in this release.

## 2.0.0-rc4 (2026-06-18)

### Features

- [#3267442](https://www.drupal.org/project/field_tokens/issues/3267442):
  Support ranges, wildcards, and omitted deltas in field tokens, e.g.
  `[node:field_name-formatted:1:3]`, `[node:field_name-formatted:*]`, or
  `[node:field_name-formatted]` with configurable delimiter.
- [#2826615](https://www.drupal.org/project/field_tokens/issues/2826615):
  Support nested array key access in property tokens using dot notation
  (e.g. `value.nested_key`).
- [#3143597](https://www.drupal.org/project/field_tokens/issues/3143597):
  Support dot-notation for nested formatter settings in formatted field tokens.
- [#3438368](https://www.drupal.org/project/field_tokens/issues/3438368):
  Drupal 11 compatibility.
- Re-implemented Custom Formatters token data integration for Drupal 8+,
  restoring `[formatted_field-*]` tokens for Custom Formatters formatter types.

### Bug fixes

- [#2386655](https://www.drupal.org/project/field_tokens/issues/2386655):
  Add entity delta token (`[file:delta]`, `[node:delta]`, etc.) for multi-value
  field position, with Custom Formatters integration.
- [#3135092](https://www.drupal.org/project/field_tokens/issues/3135092):
  Handle formatter settings without dash-value pairs gracefully.
- [#3135128](https://www.drupal.org/project/field_tokens/issues/3135128):
  Add entity_type guard and language-aware token replacement.
- [#3438368](https://www.drupal.org/project/field_tokens/issues/3438368):
  Handle missing chained token key and formatter setting delimiter edge cases.
- [#3438368](https://www.drupal.org/project/field_tokens/issues/3438368):
  Handle missing chained entity reference token key.
- [#3464190](https://www.drupal.org/project/field_tokens/issues/3464190):
  Guard against null field_types in formatter definitions.
- Fixed Token module `field_property` flag conflict in `hook_token_info_alter()`
  data.
- [#2705841](https://www.drupal.org/project/field_tokens/issues/2705841)
  by jfrederick: Use `EntityInterface::bundle()` instead of deprecated
  `$entity->getType()`.
- Extended token support to all entities, not just fieldable entities.
- [#3266313](https://www.drupal.org/project/field_tokens/issues/3266313)
  by priyanka_chauhan31: Fixed phpcs violations.

### Documentation

- [#2756539](https://www.drupal.org/project/field_tokens/issues/2756539):
  Clarified field token description placeholders.
- Updated README.md to Drupal.org template format.

### Developer experience

- [#3592047](https://www.drupal.org/project/field_tokens/issues/3592047):
  Added GitLab CI pipeline, fixed PHPStan 2.x errors, updated phpunit config,
  restored Codecov coverage.
- Applied drupal extension scaffold (4.15.0).
- Added markdown linting to CI and `make lint` target.
- Added `declare(strict_types=1)` to all source files and resolved PHPStan
  violations.
- Migrated functional tests and added comprehensive kernel test suite.

## 2.0.0-rc3 (2026-01-26)

- Fixed deprecated `renderPlain()` call — updated to `renderInIsolation()` for
  formatted field tokens.
- Updated `core_version_requirement` to `^10.1 || ^11`.
- Added default theme to tests.
- Removed duplicate semi-colon.

## 2.0.0-rc2 (2023-07-10)

- [#3287540](https://www.drupal.org/project/field_tokens/issues/3287540):
  Drupal 10 compatibility.

## 2.0.0-rc1 (2021-05-14)

- Drupal 9 compatibility.

## 8.x-1.x-dev

- Added DCIR testing.
- [#2751155](https://www.drupal.org/project/field_tokens/issues/2751155)
  by lukasss: Fixed issue with field settings delimiter.

## 8.x-1.0-beta1 (2016-02-05)

- Initial Drupal 8 port.
- Added chaining for Field property tokens.

## 7.x-1.4 (2015-08-04)

- Added Travis CI integration.
- [#2543548](https://www.drupal.org/project/field_tokens/issues/2543548)
  by jesss: Fixed undefined index `module` in `field_default_prepare_view()`.

## 7.x-1.3 (2015-07-27)

- Fixed issue with hidden fields.

## 7.x-1.2 (2015-07-10)

- Fixed dynamic tokens.

## 7.x-1.1 (2015-06-30)

- Added chained tokens.
- Added Custom Formatters integration.

## 7.x-1.0 (2015-06-28)

- Added messages regarding Formatted Field Tokens module.
- Fixed issue with formatted tokens.

## 7.x-1.0-beta1 (2015-06-27)

- Initial release.
