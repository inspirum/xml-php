<?php

declare(strict_types=1);

namespace Inspirum\XML\Tests\Formatter;

use Inspirum\XML\Formatter\DefaultConfig;
use Inspirum\XML\Formatter\Formatter;
use Inspirum\XML\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ConfigTest extends BaseTestCase
{
    public function testAlwaysArrayForAllNodes(): void
    {
        $config = new DefaultConfig(alwaysArray: true);

        self::assertTrue($config->isAlwaysArray());
        self::assertSame([], $config->getAlwaysArrayNodeNames());
    }

    public function testDefaultConfigKeepsRoot(): void
    {
        $config = new DefaultConfig();

        self::assertFalse($config->isWithoutRoot());
    }

    #[DataProvider('provideDecodeNonStringValue')]
    public function testDecodeNonStringValue(mixed $value): void
    {
        self::assertSame($value, Formatter::decodeValue($value));
    }

    /**
     * @return iterable<string,array<string,mixed>>
     */
    public static function provideDecodeNonStringValue(): iterable
    {
        yield 'null' => ['value' => null];
        yield 'integer' => ['value' => 1];
        yield 'boolean' => ['value' => true];
    }
}
