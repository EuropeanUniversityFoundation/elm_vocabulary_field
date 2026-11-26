<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks that a controlled vocabulary value is allowed for the field.
 */
#[Constraint(
  id: 'ElmControlledVocabularyValue',
  label: new TranslatableMarkup('ELM controlled vocabulary value', [], ['context' => 'Validation']),
)]
final class ControlledVocabularyValueConstraint extends SymfonyConstraint {

  /**
   * The violation message.
   */
  public string $message = 'The value you selected is not a valid choice.';

}
