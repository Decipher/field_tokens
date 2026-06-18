<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;

/**
 * Tests delta token resolution via Custom Formatters HTML+Token engine.
 *
 * Exercises the full path: HTMLToken::viewElements() per-item loop →
 * $context['delta'] → field_tokens alter → token replacement. Verifies that
 * both [file:delta] (direct) and [field_property:entity:delta] (property
 * chain) resolve to the correct per-item position.
 *
 * @group field_tokens
 */
class CustomFormattersDeltaTest extends FieldTokensTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field_tokens', 'image', 'custom_formatters'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Make the image field created by the parent multi-value.
    $storage = FieldStorageConfig::loadByName('node', $this->field->get('field_name'));
    $storage->setCardinality(FieldStorageConfig::CARDINALITY_UNLIMITED);
    $storage->save();

    // Create a Custom Formatter that renders both delta token forms.
    $formatter = \Drupal::entityTypeManager()
      ->getStorage('formatter')
      ->create([
        'id' => 'delta_token_test',
        'label' => 'Delta Token Test',
        'type' => 'html_token',
        'field_types' => ['image'],
        'data' => '<div class="cf-file-delta">[file:delta]</div><div class="cf-prop-delta">[field_property:entity:delta]</div>',
      ]);
    $formatter->save();

    // Clear the formatter plugin cache so the deriver picks up the new
    // formatter.
    \Drupal::service('plugin.manager.field.formatter')->clearCachedDefinitions();

    // Set the view display to use the Custom Formatter.
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', (string) $this->contentType->id(), 'default')
      ->setComponent($this->field->get('field_name'), [
        'type' => 'custom_formatters:delta_token_test',
        'label' => 'hidden',
      ])
      ->save();
  }

  /**
   * Tests that delta tokens resolve correctly per-item via CF.
   */
  public function testDeltaTokenViaCustomFormatter(): void {
    $field_name = $this->field->get('field_name');

    // Create 3 test images.
    $images = $this->getTestFiles('image');
    $this->assertGreaterThanOrEqual(3, count($images), 'Need at least 3 test images.');

    $values = [];
    for ($i = 0; $i < 3; $i++) {
      $this->assertNotEmpty($images[$i]->uri, "Test image at index {$i} must provide a URI.");
      $file = File::create([
        'uri' => $images[$i]->uri,
        'uid' => 1,
        'status' => 1,
      ]);
      $file->save();
      $values[] = [
        'target_id' => $file->id(),
        'alt' => 'Test image ' . $i,
      ];
    }

    // Create a node with 3 images.
    $node = $this->drupalCreateNode([
      'type' => $this->contentType->id(),
      $field_name => $values,
    ]);

    // View the node.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    // Assert both [file:delta] and [field_property:entity:delta] produced
    // correct per-item deltas for all three items.
    $content = $this->getSession()->getPage()->getContent();
    foreach (['0', '1', '2'] as $expected_delta) {
      $this->assertStringContainsString(
        '<div class="cf-file-delta">' . $expected_delta . '</div>',
        $content,
        "Direct [file:delta] resolved to $expected_delta."
      );
      $this->assertStringContainsString(
        '<div class="cf-prop-delta">' . $expected_delta . '</div>',
        $content,
        "Property-chain [field_property:entity:delta] resolved to $expected_delta."
      );
    }
  }

}
