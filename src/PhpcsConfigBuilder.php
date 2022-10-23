<?php

declare(strict_types = 1);

namespace Drupal\marvin_phpcs_incubator;

use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;

class PhpcsConfigBuilder {

  protected ?\DOMDocument $doc = NULL;

  public function getDoc(): ?\DOMDocument {
    return $this->doc;
  }

  protected ?\DOMXPath $xpath = NULL;

  public function reset(): static {
    $this->doc = NULL;
    $this->xpath = NULL;

    return $this;
  }

  public function init(string $name = 'Custom'): static {
    if ($this->doc) {
      return $this;
    }

    $this->doc = new \DOMDocument('1.0', 'UTF-8');
    $this->doc->preserveWhiteSpace = TRUE;
    $this->doc->formatOutput = TRUE;

    $ruleset = $this->doc->createElement('ruleset');
    $this->doc->appendChild($ruleset);

    $ruleset->setAttribute('name', $name);

    $ruleset->setAttribute(
      'xmlns:xsi',
      'http://www.w3.org/2001/XMLSchema-instance'
    );

    // @todo Detect "vendor-dir".
    $ruleset->setAttribute(
      'xsi:noNamespaceSchemaLocation',
      './vendor/squizlabs/php_codesniffer/phpcs.xsd'
    );

    $this->xpath = new \DOMXPath($this->doc);

    return $this;
  }

  public function addFile(
    string $fileName,
    ?bool $phpcsOnly = NULL,
    ?bool $phpcbfOnly = NULL,
  ): static {
    $this->init();

    $file = $this->doc->createElement('file');
    $this->doc->firstChild->appendChild($file);
    $file->appendChild($this->doc->createTextNode($fileName));
    if ($phpcsOnly !== NULL) {
      $file->setAttribute('phpcs-only', MarvinIncubatorUtils::boolToString($phpcsOnly, FALSE));
    }

    if ($phpcbfOnly !== NULL) {
      $file->setAttribute('phpcbf-only', MarvinIncubatorUtils::boolToString($phpcbfOnly, FALSE));
    }

    return $this;
  }

  public function addExcludePattern(
    string $pattern,
    ?bool $phpcsOnly = NULL,
    ?bool $phpcbfOnly = NULL,
  ): static {
    $this->init();

    $file = $this->doc->createElement('exclude-pattern');
    $this->doc->firstChild->appendChild($file);
    $file->appendChild($this->doc->createTextNode($pattern));

    if ($phpcsOnly !== NULL) {
      $file->setAttribute('phpcs-only', MarvinIncubatorUtils::boolToString($phpcsOnly, FALSE));
    }

    if ($phpcbfOnly !== NULL) {
      $file->setAttribute('phpcbf-only', MarvinIncubatorUtils::boolToString($phpcbfOnly, FALSE));
    }

    return $this;
  }

  public function addArg(string $name, string $value): static {
    $this->init();

    $arg = $this->doc->createElement('arg');
    $arg->setAttribute('name', $name);
    $arg->setAttribute('value', $value);
    $this->doc->firstChild->appendChild($arg);

    return $this;
  }

  /**
   * @phpstan-param array<string, mixed> $definition
   */
  public function addRule(string $ref, array $definition = []): static {
    $this->init();

    $this->ensureRuleElement($ref);

    if (array_key_exists('message', $definition)) {
      $this->setRuleMessage($ref, $definition['message']);
    }

    if (array_key_exists('severity', $definition)) {
      $this->setRuleSeverity($ref, $definition['severity']);
    }

    if (array_key_exists('type', $definition)) {
      $this->setRuleType($ref, $definition['type']);
    }

    if (array_key_exists('exclude', $definition)) {
      $this->addRuleExclude($ref, $definition['exclude']);
    }

    if (array_key_exists('exclude-pattern', $definition)) {
      $this->addRuleExcludePattern($ref, $definition['exclude-pattern']);
    }

    if (array_key_exists('include-pattern', $definition)) {
      $this->addRuleIncludePattern($ref, $definition['include-pattern']);
    }

    if (array_key_exists('properties', $definition)) {
      $this->addRuleProperties($ref, $definition['properties']);
    }

    return $this;
  }

