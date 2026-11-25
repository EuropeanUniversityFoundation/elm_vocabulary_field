<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field\Plugin\views\filter;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\elm_vocabulary_field\ControlledVocabularyProviderInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides filtering for ELM vocabularies.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter('elm_vocabulary')]
class ElmVocabularyFilter extends InOperator {

  /**
   * The entity field manager.
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
   * Constructs an ElmVocabularyFilter object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\elm_vocabulary_field\ControlledVocabularyProviderInterface $provider
   *   The controlled vocabulary provider.
   */
  public function __construct(
    array $configuration,
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
      $container->get('elm_vocabulary_field.provider'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    /** @var array<string|int, mixed>|null $cached */
    $cached = $this->valueOptions;

    if ($cached !== NULL) {
      return $cached;
    }

    $this->valueOptions = [];

    $storage = $this->entityFieldManager
      ->getFieldStorageDefinitions($this->definition['entity_type'])[$this->definition['field_name']] ?? NULL;
    $vocabulary_id = (string) ($storage?->getSetting('vocabulary') ?? '');
    $labeled_list = $this->provider->getVocabulary($vocabulary_id)?->getLabeledList() ?? [];

    foreach ($labeled_list as $key => $label) {
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $this->valueOptions[$key] = $this->t((string) $label);
    }

    // Library order is by hash, so sort by the translated label.
    uasort($this->valueOptions, static fn($a, $b) => strnatcasecmp((string) $a, (string) $b));

    return $this->valueOptions;
  }

}
