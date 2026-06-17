<?php

declare(strict_types=1);

namespace Drupal\Tests\field_tokens\Kernel;

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
   * Tests that explicit delta => NULL leaves the token cleared.
   *
   * The token should not be silently replaced with an empty string.
   */
  public function testFileDeltaTokenWithExplicitNull(): void {
    $file = $this->createTestFile();

    $result = \Drupal::token()->replace('[file:delta]', ['file' => $file, 'delta' => NULL], ['clear' => TRUE]);
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

    // Middle element: catches off-by-one and first-element-repeated bugs.
    $result1 = \Drupal::token()->replace(
      '[node:' . static::IMAGE_FIELD_NAME . '-property:1:entity:delta]',
      ['node' => $node],
    );
    $this->assertEquals('1', $result1);

    $result2 = \Drupal::token()->replace(
      '[node:' . static::IMAGE_FIELD_NAME . '-property:2:entity:delta]',
      ['node' => $node],
    );
    $this->assertEquals('2', $result2);
  }

  /**
   * Tests that an empty item in a multi-delta selection is skipped.
   *
   * Replacement of the non-empty items should still proceed.
   */
  public function testMultiDeltaWithEmptyItem(): void {
    $node = $this->createNodeWithMultipleImages(3);

    // Make the item at delta 0 empty in-memory. Do not re-save: preSave
    // filters empty items and would reindex the remaining items.
    $node->get(static::IMAGE_FIELD_NAME)[0]->setValue(['target_id' => NULL]);

    $result = \Drupal::token()->replace(
      '[node:' . static::IMAGE_FIELD_NAME . '-property:0,2:entity:delta]',
      ['node' => $node],
    );
    // Old behaviour (continue 2) aborted the whole replacement; the fix drops
    // only the empty delta 0 and still resolves delta 2.
    $this->assertEquals('2', $result);
  }

}
