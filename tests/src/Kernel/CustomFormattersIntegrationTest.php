<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Kernel;

/**
 * Tests custom_formatters token data integration.
 *
 * @group field_tokens
 */
class CustomFormattersIntegrationTest extends FieldTokensKernelTestBase {

  /**
   * Tests that the alter adds formatted_field-{type} context.
   */
  public function testAlterAddsFormattedFieldContext(): void {
    $node = $this->createNodeWithImage();
    $item = $node->get(static::IMAGE_FIELD_NAME)[0];

    $file = $node->get(static::IMAGE_FIELD_NAME)->entity;
    $token_data = ['node' => $node, 'file' => $file];
    $context = ['text' => '', 'item' => $item, 'delta' => 0];
    \Drupal::moduleHandler()->alter('custom_formatters_token_data', $token_data, $context);

    $this->assertArrayHasKey('formatted_field-image', $token_data);
    $this->assertIsArray($token_data['formatted_field-image']);
    $this->assertCount(1, $token_data['formatted_field-image']);
  }

  /**
   * Tests that the alter adds field_property context.
   */
  public function testAlterAddsFieldPropertyContext(): void {
    $node = $this->createNodeWithImage();
    $item = $node->get(static::IMAGE_FIELD_NAME)[0];
    $file = $node->get(static::IMAGE_FIELD_NAME)->entity;

    $token_data = ['node' => $node, 'file' => $file];
    $context = ['text' => '', 'item' => $item, 'delta' => 0];
    \Drupal::moduleHandler()->alter('custom_formatters_token_data', $token_data, $context);

    $this->assertArrayHasKey('_field_tokens_items', $token_data);
    $this->assertIsArray($token_data['_field_tokens_items']);
    $this->assertCount(1, $token_data['_field_tokens_items']);
  }

  /**
   * Tests that the alter adds entity and entity_type context.
   */
  public function testAlterAddsEntityContext(): void {
    $node = $this->createNodeWithImage();
    $item = $node->get(static::IMAGE_FIELD_NAME)[0];
    $file = $node->get(static::IMAGE_FIELD_NAME)->entity;

    $token_data = ['node' => $node, 'file' => $file];
    $context = ['text' => '', 'item' => $item, 'delta' => 0];
    \Drupal::moduleHandler()->alter('custom_formatters_token_data', $token_data, $context);

    $this->assertSame($node, $token_data['entity']);
    $this->assertEquals('node', $token_data['entity_type']);
  }

  /**
   * Tests that the alter adds field definition and field_name context.
   */
  public function testAlterAddsFieldDefinition(): void {
    $node = $this->createNodeWithImage();
    $item = $node->get(static::IMAGE_FIELD_NAME)[0];
    $field_definition = $item->getFieldDefinition();
    $file = $node->get(static::IMAGE_FIELD_NAME)->entity;

    $token_data = ['node' => $node, 'file' => $file];
    $context = ['text' => '', 'item' => $item, 'delta' => 0];
    \Drupal::moduleHandler()->alter('custom_formatters_token_data', $token_data, $context);

    $this->assertSame($field_definition, $token_data['field']);
    $this->assertEquals('node-field_test_image', $token_data['field_name']);
  }

  /**
   * Tests that the alter adds field_name-keyed items for token module.
   */
  public function testAlterAddsFieldNameKeyedItems(): void {
    $node = $this->createNodeWithImage();
    $item = $node->get(static::IMAGE_FIELD_NAME)[0];
    $file = $node->get(static::IMAGE_FIELD_NAME)->entity;

    $token_data = ['node' => $node, 'file' => $file];
    $context = ['text' => '', 'item' => $item, 'delta' => 0];
    \Drupal::moduleHandler()->alter('custom_formatters_token_data', $token_data, $context);

    $field_name_key = 'node-' . static::IMAGE_FIELD_NAME;
    $this->assertArrayHasKey($field_name_key, $token_data);
    $this->assertIsArray($token_data[$field_name_key]);
  }

