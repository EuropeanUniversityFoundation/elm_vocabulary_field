<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Elm\AssessmentType;
use Elm\ControlledVocabularyInterface;
use Elm\LearningActivityType;
use Elm\LearningOpportunityType;
use Elm\LearningScheduleType;
use Elm\LearningSettingType;
use Elm\ModeOfLearningAndAssessment;

/**
 * Provides ELM controlled vocabularies.
 */
final class ControlledVocabularyProvider implements ControlledVocabularyProviderInterface {

  /**
   * Vocabulary classes, keyed by vocabulary ID.
   *
   * The IDs are stored in field settings: do not rename them.
   *
   * @var array<string, class-string<\Elm\ControlledVocabularyInterface>>
   */
  private const VOCABULARIES = [
    'assessment' => AssessmentType::class,
    'learning_activity' => LearningActivityType::class,
    'learning_opportunity' => LearningOpportunityType::class,
    'learning_schedule' => LearningScheduleType::class,
    'learning_setting' => LearningSettingType::class,
    'learning_assessment' => ModeOfLearningAndAssessment::class,
  ];

  /**
   * Vocabulary instances, keyed by vocabulary ID.
   *
   * @var array<string, \Elm\ControlledVocabularyInterface>
   */
  private array $instances = [];

  /**
   * {@inheritdoc}
   */
  public function getList(): array {
    return [
      'assessment' => new TranslatableMarkup('Assessment type'),
      'learning_activity' => new TranslatableMarkup('Learning activity type'),
      'learning_opportunity' => new TranslatableMarkup('Learning opportunity type'),
      'learning_schedule' => new TranslatableMarkup('Learning schedule type'),
      'learning_setting' => new TranslatableMarkup('Learning setting type'),
      'learning_assessment' => new TranslatableMarkup('Mode of learning and assessment'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getVocabulary(string $vocabulary_id): ?ControlledVocabularyInterface {
    $class = self::VOCABULARIES[$vocabulary_id] ?? NULL;

    return $class === NULL
      ? NULL
      : ($this->instances[$vocabulary_id] ??= new $class());
  }

}
