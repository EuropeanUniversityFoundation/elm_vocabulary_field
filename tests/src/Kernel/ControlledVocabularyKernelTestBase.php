<?php

declare(strict_types=1);

namespace Drupal\Tests\elm_vocabulary_field\Kernel;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for the controlled vocabulary kernel tests.
 */
abstract class ControlledVocabularyKernelTestBase extends KernelTestBase {

  /**
   * The vocabulary used by tests that need several terms.
   *
   * It must have at least three terms.
   */
  protected const VOCABULARY = 'learning_opportunity';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'language',
    'entity_test',
    'elm_vocabulary_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_mulrev');
  }

  /**
   * Creates the field on an entity type, using the type as the bundle.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $vocabulary
   *   The vocabulary ID.
   * @param array<string|int, bool> $allow
   *   The 'allow_selection' setting.
   * @param int $cardinality
   *   The field cardinality.
   */
  protected function createField(string $entity_type, string $vocabulary, array $allow = [], int $cardinality = 1): void {
    FieldStorageConfig::create([
      'field_name' => 'field_elm',
      'entity_type' => $entity_type,
      'type' => 'elm_controlled_vocabulary',
      'cardinality' => $cardinality,
      'settings' => ['vocabulary' => $vocabulary],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_elm',
      'entity_type' => $entity_type,
      'bundle' => $entity_type,
      'settings' => ['allow_selection' => $allow],
    ])->save();
  }

  /**
   * Changes the 'allow_selection' setting of the existing field.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param array<string|int, bool> $allow
   *   The new setting.
   */
  protected function setAllowSelection(string $entity_type, array $allow): void {
    /** @var \Drupal\field\Entity\FieldConfig $field */
    $field = FieldConfig::loadByName($entity_type, $entity_type, 'field_elm');
    $field->setSetting('allow_selection', $allow)->save();
    $this->container->get('entity_type.manager')->getStorage($entity_type)->resetCache();
  }

  /**
   * Returns the term keys of a vocabulary, as strings.
   *
   * @param string $vocabulary
   *   The vocabulary ID.
   *
   * @return string[]
   *   The keys.
   */
  protected function keys(string $vocabulary): array {
    $list = $this->container->get('elm_vocabulary_field.provider')
      ->getVocabulary($vocabulary)
      ->getLabeledList();

    return array_map('strval', array_keys($list));
  }

  /**
   * Creates an unsaved entity holding the given field values.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string[] $values
   *   The field values, one per delta.
   */
  protected function entity(string $entity_type, array $values): ContentEntityInterface {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create([
        'name' => 'test',
        'field_elm' => array_map(static fn($value) => ['value' => $value], $values),
      ]);

    return $entity;
  }

  /**
   * Reloads an entity from storage, bypassing the static cache.
   */
  protected function reload(ContentEntityInterface $entity): ContentEntityInterface {
    $storage = $this->container->get('entity_type.manager')->getStorage($entity->getEntityTypeId());
    $storage->resetCache();
    $reloaded = $storage->load($entity->id());
    $this->assertInstanceOf(ContentEntityInterface::class, $reloaded);

    return $reloaded;
  }

  /**
   * Counts the violations of the field on an entity.
   */
  protected function violations(ContentEntityInterface $entity): int {
    return $entity->get('field_elm')->validate()->count();
  }

  /**
   * Returns the English label of a term.
   *
   * @param string $vocabulary
   *   The vocabulary ID.
   * @param string $key
   *   The term key.
   */
  protected function label(string $vocabulary, string $key): string {
    $object = $this->container->get('elm_vocabulary_field.provider')->getVocabulary($vocabulary);
    $this->assertNotNull($object);

    return (string) ($object->getLabeledList()[$key] ?? $key);
  }

}