  /**
   * @phpstan-param iterable<string> $subRefs
   */
  public function addRuleExclude(string $ref, iterable $subRefs): static {
    $this->init();

    $rule = $this->ensureRuleElement($ref);
    foreach ($subRefs as $subRef) {
      $exclude = $this->doc->createElement('exclude');
      $exclude->setAttribute('name', $subRef);
      $rule->appendChild($exclude);
    }

    return $this;
  }

  public function setRuleMessage(string $ref, string $message): static {
    $this->init();

    $rule = $this->ensureRuleElement($ref);
    $this->addUniqueChildElement($rule, 'message', $message);

    return $this;
  }

  public function setRuleSeverity(string $ref, int $severity): static {
    $this->init();

    $rule = $this->ensureRuleElement($ref);
    $this->addUniqueChildElement($rule, 'severity', (string) $severity);

    return $this;
  }

  public function setRuleType(string $ref, string $type): static {
    $this->init();

    $rule = $this->ensureRuleElement($ref);
    $this->addUniqueChildElement($rule, 'type', $type);

    return $this;
  }

  /**
   * @phpstan-param iterable<string> $patterns
   */
  public function addRuleExcludePattern(string $ref, iterable $patterns): static {
    $this->init();
    $rule = $this->ensureRuleElement($ref);
    foreach ($patterns as $pattern) {
      $rule->appendChild($rule->ownerDocument->createElement('exclude-pattern', $pattern));
    }

    return $this;
  }

  /**
   * @phpstan-param iterable<string> $patterns
   */
  public function addRuleIncludePattern(string $ref, iterable $patterns): static {
    $this->init();
    $rule = $this->ensureRuleElement($ref);
    foreach ($patterns as $pattern) {
      $rule->appendChild($rule->ownerDocument->createElement('include-pattern', $pattern));
    }

    return $this;
  }

  /**
   * @phpstan-param iterable<string, string> $properties
   */
  public function addRuleProperties(string $ref, iterable $properties): static {
    $this->init();

    $rule = $this->ensureRuleElement($ref);
    $parent = $this->ensureElement($rule, 'properties');
    foreach ($properties as $name => $value) {
      $elements = $this->xpath->query("./property[@name='$name']", $parent);
      if ($elements && $elements->count()) {
        /** @var \DOMElement $property */
        $property = $elements->item(0);
      }
      else {
        $property = $rule->ownerDocument->createElement('property');
        $parent->appendChild($property);
      }

      $property->setAttribute('name', $name);
      $property->setAttribute('value', $value);
    }

    return $this;
  }

  public function build(): string {
    $this->init();

    return (string) $this->doc->saveXML();
  }

  protected function addUniqueChildElement(\DOMElement $parent, string $name, string $value): static {
    $elements = $parent->getElementsByTagName($name);
    if (!$elements->count()) {
      $parent->appendChild($this->doc->createElement($name, $value));

      return $this;
    }

    $elements->item(0)->nodeValue = $value;

    return $this;
  }

  protected function ensureElement(\DOMElement $parent, string $name): \DOMElement {
    $elements = $parent->getElementsByTagName($name);

    // @phpstan-ignore-next-line
    return $elements->count() ?
      $elements->item(0)
      : $parent->appendChild($parent->ownerDocument->createElement($name));
  }

  protected function ensureRuleElement(string $ref): \DOMElement {
    return $this->findRuleElement($ref) ?: $this->createRuleElement($ref);
  }

  protected function findRuleElement(string $ref): ?\DOMElement {
    $result = $this->xpath->query("/ruleset/rule[@ref='$ref']");

    // @phpstan-ignore-next-line
    return $result ?
      $result->item(0)
      : NULL;
  }

  protected function createRuleElement(string $ref): \DOMElement {
    /** @var \DOMElement $rule */
    $rule = $this
      ->doc
      ->firstChild
      ->appendChild($this->doc->createElement('rule'));
    $rule->setAttribute('ref', $ref);

    return $rule;
  }

}
