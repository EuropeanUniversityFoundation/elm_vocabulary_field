<?php

declare(strict_types=1);

namespace Drupal\Tests\elm_vocabulary_field\Kernel;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the value constraint on revisions and translations.
 *
 * @group elm_vocabulary_field
 */
class ControlledVocabularyRevisionTranslationTest extends ControlledVocabularyKernelTestBase {

  /**
   * The entity type that is both revisionable and translatable.
   */
  private const ENTITY_TYPE = 'entity_test_mulrev';

  /**
   * The loaded revision is the reference, not the default one.
   */
  public function testLoadedRevisionIsCompared(): void {
    [$a, $b, $c] = $this->keys(static::VOCABULARY);
    $this->createField(self::ENTITY_TYPE, static::VOCABULARY);
    $entity = $this->entity(self::ENTITY_TYPE, [$a]);
    $entity->save();

    // Store B in a non-default revision.
    $entity->setNewRevision(TRUE);
    $entity->isDefaultRevision(FALSE);
    $entity->set('field_elm', [['value' => $b]]);
    $entity->save();
    $draft_id = $entity->getRevisionId();

    $this->setAllowSelection(self::ENTITY_TYPE, [$c => TRUE]);

    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage(self::ENTITY_TYPE);
    $draft = $storage->loadRevision($draft_id);
    $this->assertInstanceOf(ContentEntityInterface::class, $draft);
    $this->assertSame(0, $this->violations($draft));

    // A is only stored in the default revision, so it no longer counts.
    $draft->set('field_elm', [['value' => $a]]);
    $this->assertSame(1, $this->violations($draft));
  }

  /**
   * Each translation is compared with its own stored values.
   */
  public function testTranslationIsCompared(): void {
    ConfigurableLanguage::createFromLangcode('hu')->save();
    [$a, $b, $c] = $this->keys(static::VOCABULARY);
    $this->createField(self::ENTITY_TYPE, static::VOCABULARY);

    $entity = $this->entity(self::ENTITY_TYPE, [$a]);
    $entity->addTranslation('hu', [
      'name' => 'test',
      'field_elm' => [['value' => $b]],
    ]);
    $entity->save();

    $this->setAllowSelection(self::ENTITY_TYPE, [$c => TRUE]);
    $entity = $this->reload($entity);

    $this->assertSame(0, $this->violations($entity));

    $translation = $entity->getTranslation('hu');
    $this->assertSame(0, $this->violations($translation));

    // A is only stored in the source language.
    $translation->set('field_elm', [['value' => $a]]);
    $this->assertSame(1, $this->violations($translation));
  }

}
