<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_phpcs_incubator;

use Drupal\marvin\Attributes as MarvinCLI;
use Drupal\marvin_incubator\Attributes as MarvinIncubatorCLI;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\marvin_phpcs\PhpcsCommandsBase;
use Robo\Collection\CollectionBuilder;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputInterface;

class PhpcsCommands extends PhpcsCommandsBase {

  use CommandsBaseTrait;

  /**
   * @hook on-event marvin:git-hook:pre-commit
   *
   * @phpstan-return array<string, marvin-task-definition>
   */
  public function onEventMarvinGitHookPreCommit(InputInterface $input): array {
    $package = $this->normalizeManagedDrupalExtensionName($input->getArgument('packagePath'));

    return [
      'marvin.lint.phpcs' => [
        'weight' => -200,
        'task' => $this->cmdMarvinLintPhpcsExecute([$package['name']]),
      ],
    ];
  }

  /**
   * @hook on-event marvin:lint
   *
   * @phpstan-return array<string, marvin-task-definition>
   */
  public function onEventMarvinLint(InputInterface $input): array {
    return [
      'marvin.lint.phpcs' => [
        'weight' => -200,
        'task' => $this->cmdMarvinLintPhpcsExecute($input->getArgument('packageNames')),
      ],
    ];
  }

  /**
   * Runs PHP Code Sniffer.
   *
   * @param array<string> $packageNames
   *   See: `drush help marvin:managed-drupal-extension:list`.
   */
  #[MarvinIncubatorCLI\ValidatePackageNames(
    locators: [
      [
        'type' => 'argument',
        'name' => 'packageNames',
      ],
    ],
  )]
  #[MarvinCLI\PreCommandInitLintReporters()]
  #[CLI\Command(name: 'marvin:lint:phpcs')]
  #[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
  #[CLI\Argument(
    name: 'packageNames',
    description: ' Package names. See: `drush marvin:managed-drupal-extension:list`',
  )]
  #[CLI\Complete(method_name_or_callable: 'complete')]
  #[CLI\Usage(
    name: "drush marvin:lint:phpcs 'my_module_01' 'drupal/my_module_02'",
    description: 'Runs PHPCS for managed extensions my_module_01 and my_module_02',
  )]
  public function cmdMarvinLintPhpcsExecute(array $packageNames): CollectionBuilder {
    $managedDrupalExtensions = $this->getManagedDrupalExtensions();
    $cb = $this->collectionBuilder();
    foreach ($packageNames as $packageName) {
      $package = $managedDrupalExtensions[$packageName];
      $cb->addTask($this->getTaskLintPhpcsExtension($package['path']));
    }

    return $cb;
  }

  public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void {
    if ($input->mustSuggestArgumentValuesFor('packageNames')) {
      $suggestions->suggestValues(array_keys($this->getManagedDrupalExtensions()));
    }
  }

}
