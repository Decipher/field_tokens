<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Kernel;

use Drupal\node\Entity\Node;

/**
 * Tests formatted field token replacement.
 *
 * @group field_tokens
 */
class FormattedTokenReplaceTest extends FieldTokensKernelTestBase {

  /**
   * Tests formatted token with text_default formatter.
   */
  public function testFormattedTokenWithTextDefault(): void {
    $node = $this->createNodeWithText([['value' => 'Hello World', 'format' => 'plain_text']]);

    $token = '[node:' . static::FIELD_NAME . '-formatted:0:text_default]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertStringContainsString('Hello World', (string) $result);
  }

  /**
   * Tests formatted token with text_trimmed formatter and settings.
   */
  public function testFormattedTokenWithTrimmedFormatter(): void {
    $node = $this->createNodeWithText([['value' => 'This is a longer text value for trimming', 'format' => 'plain_text']]);

    $token = '[node:' . static::FIELD_NAME . '-formatted:0:text_trimmed:trim_length-10]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertStringContainsString('This is', (string) $result);
  }

  /**
   * Tests formatted token with default formatter when no formatter specified.
   */
  public function testFormattedTokenWithDefaultFormatter(): void {
    $node = $this->createNodeWithText([['value' => 'Default formatter test', 'format' => 'plain_text']]);

    $token = '[node:' . static::FIELD_NAME . '-formatted:0:]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertStringContainsString('Default formatter test', (string) $result);
  }

  /**
   * Tests formatted token with image formatter.
   */
  public function testFormattedTokenWithImageFormatter(): void {
    $node = $this->createNodeWithImage();

    $token = '[node:' . static::IMAGE_FIELD_NAME . '-formatted:0:image]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertNotEmpty($result);
    $this->assertStringContainsString('<img', (string) $result);
  }

