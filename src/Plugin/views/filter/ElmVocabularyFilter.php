<?php

namespace Drupal\elm_vocabulary_field\Plugin\views\filter;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\elm_vocabulary_field\ControlledVocabularyProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides filtering for ELM vocabularies.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("elm_vocabulary")]
class ElmVocabularyFilter extends InOperator implements ContainerFactoryPluginInterface {

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The controlled vocabulary provider.
   *
   * @var \Drupal\elm_vocabulary_field\ControlledVocabularyProviderInterface
   */
  protected $provider;

  /**
   * Constructs a new ElmVocabularyFilter instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\elm_vocabulary_field\ControlledVocabularyProviderInterface $provider
   *   The controlled vocabulary provider.
   */
  public function __construct(
    $configuration,
    $plugin_id,
    $plugin_definition,
    EntityFieldManagerInterface $entity_field_manager,
    ControlledVocabularyProviderInterface $provider,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entity_field_manager;
    $this->provider = $provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('elm_vocabulary_field.provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $entity_type = $this->definition['entity_type'];
    $field_name = $this->definition['field_name'];
    $field_storage_definition = $this->entityFieldManager
      ->getFieldStorageDefinitions($entity_type)[$field_name];

    $settings = $field_storage_definition->getSettings();

    $vocabulary_id = $settings['vocabulary'];
    $vocabulary = $this->provider->getVocabulary($vocabulary_id);

    $labeled_list = $vocabulary->getLabeledList();

    $options = [];

    foreach ($labeled_list as $key => $value) {
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $options[$key] = $this->t($value);
    }

    $this->valueOptions = $options;

    return $this->valueOptions;
  }

}
