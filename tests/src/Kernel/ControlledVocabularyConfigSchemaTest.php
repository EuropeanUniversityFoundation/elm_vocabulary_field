<?php

declare(strict_types=1);

namespace Drupal\Tests\elm_vocabulary_field\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\views\Entity\View;

/**
 * Tests the config schema of the module.
 *
 * @group elm_vocabulary_field
 */
class ControlledVocabularyConfigSchemaTest extends ControlledVocabularyKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'language',
    'views',
    'entity_test',
    'elm_vocabulary_field',
  ];

  /**
   * The widget and formatter settings and the default value are valid.
   */
  public function testDisplaysAndDefaultValue(): void {
    $this->createField('entity_test', static::VOCABULARY);
    $a = $this->keys(static::VOCABULARY)[0];

    $field = FieldConfig::loadByName('entity_test', 'entity_test', 'field_elm');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $field->setDefaultValue([['value' => $a]])->save();

    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $form_display->setComponent('field_elm', [
      'type' => 'elm_controlled_vocabulary_select',
      'settings' => ['prefix' => TRUE],
    ])->save();

    $view_display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $view_display->setComponent('field_elm', [
      'type' => 'elm_controlled_vocabulary_default',
      'settings' => ['prefix' => TRUE],
    ])->save();

    $this->assertTrue($form_display->getComponent('field_elm')['settings']['prefix']);
    $this->assertTrue($view_display->getComponent('field_elm')['settings']['prefix']);
  }

  /**
   * An exposed filter is valid.
   */
  public function testExposedFilter(): void {
    $this->saveView([
      'exposed' => TRUE,
      'expose' => [
        'identifier' => 'elm',
        'label' => 'ELM',
        'multiple' => TRUE,
        'reduce' => FALSE,
      ],
    ]);

    $this->assertNotNull(View::load('elm_test'));
  }

  /**
   * A grouped filter is valid.
   */
  public function testGroupedFilter(): void {
    $a = $this->keys(static::VOCABULARY)[0];

    $this->saveView([
      'exposed' => TRUE,
      'is_grouped' => TRUE,
      'group_info' => [
        'label' => 'ELM',
        'description' => '',
        'identifier' => 'elm',
        'optional' => TRUE,
        'widget' => 'select',
        'multiple' => FALSE,
        'remember' => FALSE,
        'default_group' => 'All',
        'default_group_multiple' => [],
        'group_items' => [
          1 => ['title' => 'First', 'operator' => 'in', 'value' => [$a]],
        ],
      ],
    ]);

    $this->assertNotNull(View::load('elm_test'));
  }

  /**
   * Saves a view with an ELM vocabulary filter.
   *
   * @param array<string, mixed> $options
   *   Filter options merged over the defaults.
   */
  protected function saveView(array $options): void {
    $filter = $options + [
      'id' => 'field_elm_value',
      'table' => 'entity_test__field_elm',
      'field' => 'field_elm_value',
      'plugin_id' => 'elm_vocabulary',
      'operator' => 'in',
      'value' => [],
      'group' => 1,
    ];

    View::create([
      'id' => 'elm_test',
      'label' => 'ELM test',
      'base_table' => 'entity_test',
      'base_field' => 'id',
      'display' => [
        'default' => [
          'id' => 'default',
          'display_plugin' => 'default',
          'display_title' => 'Default',
          'position' => 0,
          'display_options' => ['filters' => ['field_elm_value' => $filter]],
        ],
      ],
    ])->save();
  }

}
