<?php

declare(strict_types=1);

namespace Drupal\Tests\elm_vocabulary_field\Kernel;

use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormState;

/**
 * Tests the options offered by the select widget.
 *
 * @group elm_vocabulary_field
 */
class ControlledVocabularyWidgetTest extends ControlledVocabularyKernelTestBase {

  /**
   * Builds the select options for an entity holding a single value.
   *
   * @param string $value
   *   The stored value.
   * @param array<string, mixed> $settings
   *   The widget settings.
   *
   * @return array<string|int, mixed>
   *   The select options.
   */
  protected function options(string $value, array $settings = []): array {
    $items = $this->entity('entity_test', [$value])->get('field_elm');
    $widget = $this->container->get('plugin.manager.field.widget')->getInstance([
      'field_definition' => $items->getFieldDefinition(),
      'form_mode' => 'default',
      'configuration' => [
        'type' => 'elm_controlled_vocabulary_select',
        'settings' => $settings,
        'third_party_settings' => [],
      ],
    ]);
    $this->assertInstanceOf(WidgetInterface::class, $widget);

    $form = [];
    $element = $widget->formElement($items, 0, [], $form, new FormState());

    return $element['value']['#options'];
  }

  /**
   * Returns the option keys as strings.
   *
   * @param array<string|int, mixed> $options
   *   The select options.
   *
   * @return string[]
   *   The keys.
   */
  protected function optionKeys(array $options): array {
    return array_map('strval', array_keys($options));
  }

  /**
   * Without a selection, every term is offered.
   */
  public function testNoSelectionOffersAll(): void {
    $this->createField('entity_test', static::VOCABULARY);
    $keys = $this->keys(static::VOCABULARY);

    $this->assertEqualsCanonicalizing($keys, $this->optionKeys($this->options($keys[0])));
  }

  /**
   * Only the allowed terms are offered.
   */
  public function testOnlyAllowedTermsAreOffered(): void {
    [, $b] = $this->keys(static::VOCABULARY);
    $this->createField('entity_test', static::VOCABULARY, [$b => TRUE]);

    $this->assertSame([$b], $this->optionKeys($this->options($b)));
  }

  /**
   * A valid stored term stays available although it is no longer allowed.
   */
  public function testStoredValueIsKept(): void {
    [$a, $b] = $this->keys(static::VOCABULARY);
    $this->createField('entity_test', static::VOCABULARY, [$b => TRUE]);

    $this->assertEqualsCanonicalizing([$a, $b], $this->optionKeys($this->options($a)));
  }

  /**
   * A stored value missing from the vocabulary is dropped.
   */
  public function testUnknownValueIsDropped(): void {
    [, $b] = $this->keys(static::VOCABULARY);
    $this->createField('entity_test', static::VOCABULARY, [$b => TRUE]);

    $this->assertSame([$b], $this->optionKeys($this->options('removed-term')));
  }

  /**
   * The prefix setting puts the term key before the label.
   */
  public function testPrefix(): void {
    $this->createField('entity_test', static::VOCABULARY);
    $first = $this->keys(static::VOCABULARY)[0];

    $plain = $this->options($first);
    $prefixed = $this->options($first, ['prefix' => TRUE]);

    $this->assertSame(array_keys($plain), array_keys($prefixed));
    foreach ($plain as $key => $label) {
      $this->assertSame($key . ' - ' . $label, (string) $prefixed[$key]);
    }
  }

  /**
   * Options are sorted by label.
   */
  public function testOptionsAreSortedByLabel(): void {
    $this->createField('entity_test', static::VOCABULARY);
    $labels = array_map('strval', array_values($this->options($this->keys(static::VOCABULARY)[0])));

    $sorted = $labels;
    usort($sorted, 'strnatcasecmp');

    $this->assertSame($sorted, $labels);
  }

}
