<?php

declare(strict_types = 1);

namespace Drupal\marvin_phpcs_incubator;

use Drupal\marvin\Utils as MarvinUtils;

/**
 * @see https://github.com/drush-ops/drush/issues/5662
 */
class PhpcsConfigGen {

  protected PhpcsConfigBuilder $builder;

  protected string $packagePath = '';

  public function __construct(PhpcsConfigBuilder $builder) {
    $this->builder = $builder;
  }

  public function generate(string $packagePath): string {
    $this->packagePath = $packagePath;

    $phpExtension = array_keys(MarvinUtils::$drupalPhpExtensions, TRUE);

    $this
      ->builder
      ->reset()
      ->init()
      ->addArg(
        'extensions',
        implode(',', MarvinUtils::prefixSuffixItems($phpExtension, '', '/php'))
      );

    return $this->builder->build();
  }

}
