<?php

declare(strict_types=1);

namespace Drupal\Tests\elm_vocabulary_field\Kernel;

/**
 * Tests the value constraint of the controlled vocabulary field.
 *
 * @group elm_vocabulary_field
 */
class ControlledVocabularyConstraintTest extends ControlledVocabularyKernelTestBase {

  /**
   * Provides the vocabulary IDs.
   *
   * @return array<int, array<int, string>>
   *   The data sets.
   */
  public static function vocabularies(): array {
    return array_map(
      static fn(string $id): array => [$id],
      [
        'assessment',
        'learning_activity',
        'learning_opportunity',
        'learning_schedule',
        'learning_setting',
        'learning_assessment',
      ],
    );
  }

  /**
   * Every key of every vocabulary is valid on a new entity.
   *
   * @dataProvider vocabularies
   */
  public function testEveryKeyValidates(string $vocabulary): void {
    $this->createField('entity_test', $vocabulary);

    foreach ($this->keys($vocabulary) as $key) {
      $this->assertSame(0, $this->violations($this->entity('entity_test', [$key])), "Key $key should validate.");
    }
  }

  /**
   * A key outside the vocabulary is invalid.
   */
  public function testUnknownKeyFails(): void {
    $this->createField('entity_test', static::VOCABULARY);

    $this->assertSame(1, $this->violations($this->entity('entity_test', ['not-a-term'])));
  }

  /**
   * A new entity accepts only the allowed terms.
   */
  public function testNarrowedSelectionOnNewEntity(): void {
    [$a, $b, $c] = $this->keys(static::VOCABULARY);
    $this->createField('entity_test', static::VOCABULARY, [$a => TRUE, $b => FALSE]);

    $this->assertSame(0, $this->violations($this->entity('entity_test', [$a])));
    $this->assertSame(1, $this->violations($this->entity('entity_test', [$b])));
    $this->assertSame(1, $this->violations($this->entity('entity_test', [$c])));
  }

  /**
   * A stored value that is no longer allowed survives an unchanged re-save.
   */
  public function testStoredValueSurvivesNarrowing(): void {
    [$a, $b] = $this->keys(static::VOCABULARY);
    $this->createField('entity_test', static::VOCABULARY);
    $entity = $this->entity('entity_test', [$a]);
    $entity->save();

    $this->setAllowSelection('entity_test', [$b => TRUE]);

    $this->assertSame(0, $this->violations($this->reload($entity)));
  }

  /**
   * Changing to a different disallowed term fails.
   */
  public function testChangedValueIsStillChecked(): void {
    [$a, $b, $c] = $this->keys(static::VOCABULARY);
    $this->createField('entity_test', static::VOCABULARY);
    $entity = $this->entity('entity_test', [$a]);
    $entity->save();

    $this->setAllowSelection('entity_test', [$b => TRUE]);
    $entity = $this->reload($entity);
    $entity->set('field_elm', [['value' => $c]]);

    $this->assertSame(1, $this->violations($entity));
  }

  /**
   * Stored values are matched at any delta, so reordering is valid.
   */
  public function testReorderedStoredValuesPass(): void {
    [$a, $b, $c] = $this->keys(static::VOCABULARY);
    $this->createField('entity_test', static::VOCABULARY, [], -1);
    $entity = $this->entity('entity_test', [$a, $b]);
    $entity->save();

    $this->setAllowSelection('entity_test', [$c => TRUE]);
    $entity = $this->reload($entity);
    $entity->set('field_elm', [['value' => $b], ['value' => $a]]);

    $this->assertSame(0, $this->violations($entity));
  }

  /**
   * A stored value missing from the library is rejected on re-save.
   */
  public function testStoredUnknownValueFails(): void {
    $this->createField('entity_test', static::VOCABULARY);
    $entity = $this->entity('entity_test', ['removed-term']);
    $entity->save();

    $this->assertSame(1, $this->violations($this->reload($entity)));
  }

  /**
   * A field without items raises no violation.
   */
  public function testEmptyFieldPasses(): void {
    $this->createField('entity_test', static::VOCABULARY);

    $this->assertSame(0, $this->violations($this->entity('entity_test', [])));
  }

}
