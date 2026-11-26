<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Defines the 'elm_controlled_vocabulary_select' field widget.
 */
#[FieldWidget(
  id: 'elm_controlled_vocabulary_select',
  label: new TranslatableMarkup('Select list'),
  field_types: ['elm_controlled_vocabulary'],
)]
final class ControlledVocabularySelectWidget extends WidgetBase {

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
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    /** @var \Drupal\elm_vocabulary_field\Plugin\Field\FieldType\ControlledVocabularyItem $item */
    $item = $items[$delta];
    $keys = $item->getAllowedValues();
    $current = (string) ($item->value ?? '');

    // Keep a stored value that is valid but no longer allowed.
    if ($current !== '' && in_array($current, $item->getPossibleValues(), TRUE)) {
      $keys[] = $current;
    }

    $options = array_intersect_key($item->getPossibleOptions(), array_flip($keys));

    if ($this->getSetting('prefix')) {
      foreach ($options as $key => $label) {
        $options[$key] = $key . ' - ' . $label;
      }
    }

    $element['value'] = $element + [
      '#type' => 'select',
      '#options' => $options,
      '#empty_value' => '',
      '#default_value' => $item->value ?? NULL,
    ];

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

    $property = explode('.', $error->getPropertyPath())[1] ?? NULL;

    return ($property !== NULL && isset($element[$property]))
      ? $element[$property]
      : $element;
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
