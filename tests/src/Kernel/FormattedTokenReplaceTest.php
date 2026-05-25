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

}
