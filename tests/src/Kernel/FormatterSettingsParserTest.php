<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Kernel;

/**
 * Tests the formatter settings parser.
 *
 * Covers the syntax supported by field_tokens_parse_formatter_settings(),
 * including dot notation for nested array settings.
 *
 * @group field_tokens
 */
class FormatterSettingsParserTest extends FieldTokensKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Invoking a token hook triggers Drupal's auto-inclusion of
    // field_tokens.tokens.inc, making the procedural helper available.
    \Drupal::moduleHandler()->invoke('field_tokens', 'token_info');
  }

  /**
   * Tests flat key/value parsing (regression).
   */
  public function testFlatSetting(): void {
    $this->assertSame(
      ['trim_length' => '200'],
      field_tokens_parse_formatter_settings(['trim_length-200'])
    );
  }

  /**
   * Tests valueless flags parse to NULL.
   */
  public function testValuelessSetting(): void {
    $this->assertSame(
      ['link_to_entity' => NULL],
      field_tokens_parse_formatter_settings(['link_to_entity'])
    );
  }

  /**
   * Tests nested array settings via dot notation.
   */
  public function testNestedSetting(): void {
    $this->assertSame(
      ['image_loading' => ['attribute' => 'eager']],
      field_tokens_parse_formatter_settings(['image_loading.attribute-eager'])
    );
  }

  /**
   * Tests mixed flat and nested settings in one token.
   */
  public function testMixedFlatAndNestedSettings(): void {
    $this->assertSame(
      [
        'trim_length' => '200',
        'image_loading' => ['attribute' => 'eager'],
      ],
      field_tokens_parse_formatter_settings(['trim_length-200', 'image_loading.attribute-eager'])
    );
  }

  /**
   * Tests deep (3+ level) nesting.
   */
  public function testDeepNesting(): void {
    $this->assertSame(
      ['a' => ['b' => ['c' => 'value']]],
      field_tokens_parse_formatter_settings(['a.b.c-value'])
    );
  }

  /**
   * Tests multiple nested keys under the same parent merge correctly.
   */
  public function testSiblingNestedSettings(): void {
    $this->assertSame(
      [
        'image_loading' => [
          'attribute' => 'eager',
          'weight' => '10',
        ],
      ],
      field_tokens_parse_formatter_settings(['image_loading.attribute-eager', 'image_loading.weight-10'])
    );
  }

  /**
   * Tests empty segments are ignored.
   */
  public function testEmptySegmentsAreIgnored(): void {
    $this->assertSame(
      ['trim_length' => '200'],
      field_tokens_parse_formatter_settings(['', 'trim_length-200', ''])
    );
  }

  /**
   * Tests a valueless nested flag.
   */
  public function testNestedValuelessSetting(): void {
    $this->assertSame(
      ['trim_options' => ['text' => NULL]],
      field_tokens_parse_formatter_settings(['trim_options.text'])
    );
  }

}