  /**
   * Tests formatted token returns original for empty field.
   */
  public function testFormattedTokenEmptyField(): void {
    $node = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'uid' => 1,
    ]);
    $node->save();

    $token = '[node:' . static::FIELD_NAME . '-formatted:0:text_default]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertEquals($token, $result);
  }

  /**
   * Tests formatted token with multiple deltas.
   */
  public function testFormattedTokenMultipleDeltas(): void {
    $node = $this->createNodeWithText([
      ['value' => 'First value', 'format' => 'plain_text'],
      ['value' => 'Second value', 'format' => 'plain_text'],
    ]);

    $token = '[node:' . static::FIELD_NAME . '-formatted:0,1:text_default]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertStringContainsString('First value', (string) $result);
    $this->assertStringContainsString('Second value', (string) $result);
  }

  /**
   * Tests formatted token with invalid delta returns original.
   */
  public function testFormattedTokenInvalidDelta(): void {
    $node = $this->createNodeWithText([['value' => 'Only one value', 'format' => 'plain_text']]);

    $token = '[node:' . static::FIELD_NAME . '-formatted:5:text_default]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertEquals($token, $result);
  }

  /**
   * Tests that formatted token output matches direct field render.
   */
  public function testFormattedTokenMatchesDirectRender(): void {
    $node = $this->createNodeWithText([['value' => 'Render comparison', 'format' => 'plain_text']]);

    $token = '[node:' . static::FIELD_NAME . '-formatted:0:text_default]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $element = $node->get(static::FIELD_NAME)->view([
      'type' => 'text_default',
      'label' => 'hidden',
      'settings' => [],
    ]);
    $direct_output = (string) \Drupal::service('renderer')->renderRoot($element[0]);

    $this->assertEquals(trim($direct_output), trim((string) $result));
  }

  /**
   * Tests formatted token without delta renders all values.
   */
  public function testFormattedTokenNoDelta(): void {
    $node = $this->createNodeWithText([
      ['value' => 'First', 'format' => 'plain_text'],
      ['value' => 'Second', 'format' => 'plain_text'],
    ]);

    $token = '[node:' . static::FIELD_NAME . '-formatted:text_default]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertStringContainsString('First', (string) $result);
    $this->assertStringContainsString('Second', (string) $result);
  }

  /**
   * Tests formatted token with range syntax renders correct values.
   */
  public function testFormattedTokenRange(): void {
    $node = $this->createNodeWithText([
      ['value' => 'One', 'format' => 'plain_text'],
      ['value' => 'Two', 'format' => 'plain_text'],
      ['value' => 'Three', 'format' => 'plain_text'],
    ]);

    $token = '[node:' . static::FIELD_NAME . '-formatted:0-1:text_default]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertStringContainsString('One', (string) $result);
    $this->assertStringContainsString('Two', (string) $result);
    $this->assertStringNotContainsString('Three', (string) $result);
  }

  /**
   * Tests formatted image token without delta renders all values.
   */
  public function testFormattedImageNoDelta(): void {
    $node = $this->createNodeWithMultipleImages(3);

    $token = '[node:' . static::IMAGE_FIELD_NAME . '-formatted:image]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertNotEmpty($result);
    $this->assertStringContainsString('<img', (string) $result);
  }

  /**
   * Tests formatted image token with range syntax.
   */
  public function testFormattedImageRange(): void {
    $node = $this->createNodeWithMultipleImages(3);

    $token = '[node:' . static::IMAGE_FIELD_NAME . '-formatted:0-1:image]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertNotEmpty($result);
    $this->assertStringContainsString('<img', (string) $result);
  }

  /**
   * Tests formatted image token with wildcard.
   */
  public function testFormattedImageWildcard(): void {
    $node = $this->createNodeWithMultipleImages(3);

    $token = '[node:' . static::IMAGE_FIELD_NAME . '-formatted:*:image]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertNotEmpty($result);
    $this->assertStringContainsString('<img', (string) $result);
  }

  /**
   * Tests formatted token with a setting that has no value (no dash).
   */
  public function testFormattedTokenValuelessSetting(): void {
    $node = $this->createNodeWithText([['value' => 'Valueless setting test', 'format' => 'plain_text']]);

    $token = '[node:' . static::FIELD_NAME . '-formatted:0:text_default:link_to_entity]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertStringContainsString('Valueless setting test', (string) $result);
  }

  /**
   * Tests formatted token with mixed valueless and valued settings.
   */
  public function testFormattedTokenMixedSettings(): void {
    $node = $this->createNodeWithText([['value' => 'Mixed settings test content', 'format' => 'plain_text']]);

    $token = '[node:' . static::FIELD_NAME . '-formatted:0:text_trimmed:trim_length-10:link_to_entity]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertStringContainsString('Mixed', (string) $result);
    $this->assertStringNotContainsString('content', (string) $result);
  }

  /**
   * Tests formatted token with default formatter and valueless setting.
   */
  public function testFormattedTokenDefaultFormatterWithValuelessSetting(): void {
    $node = $this->createNodeWithText([['value' => 'Default formatter with setting', 'format' => 'plain_text']]);

    $token = '[node:' . static::FIELD_NAME . '-formatted:0::link_to_entity]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertStringContainsString('Default formatter with setting', (string) $result);
  }

  /**
   * Tests formatted token with trailing colon ignores empty setting.
   */
  public function testFormattedTokenTrailingColon(): void {
    $node = $this->createNodeWithText([['value' => 'Trailing colon test', 'format' => 'plain_text']]);

    $token = '[node:' . static::FIELD_NAME . '-formatted:0:text_default:]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertStringContainsString('Trailing colon test', (string) $result);
  }

  /**
   * Tests formatted image token with mixed range and list syntax.
   */
  public function testFormattedImageMixedRange(): void {
    $node = $this->createNodeWithMultipleImages(5);

    // Select deltas 0, 2, 3, 4 (using range 2-4 + single 0).
    $token = '[node:' . static::IMAGE_FIELD_NAME . '-formatted:0,2-4:image]';
    $result = \Drupal::token()->replace($token, ['node' => $node]);

    $this->assertNotEmpty($result);
    $this->assertStringContainsString('<img', (string) $result);
  }

}
