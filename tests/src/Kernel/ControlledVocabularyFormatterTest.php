<?php

declare(strict_types=1);

namespace Drupal\Tests\elm_vocabulary_field\Kernel;

use Drupal\Core\Field\FormatterInterface;

/**
 * Tests the default formatter.
 *
 * @group elm_vocabulary_field
 */
class ControlledVocabularyFormatterTest extends ControlledVocabularyKernelTestBase {

  /**
   * Renders the field of an entity holding the given values.
   *
   * @param string[] $values
   *   The stored values.
   * @param bool $prefix
   *   The formatter 'prefix' setting.
   *
   * @return array<int, mixed>
   *   The render elements, keyed by delta.
   */
  protected function renderField(array $values, bool $prefix = FALSE): array {
    $items = $this->entity('entity_test', $values)->get('field_elm');
    $formatter = $this->container->get('plugin.manager.field.formatter')->getInstance([
      'field_definition' => $items->getFieldDefinition(),
      'view_mode' => 'default',
      'configuration' => [
        'type' => 'elm_controlled_vocabulary_default',
        'settings' => ['prefix' => $prefix],
        'label' => 'hidden',
        'third_party_settings' => [],
      ],
    ]);
    $this->assertInstanceOf(FormatterInterface::class, $formatter);

    return $formatter->viewElements($items, 'en');
  }

  /**
   * A known term renders its label.
   */
  public function testKnownKeyRendersLabel(): void {
    $this->createField('entity_test', static::VOCABULARY);
    $a = $this->keys(static::VOCABULARY)[0];

    $this->assertSame(
      [0 => ['value' => ['#markup' => $this->label(static::VOCABULARY, $a)]]],
      $this->renderField([$a]),
    );
  }

  /**
   * The prefix setting puts the term key before the label.
   */
  public function testPrefix(): void {
    $this->createField('entity_test', static::VOCABULARY);
    $a = $this->keys(static::VOCABULARY)[0];

    $this->assertSame(
      [0 => ['value' => ['#markup' => $a . ' - ' . $this->label(static::VOCABULARY, $a)]]],
      $this->renderField([$a], TRUE),
    );
  }

  /**
   * A value missing from the vocabulary renders as stored.
   */
  public function testUnknownValueRendersRaw(): void {
    $this->createField('entity_test', static::VOCABULARY);

    $this->assertSame(
      [0 => ['value' => ['#markup' => 'removed-term']]],
      $this->renderField(['removed-term']),
    );
  }

  /**
   * Deltas are preserved for multiple values.
   */
  public function testMultipleValues(): void {
    $this->createField('entity_test', static::VOCABULARY, [], -1);
    [$a, $b] = $this->keys(static::VOCABULARY);

    $elements = $this->renderField([$a, $b]);

    $this->assertSame([0, 1], array_keys($elements));
    $this->assertSame($this->label(static::VOCABULARY, $b), $elements[1]['value']['#markup']);
  }

  /**
   * Empty fields and empty values render nothing.
   */
  public function testEmptyRendersNothing(): void {
    $this->createField('entity_test', static::VOCABULARY);

    $this->assertSame([], $this->renderField([]));
    $this->assertSame([], $this->renderField(['']));
  }

}
