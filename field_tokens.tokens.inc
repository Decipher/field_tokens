<?php

/**
 * @file
 * Token functions for Field tokens module.
 */

declare(strict_types=1);

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\field\Entity\FieldConfig;

/**
 * Implements hook_token_info().
 */
function field_tokens_token_info(): array {
  /** @var \Drupal\Core\Field\FieldTypePluginManager $field_type_manager */
  $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
  $field_types = $field_type_manager->getDefinitions();

  /** @var \Drupal\Core\Field\FormatterPluginManager $formatter_manager */
  $formatter_manager = \Drupal::service('plugin.manager.field.formatter');
  $formatters = $formatter_manager->getDefinitions();

  /** @var \Drupal\field\FieldConfigStorage $field_config_storage */
  $field_config_storage = \Drupal::entityTypeManager()
    ->getStorage('field_config');
  $fields = $field_config_storage->loadMultiple();

  /** @var \Drupal\token\TokenEntityMapper $token_entity_mapper */
  $token_entity_mapper = \Drupal::service('token.entity_mapper');

  $types = [];
  $tokens = [];

  // Build token types for each field type.
  foreach ($field_types as $field_type_name => $field_type_info) {
    // Build tokens for each formatter of the current field type.
    foreach ($formatters as $formatter_name => $formatter_info) {
      if (!empty($formatter_info['field_types']) && in_array($field_type_name, $formatter_info['field_types'])) {
        $tokens['formatted_field-' . $field_type_name][$formatter_name] = [
          'name'        => $formatter_info['label'],
          'description' => t('@label formatter.', ['@label' => $formatter_info['label']]),
          'dynamic'     => TRUE,
        ];

        // Adjust token name and description if formatter has no settings.
        $default_settings = $formatter_manager->getDefaultSettings($formatter_name);
        if (empty($default_settings)) {
          unset($tokens['formatted_field-' . $field_type_name][$formatter_name]['dynamic']);
        }

        // Append settings list to description.
        else {
          $settings = [];
          foreach ($default_settings as $key => $default) {
            $settings[] = $key;
          }
          $tokens['formatted_field-' . $field_type_name][$formatter_name]['description'] .= ' ' . t("Pass settings as 'SETTING-VALUE' pairs joined by ':'; 'SETTING' alone for a valueless flag. Available settings: @settings", ['@settings' => implode(', ', $settings)]);
          // Mention dot notation only for formatters with array-valued
          // settings (e.g. image_loading, trim_options), keeping the
          // per-formatter description short in the token tree.
          if (array_filter($default_settings, is_array(...))) {
            $tokens['formatted_field-' . $field_type_name][$formatter_name]['description'] .= ' ' . t("Use dots in the key for nested settings (e.g. 'image_loading.attribute-eager').");
          }
        }
      }
    }

    if (!empty($tokens['formatted_field-' . $field_type_name])) {
      $types['formatted_field-' . $field_type_name] = [
        'name'        => t('Formatted @label field', ['@label' => $field_type_info['label']]),
        'description' => t('Tokens related to Formatted @label fields.', ['@label' => $field_type_info['label']]),
        'needs-data'  => 'formatted_field-' . $field_type_name,
      ];
    }

    // Build tokens for each property of the current field type.
    /** @var \Drupal\field\Entity\FieldConfig $field */
    foreach ($fields as $field) {
      if ($field->getType() == $field_type_name) {
        $properties = $field->getFieldStorageDefinition()
          ->getPropertyDefinitions();

        /** @var \Drupal\Core\TypedData\DataDefinition $data_definition */
        foreach ($properties as $property_name => $data_definition) {
          $tokens['field_property-' . $field_type_name][$property_name] = [
            'name'        => $data_definition->getLabel(),
            'description' => t('@label property.', ['@label' => $data_definition->getLabel()]),
          ];

          if ($data_definition instanceof DataReferenceDefinition) {
            $tokens['field_property-' . $field_type_name][$property_name]['dynamic'] = TRUE;
            // Normalize the constraint value. Drupal 11.4+ uses named arguments
            // for constraints, so EntityType is stored as ['type' => $value]
            // instead of a bare string.
            // @see https://www.drupal.org/node/3554746
            /** @var string|array|null $entity_type_constraint */
            $entity_type_constraint = $data_definition->getConstraint('EntityType');
            if (is_array($entity_type_constraint) && isset($entity_type_constraint['type'])) {
              $entity_type_constraint = $entity_type_constraint['type'];
            }
            if (is_string($entity_type_constraint)) {
              $token_type = $token_entity_mapper->getTokenTypeForEntityType($entity_type_constraint);
              $tokens['field_property-' . $field_type_name][$property_name]['dynamic'] = FALSE;
              $tokens['field_property-' . $field_type_name][$property_name]['type'] = $token_type;
            }
          }
        }
      }
    }

    if (!empty($tokens['field_property-' . $field_type_name])) {
      $types['field_property-' . $field_type_name] = [
        'name'        => t('@label field properties', ['@label' => $field_type_info['label']]),
        'description' => t('Properties of the @label fields.', ['@label' => $field_type_info['label']]),
        'needs-data'  => 'field_property-' . $field_type_name,
      ];
    }
  }

  return [
    'types'  => $types,
    'tokens' => $tokens,
  ];
}

