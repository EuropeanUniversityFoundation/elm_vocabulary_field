<?php

declare(strict_types=1);

namespace Drupal\Tests\elm_vocabulary_field\Kernel;

use Composer\InstalledVersions;
use Drupal\elm_vocabulary_field\TranslationMergeInterface;
use Drupal\KernelTests\KernelTestBase;
use Gettext\Loader\PoLoader;

/**
 * Tests the translation merge against the installed library.
 *
 * @group elm_vocabulary_field
 */
class TranslationMergeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'elm_vocabulary_field',
  ];

  /**
   * The result of the merge.
   *
   * @var array{written: array<string, string>, skipped: string[]}
   */
  protected array $result;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $service = $this->container->get('elm_vocabulary_field.merge');
    $this->assertInstanceOf(TranslationMergeInterface::class, $service);
    $this->result = $service->mergeTranslations();
  }

  /**
   * The Drupal-friendly locales are written and their sources skipped.
   */
  public function testWrittenAndSkipped(): void {
    foreach (['pt-pt', 'en-gb', 'nb'] as $langcode) {
      $this->assertArrayHasKey($langcode, $this->result['written']);
    }
    foreach (['pt', 'no'] as $directory) {
      $this->assertContains($directory, $this->result['skipped']);
      $this->assertArrayNotHasKey($directory, $this->result['written']);
    }
  }

  /**
   * Every written file exists and loads, and no temporary file is left.
   */
  public function testFilesLoad(): void {
    $loader = new PoLoader();

    $this->assertNotEmpty($this->result['written']);

    foreach ($this->result['written'] as $langcode => $path) {
      $this->assertFileExists($path);
      $this->assertGreaterThan(0, count($loader->loadFile($path)), "File for $langcode is empty.");
    }

    $directory = dirname(reset($this->result['written']));
    $this->assertSame([], glob($directory . '/*.tmp') ?: []);
  }

  /**
   * The Portuguese file holds both module and library translations.
   */
  public function testModuleAndLibraryStringsAreMerged(): void {
    $loader = new PoLoader();
    $compiled = $loader->loadFile($this->result['written']['pt-pt']);

    $module = $compiled->find(NULL, 'Assessment type');
    $this->assertNotNull($module);
    $this->assertSame('Tipo de avaliação', $module->getTranslation());

    $root = InstalledVersions::getInstallPath('euf/elm_vocabularies');
    $files = glob($root . '/translations/pt_PT/LC_MESSAGES/*.po') ?: [];
    $this->assertNotEmpty($files);

    $checked = 0;
    foreach ($loader->loadFile($files[0]) as $source) {
      if (!$source->getTranslation()) {
        continue;
      }
      $found = $compiled->find($source->getContext(), $source->getOriginal());
      $this->assertNotNull($found);
      $this->assertSame($source->getTranslation(), $found->getTranslation());
      if (++$checked >= 5) {
        break;
      }
    }

    $this->assertGreaterThan(0, $checked);
  }

  /**
   * The header names the source files but no server paths.
   */
  public function testHeaderHasNoServerPaths(): void {
    $path = $this->result['written']['pt-pt'];
    $contents = (string) file_get_contents($path);

    $this->assertStringContainsString('Source files:', $contents);
    $this->assertStringNotContainsString($this->root, $contents);
    $this->assertSame('pt-pt', (new PoLoader())->loadFile($path)->getLanguage());
  }

  /**
   * A language without a module translation still gets a language header.
   */
  public function testFallbackLanguageHeader(): void {
    $compiled = (new PoLoader())->loadFile($this->result['written']['nb']);

    $this->assertSame('nb', $compiled->getLanguage());
  }

}
