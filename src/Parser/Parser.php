<?php

declare(strict_types=1);

namespace Inspirum\XML\Parser;

use InvalidArgumentException;
use function count;
use function explode;
use function preg_match;
use function sprintf;

/**
 * @internal
 */
final class Parser
{
    /**
     * Parse node name to namespace prefix and local name
     *
     * @return array{0: string|null, 1: string}
     */
    public static function parseQualifiedName(string $name): array
    {
        self::validateElementName($name);

        if ($name === 'xmlns') {
            return [$name, ''];
        }

        $parsed = explode(':', $name, 2);

        if (count($parsed) === 2) {
            return $parsed;
        }

        return [null, $parsed[0]];
    }

    /**
     * Get local name from node name
     */
    public static function getLocalName(string $name): string
    {
        return self::parseQualifiedName($name)[1];
    }

    /**
     * Get namespace prefix from node name
     */
    public static function getNamespacePrefix(string $name): ?string
    {
        return self::parseQualifiedName($name)[0];
    }

    /**
     * Validate element name
     *
     * @throws \InvalidArgumentException
     */
    private static function validateElementName(string $value): void
    {
        $regex = '/^([a-zA-Z_][\w.-]*)(:[a-zA-Z_][\w.-]*)?$/';

        if (preg_match($regex, $value) !== 1) {
            throw new InvalidArgumentException(
                sprintf('Element name or namespace prefix [%s] has invalid value', $value),
            );
        }
    }

    /**
     * Prefix namespaces with xmlns
     *
     * @param array<string,string> $attributes
     *
     * @return array<string,string>
     */
    public static function parseNamespaces(array $attributes): array
    {
        $namespaces = [];

        foreach ($attributes as $attributeName => $attributeValue) {
            [$prefix, $namespaceLocalName] = self::parseQualifiedName($attributeName);

            if ($prefix === 'xmlns' && $attributeValue !== '') {
                $namespaces[$namespaceLocalName] = $attributeValue;
            }
        }

        return $namespaces;
    }
}
