<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageManager;
use Elm\ControlledVocabularyInterface;
use Gettext\Loader\PoLoader;
use Gettext\Generator\PoGenerator;
use Gettext\Merge;

/**
 * Merges translation files.
 */
class TranslationMerge implements TranslationMergeInterface {

  const MODULE_NAME = 'elm_vocabulary_field';

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function mergeTranslations(): void {
    // Module translations.
    $module_po_dir = realpath(__DIR__ . '/../translations');
    $module_po_prefix = implode('.', [self::MODULE_NAME, 'base']);
    $module_po_prefix_path = implode('/', [$module_po_dir, $module_po_prefix]);
    $module_pot = implode('.', [$module_po_prefix, 'pot']);
    $module_pot_path = implode('/', [$module_po_dir, $module_pot]);

    // Drupal languages.
    $drupal_lang_list = LanguageManager::getStandardLanguageList();

    // Library translations.
    $reflector = new \ReflectionClass(ControlledVocabularyInterface::class);
    $lib_src_dir = dirname($reflector->getFileName());
    $lib_po_base_path = realpath($lib_src_dir . '/../translations');
    $lib_po_lang_list = array_diff(scandir($lib_po_base_path), ['.', '..']);

    $lib_po_file_list = [];

    foreach ($lib_po_lang_list as $lang) {
      $lang_po_dir = implode('/', [$lib_po_base_path, $lang, 'LC_MESSAGES']);
      $lib_po_lang_vocab_list = array_diff(scandir($lang_po_dir), ['.', '..']);
      $lib_po_file_list[$lang] = [];

      foreach ($lib_po_lang_vocab_list as $vocab_po_file) {
        $vocab_po_file_path = implode('/', [$lang_po_dir, $vocab_po_file]);
        $lib_po_file_list[$lang][] = $vocab_po_file_path;
      }
    }

    // File mapping.
    $po_prefix = implode('.', [self::MODULE_NAME, 'compiled']);
    $public_path = $this->fileSystem->realpath('public://');
    $public_po_path = implode('/', [$public_path, 'translations']);
    $public_po_prefix_path = implode('/', [$public_po_path, $po_prefix]);
    $merge = [];

    foreach ($lib_po_file_list as $lib_lang => $files) {
      $lang = strtolower(str_replace('_', '-', $lib_lang));
      $merge[$lang] = [
        'base' => implode('.', [$module_po_prefix_path, $lang, 'po']),
        'extra' => $files,
        'result' => implode('.', [$public_po_prefix_path, $lang, 'po']),
      ];
    }

    // Load, merge and generate.
    $loader = new PoLoader();
    $generator = new PoGenerator();
    $strategy = Merge::COMMENTS_OURS;

    foreach ($merge as $lang => $item) {
      if (array_key_exists($lang, $drupal_lang_list)) {
        $base = is_file($item['base']) ? $item['base'] : $module_pot_path;
        $result = $loader->loadFile($base);

        $name = $drupal_lang_list[$lang][0];
        $description = "Compiled translation for: " . $name . ".\n";

        foreach ($item['extra'] as $file) {
          $description .= "\nSource file: " . $file;
          $extra = $loader->loadFile($file);
          $result = $result->mergeWith($extra, $strategy);
        }

        $result->setDescription($description);
        $generator->generateFile($result, $item['result']);
      }
    }
  }

}
