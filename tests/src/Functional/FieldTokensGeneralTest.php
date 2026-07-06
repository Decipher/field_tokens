<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Functional;

/**
 * Tests general functionality.
 *
 * @group field_tokens
 */
class FieldTokensGeneralTest extends FieldTokensTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test hidden fields with a Field tokens rendered field.
   *
   * @see http://drupal.org/node/2543548
   */
  public function testHiddenFields() {
    // Create a second image field.
    $field_name = strtolower($this->randomMachineName());
    $this->createImageField($field_name, 'node', (string) $this->contentType->id());

    // Set second image field to hidden via entity display.
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', (string) $this->contentType->id(), 'default')
      ->removeComponent($field_name)
      ->save();

    // Create a node with images attached.
    $node = $this->createNodeWithImage();

    // Execute token_replace() with a Field token on a node with hidden fields.
    $token = "[node:{$this->field->getName()}-formatted:0:image]";
    $result = \Drupal::service('token')->replace($token, ['node' => $node]);

    // Ensure token replacement does not crash and produces output.
    $this->assertNotEmpty($result, 'Token replacement succeeds with hidden fields present.');
  }

}
