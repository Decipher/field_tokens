<?php

/**
 * @file
 * Token functions for Field tokens module.
 */

declare(strict_types=1);

use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\field_tokens\Hook\FieldTokensTokensHooks;

/**
 * Implements hook_token_info().
 */
#[LegacyHook]
function field_tokens_token_info(): array {
  return \Drupal::service(FieldTokensTokensHooks::class)->tokenInfo();
}

/**
 * Implements hook_token_info_alter().
 */
#[LegacyHook]
function field_tokens_token_info_alter(array &$data): void {
  \Drupal::service(FieldTokensTokensHooks::class)->tokenInfoAlter($data);
}

/**
 * Implements hook_tokens().
 */
#[LegacyHook]
function field_tokens_tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  return \Drupal::service(FieldTokensTokensHooks::class)->tokens($type, $tokens, $data, $options, $bubbleable_metadata);
}

/**
 * Parses formatter setting segments into a settings array.
 *
 * Each segment is a colon-separated part of the token following the formatter
 * name. Supported forms:
 * - 'key-value': a flat key/value pair.
 * - 'key': a valueless flag, parsed as NULL.
 * - 'parent.child-value': a nested array setting, where dots in the key build
 *   array depth. This supports formatters whose settings are themselves arrays
 *   (e.g. the image formatter's 'image_loading.attribute' or Smart Trim's
 *   'trim_options.text'). Dots are safe to repurpose here because no core or
 *   contrib formatter uses dots in setting key names.
 *
 * @param string[] $args
 *   The setting segments.
 *
 * @return array<string,mixed>
 *   The parsed formatter settings.
 */
function field_tokens_parse_formatter_settings(array $args): array {
  $settings = [];
  foreach ($args as $arg) {
    if ($arg === '') {
      continue;
    }
    [$name, $value] = str_contains($arg, '-')
      ? explode('-', $arg, 2)
      : [$arg, NULL];
    if (!str_contains((string) $name, '.')) {
      $settings[$name] = $value;
      continue;
    }
    // Dot notation: build a nested array from the leaf up, then recursively
    // merge it so sibling nested keys accumulate rather than overwrite.
    $keys = explode('.', (string) $name);
    $nested = [$keys[count($keys) - 1] => $value];
    for ($i = count($keys) - 2; $i >= 0; $i--) {
      $nested = [$keys[$i] => $nested];
    }
    $settings = array_replace_recursive($settings, $nested);
  }
  return $settings;
}
