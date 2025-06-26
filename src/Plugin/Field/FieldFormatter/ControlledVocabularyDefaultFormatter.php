<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\elm_vocabulary_field\ControlledVocabularyProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'elm_controlled_vocabulary_default' formatter.
 */
#[FieldFormatter(
  id: 'elm_controlled_vocabulary_default',
  label: new TranslatableMarkup('Default'),
  field_types: ['elm_controlled_vocabulary'],
)]
final class ControlledVocabularyDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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
    $label,
    $view_mode,
    array $third_party_settings,
    ControlledVocabularyProviderInterface $vocabulary_provider,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
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
      $configuration['label'],
      $configuration['view_mode'],
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
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $vocabulary_id = $this->getFieldSetting('vocabulary');
    $vocabulary = $this->vocabularyProvider->getVocabulary($vocabulary_id);

    $element = [];

    foreach ($items as $delta => $item) {

      if ($item->value) {
        $labeled_list = $vocabulary->getLabeledList(self::DEFAULT_LANGUAGE);
        $label = $this->t('@label', ['@label' => $labeled_list[$item->value]]);

        $markup = ($this->getSetting('prefix'))
          ? $item->value . ' - ' . $label
          : $label;

        $element[$delta]['value'] = [
          '#markup' => $markup,
        ];
      }

    }

    return $element;
  }

}
