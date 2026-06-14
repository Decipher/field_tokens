<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Kernel;

/**
 * Tests hook_token_info() output.
 *
 * @group field_tokens
 */
class TokenInfoTest extends FieldTokensKernelTestBase {

  /**
   * Tests that hook_token_info() returns types and tokens keys.
   */
  public function testTokenInfoReturnsStructure(): void {
    $info = \Drupal::moduleHandler()->invoke('field_tokens', 'token_info');

    $this->assertArrayHasKey('types', $info);
    $this->assertArrayHasKey('tokens', $info);
  }

  /**
   * Tests formatted field types are created for installed field types.
   */
  public function testFormattedFieldTypesExist(): void {
    $info = \Drupal::moduleHandler()->invoke('field_tokens', 'token_info');

    $this->assertArrayHasKey('formatted_field-text', $info['types']);
    $this->assertArrayHasKey('formatted_field-image', $info['types']);
  }

  /**
   * Tests formatted field types have required properties.
   */
  public function testFormattedFieldTypesHaveRequiredProperties(): void {
    $info = \Drupal::moduleHandler()->invoke('field_tokens', 'token_info');
    $type = $info['types']['formatted_field-text'];

    $this->assertArrayHasKey('name', $type);
    $this->assertArrayHasKey('description', $type);
    $this->assertArrayHasKey('needs-data', $type);
    $this->assertEquals('formatted_field-text', $type['needs-data']);
  }

  /**
   * Tests formatter tokens are included under each field type.
   */
  public function testFormatterTokensPresent(): void {
    $info = \Drupal::moduleHandler()->invoke('field_tokens', 'token_info');

    $this->assertArrayHasKey('formatted_field-text', $info['tokens']);
    $this->assertArrayHasKey('text_default', $info['tokens']['formatted_field-text']);
  }

  /**
   * Tests that formatters with settings are marked as dynamic.
   */
  public function testFormattersWithSettingsAreDynamic(): void {
    $info = \Drupal::moduleHandler()->invoke('field_tokens', 'token_info');

    $this->assertArrayHasKey('formatted_field-image', $info['tokens']);
    $image_token = $info['tokens']['formatted_field-image']['image'] ?? NULL;
    $this->assertNotNull($image_token);
    $this->assertTrue($image_token['dynamic'] ?? FALSE);
    $this->assertStringContainsString('image_style', $image_token['description']);
  }

  /**
   * Tests that formatters without settings are not dynamic.
   */
  public function testFormattersWithoutSettingsAreNotDynamic(): void {
    $info = \Drupal::moduleHandler()->invoke('field_tokens', 'token_info');

    $text_token = $info['tokens']['formatted_field-text']['text_default'] ?? NULL;
    $this->assertNotNull($text_token);
    $this->assertArrayNotHasKey('dynamic', $text_token);
  }

  /**
   * Tests property tokens are created for field configs.
   */
  public function testPropertyTokensCreatedForFieldConfigs(): void {
    $info = \Drupal::moduleHandler()->invoke('field_tokens', 'token_info');

    $this->assertArrayHasKey('field_property-text', $info['tokens']);
    $this->assertArrayHasKey('value', $info['tokens']['field_property-text']);
    $this->assertArrayHasKey('format', $info['tokens']['field_property-text']);
  }

  /**
   * Tests property types have required properties.
   */
  public function testPropertyTypesHaveRequiredProperties(): void {
    $info = \Drupal::moduleHandler()->invoke('field_tokens', 'token_info');

    $this->assertArrayHasKey('field_property-text', $info['types']);
    $type = $info['types']['field_property-text'];
    $this->assertArrayHasKey('name', $type);
    $this->assertArrayHasKey('needs-data', $type);
    $this->assertEquals('field_property-text', $type['needs-data']);
  }

  /**
   * Tests image field property tokens include expected properties.
   */
  public function testImagePropertyTokens(): void {
    $info = \Drupal::moduleHandler()->invoke('field_tokens', 'token_info');

    $this->assertArrayHasKey('field_property-image', $info['tokens']);
    $this->assertArrayHasKey('target_id', $info['tokens']['field_property-image']);
    $this->assertArrayHasKey('alt', $info['tokens']['field_property-image']);
  }

  /**
   * Tests that formatter descriptions document valueless setting syntax.
   */
  public function testFormatterDescriptionDocumentsValuelessSettings(): void {
    $info = \Drupal::moduleHandler()->invoke('field_tokens', 'token_info');

    $image_token = $info['tokens']['formatted_field-image']['image'] ?? NULL;
    $this->assertNotNull($image_token);
    $this->assertStringContainsString('SETTING', $image_token['description']);
  }

  /**
   * Tests image entity reference property has type set.
   */
  public function testImageEntityPropertyHasType(): void {
    $info = \Drupal::moduleHandler()->invoke('field_tokens', 'token_info');

    $entity_token = $info['tokens']['field_property-image']['entity'] ?? NULL;
    $this->assertNotNull($entity_token);
    $this->assertFalse($entity_token['dynamic']);
    $this->assertEquals('file', $entity_token['type']);
  }

}
