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
    switch ($vocabulary_id) {
      case 'assessment':
        $vocabulary = new AssessmentType();
        break;

      case 'learning_activity':
        $vocabulary = new LearningActivityType();
        break;

      case 'learning_opportunity':
        $vocabulary = new LearningOpportunityType();
        break;

      case 'learning_schedule':
        $vocabulary = new LearningScheduleType();
        break;

      case 'learning_setting':
        $vocabulary = new LearningSettingType();
        break;

      case 'learning_assessment':
        $vocabulary = new ModeOfLearningAndAssessment();
        break;

      default:
        $vocabulary = NULL;
        break;
    }

    return $vocabulary;
  }

}
