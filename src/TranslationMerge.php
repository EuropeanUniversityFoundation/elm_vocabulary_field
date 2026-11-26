<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field;

use Gettext\Translations;
use Composer\InstalledVersions;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageManager;
use Gettext\Generator\PoGenerator;
use Gettext\Loader\PoLoader;
use Gettext\Merge;

/**
 * Merges translation files.
 */
class TranslationMerge implements TranslationMergeInterface {

  /**
   * The module machine name.
   */
  const MODULE_NAME = 'elm_vocabulary_field';

  /**
   * The Composer package providing the library translations.
   */
  const LIBRARY = 'euf/elm_vocabularies';

  /**
   * The directory receiving the compiled translations.
   */
  const TARGET_DIRECTORY = 'public://translations';

  /**
   * Library directories covered by a copy under a Drupal-friendly locale.
   *
   * Keep in sync with $localeCopies in the library build script.
   */
  const IGNORED_DIRECTORIES = ['en', 'no', 'pt'];

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * Constructs a TranslationMerge object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function mergeTranslations(): array {
    $module_dir = dirname(__DIR__) . '/translations';
    $module_pot = $module_dir . '/' . self::MODULE_NAME . '.base.pot';
    $lib_dir = $this->libraryDirectory();
    $target_dir = $this->targetDirectory();

    $drupal_languages = LanguageManager::getStandardLanguageList();
    $loader = new PoLoader();
    $generator = new PoGenerator();
    $result = ['written' => [], 'skipped' => []];

    foreach (glob($lib_dir . '/*', GLOB_ONLYDIR) ?: [] as $lang_dir) {
      $dirname = basename($lang_dir);

      if (in_array($dirname, self::IGNORED_DIRECTORIES, TRUE)) {
        $result['skipped'][] = $dirname;
        continue;
      }

      $langcode = strtolower(str_replace('_', '-', $dirname));
      $base = $module_dir . '/' . self::MODULE_NAME . '.base.' . $langcode . '.po';
      $translations = $loader->loadFile(is_file($base) ? $base : $module_pot);

      $sources = [];
      foreach (glob($lang_dir . '/LC_MESSAGES/*.po') ?: [] as $file) {
        $translations = $translations->mergeWith(
          $loader->loadFile($file),
          Merge::COMMENTS_OURS | Merge::HEADERS_OURS,
        );
        $sources[] = basename($file);
      }

      $translations->setLanguage($langcode);

      $translations->setDescription(sprintf(
        "Compiled translation for: %s.\n\nSource files: %s",
        $drupal_languages[$langcode][0] ?? $dirname,
        implode(', ', $sources),
      ));

      $target = sprintf('%s/%s.compiled.%s.po', $target_dir, self::MODULE_NAME, $langcode);
      $this->write($generator, $translations, $target);
      $result['written'][$langcode] = $target;
    }

    return $result;
  }

  /**
   * Returns the library translations directory.
   *
   * @return string
   *   The absolute path.
   *
   * @throws \RuntimeException
   *   If the library or its translations are not found.
   */
  private function libraryDirectory(): string {
    $root = InstalledVersions::getInstallPath(self::LIBRARY);
    $dir = $root === NULL ? '' : $root . '/translations';

    if (!is_dir($dir)) {
      throw new \RuntimeException('Library translations not found.');
    }

    return $dir;
  }

  /**
   * Returns the target directory, creating it if needed.
   *
   * @return string
   *   The absolute path.
   *
   * @throws \RuntimeException
   *   If the directory is missing, not writable or not a local path.
   */
  private function targetDirectory(): string {
    $uri = self::TARGET_DIRECTORY;
    $ready = $this->fileSystem->prepareDirectory(
      $uri,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS,
    );
    $path = $ready ? $this->fileSystem->realpath($uri) : FALSE;

    if ($path === FALSE) {
      throw new \RuntimeException('Cannot write to ' . $uri);
    }

    return $path;
  }

  /**
   * Writes a translation file through a temporary file.
   *
   * The file is renamed into place, so a locale import never reads a partial
   * file.
   *
   * @param \Gettext\Generator\PoGenerator $generator
   *   The PO generator.
   * @param \Gettext\Translations $translations
   *   The translations to write.
   * @param string $target
   *   The absolute target path.
   *
   * @throws \RuntimeException
   *   If the file cannot be written.
   */
  private function write(PoGenerator $generator, Translations $translations, string $target): void {
    $temporary = $target . '.tmp';

    if (!$generator->generateFile($translations, $temporary) || !rename($temporary, $target)) {
      @unlink($temporary);
      throw new \RuntimeException('Failed to write ' . basename($target));
    }
  }

}
