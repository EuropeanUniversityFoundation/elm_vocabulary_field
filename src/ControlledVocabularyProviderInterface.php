<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field;

use Elm\ControlledVocabularyInterface;

/**
 * Defines an interface for a controlled vocabulary provider.
 */
interface ControlledVocabularyProviderInterface {

  /**
   * Returns the labels of the available vocabularies.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   Vocabulary labels, keyed by vocabulary ID.
   */
  public function getList(): array;

  /**
   * Returns a vocabulary by ID.
   *
   * @param string $vocabulary_id
   *   The vocabulary ID.
   *
   * @return \Elm\ControlledVocabularyInterface|null
   *   The vocabulary, or NULL if the ID is unknown.
   */
  public function getVocabulary(string $vocabulary_id): ?ControlledVocabularyInterface;

}
