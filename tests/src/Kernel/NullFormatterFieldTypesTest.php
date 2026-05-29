<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Kernel;

use Drupal\Core\Field\FormatterPluginManager;

/**
 * Tests that null field_types in formatter definitions don't crash token info.
 *
 * @group field_tokens
 */
class NullFormatterFieldTypesTest extends FieldTokensKernelTestBase {

  /**
   * Tests that token info handles formatter with null field_types.
   */
  public function testTokenInfoHandlesNullFieldTypes(): void {
    /** @var \Drupal\Core\Field\FormatterPluginManager $formatter_manager */
    $formatter_manager = $this->container->get('plugin.manager.field.formatter');

    $definitions = $formatter_manager->getDefinitions();
    $definitions['test_null_field_types'] = [
      'id' => 'test_null_field_types',
      'label' => 'Test Null Field Types',
      'class' => reset($definitions)['class'],
      'provider' => 'field_tokens',
      'field_types' => NULL,
    ];

    $mock_manager = $this->createMock(FormatterPluginManager::class);
    $mock_manager->method('getDefinitions')->willReturn($definitions);
    $mock_manager->method('getDefaultSettings')->willReturn([]);
    $mock_manager->method('getDefinition')->willReturnCallback(
      fn(string $id) => $definitions[$id] ?? NULL,
    );

    $this->container->set('plugin.manager.field.formatter', $mock_manager);

    $info = \Drupal::moduleHandler()->invoke('field_tokens', 'token_info');

    $this->assertIsArray($info);
    $this->assertArrayHasKey('types', $info);
    $this->assertArrayHasKey('tokens', $info);
  }

  /**
   * Tests that token info handles formatter with absent field_types.
   */
  public function testTokenInfoHandlesAbsentFieldTypes(): void {
    /** @var \Drupal\Core\Field\FormatterPluginManager $formatter_manager */
    $formatter_manager = $this->container->get('plugin.manager.field.formatter');

    $definitions = $formatter_manager->getDefinitions();
    $definitions['test_absent_field_types'] = [
      'id' => 'test_absent_field_types',
      'label' => 'Test Absent Field Types',
      'class' => reset($definitions)['class'],
      'provider' => 'field_tokens',
    ];

    $mock_manager = $this->createMock(FormatterPluginManager::class);
    $mock_manager->method('getDefinitions')->willReturn($definitions);
    $mock_manager->method('getDefaultSettings')->willReturn([]);
    $mock_manager->method('getDefinition')->willReturnCallback(
      fn(string $id) => $definitions[$id] ?? NULL,
    );

    $this->container->set('plugin.manager.field.formatter', $mock_manager);

    $info = \Drupal::moduleHandler()->invoke('field_tokens', 'token_info');

    $this->assertIsArray($info);
    $this->assertArrayHasKey('types', $info);
    $this->assertArrayHasKey('tokens', $info);
  }

}
