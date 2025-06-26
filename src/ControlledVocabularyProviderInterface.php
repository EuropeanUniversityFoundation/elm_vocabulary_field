<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field;

use Elm\ControlledVocabularyInterface;

/**
 * Defines an interface for a controlled vocabulary provider.
 */
interface ControlledVocabularyProviderInterface {

  /**
   * Returns the list of controlled vocabularies.
   */
  public function getList(): array;

  /**
   * Returns a controlled vocabulary per the ID provided.
   */
  public function getVocabulary(string $vocabulary_id): ?ControlledVocabularyInterface;

}
