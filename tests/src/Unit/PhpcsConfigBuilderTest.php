<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_phpcs_incubator\Unit;

use Drupal\marvin_phpcs_incubator\PhpcsConfigBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Drupal\marvin_incubator\PhpcsConfigBuilder
 */
class PhpcsConfigBuilderTest extends TestCase {

  public function testAllInOne(): void {
    $builder = new PhpcsConfigBuilder();

    $rootAttributes = implode(' ', [
      'name="Custom"',
      'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"',
      'xsi:noNamespaceSchemaLocation="./vendor/squizlabs/php_codesniffer/phpcs.xsd"',
    ]);

    $expected = implode(\PHP_EOL, [
      '<?xml version="1.0" encoding="UTF-8"?>',
      "<ruleset $rootAttributes/>",
      '',
    ]);
    static::assertSame($expected, $builder->build());

    $expectedPrefix = implode(\PHP_EOL, [
      '<?xml version="1.0" encoding="UTF-8"?>',
      "<ruleset $rootAttributes>",
      '',
    ]);
    $expectedSuffix = implode(\PHP_EOL, [
      '</ruleset>',
      '',
    ]);

    $expectedBody = implode(\PHP_EOL, [
      '  <file>./a.php</file>',
      '  <file phpcs-only="true" phpcbf-only="true">./b.php</file>',
      '  <file phpcs-only="false" phpcbf-only="false">./c.php</file>',
      '',
    ]);
    $builder->addFile('./a.php');
    $builder->addFile('./b.php', TRUE, TRUE);
    $builder->addFile('./c.php', FALSE, FALSE);
    static::assertSame($expectedPrefix . $expectedBody . $expectedSuffix, $builder->build());

    $expectedBody .= implode(\PHP_EOL, [
      '  <exclude-pattern>./Generated/A/</exclude-pattern>',
      '  <exclude-pattern phpcs-only="true" phpcbf-only="true">./Generated/B/</exclude-pattern>',
      '  <exclude-pattern phpcs-only="false" phpcbf-only="false">./Generated/C/</exclude-pattern>',
      '',
    ]);
    $builder->addExcludePattern('./Generated/A/');
    $builder->addExcludePattern('./Generated/B/', TRUE, TRUE);
    $builder->addExcludePattern('./Generated/C/', FALSE, FALSE);
    static::assertSame($expectedPrefix . $expectedBody . $expectedSuffix, $builder->build());

    $expectedBody .= implode(\PHP_EOL, [
      '  <arg name="foo" value="bar"/>',
      '',
    ]);
    $builder->addArg('foo', 'bar');
    static::assertSame($expectedPrefix . $expectedBody . $expectedSuffix, $builder->build());

    $expectedBody .= implode(\PHP_EOL, [
      '  <rule ref="RuleA"/>',
      '',
    ]);
    $builder->addRule('RuleA');
    static::assertSame($expectedPrefix . $expectedBody . $expectedSuffix, $builder->build());

    $expectedBody .= implode(\PHP_EOL, [
      '  <rule ref="RuleB">',
      '    <message>My message 01</message>',
      '    <severity>2</severity>',
      '    <type>error</type>',
      '    <exclude name="RuleB.Foo"/>',
      '    <exclude name="RuleB.Bar"/>',
      '    <exclude-pattern>a.php</exclude-pattern>',
      '    <exclude-pattern>b.php</exclude-pattern>',
      '    <include-pattern>c.php</include-pattern>',
      '    <include-pattern>d.php</include-pattern>',
      '    <properties>',
      '      <property name="prop01" value="value01"/>',
      '      <property name="prop02" value="value03"/>',
      '      <property name="prop03" value="value04"/>',
      '    </properties>',
      '  </rule>',
      '',
    ]);
    $builder->addRule(
      'RuleB',
      [
        'message' => 'My message 01',
        'severity' => 2,
        'type' => 'error',
        'exclude' => [
          'RuleB.Foo',
          'RuleB.Bar',
        ],
        'exclude-pattern' => [
          'a.php',
          'b.php',
        ],
        'include-pattern' => [
          'c.php',
          'd.php',
        ],
        'properties' => [
          'prop01' => 'value01',
          'prop02' => 'value02',
        ],
      ],
    );
    $builder->addRuleProperties(
      'RuleB',
      [
        'prop02' => 'value03',
        'prop03' => 'value04',
      ],
    );
    static::assertSame($expectedPrefix . $expectedBody . $expectedSuffix, $builder->build());

    $builder->reset();
    $expected = implode(\PHP_EOL, [
      '<?xml version="1.0" encoding="UTF-8"?>',
      "<ruleset $rootAttributes/>",
      '',
    ]);
    static::assertSame($expected, $builder->build());

    $expectedBody = implode(\PHP_EOL, [
      '  <rule ref="RuleA">',
      '    <message>My message 01</message>',
      '  </rule>',
      '',
    ]);
    $builder->setRuleMessage('RuleA', 'My message 01');
    static::assertSame($expectedPrefix . $expectedBody . $expectedSuffix, $builder->build());

    $expectedBody = implode(\PHP_EOL, [
      '  <rule ref="RuleA">',
      '    <message>My message 02</message>',
      '  </rule>',
      '',
    ]);
    $builder->setRuleMessage('RuleA', 'My message 02');
    static::assertSame($expectedPrefix . $expectedBody . $expectedSuffix, $builder->build());

    static::assertSame(
      $expectedPrefix . $expectedBody . $expectedSuffix,
      $builder->getDoc()->saveXML(),
    );
  }

}
