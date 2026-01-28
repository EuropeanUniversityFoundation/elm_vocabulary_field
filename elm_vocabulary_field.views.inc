<?php

/**
 * @file
 * Views hooks for the ELM vocabulary field module.
 */

declare(strict_types=1);

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 */
function elm_vocabulary_field_field_views_data(FieldStorageConfigInterface $field_storage) : array {
  $data = views_field_default_views_data($field_storage);

  foreach ($data as $table_name => $table_data) {
    $property_name = implode('_', [$field_storage->getName(), 'value']);

    $data[$table_name][$property_name]['filter']['id'] = 'elm_vocabulary';
  }

  return $data;
}