/**
 * Implements hook_token_info_alter().
 */
function field_tokens_token_info_alter(array &$data): void {
  /** @var \Drupal\Core\Entity\EntityTypeBundleInfo $bundle_info */
  $bundle_info = \Drupal::service('entity_type.bundle.info');

  /** @var \Drupal\Core\Entity\EntityFieldManager $entity_field_manager */
  $entity_field_manager = \Drupal::service('entity_field.manager');

  /** @var \Drupal\token\TokenEntityMapper $token_entity_mapper */
  $token_entity_mapper = \Drupal::service('token.entity_mapper');
  $token_entity_map = $token_entity_mapper->getEntityTypeMappings();

  // Add a delta token to every registered entity token type. The delta is the
  // zero-based position of the entity within its parent multi-value field and
  // is only populated when the caller passes it in token data (e.g.
  // filefield_paths for [file:delta], or field_tokens' own property chaining).
  foreach ($token_entity_map as $token_type) {
    if (isset($data['tokens'][$token_type])) {
      $data['tokens'][$token_type]['delta'] = [
        'name'        => t('Delta'),
        'description' => t('The delta (position) of this entity within its parent multi-value field. Only available when the calling code passes the delta in token data.'),
      ];
    }
  }
  foreach ($token_entity_map as $entity_type => $token_type) {
    if (isset($data['tokens'][$token_type])) {
      $bundles = $bundle_info->getBundleInfo($entity_type);
      foreach (array_keys($bundles) as $bundle) {
        try {
          $fields = $entity_field_manager->getFieldDefinitions($entity_type, $bundle);
          foreach ($fields as $field_name => $field) {
            if ($field instanceof FieldConfig && isset($data['tokens'][$token_type][$field_name])) {
              // Formatted field tokens.
              $data['tokens'][$token_type][$field_name . '-formatted'] = [
                'name'        => t('@label: Formatted field', ['@label' => $field->label()]),
                'description' => t("The formatted value of one or more @label field values. The full token is '[entity:field_name-formatted:DELTA:FORMATTER:SETTING-VALUE:...]'. Replace DELTA with a single delta (e.g. '0'), a comma separated list (e.g. '0,1'), a range (e.g. '0-3'), or a combination (e.g. '0-2,4,6-8'); use '*' or omit it for all values. FORMATTER is a field formatter machine name and the optional SETTING-VALUE pairs pass formatter settings.", ['@label' => $field->label()]),
                'type'        => 'formatted_field-' . $field->getType(),
                'dynamic'     => TRUE,
              ];

              // Field property tokens.
              $data['tokens'][$token_type][$field_name . '-property'] = [
                'name'        => t('@label: Field properties', ['@label' => $field->label()]),
                'description' => t("Field properties from one or more @label field values. The full token is '[entity:field_name-property:DELTA:PROPERTY]'. Replace DELTA with a single delta (e.g. '0'), a comma separated list (e.g. '0,1'), a range (e.g. '0-3'), or a combination (e.g. '0-2,4,6-8'); use '*' or omit it for all values. PROPERTY is a field property such as 'value', 'target_id', or 'alt'.", ['@label' => $field->label()]),
                'type'        => 'field_property-' . $field->getType(),
                'dynamic'     => TRUE,
              ];
            }
          }
        }
        catch (Exception) {
          // @todo Find a better way to prevent errors when loading field
          // definitions.
        }
      }
    }
  }
}

/**
 * Implements hook_tokens().
 */
