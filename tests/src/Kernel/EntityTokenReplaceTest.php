<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Kernel;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests entity-level token replacement and edge cases.
 *
 * @group field_tokens
 */
class EntityTokenReplaceTest extends FieldTokensKernelTestBase {

  /**
   * Tests that non-fieldable entities do not trigger token processing.
   *
   * Regression test for issue #2835032.
   */
  public function testNonFieldableEntityNoProcessing(): void {
    $bubbleable = new BubbleableMetadata();
    $node_type = NodeType::load('page');

    $tokens = [
      static::FIELD_NAME . '-formatted:0:text_default' => '[node:' . static::FIELD_NAME . '-formatted:0:text_default]',
    ];
    $result = \Drupal::moduleHandler()->invoke('field_tokens', 'tokens', ['entity', $tokens, ['entity' => $node_type], [], $bubbleable]);

    $this->assertEmpty($result);
  }

  /**
   * Tests that non-FieldConfig fields are skipped.
   */
  public function testNonFieldConfigFieldsSkipped(): void {
    $node = $this->createNodeWithText([['value' => 'Test', 'format' => 'plain_text']]);

    $bubbleable = new BubbleableMetadata();
    $tokens = [
      'title-formatted:0:text_default' => '[node:title-formatted:0:text_default]',
    ];
    $result = \Drupal::moduleHandler()->invoke('field_tokens', 'tokens', ['entity', $tokens, ['entity' => $node, 'entity_type' => 'node', 'token_type' => 'node'], [], $bubbleable]);

    $this->assertEmpty($result);
  }

  /**
   * Tests that invalid deltas produce no replacement.
   */
  public function testInvalidDeltaNoReplacement(): void {
    $node = $this->createNodeWithText([['value' => 'Only delta 0', 'format' => 'plain_text']]);

    $bubbleable = new BubbleableMetadata();
    $tokens = [
      static::FIELD_NAME . '-formatted:5:text_default' => '[node:' . static::FIELD_NAME . '-formatted:5:text_default]',
    ];
    $result = \Drupal::moduleHandler()->invoke('field_tokens', 'tokens', ['entity', $tokens, ['entity' => $node, 'entity_type' => 'node', 'token_type' => 'node'], [], $bubbleable]);

    $this->assertEmpty($result);
  }

  /**
   * Tests that empty field items produce no replacement.
   */
  public function testEmptyFieldItemsNoReplacement(): void {
    $node = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'uid' => 1,
    ]);
    $node->save();

    $bubbleable = new BubbleableMetadata();
    $tokens = [
      static::FIELD_NAME . '-formatted:0:text_default' => '[node:' . static::FIELD_NAME . '-formatted:0:text_default]',
    ];
    $result = \Drupal::moduleHandler()->invoke('field_tokens', 'tokens', ['entity', $tokens, ['entity' => $node, 'entity_type' => 'node', 'token_type' => 'node'], [], $bubbleable]);

    $this->assertEmpty($result);
  }

  /**
   * Tests that delta values are cast to integers.
   */
  public function testDeltaCastToInt(): void {
    $node = $this->createNodeWithText([['value' => 'Delta zero', 'format' => 'plain_text']]);

    $result = \Drupal::token()->replace(
      '[node:' . static::FIELD_NAME . '-formatted:0:text_default]',
      ['node' => $node]
    );

    $this->assertStringContainsString('Delta zero', (string) $result);
  }

  /**
   * Tests that the entity path uses bundle() method.
   *
   * Regression test for issue #2705841.
   */
  public function testEntityUsesBundleMethod(): void {
    $node = $this->createNodeWithImage();

    $result = \Drupal::token()->replace(
      '[node:' . static::IMAGE_FIELD_NAME . '-formatted:0:image]',
      ['node' => $node]
    );

    $this->assertNotEmpty($result);
    $this->assertStringContainsString('<img', (string) $result);
  }

  /**
   * Tests formatted and property tokens on the same entity.
   */
  public function testMixedTokenTypesOnSameEntity(): void {
    $node = $this->createNodeWithText([['value' => 'Mixed test', 'format' => 'plain_text']]);

    $text = '[node:' . static::FIELD_NAME . '-formatted:0:text_default] and [node:' . static::FIELD_NAME . '-property:0:value]';
    $result = \Drupal::token()->replace($text, ['node' => $node]);

    $this->assertStringContainsString('Mixed test', (string) $result);
  }

  /**
   * Tests entity token with no entity data returns empty.
   */
  public function testNoEntityDataReturnsEmpty(): void {
    $bubbleable = new BubbleableMetadata();
    $tokens = [
      static::FIELD_NAME . '-formatted:0:text_default' => '[node:' . static::FIELD_NAME . '-formatted:0:text_default]',
    ];
    $result = \Drupal::moduleHandler()->invoke('field_tokens', 'tokens', ['entity', $tokens, [], [], $bubbleable]);

    $this->assertEmpty($result);
  }

  /**
   * Tests entity token with wrong type returns empty.
   */
  public function testWrongTypeReturnsEmpty(): void {
    $node = $this->createNodeWithText([['value' => 'Test', 'format' => 'plain_text']]);

    $bubbleable = new BubbleableMetadata();
    $tokens = [
      static::FIELD_NAME . '-formatted:0:text_default' => '[node:' . static::FIELD_NAME . '-formatted:0:text_default]',
    ];
    $result = \Drupal::moduleHandler()->invoke('field_tokens', 'tokens', ['user', $tokens, ['entity' => $node], [], $bubbleable]);

    $this->assertEmpty($result);
  }

  /**
   * Tests multiple fields on same entity work independently.
   */
  public function testMultipleFieldsOnSameEntity(): void {
    $node = $this->createNodeWithText([['value' => 'Text field value', 'format' => 'plain_text']]);

    $text_result = \Drupal::token()->replace(
      '[node:' . static::FIELD_NAME . '-property:0:value]',
      ['node' => $node]
    );

    $this->assertEquals('Text field value', $text_result);
  }

}
