<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'elm_controlled_vocabulary' field type.
 */
#[FieldType(
  id: "elm_controlled_vocabulary",
  module: "elm_vocabulary_field",
  label: new TranslatableMarkup("Controlled vocabulary"),
  description: new TranslatableMarkup("Choice of ELM controlled vocabularies."),
  category: "selection_list",
  default_widget: "elm_controlled_vocabulary_select",
  default_formatter: "elm_controlled_vocabulary_default",
)]
final class ControlledVocabularyItem extends FieldItemBase {

  const DEFAULT_LANGUAGE = 'en';

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings(): array {
    return ['vocabulary' => ''] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data): array {
    $settings = $this->getSettings();
    // DI is not supported here.
    $provider = \Drupal::service('elm_vocabulary_field.provider');

    $element['vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Controlled vocabulary'),
      '#options' => $provider->getList(),
      '#empty_value' => '',
      '#default_value' => $settings['vocabulary'],
      '#required' => !$has_data,
      '#disabled' => $has_data,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings(): array {
    return ['allow_selection' => []] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state): array {
    $settings = $this->getSettings();
    $vocabulary_id = $settings['vocabulary'];

    if (!empty($vocabulary_id)) {
      // DI is not supported here.
      $provider = \Drupal::service('elm_vocabulary_field.provider');
      $vocabulary = $provider->getVocabulary($vocabulary_id);
      $labeled_list = $vocabulary->getLabeledList(self::DEFAULT_LANGUAGE);
    }
    else {
      $labeled_list = [];
    }

    $options = [];

    foreach ($labeled_list as $key => $value) {
      if (is_string($value)) {
        $options[$key] = $this->t('@label', ['@label' => $value]);
      }
    }

    $element['allow_selection'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allow selection'),
      '#options' => $options,
      '#description' => $this->t('If @condition then @result.', [
        '@condition' => $this->t('no values are checked'),
        '@result' => $this->t('all values can be selected'),
      ]),
    ];

    foreach ($settings['allow_selection'] as $key => $value) {
      $element['allow_selection'][$key]['#default_value'] = $value;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {

    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(): array {
    $settings = $this->getSettings();
    // DI is not supported here.
    $provider = \Drupal::service('elm_vocabulary_field.provider');

    $vocabulary_id = $settings['vocabulary'];
    $vocabulary = $provider->getVocabulary($vocabulary_id);
    $labeled_list = $vocabulary->getLabeledList(self::DEFAULT_LANGUAGE);

    $allow_storage = array_keys($labeled_list);
    $allow_selection = $settings['allow_selection'];

    if (in_array(TRUE, $allow_selection)) {
      $allowed_values = [];

      foreach ($allow_storage as $key) {
        if (array_key_exists($key, $allow_selection) && $allow_selection[$key]) {
          $allowed_values[] = $key;
        }
      }
    }
    else {
      $allowed_values = $allow_storage;
    }

    $constraints = parent::getConstraints();

    $options['value']['AllowedValues'] = $allowed_values;
    $options['value']['NotBlank'] = [];

    $constraint_manager = \Drupal::typedDataManager()
      ->getValidationConstraintManager();

    $constraints[] = $constraint_manager->create('ComplexData', $options);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    return match ($this->get('value')->getValue()) {
      NULL, '' => TRUE,
      default => FALSE,
    };
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {
    // DI is not supported here.
    $provider = \Drupal::service('elm_vocabulary_field.provider');

    $vocabulary_id = $field_definition->getSetting('vocabulary');
    $vocabulary = $provider->getVocabulary($vocabulary_id);
    $labeled_list = $vocabulary->getLabeledList(self::DEFAULT_LANGUAGE);

    $values['value'] = array_rand($labeled_list);
    return $values;
  }

}
