<?php

declare(strict_types=1);

namespace Drupal\Tests\elm_vocabulary_field\Unit;

use Drupal\elm_vocabulary_field\ControlledVocabularyProvider;
use Drupal\Tests\UnitTestCase;
use Elm\ControlledVocabularyInterface;

/**
 * Tests the controlled vocabulary provider.
 *
 * @coversDefaultClass \Drupal\elm_vocabulary_field\ControlledVocabularyProvider
 * @group elm_vocabulary_field
 */
class ControlledVocabularyProviderTest extends UnitTestCase {

  /**
   * The provider under test.
   */
  protected ControlledVocabularyProvider $provider;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->provider = new ControlledVocabularyProvider();
  }

  /**
   * Every listed ID resolves to a vocabulary.
   *
   * @covers ::getList
   * @covers ::getVocabulary
   */
  public function testListedIdsResolve(): void {
    $list = $this->provider->getList();

    $this->assertNotEmpty($list);

    foreach (array_keys($list) as $id) {
      $this->assertInstanceOf(
        ControlledVocabularyInterface::class,
        $this->provider->getVocabulary((string) $id),
        "Listed ID $id should resolve.",
      );
    }
  }

  /**
   * The label list and the class map hold the same IDs.
   *
   * @covers ::getList
   */
  public function testListAndMapAreInSync(): void {
    $map = (new \ReflectionClass(ControlledVocabularyProvider::class))
      ->getConstant('VOCABULARIES');

    $this->assertEqualsCanonicalizing(
      array_keys($map),
      array_keys($this->provider->getList()),
    );
  }

  /**
   * An unknown ID returns NULL.
   *
   * @covers ::getVocabulary
   */
  public function testUnknownIdReturnsNull(): void {
    $this->assertNull($this->provider->getVocabulary('nope'));
  }

  /**
   * Repeated lookups return the same instance.
   *
   * @covers ::getVocabulary
   */
  public function testInstancesAreCached(): void {
    $this->assertSame(
      $this->provider->getVocabulary('assessment'),
      $this->provider->getVocabulary('assessment'),
    );
  }

}
