<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\user\Entity\User;

/**
 * Base class for Field Tokens kernel tests.
 */
abstract class FieldTokensKernelTestBase extends KernelTestBase {
  use TestFileCreationTrait;

  /**
   * The text field name used in tests.
   */
  protected const FIELD_NAME = 'field_test_text';

  /**
   * The image field name used in tests.
   */
  protected const IMAGE_FIELD_NAME = 'field_test_image';

  /**
   * The array field name used in tests.
   */
  protected const ARRAY_FIELD_NAME = 'field_test_array';

  /**
   * Modules to enable.
   *
   * @var array<string>
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
    'file',
    'image',
    'token',
    'field_tokens',
    'field_tokens_test',
    'smart_trim',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['system', 'field', 'node', 'text', 'image', 'filter', 'field_tokens']);

    User::create(['uid' => 1, 'name' => 'admin', 'status' => 1])->save();

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();

    FieldStorageConfig::create([
      'field_name' => static::FIELD_NAME,
      'entity_type' => 'node',
      'type' => 'text',
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    ])->save();

    FieldConfig::create([
      'field_name' => static::FIELD_NAME,
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Test Text',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => static::IMAGE_FIELD_NAME,
      'entity_type' => 'node',
      'type' => 'image',
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    ])->save();

    FieldConfig::create([
      'field_name' => static::IMAGE_FIELD_NAME,
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Test Image',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => static::ARRAY_FIELD_NAME,
      'entity_type' => 'node',
      'type' => 'field_tokens_test_array',
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    ])->save();

    FieldConfig::create([
      'field_name' => static::ARRAY_FIELD_NAME,
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Test Array',
    ])->save();

    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent(static::FIELD_NAME, [
      'type' => 'text_default',
      'label' => 'hidden',
    ])->setComponent(static::IMAGE_FIELD_NAME, [
      'type' => 'image',
      'label' => 'hidden',
      'settings' => [
        'image_style' => '',
        'image_link' => '',
      ],
    ])->save();
  }

  /**
   * Creates a node with text field values.
   */
  protected function createNodeWithText(array $values): Node {
    $node = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'uid' => 1,
    ]);
    $node->set(static::FIELD_NAME, $values);
    $node->save();
    return $node;
  }

  /**
   * Creates a node with array field values.
   *
   * Each value is an item array, e.g. ['value' => ['a', 'b', 'c']].
   */
  protected function createNodeWithArray(array $values): Node {
    $node = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'uid' => 1,
    ]);
    $node->set(static::ARRAY_FIELD_NAME, $values);
    $node->save();
    return $node;
  }

  /**
   * Creates a node with an image field value.
   */
  protected function createNodeWithImage(): Node {
    $file = $this->createTestFile();

    $node = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'uid' => 1,
      static::IMAGE_FIELD_NAME => [
        'target_id' => $file->id(),
        'alt' => 'Test image',
      ],
    ]);
    $node->save();
    return $node;
  }

  /**
   * Creates a test file entity.
   */
  protected function createTestFile(): File {
    $images = $this->getTestFiles('image');
    $image = reset($images);
    $this->assertNotFalse($image);

    $file = File::create([
      'uri' => $image->uri ?? '',
      'uid' => 1,
      'status' => 1,
    ]);
    $file->save();
    return $file;
  }

  /**
   * Creates a node with multiple image field values.
   *
   * @param int $count
   *   Number of images to create.
   *
   * @return \Drupal\node\Entity\Node
   *   The created node.
   */
  protected function createNodeWithMultipleImages(int $count = 2): Node {
    $images = $this->getTestFiles('image');
    $this->assertGreaterThanOrEqual($count, count($images),
      "Need at least $count test images");

    $values = [];
    $index = 0;
    foreach ($images as $image) {
      if ($index >= $count) {
        break;
      }
      $file = File::create([
        'uri' => $image->uri ?? '',
        'uid' => 1,
        'status' => 1,
      ]);
      $file->save();

      $values[] = [
        'target_id' => $file->id(),
        'alt' => 'Test image ' . $index,
      ];
      $index++;
    }

    $node = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'uid' => 1,
      static::IMAGE_FIELD_NAME => $values,
    ]);
    $node->save();
    return $node;
  }

}
