<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\elm_vocabulary_field\ControlledVocabularyProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Defines the 'elm_controlled_vocabulary' field widget.
 */
#[FieldWidget(
  id: 'elm_controlled_vocabulary_select',
  label: new TranslatableMarkup('Select list'),
  field_types: ['elm_controlled_vocabulary'],
)]
final class ControlledVocabularySelectWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  const DEFAULT_LANGUAGE = 'en';

  /**
   * The controlled vocabulary provider.
   *
   * @var \Drupal\elm_vocabulary_field\ControlledVocabularyProviderInterface
   */
  protected $vocabularyProvider;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    ControlledVocabularyProviderInterface $vocabulary_provider,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->vocabularyProvider = $vocabulary_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('elm_vocabulary_field.provider'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return ['prefix' => FALSE] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element['prefix'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prefix the label with the respective code'),
      '#default_value' => $this->getSetting('prefix'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    return [
      $this->t('Prefix the label with the respective code: @bool', [
        '@bool' => $this->getSetting('prefix')
          ? $this->t('Yes')
          : $this->t('No'),
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $vocabulary_id = $this->getFieldSetting('vocabulary');

    if (!empty($vocabulary_id)) {
      $vocabulary = $this->vocabularyProvider->getVocabulary($vocabulary_id);
      $labeled_list = $vocabulary->getLabeledList(self::DEFAULT_LANGUAGE);
    }
    else {
      $labeled_list = [];
    }

    $allow_storage = array_keys($labeled_list);
    $allow_selection = $this->getFieldSetting('allow_selection');

    if (in_array(TRUE, $allow_selection)) {
      $options = [];

      foreach ($allow_storage as $key) {
        if (array_key_exists($key, $allow_selection) && $allow_selection[$key]) {
          $value = $labeled_list[$key];
          if (is_string($value)) {
            $options[$key] = $this->t('@label', ['@label' => $value]);
          }
        }
      }
    }
    else {
      $options = $labeled_list;
    }

    $element['value'] = [
      '#type' => 'select',
      '#options' => $options,
      '#empty_value' => '',
      '#default_value' => $items[$delta]->value ?? NULL,
    ];

    // If cardinality is 1, ensure a proper label is output for the field.
    $cardinality = $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->getCardinality();

    if ($cardinality === 1) {
      $element['value']['#title'] = $element['#title'];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state): array|bool {
    $element = parent::errorElement($element, $error, $form, $form_state);
    if ($element === FALSE) {
      return FALSE;
    }
    $error_property = explode('.', $error->getPropertyPath())[1];
    return $element[$error_property];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    foreach ($values as $delta => $value) {
      if ($value['value'] === '') {
        $values[$delta]['value'] = NULL;
      }
    }
    return $values;
  }

}
