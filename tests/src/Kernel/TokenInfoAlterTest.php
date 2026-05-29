<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Kernel;

use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Tests hook_token_info_alter() output.
 *
 * @group field_tokens
 */
class TokenInfoAlterTest extends FieldTokensKernelTestBase {

  /**
   * Token info cache.
   *
   * @var array
   */
  protected array $tokenInfo;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->tokenInfo = \Drupal::token()->getInfo();
  }

  /**
   * Tests that formatted tokens are added to node token type.
   */
  public function testFormattedTokensAddedToNode(): void {
    $key = static::FIELD_NAME . '-formatted';
    $this->assertArrayHasKey($key, $this->tokenInfo['tokens']['node']);
    $token = $this->tokenInfo['tokens']['node'][$key];

    $this->assertArrayHasKey('name', $token);
    $this->assertArrayHasKey('description', $token);
    $this->assertArrayHasKey('type', $token);
    $this->assertEquals('formatted_field-text', $token['type']);
    $this->assertTrue($token['dynamic']);
  }

  /**
   * Tests that property tokens are added to node token type.
   */
  public function testPropertyTokensAddedToNode(): void {
    $key = static::FIELD_NAME . '-property';
    $this->assertArrayHasKey($key, $this->tokenInfo['tokens']['node']);
    $token = $this->tokenInfo['tokens']['node'][$key];

    $this->assertArrayHasKey('name', $token);
    $this->assertArrayHasKey('description', $token);
    $this->assertArrayHasKey('type', $token);
    $this->assertEquals('field_property-text', $token['type']);
    $this->assertTrue($token['dynamic']);
  }

  /**
   * Tests that image field formatted and property tokens are added.
   */
  public function testImageFieldTokensAdded(): void {
    $this->assertArrayHasKey(static::IMAGE_FIELD_NAME . '-formatted', $this->tokenInfo['tokens']['node']);
    $this->assertArrayHasKey(static::IMAGE_FIELD_NAME . '-property', $this->tokenInfo['tokens']['node']);
  }

  /**
   * Tests that base fields (non-FieldConfig) are not altered.
   */
  public function testBaseFieldsNotAltered(): void {
    $this->assertArrayNotHasKey('title-formatted', $this->tokenInfo['tokens']['node']);
    $this->assertArrayNotHasKey('title-property', $this->tokenInfo['tokens']['node']);
  }

  /**
   * Tests that formatted token description mentions deltas.
   */
  public function testFormattedTokenDescriptionMentionsDeltas(): void {
    $key = static::FIELD_NAME . '-formatted';
    $token = $this->tokenInfo['tokens']['node'][$key];
    $description = (string) $token['description'];
    $this->assertStringContainsString('delta', strtolower($description));
  }

  /**
   * Tests that property token description mentions deltas.
   */
  public function testPropertyTokenDescriptionMentionsDeltas(): void {
    $key = static::FIELD_NAME . '-property';
    $token = $this->tokenInfo['tokens']['node'][$key];
    $description = (string) $token['description'];
    $this->assertStringContainsString('delta', strtolower($description));
  }

  /**
   * Tests that an exception in getFieldDefinitions is caught gracefully.
   */
  public function testGetFieldDefinitionsExceptionIsCaught(): void {
    $mock_entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $mock_entity_field_manager->method('getFieldDefinitions')
      ->willThrowException(new \Exception('Simulated field definition failure'));

    $this->container->set('entity_field.manager', $mock_entity_field_manager);

    $data = [
      'types' => [],
      'tokens' => [
        'node' => [
          static::FIELD_NAME => [
            'name' => 'Test Text',
            'description' => 'Test field.',
          ],
        ],
      ],
    ];
    \Drupal::moduleHandler()->invoke('field_tokens', 'token_info_alter', [&$data]);

    $this->assertArrayNotHasKey(
      static::FIELD_NAME . '-formatted',
      $data['tokens']['node'],
    );
  }

}
