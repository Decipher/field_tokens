<?php

declare(strict_types=1);

namespace Drupal\field_tokens\Hook;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for field_tokens.
 */
class FieldTokensHooks {

  /**
   * Implements hook_custom_formatters_token_data_alter().
   */
  #[Hook('custom_formatters_token_data_alter')]
  public static function customFormattersTokenDataAlter(array &$token_data, array $context): void {
    $item = $context['item'];
    if (!$item instanceof FieldItemInterface) {
      return;
    }

    $field_definition = $item->getFieldDefinition();
    $field_type = $field_definition->getType();
    $entity = $item->getEntity();

    $token_data['formatted_field-' . $field_type] = [$item];
    $token_data['_field_tokens_items'] = [$item];
    if (isset($context['delta'])) {
      $token_data['_field_tokens_deltas'] = [$context['delta']];
      $token_data['delta'] = $context['delta'];
    }
    $token_data['entity'] = $entity;
    $token_data['entity_type'] = $entity->getEntityTypeId();
    $token_data['field'] = $field_definition;
    $token_data['field_name'] = $entity->getEntityTypeId() . '-' . $field_definition->getName();
    $token_data[$token_data['field_name']] = [$item];
  }

}
