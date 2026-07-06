<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Functional;

/**
 * Tests the Formatted field tokens.
 *
 * @group field_tokens
 */
class FieldTokensFormattedTest extends FieldTokensTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test that Formatted tokens render correctly.
   */
  public function testFormattedTokens() {
    // Create a node with an image attached.
    $node = $this->createNodeWithImage();

    // Render the field directly for comparison.
    $display = [
      'type'     => 'image',
      'settings' => [
        'image_style' => '',
        'image_link'  => '',
      ],
      'module'   => 'image',
    ];
    $element = $node->{$this->field->getName()}->view($display);
    $output = \Drupal::service('renderer')->renderRoot($element['0']);

    // Image field with Image formatter.
    $token = "[node:{$this->field->getName()}-formatted:0:image]";
    $value = \Drupal::service('token')->replace($token, ['node' => $node]);

    // Check the token is rendered correctly.
    $this->assertEquals((string) $value, (string) $output, $token . ' matches rendered Image formatter for provided Image field.');
  }

}
