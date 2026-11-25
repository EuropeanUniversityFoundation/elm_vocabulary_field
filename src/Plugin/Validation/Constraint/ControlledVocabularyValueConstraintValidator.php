<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates a controlled vocabulary value.
 *
 * A value passes if it is settable per the field settings, or if it belongs to
 * the vocabulary and is already stored on the entity being edited.
 */
final class ControlledVocabularyValueConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs the validator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (!$value instanceof FieldItemInterface || !$value instanceof OptionsProviderInterface) {
      return;
    }

    $current = (string) $value->get('value')->getValue();

    if ($current === '') {
      return;
    }

    $valid = in_array($current, $value->getPossibleValues(), TRUE)
      && (in_array($current, $value->getSettableValues(), TRUE)
        || in_array($current, $this->storedValues($value), TRUE));

    if (!$valid) {
      $this->context->buildViolation($constraint->message)
        ->atPath('value')
        ->addViolation();
    }
  }

  /**
   * Returns the values stored for the field on the entity being edited.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item being validated.
   *
   * @return string[]
   *   The stored values, or an empty array for a new entity.
   */
  private function storedValues(FieldItemInterface $item): array {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $item->getEntity();

    if ($entity->isNew()) {
      return [];
    }

    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $revision_id = $entity->getLoadedRevisionId();

    /** @var \Drupal\Core\Entity\ContentEntityInterface|null $original */
    $original = $revision_id
      ? $storage->loadRevision($revision_id)
      : $storage->loadUnchanged($entity->id());
    $field_name = $item->getFieldDefinition()->getName();

    if ($original === NULL || !$original->hasField($field_name)) {
      return [];
    }

    $langcode = $entity->language()->getId();
    if ($original->hasTranslation($langcode)) {
      $original = $original->getTranslation($langcode);
    }

    return array_map('strval', array_column($original->get($field_name)->getValue(), 'value'));
  }

}
