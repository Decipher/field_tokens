<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Kernel;

use Drupal\file\Entity\File;

/**
 * Tests [file:delta] token provided by field_tokens.
 *
 * @group field_tokens
 */
class FileDeltaTokenTest extends FieldTokensKernelTestBase {

  /**
   * Tests that [file:delta] is declared in token info.
   */
  public function testFileDeltaTokenIsRegistered(): void {
    $info = \Drupal::token()->getInfo();
    $this->assertArrayHasKey('delta', $info['tokens']['file']);
    $token = $info['tokens']['file']['delta'];
    $this->assertArrayHasKey('name', $token);
    $this->assertArrayHasKey('description', $token);
  }

  /**
   * Tests that [file:delta] returns the numeric delta when set in token data.
   */
  public function testFileDeltaTokenReturnsValue(): void {
    $file = $this->createTestFile();

    $result = \Drupal::token()->replace('[file:delta]', ['file' => $file, 'delta' => 2]);
    $this->assertEquals('2', $result);
  }

  /**
   * Tests that delta=0 is correctly returned (not treated as empty/falsy).
   */
  public function testFileDeltaTokenReturnsZero(): void {
    $file = $this->createTestFile();

    $result = \Drupal::token()->replace('[file:delta]', ['file' => $file, 'delta' => 0]);
    $this->assertEquals('0', $result);
  }

  /**
   * Tests that [file:delta] returns empty when delta is not set in token data.
   */
  public function testFileDeltaTokenReturnsEmptyWhenNotSet(): void {
    $file = $this->createTestFile();

    $result = \Drupal::token()->replace('[file:delta]', ['file' => $file], ['clear' => TRUE]);
    $this->assertEquals('', $result);
  }

  /**
   * Tests that field_tokens passes the correct delta when chaining file tokens.
   */
  public function testFileDeltaViaFieldPropertyChain(): void {
    $node = $this->createNodeWithMultipleImages(3);

    $result0 = \Drupal::token()->replace(
      '[node:' . static::IMAGE_FIELD_NAME . '-property:0:entity:delta]',
      ['node' => $node],
    );
    $this->assertEquals('0', $result0);

    $result2 = \Drupal::token()->replace(
      '[node:' . static::IMAGE_FIELD_NAME . '-property:2:entity:delta]',
      ['node' => $node],
    );
    $this->assertEquals('2', $result2);
  }

  /**
   * Creates a test file entity.
   */
  private function createTestFile(): File {
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

}
