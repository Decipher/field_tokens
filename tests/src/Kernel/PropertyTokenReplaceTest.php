<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Kernel;

use Drupal\node\Entity\Node;

/**
 * Tests field property token replacement.
 *
 * @group field_tokens
 */
class PropertyTokenReplaceTest extends FieldTokensKernelTestBase {

  /**
   * Tests property token extracts text value.
   */
  public function testPropertyTokenExtractsValue(): void {
    $node = $this->createNodeWithText([['value' => 'Test value', 'format' => 'plain_text']]);

    $token = '[node:' . static::FIELD_NAME . '-property:0:value]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertEquals('Test value', $result);
  }

  /**
   * Tests property token extracts format.
   */
  public function testPropertyTokenExtractsFormat(): void {
    $node = $this->createNodeWithText([['value' => 'Test value', 'format' => 'plain_text']]);

    $token = '[node:' . static::FIELD_NAME . '-property:0:format]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertEquals('plain_text', $result);
  }

  /**
   * Tests property token extracts image target_id.
   */
  public function testPropertyTokenExtractsImageTargetId(): void {
    $node = $this->createNodeWithImage();

    $token = '[node:' . static::IMAGE_FIELD_NAME . '-property:0:target_id]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $expected = (string) $node->get(static::IMAGE_FIELD_NAME)->target_id;
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests property token extracts image alt text.
   */
  public function testPropertyTokenExtractsImageAlt(): void {
    $node = $this->createNodeWithImage();

    $token = '[node:' . static::IMAGE_FIELD_NAME . '-property:0:alt]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertEquals('Test image', $result);
  }

  /**
   * Tests property token with multiple items returns comma-separated.
   */
  public function testPropertyTokenMultipleItems(): void {
    $node = $this->createNodeWithText([
      ['value' => 'First', 'format' => 'plain_text'],
      ['value' => 'Second', 'format' => 'plain_text'],
    ]);

    $token = '[node:' . static::FIELD_NAME . '-property:0,1:value]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertEquals('First, Second', $result);
  }

  /**
   * Tests property token returns original for invalid property.
   */
  public function testPropertyTokenInvalidProperty(): void {
    $node = $this->createNodeWithText([['value' => 'Test', 'format' => 'plain_text']]);

    $token = '[node:' . static::FIELD_NAME . '-property:0:nonexistent]';
    $result = \Drupal::token()->replace($token, ['node' => $node], ['clear' => TRUE]);

    $this->assertEquals('', $result);
  }

  /**
   * Tests property token returns original for empty field.
   */
  public function testPropertyTokenEmptyField(): void {
    $node = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'uid' => 1,
    ]);
    $node->save();

