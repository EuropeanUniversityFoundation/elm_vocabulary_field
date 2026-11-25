<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\elm_vocabulary_field\ControlledVocabularyProviderInterface;

/**
 * Defines the 'elm_controlled_vocabulary' field type.
 *
 * @property string|null $value
 *   The stored term key.
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
final class ControlledVocabularyItem extends FieldItemBase implements OptionsProviderInterface {

  /**
   * Returns the vocabulary provider.
   *
   * Field items do not support dependency injection.
   */
  private static function provider(): ControlledVocabularyProviderInterface {
    return \Drupal::service('elm_vocabulary_field.provider');
  }

  /**
   * Returns the labeled list of a vocabulary.
   *
   * @param string $vocabulary_id
   *   The vocabulary ID.
   *
   * @return array<string|int, string>
   *   Labels keyed by term key, or an empty array for an unknown vocabulary.
   */
  private static function labeledList(string $vocabulary_id): array {
    return self::provider()->getVocabulary($vocabulary_id)?->getLabeledList() ?? [];
  }

  /**
   * Returns the term keys that may be selected.
   *
   * @param string $vocabulary_id
   *   The vocabulary ID.
   * @param array<string|int, bool> $allow_selection
   *   The 'allow_selection' field setting.
   *
   * @return string[]
   *   The checked keys, or all keys if none is checked.
   */
  private static function allowedKeys(string $vocabulary_id, array $allow_selection): array {
    $keys = array_map('strval', array_keys(self::labeledList($vocabulary_id)));
    $checked = array_map('strval', array_keys(array_filter($allow_selection)));
    $selected = array_values(array_intersect($keys, $checked));

    return $selected ?: $keys;
  }

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
    $element = [];
    $element['vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Controlled vocabulary'),
      '#options' => self::provider()->getList(),
      '#empty_value' => '',
      '#default_value' => $this->getSetting('vocabulary'),
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
    $element = [];
    $element['allow_selection'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allow selection'),
      '#options' => $this->getPossibleOptions(),
      '#default_value' => array_keys(array_filter((array) $this->getSetting('allow_selection'))),
      '#description' => $this->t('If no values are checked, all values can be selected.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties = [];
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(): array {
    $constraints = parent::getConstraints();
    $constraints[] = \Drupal::typedDataManager()
      ->getValidationConstraintManager()
      ->create('ElmControlledVocabularyValue', []);

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
    $allowed = self::allowedKeys(
      (string) $field_definition->getSetting('vocabulary'),
      (array) $field_definition->getSetting('allow_selection'),
    );

    return ['value' => $allowed ? $allowed[array_rand($allowed)] : NULL];
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(?AccountInterface $account = NULL): array {
    $list = self::labeledList((string) $this->getSetting('vocabulary'));

    return array_map('strval', array_keys($list));
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(?AccountInterface $account = NULL): array {
    $options = [];

    foreach (self::labeledList((string) $this->getSetting('vocabulary')) as $key => $label) {
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $options[$key] = $this->t((string) $label);
    }

    // Library order is by hash, so sort by the translated label.
    uasort($options, static fn($a, $b) => strnatcasecmp((string) $a, (string) $b));

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(?AccountInterface $account = NULL): array {
    return self::allowedKeys(
      (string) $this->getSetting('vocabulary'),
      (array) $this->getSetting('allow_selection'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(?AccountInterface $account = NULL): array {
    return array_intersect_key(
      $this->getPossibleOptions($account),
      array_flip($this->getSettableValues($account)),
    );
  }

}
