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
   * Tests image property token with wildcard.
   */
  public function testPropertyImageWildcard(): void {
    $node = $this->createNodeWithMultipleImages(3);

    $token = '[node:' . static::IMAGE_FIELD_NAME . '-property:*:target_id]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertNotEmpty($result);
    $target_ids = explode(', ', (string) $result);
    $this->assertCount(3, $target_ids);
  }

}
