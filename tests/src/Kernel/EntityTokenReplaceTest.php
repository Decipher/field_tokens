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

  /**
   * Tests that omitting the delta renders all field values.
   */
  public function testNoDeltaReturnsAllValues(): void {
    $node = $this->createNodeWithText([
      ['value' => 'First', 'format' => 'plain_text'],
      ['value' => 'Second', 'format' => 'plain_text'],
    ]);

    $result = \Drupal::token()->replace(
      '[node:' . static::FIELD_NAME . '-formatted:text_default]',
      ['node' => $node]
    );

    $this->assertStringContainsString('First', (string) $result);
    $this->assertStringContainsString('Second', (string) $result);
  }

  /**
   * Tests that the * wildcard renders all field values.
   */
  public function testWildcardDeltaReturnsAllValues(): void {
    $node = $this->createNodeWithText([
      ['value' => 'Alpha', 'format' => 'plain_text'],
      ['value' => 'Beta', 'format' => 'plain_text'],
    ]);

    $result = \Drupal::token()->replace(
      '[node:' . static::FIELD_NAME . '-formatted:*:text_default]',
      ['node' => $node]
    );

    $this->assertStringContainsString('Alpha', (string) $result);
    $this->assertStringContainsString('Beta', (string) $result);
  }

  /**
   * Tests that a delta range expands to the correct values.
   */
  public function testDeltaRangeReturnsCorrectValues(): void {
    $node = $this->createNodeWithText([
      ['value' => 'Zero', 'format' => 'plain_text'],
      ['value' => 'One', 'format' => 'plain_text'],
      ['value' => 'Two', 'format' => 'plain_text'],
    ]);

    $result = \Drupal::token()->replace(
      '[node:' . static::FIELD_NAME . '-formatted:0-2:text_default]',
      ['node' => $node]
    );

    $this->assertStringContainsString('Zero', (string) $result);
    $this->assertStringContainsString('One', (string) $result);
    $this->assertStringContainsString('Two', (string) $result);
  }

  /**
   * Tests that a mixed range and list syntax works correctly.
   */
  public function testDeltaMixedRangeAndList(): void {
    $node = $this->createNodeWithText([
      ['value' => 'A', 'format' => 'plain_text'],
      ['value' => 'B', 'format' => 'plain_text'],
      ['value' => 'C', 'format' => 'plain_text'],
      ['value' => 'D', 'format' => 'plain_text'],
      ['value' => 'E', 'format' => 'plain_text'],
    ]);

    // Select deltas 0, 2, 3, 4 (using range 2-4 + single 0).
    $result = \Drupal::token()->replace(
      '[node:' . static::FIELD_NAME . '-formatted:0,2-4:text_default]',
      ['node' => $node]
    );

    $this->assertStringContainsString('A', (string) $result);
    $this->assertStringNotContainsString('B', (string) $result);
    $this->assertStringContainsString('C', (string) $result);
    $this->assertStringContainsString('D', (string) $result);
    $this->assertStringContainsString('E', (string) $result);
  }

}
