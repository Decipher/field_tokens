# Field tokens

[![Test](https://github.com/Decipher/field_tokens/actions/workflows/test.yml/badge.svg?branch=2.0.x)](https://github.com/Decipher/field_tokens/actions/workflows/test.yml?query=branch%3A2.0.x)
[![Coverage](https://codecov.io/gh/Decipher/field_tokens/branch/2.0.x/graph/badge.svg)](https://codecov.io/gh/Decipher/field_tokens/branch/2.0.x)

The Field tokens module adds two additional types of field tokens:
Formatted fields and field properties.

## Formatted field tokens

Formatted Field tokens are tokens allowing one or many field values to be
rendered via the default or specified field formatter.

The format is:

```text
[PREFIX:DELTA(S):FORMATTER:FORMATTER_SETTING_KEY-FORMATTER_SETTING_VALUE:...]
```

e.g. `[node:field_image-formatted:0,1:image:image_style-thumbnail]`

## Field property tokens

Field property tokens are tokens allowing access to field properties on
one or many fields.

Properties are dependent on the field type.

The format is:

```text
[PREFIX:DELTA(S):PROPERTY]
```

e.g. `[node:field_image-property:0:entity:url]`

## Delta specification

The `DELTA(S)` position supports multiple formats for selecting which field
item values to render:

| Format | Example | Description |
| ------- | ------- | ----------- |
| Single delta | `0` | A single field item |
| Comma-separated | `0,2,4` | Specific field items |
| Range | `0-2` | A contiguous range of field items (0, 1, 2) |
| Mixed | `0-2,4,6-8` | Combination of ranges and single deltas |
| Wildcard | `*` | All field items |
| Omitted | _(none)_ | All field items (delta position empty) |

### Examples

```text
# Single value
[node:field_image-formatted:0:image]
[node:field_image-property:0:target_id]

# Multiple specific values
[node:field_image-formatted:0,2,4:image]

# Range of values
[node:field_image-formatted:0-3:image:image_style-thumbnail]

# Mixed range and list
[node:field_image-property:0-2,4:target_id]

# All values (wildcard)
[node:field_image-formatted:*:image]

# All values (omit delta)
[node:field_image-formatted:image]
[node:field_image-property:target_id]
```

## Custom Formatters integration

Field tokens integrates with the
[Custom Formatters](https://www.drupal.org/project/custom_formatters) module
via `hook_custom_formatters_token_data_alter()`, providing the token data
context needed for the HTML + Token formatter engine.

When both modules are enabled, tokens like
`[formatted_field-image:image:...]` and `[field_property:alt]` can be used
directly in HTML + Token formatters without entity-level chaining.

## Requirements

- Drupal 10 or 11
- PHP 8.2+
- [Token](https://www.drupal.org/project/token)

## Recommended modules

- [Custom Formatters](https://www.drupal.org/project/custom_formatters) --
  Provides the HTML + Token formatter engine.

## Testing

This project includes a Makefile and
[Ahoy](https://ahoy-cli.readthedocs.io/)-based development environment.

```bash
make build       # Build the development environment
make provision   # Install Drupal
make test        # Run all PHPUnit tests
make lint        # Run PHPCS, PHPStan, Rector, and Twig CS Fixer
```