  /**
   * Tests that the alter does nothing for non-FieldItemInterface items.
   */
  public function testAlterDoesNothingForNonFieldItem(): void {
    $token_data = ['node' => 'not_an_entity'];
    $context = ['text' => '', 'item' => 'not_a_field_item', 'delta' => 0];
    \Drupal::moduleHandler()->alter('custom_formatters_token_data', $token_data, $context);

    $this->assertArrayNotHasKey('formatted_field-image', $token_data);
    $this->assertArrayNotHasKey('_field_tokens_items', $token_data);
    $this->assertArrayNotHasKey('entity', $token_data);
    $this->assertArrayNotHasKey('field', $token_data);
  }

  /**
   * Tests that the alter works with text field types.
   */
  public function testAlterWorksWithTextField(): void {
    $node = $this->createNodeWithText([['value' => 'Hello', 'format' => 'plain_text']]);
    $item = $node->get(static::FIELD_NAME)[0];

    $token_data = ['node' => $node];
    $context = ['text' => '', 'item' => $item, 'delta' => 0];
    \Drupal::moduleHandler()->alter('custom_formatters_token_data', $token_data, $context);

    $this->assertArrayHasKey('formatted_field-text', $token_data);
    $this->assertArrayHasKey('_field_tokens_items', $token_data);
    $this->assertEquals('text', $token_data['field']->getType());
  }

  /**
   * Tests formatted field token resolution via entity-scoped path.
   *
   * Regression test: the alter hook's field_property key must not cause the
   * Token module's field_property handler to interfere with formatted_field
   * token resolution.
   */
  public function testFormattedFieldTokenViaEntityWithAlterContext(): void {
    $node = $this->createNodeWithImage();
    $item = $node->get(static::IMAGE_FIELD_NAME)[0];

    $token_data = ['node' => $node, 'file' => $node->get(static::IMAGE_FIELD_NAME)->entity];
    $context = ['text' => '', 'item' => $item, 'delta' => 0];
    \Drupal::moduleHandler()->alter('custom_formatters_token_data', $token_data, $context);

    $result = \Drupal::token()->replace(
      '[node:' . static::IMAGE_FIELD_NAME . '-formatted:0:image:image_style-thumbnail]',
      $token_data,
      ['clear' => TRUE],
    );

    $this->assertStringContainsString('<img', $result);
    $this->assertStringContainsString('/styles/thumbnail/', $result);
  }

  /**
   * Tests property token resolution via entity-scoped path with alter context.
   */
  public function testPropertyTokenViaEntityWithAlterContext(): void {
    $node = $this->createNodeWithImage();
    $item = $node->get(static::IMAGE_FIELD_NAME)[0];

    $token_data = ['node' => $node, 'file' => $node->get(static::IMAGE_FIELD_NAME)->entity];
    $context = ['text' => '', 'item' => $item, 'delta' => 0];
    \Drupal::moduleHandler()->alter('custom_formatters_token_data', $token_data, $context);

    $result = \Drupal::token()->replace(
      '[node:' . static::IMAGE_FIELD_NAME . '-property:0:alt]',
      $token_data,
      ['clear' => TRUE],
    );

    $this->assertEquals('Test image', $result);
  }

  /**
   * Tests end-to-end token replacement using alter-provided context.
   */
  public function testTokenReplacementViaAlterContext(): void {
    $node = $this->createNodeWithImage();
    $item = $node->get(static::IMAGE_FIELD_NAME)[0];

    $token_data = ['node' => $node, 'file' => $node->get(static::IMAGE_FIELD_NAME)->entity];
    $context = ['text' => '', 'item' => $item, 'delta' => 0];
    \Drupal::moduleHandler()->alter('custom_formatters_token_data', $token_data, $context);

    $result = \Drupal::token()->replace('[field_property:alt]', $token_data, ['clear' => TRUE]);

    $this->assertEquals('Test image', $result);
  }

}
