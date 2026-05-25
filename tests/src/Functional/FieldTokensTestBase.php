<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Functional;

use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\Tests\image\Functional\ImageFieldTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Field Tokens functional tests.
 */
abstract class FieldTokensTestBase extends ImageFieldTestBase {
  use TestFileCreationTrait;
  use StringTranslationTrait;

  /**
   * A content type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  /**
   * An Image field.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * Modules to enable.
   *
   * @var array<string>
   */
  protected static $modules = ['field_tokens', 'image'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a content type.
    $this->contentType = $this->drupalCreateContentType();

    // Create an Image field.
    $field_name = strtolower($this->randomMachineName());
    $this->field = $this->createImageField($field_name, 'node', (string) $this->contentType->id());
  }

  /**
   * Creates a node with an image field value.
   */
  protected function createNodeWithImage(): NodeInterface {
    $images = $this->getTestFiles('image');
    $image = reset($images);
    $this->assertNotFalse($image);

    $file = File::create([
      'uri' => $image->uri ?? '',
      'uid' => 1,
      'status' => 1,
    ]);
    $file->save();

    $node = $this->drupalCreateNode([
      'type' => $this->contentType->id(),
      $this->field->get('field_name') => [
        'target_id' => $file->id(),
        'alt' => $this->randomString(),
      ],
    ]);
    return $node;
  }

}
