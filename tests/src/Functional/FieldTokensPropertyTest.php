<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Functional;

/**
 * Tests the Field property tokens.
 *
 * @group field_tokens
 */
class FieldTokensPropertyTest extends FieldTokensTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test that Field property tokens render correctly.
   */
  public function testPropertyTokens() {
    // Create a node with an image attached.
    $node = $this->createNodeWithImage();

    // Image field target_id property token.
    $token = "[node:{$this->field->get('field_name')}-property:0:target_id]";
    $value = \Drupal::service('token')->replace($token, ['node' => $node]);

    // Check the token is rendered correctly.
    $this->assertEquals($value, $node->{$this->field->get('field_name')}[0]->target_id, $token . ' matches provided Image field target_id property.');
  }

}
