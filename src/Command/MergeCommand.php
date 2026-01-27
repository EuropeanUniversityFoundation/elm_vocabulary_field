<?php

declare(strict_types=1);

namespace Drupal\elm_vocabulary_field\Command;

use Drupal\elm_vocabulary_field\TranslationMergeInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: 'elm_vocabulary_field:merge',
  description: 'Merges ELM vocabulary translations',
  aliases: ['elm-merge'],
)]
final class MergeCommand extends Command {

  /**
   * Constructs a MergeCommand object.
   */
  public function __construct(
    private readonly TranslationMergeInterface $elmVocabularyFieldMerge,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->elmVocabularyFieldMerge->mergeTranslations();
    $output->writeln('<info>ELM vocabulary translations merged.</info>');
    return self::SUCCESS;
  }

}
