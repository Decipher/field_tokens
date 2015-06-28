<?php

/**
 * @file
 * Token functions for Field tokens module.
 */

/**
 * Implements hook_token_info_alter().
 */
function field_tokens_token_info_alter(&$data) {
  $token_info = _token_field_info();

  foreach ($token_info as $field_name => $info) {
    foreach ($info['token types'] as $token_type) {
      if (isset($data['tokens'][$token_type])) {
        // Formatted field tokens.
        $data['tokens'][$token_type]["{$field_name}-formatted:DELTA(S):?"] = array(
          'name'        => t('Formatted @label field', array('@label' => $info['label'])),
          'description' => t('The Formatted Field token provides one or many field values rendered via the default or specified field formatter.<br />The format is [PREFIX:DELTA(S):FORMATTER:FORMATTER_SETTING_KEY-FORMATTER_SETTING_VALUE:...] (e.g. [node:field_image:0,1:image:image_style-thumbnail]).'),
        );

        // Field property tokens.
        $data['tokens'][$token_type]["{$field_name}-property:DELTA:?"] = array(
          'name'        => t('@label field properties', array('@label' => $info['label'])),
          'description' => t('The Field Property token provides raw value of a specified field property.<br />The format is [PREFIX:DELTA:PROPERTY] (e.g. [node:field_image:0:uri]).'),
        );
      }
    }
  }
}

/**
 * Implements hook_tokens().
 */
function field_tokens_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $url_options   = array('absolute' => TRUE);
  $language_code = NULL;
  if (isset($options['language'])) {
    $url_options['language'] = $options['language'];
    $language_code           = $options['language']->language;
  }
  $sanitize = !empty($options['sanitize']);

  $token_info   = _token_field_info();
  $replacements = array();

  if ($type == 'entity' && !empty($data['entity'])) {
    $entity      = $data['entity'];
    $entity_type = $data['entity_type'];
    list($id, $vid, $bundle_name) = entity_extract_ids($entity_type, $data['entity']);
    $instances = field_info_instances($entity_type, $bundle_name);

    $token_types = array('formatted', 'property');
    foreach ($token_info as $field_name => $token_field_info) {
      if (isset($instances[$field_name])) {
        $instance = $instances[$field_name];
        foreach ($token_types as $token_type) {
          $field_tokens = token_find_with_prefix($tokens, "{$field_name}-{$token_type}");
          if ($field_tokens) {
            foreach ($field_tokens as $name => $original) {
              $parts  = explode(':', $name);
              $deltas = explode(',', array_shift($parts));

              // Field info.
              $field    = field_info_field($field_name);
              $langcode = field_language($entity_type, $entity, $field_name);
              $items    = field_get_items($entity_type, $entity, $field_name);

              if (!empty($parts) && !empty($items)) {
                // Ensure that all referenced deltas exist.
                $diff = array_diff(array_values($deltas), array_keys($items));
                if (empty($diff)) {
                  $token_items = array();
                  foreach ($deltas as $delta) {
                    $token_items[] = $items[$delta];
                  }

                  // Invoke MODULE_field_load().
                  $function = "{$field['module']}_field_load";
                  if (function_exists($function)) {
                    $items = array($token_items);
                    $function($entity_type, array($entity), $field, array($instance), $langcode, $items, FIELD_LOAD_CURRENT);
                    $token_items = $items[0];
                  }

                  // Invoke MODULE_field_is_empty().
                  foreach ($token_items as $item) {
                    $function = "{$field['module']}_field_is_empty";
                    if ((function_exists($function) && $function($item, $field))) {
                      continue(2);
                    }

                    $function = "field_tokens_{$token_type}_tokens";
                    if (function_exists($function)) {
                      $result = $function($entity, $instance, $token_items, $parts);
                      if ($result) {
                        $replacements[$original] = $result;
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  return $replacements;
}

/**
 * Tokens callback for 'formatted' field tokens.
 *
 * @param $entity
 * @param $instance
 * @param $items
 * @param $args
 *
 * @return bool|string
 */
function field_tokens_formatted_tokens($entity, $instance, $items, $args) {
  if (!empty($args)) {
    $output = '';

    $formatter          = array_shift($args);
    $formatter_settings = array();
    foreach ($args as $formatter_setting) {
      list($name, $value) = explode('-', $formatter_setting);
      $formatter_settings[$name] = $value;
    }
    if (!empty($formatters[$formatter]['settings'])) {
      $formatter_settings += $formatters[$formatter]['settings'];
    }

    $entity_type = $instance['entity_type'];
    $field_name  = $instance['field_name'];
    $field       = field_info_field($field_name);
    $langcode    = field_language($entity_type, $entity, $field_name);
    $display     = $instance['display']['default'];

    $formatters = field_info_formatter_types();
    if (!is_null($formatter) && isset($formatters[$formatter]) && in_array($field['type'], $formatters[$formatter]['field types'])) {
      $display['type']     = $formatter;
      $display['settings'] = isset($formatter_settings) ? $formatter_settings : $formatters[$formatter]['settings'];
    }

    // Clone entitiy and attach specified items.
    $cloned_entity                           = clone $entity;
    $cloned_entity->{$field_name}[$langcode] = $items;

    // Render formatted field.
    $element = field_view_field($entity_type, $cloned_entity, $field_name, $display, $langcode);
    if ($element) {
      foreach (element_children($element) as $delta) {
        $output .= render($element[$delta]);
      }
    }
    if (!empty($output)) {
      return $output;
    }
  }

  return FALSE;
}

/**
 * Tokens callback for 'property' field tokens.
 *
 * @param $entity
 * @param $instance
 * @param $items
 * @param $args
 *
 * @return bool|string
 */
function field_tokens_property_tokens($entity, $instance, $items, $args) {
  if (!empty($args)) {
    $output = array();

    $property = array_shift($args);
    foreach ($items as $item) {
      if (isset($item[$property])) {
        $output[] = $item[$property];
      }
    }

    if (!empty($output)) {
      return implode(', ', $output);
    }
  }

  return FALSE;
}
