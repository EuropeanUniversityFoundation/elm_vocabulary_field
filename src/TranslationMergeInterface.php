<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field;

/**
 * Defines an interface for a translation merge service.
 */
interface TranslationMergeInterface {

  /**
   * Merges the library translations with those of this module.
   *
   * @return array{written: array<string, string>, skipped: string[]}
   *   The files written, keyed by langcode, and the library language
   *   directories deliberately ignored.
   *
   * @throws \RuntimeException
   *   If the library translations are missing or the target is not writable.
   */
  public function mergeTranslations(): array;

}