    $token = '[node:' . static::FIELD_NAME . '-property:0:value]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertEquals($token, $result);
  }

  /**
   * Tests property token with chained entity reference.
   */
  public function testPropertyTokenChainedEntityReference(): void {
    $node = $this->createNodeWithImage();

    $token = '[node:' . static::IMAGE_FIELD_NAME . '-property:0:entity:fid]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    if ($result !== $token) {
      $file = $node->get(static::IMAGE_FIELD_NAME)->entity;
      $this->assertEquals((string) $file->id(), $result);
    }
    else {
      $this->fail("Token was not replaced: $token");
    }
  }

  /**
   * Tests property token without delta returns all values.
   */
  public function testPropertyTokenNoDelta(): void {
    $node = $this->createNodeWithText([
      ['value' => 'First', 'format' => 'plain_text'],
      ['value' => 'Second', 'format' => 'plain_text'],
    ]);

    $token = '[node:' . static::FIELD_NAME . '-property:value]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertEquals('First, Second', $result);
  }

  /**
   * Tests image property token without delta returns all target IDs.
   */
  public function testPropertyImageNoDelta(): void {
    $node = $this->createNodeWithMultipleImages(3);

    $token = '[node:' . static::IMAGE_FIELD_NAME . '-property:target_id]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertNotEmpty($result);
    $target_ids = explode(', ', (string) $result);
    $this->assertCount(3, $target_ids);
  }

  /**
   * Tests image property token with range syntax.
   */
  public function testPropertyImageRange(): void {
    $node = $this->createNodeWithMultipleImages(3);

    $token = '[node:' . static::IMAGE_FIELD_NAME . '-property:0-1:target_id]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertNotEmpty($result);
    $target_ids = explode(', ', (string) $result);
    $this->assertCount(2, $target_ids);
  }

  /**
   * Tests property token with wildcard.
   */
  public function testPropertyImageWildcard(): void {
    $node = $this->createNodeWithMultipleImages(3);

    $token = '[node:' . static::IMAGE_FIELD_NAME . '-property:*:target_id]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertNotEmpty($result);
    $target_ids = explode(', ', (string) $result);
    $this->assertCount(3, $target_ids);
  }

  /**
   * Tests array property token with a single integer key.
   */
  public function testPropertyTokenArrayIntegerKey(): void {
    $node = $this->createNodeWithArray([
      ['value' => ['a', 'b', 'c']],
    ]);

    $token = '[node:' . static::ARRAY_FIELD_NAME . '-property:0:value:1]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertEquals('b', $result);
  }

  /**
   * Tests array property token with multi-level nested keys.
   */
  public function testPropertyTokenArrayNestedKeys(): void {
    $node = $this->createNodeWithArray([
      ['value' => [['a', 'b'], ['c', 'd']]],
    ]);

    $token = '[node:' . static::ARRAY_FIELD_NAME . '-property:0:value:1:0]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertEquals('c', $result);
  }

  /**
   * Tests array property token with a string key.
   */
  public function testPropertyTokenArrayStringKey(): void {
    $node = $this->createNodeWithArray([
      ['value' => ['first' => 'alpha', 'second' => 'beta']],
    ]);

    $token = '[node:' . static::ARRAY_FIELD_NAME . '-property:0:value:second]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertEquals('beta', $result);
  }

  /**
   * Tests array property token with a non-existent key produces no output.
   */
  public function testPropertyTokenArrayInvalidKey(): void {
    $node = $this->createNodeWithArray([
      ['value' => ['a', 'b']],
    ]);

    $token = '[node:' . static::ARRAY_FIELD_NAME . '-property:0:value:5]';
    $result = \Drupal::token()->replace($token, ['node' => $node], ['clear' => TRUE]);

    $this->assertEquals('', $result);
  }

  /**
   * Tests array property token casts a scalar (int) leaf to string.
   */
  public function testPropertyTokenArrayScalarLeaf(): void {
    $node = $this->createNodeWithArray([
      ['value' => [42, 99]],
    ]);

    $token = '[node:' . static::ARRAY_FIELD_NAME . '-property:0:value:0]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertEquals('42', $result);
  }

  /**
   * Tests array property token produces no output when the leaf is an array.
   */
  public function testPropertyTokenArrayLeafIsArray(): void {
    $node = $this->createNodeWithArray([
      ['value' => ['key' => ['nested' => 'val']]],
    ]);

    $token = '[node:' . static::ARRAY_FIELD_NAME . '-property:0:value:key]';
    $result = \Drupal::token()->replace($token, ['node' => $node], ['clear' => TRUE]);

    $this->assertEquals('', $result);
  }

  /**
   * Tests array property token with no key segments drops array values.
   */
  public function testPropertyTokenArrayNoSegmentsDropped(): void {
    $node = $this->createNodeWithArray([
      ['value' => ['a', 'b']],
    ]);

    $token = '[node:' . static::ARRAY_FIELD_NAME . '-property:0:value]';
    $result = \Drupal::token()->replace($token, ['node' => $node], ['clear' => TRUE]);

    $this->assertEquals('', $result);
  }

  /**
   * Tests array property token partial mismatch (string leaf, further key).
   */
  public function testPropertyTokenArrayPartialMismatch(): void {
    $node = $this->createNodeWithArray([
      ['value' => ['a', 'b']],
    ]);

    $token = '[node:' . static::ARRAY_FIELD_NAME . '-property:0:value:0:deep]';
    $result = \Drupal::token()->replace($token, ['node' => $node], ['clear' => TRUE]);

    $this->assertEquals('', $result);
  }

  /**
   * Tests array property token across multiple items.
   */
  public function testPropertyTokenArrayMultipleItems(): void {
    $node = $this->createNodeWithArray([
      ['value' => ['first', 'second']],
      ['value' => ['third', 'fourth']],
    ]);

    $token = '[node:' . static::ARRAY_FIELD_NAME . '-property:0,1:value:0]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertEquals('first, third', $result);
  }

}