function field_tokens_tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  /** @var \Drupal\token\Token $token_service */
  $token_service = \Drupal::token();
  /** @var \Drupal\Core\Field\FormatterPluginManager $formatter_manager */
  $formatter_manager = \Drupal::service('plugin.manager.field.formatter');

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()
      ->getLanguage($options['langcode']);
  }
  $replacements = [];

  $token_types = [
    'formatted' => [
      'token_type' => 'formatted_field',
      'field_type' => TRUE,
    ],
    'property'  => [
      'token_type' => 'field_property',
      'field_type' => FALSE,
    ],
  ];

  // Entity tokens.
  if ($type === 'entity' && !empty($data['entity_type']) && !empty($data['entity']) && $data['entity'] instanceof FieldableEntityInterface) {
    $entity = $data['entity'];
    if (!isset($options['langcode'])) {
      // Set the active language in $options, so that it is passed along.
      $options['langcode'] = $entity->language()->getId();
    }
    // Obtain the entity with the correct language.
    $entity = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $options['langcode']);

    $fields = $entity->getFieldDefinitions();

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
    foreach ($fields as $field_name => $field) {
      if ($field instanceof FieldConfig) {
        foreach ($token_types as $token_type => $token_type_info) {
          $field_tokens = $token_service->findWithPrefix($tokens, sprintf('%s-%s', $field_name, $token_type));
          if ($field_tokens) {
            $token_data_type = $token_type_info['field_type'] ? sprintf('%s-%s', $token_type_info['token_type'], $field->getType()) : $token_type_info['token_type'];
            foreach ($field_tokens as $name => $original) {
              /** @var \Drupal\Core\Field\FieldItemList<\Drupal\Core\Field\FieldItemInterface> $items */
              $items = $entity->{$field_name};
              if (!$items->isEmpty()) {
                $parts = explode(':', (string) $name);
                $field_keys = array_keys($items->getValue());
                $deltas = [];

                // Check if first segment is a wildcard for "all deltas".
                if ($parts[0] === '*') {
                  array_shift($parts);
                  $deltas = $field_keys;
                }
                // Check if first segment is a delta specification
                // (e.g., '0', '0-2', '0,2,4', '0-2,4,6-8').
                elseif (preg_match('/^\d+(?:-\d+)?(?:,\d+(?:-\d+)?)*$/', $parts[0])) {
                  foreach (explode(',', $parts[0]) as $segment) {
                    if (preg_match('/^(\d+)-(\d+)$/', $segment, $matches)) {
                      $start = (int) $matches[1];
                      $end = (int) $matches[2];
                      array_push($deltas, ...range($start, $end));
                    }
                    else {
                      $deltas[] = (int) $segment;
                    }
                  }

                  // Validate all requested deltas exist.
                  if (array_diff($deltas, $field_keys)) {
                    continue;
                  }

                  array_shift($parts);
                }
                else {
                  // No delta specified — use all items.
                  // The first segment is the formatter/property name,
                  // so we leave $parts intact for chained processing.
                  $deltas = $field_keys;
                }

                $token_items = [];
                $token_deltas = [];
                foreach ($deltas as $delta) {
                  $item = $items->get($delta);
                  if ($item !== NULL) {
                    $token_items[] = $item;
                    $token_deltas[] = $delta;
                  }
                }

                foreach ($token_items as $token_item) {
                  if ($token_item->isEmpty()) {
                    continue(2);
                  }
                }

                // Pass through to chained field tokens.
                $field_key = $data['entity_type'] . '-' . $field_name;
                $chained_data = array_merge($data, [
                  ($token_type === 'property' ? '_field_tokens_items' : $token_data_type) => $token_items,
                  'field'             => $field,
                  'field_name'        => $field_key,
                  '_field_tokens_deltas' => $token_deltas,
                ]);
                $chained_data[$field_key] = $token_items;

                $replacements += $token_service->generate($token_data_type, [implode(':', $parts) => $original], $chained_data, $options, $bubbleable_metadata);
              }
            }
          }
        }
      }
    }
  }

  // Formatted field tokens.
  elseif (str_starts_with($type, 'formatted_field') && isset($data[$type]) && isset($data['entity_type']) && isset($data['entity'])) {
    /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
    $entity = $data['entity'];

    /** @var \Drupal\field\Entity\FieldConfig $field */
    $field = $data['field'];
    $field_type = \Drupal::service('plugin.manager.field.field_type')
      ->getDefinition($field->getType());

    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $view_mode */
    $view_mode = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.default');
    $display = $view_mode->getComponent($field->getName()) ?? [];
    $display['field_definition'] = $field;
    $display['view_mode'] = 'default';

    $items = $data[$type];
    $formatters = $formatter_manager->getDefinitions();

    foreach ($tokens as $args => $original) {
      $args = explode(':', (string) $args);
      $formatter_name = $field_type['default_formatter'];
      $formatter_settings = [];
      // Determine the formatter name. If the first segment is non-empty, it
      // is a custom formatter. If it is empty, use the default formatter but
      // still consume the segment so remaining args are parsed as settings.
      if ($args[0] !== '') {
        $formatter_name = array_shift($args);
      }
      else {
        array_shift($args);
      }

      // Parse all remaining segments as formatter settings. See
      // field_tokens_parse_formatter_settings() for the supported syntax,
      // including dot notation for nested array settings.
      $formatter_settings = field_tokens_parse_formatter_settings($args);

      if (!is_null($formatter_name) && isset($formatters[$formatter_name]) && !empty($formatters[$formatter_name]['field_types']) && in_array($field_type['id'], $formatters[$formatter_name]['field_types'])) {
        $default_settings = $formatter_manager->getDefaultSettings($formatter_name);
        if (!empty($default_settings)) {
          $formatter_settings = array_replace_recursive($default_settings, $formatter_settings);
        }

        // Inject formatter and formatter settings into $display.
        $display['type'] = $formatter_name;
        $display['settings'] = $formatter_settings;

        // Clone entity.
        $cloned_entity = clone $entity;

        foreach ($items as &$item) {
          $item = $item->getValue();
        }
        $cloned_entity->{$field->getName()}->setValue($items);

        $output = '';
        $element = $cloned_entity->{$field->getName()}->view($display);
        if ($element) {
          foreach (Element::children($element) as $delta) {
            $output .= \Drupal::service('renderer')
              ->renderInIsolation($element[$delta]);
          }
        }
        if (!empty($output)) {
          $replacements[$original] = Markup::create($output);
        }
      }
    }
  }

  // Field property tokens.
  elseif ($type === 'field_property') {
    /** @var \Drupal\field\Entity\FieldConfig $field */
    $field = $data['field'];

    foreach ($tokens as $args => $original) {
      $output = [];

      $args = explode(':', (string) $args);
      $property = array_shift($args);
      /** @var Drupal\Core\Field\FieldItemBase $item */
      foreach (($data['_field_tokens_items'] ?? []) as $idx => $item) {
        $delta = ($data['_field_tokens_deltas'] ?? [])[$idx] ?? $idx;
        $properties = $field->getFieldStorageDefinition()
          ->getPropertyDefinitions();
        if (isset($properties[$property]) && $properties[$property] instanceof DataReferenceDefinition && !empty($args)) {
          $reference = $item->get($property)->getValue();
          if ($reference instanceof EntityInterface) {
            /** @var \Drupal\token\TokenEntityMapper $token_entity_mapper */
            $token_entity_mapper = \Drupal::service('token.entity_mapper');

            $token_type = $token_entity_mapper->getTokenTypeForEntityType($reference->getEntityTypeId());
            $chained_data = [
              $token_type => $reference,
              'delta'     => $delta,
            ];
            $result = $token_service->generate($token_type, [implode(':', $args) => $original], $chained_data, $options, $bubbleable_metadata);
            if (isset($result[$original])) {
              $output[] = $result[$original];
            }
          }
        }
        elseif (isset($properties[$property])) {
          $value = $item->get($property)->getValue();
          if (!empty($args) && is_array($value)) {
            foreach ($args as $arg) {
              if (is_array($value) && array_key_exists($arg, $value)) {
                $value = $value[$arg];
              }
              else {
                $value = NULL;
                break;
              }
            }
          }
          elseif (!empty($args)) {
            $value = NULL;
          }
          if (is_string($value)) {
            $output[] = $value;
          }
          elseif (is_scalar($value)) {
            $output[] = (string) $value;
          }
        }
      }

      if (!empty($output)) {
        $replacements[$original] = Markup::create(implode(', ', $output));
      }
    }
  }

  // Entity delta token — works for any entity token type (file, node, term…).
  elseif (isset($data[$type]) && $data[$type] instanceof EntityInterface && array_key_exists('delta', $data)) {
    foreach ($tokens as $name => $original) {
      if ($name === 'delta') {
        $replacements[$original] = (string) $data['delta'];
      }
    }
  }

  return $replacements;
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
