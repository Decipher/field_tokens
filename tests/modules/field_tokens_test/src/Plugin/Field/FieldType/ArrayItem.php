<?php

declare(strict_types=1);

namespace Drupal\field_tokens_test\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines a field type whose main property stores an array.
 *
 * Used to test nested array key traversal in field property tokens.
 *
 * @FieldType(
 *   id = "field_tokens_test_array",
 *   label = @Translation("Test array field"),
 *   category = @Translation("Field Tokens"),
 *   default_widget = "string_textarea",
 *   default_formatter = "string",
 *   no_ui = TRUE,
 * )
 */
class ArrayItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('any')
      ->setLabel(t('Array value'))
      ->setDescription(t('Stores an array accessible via property token key traversal.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '' || $value === [];
  }

}
