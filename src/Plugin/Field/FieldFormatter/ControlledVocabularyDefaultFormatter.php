<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\elm_vocabulary_field\Plugin\Field\FieldType\ControlledVocabularyItem;

/**
 * Plugin implementation of the 'elm_controlled_vocabulary_default' formatter.
 */
#[FieldFormatter(
  id: 'elm_controlled_vocabulary_default',
  label: new TranslatableMarkup('Default'),
  field_types: ['elm_controlled_vocabulary'],
)]
final class ControlledVocabularyDefaultFormatter extends FormatterBase {

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
    $element = [];
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
    $elements = [];
    $first = $items->first();

    if (!$first instanceof ControlledVocabularyItem) {
      return $elements;
    }

    // The options depend only on the field settings, so one lookup serves all.
    $options = $first->getPossibleOptions();
    $prefix = (bool) $this->getSetting('prefix');

    foreach ($items as $delta => $item) {
      /** @var \Drupal\elm_vocabulary_field\Plugin\Field\FieldType\ControlledVocabularyItem $item */
      $value = (string) $item->value;

      if ($value === '') {
        continue;
      }

      // Unknown values are shown as stored, so content is never blanked.
      $label = (string) ($options[$value] ?? $value);

      $elements[$delta]['value'] = [
        '#markup' => $prefix ? $value . ' - ' . $label : $label,
      ];
    }

    return $elements;
  }

}
