# Field tokens

[![Pipeline](https://git.drupalcode.org/project/field_tokens/badges/2.0.x/pipeline.svg)](https://git.drupalcode.org/project/field_tokens/-/pipelines)
[![Test](https://github.com/Decipher/field_tokens/actions/workflows/test.yml/badge.svg?branch=2.0.x)](https://github.com/Decipher/field_tokens/actions/workflows/test.yml?query=branch%3A2.0.x)
[![Coverage](https://codecov.io/gh/Decipher/field_tokens/branch/2.0.x/graph/badge.svg)](https://codecov.io/gh/Decipher/field_tokens/branch/2.0.x)

The Field tokens module adds two additional types of field tokens: Formatted
fields and field properties.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/field_tokens).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/field_tokens).

## Table of contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Formatted field tokens](#formatted-field-tokens)
- [Field property tokens](#field-property-tokens)
- [Entity delta token](#entity-delta-token)
- [Delta specification](#delta-specification)
- [Custom Formatters integration](#custom-formatters-integration)
- [Maintainers](#maintainers)

## Requirements

- Drupal 10 or 11
- PHP 8.2+
- [Token](https://www.drupal.org/project/token)

The following modules are recommended:

- [Custom Formatters](https://www.drupal.org/project/custom_formatters) —
  Provides the HTML + Token formatter engine.

## Installation

1. Download and install via Composer:

   ```bash
   composer require drupal/field_tokens
   ```

1. Enable the module:

   ```bash
   drush en field_tokens
   ```

## Configuration

The module works automatically once enabled. Use the Token module's browser
to explore available tokens or use them directly in content. No configuration
form is needed.

## Formatted field tokens

Formatted Field tokens are tokens allowing one or many field values to be
rendered via the default or specified field formatter.

The format is:

```text
[PREFIX:DELTA(S):FORMATTER:FORMATTER_SETTING_KEY-FORMATTER_SETTING_VALUE:...]
```

e.g. `[node:field_image-formatted:0,1:image:image_style-thumbnail]`

### Nested formatter settings

Some formatters have settings that are themselves arrays (for example the
image formatter's `image_loading`, or Smart Trim's `trim_options`). Use dot
notation in the setting key to set a nested value:

```text
[PREFIX:DELTA(S):FORMATTER:PARENT.CHILD-VALUE:...]
```

| Example | Description |
| ------- | ----------- |
| `[node:field_image-formatted:0:image:image_loading.attribute-eager]` | `image_loading`: `['attribute' => 'eager']` |
| `[paragraph:field_body-formatted:0:smart_trim:trim_length-200:trim_options.text-text]` | Flat + nested settings |

## Field property tokens

Field property tokens are tokens allowing access to field properties on
one or many fields.

Properties are dependent on the field type.

The format is:

```text
[PREFIX:DELTA(S):PROPERTY]
```

e.g. `[node:field_image-property:0:entity:url]`

### Array property access

When a field property stores an array (e.g., structured data, tablefield cells),
additional colon-separated segments after the property name traverse into the
value using each segment as a key:

```text
[PREFIX:DELTA(S):PROPERTY:KEY:KEY:...]
```

| Example | Description |
| ------- | ----------- |
| `[node:field_table-property:0:value:2:1]` | Integer keys — row 2, column 1 |
| `[node:field_data-property:0:value:key]` | String key on associative array |
| `[node:field_data-property:0:value:0:nested]` | Multi-level nesting |

Both integer and string keys are supported. Scalar leaf values (`int`, `float`,
`bool`) are cast to string. If the resolved leaf is still an array, no output is
produced.

## Entity delta token

A `delta` token is available on every entity token type (e.g. `[file:delta]`,
`[node:delta]`, `[taxonomy_term:delta]`). It returns the zero-based position of
the entity within its parent multi-value field.

The delta is provided at runtime by the calling code — for example,
[filefield_paths](https://www.drupal.org/project/filefield_paths) passes each
file's position when building filename patterns:

```text
[node:title]-[file:delta].[file:ffp-extension-original]
```

For a three-image field this produces `my-title-0.png`, `my-title-1.png`,
`my-title-2.png`.

In a [Custom Formatters](https://www.drupal.org/project/custom_formatters)
HTML+Token formatter, the current item's delta is injected automatically into
both the direct and property-chain forms:

```text
[file:delta]
[field_property:entity:delta]
```

When no delta is available the token returns an empty string. Resolving
multiple items (via `*`, a range, or an omitted delta) produces a
comma-separated list (`"0, 1, 2"`).

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

## Maintainers

- Stuart Clark - [deciphered](https://www.drupal.org/u/deciphered)
- Dave Nattriss - [natts](https://www.drupal.org/u/natts)
