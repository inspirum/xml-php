<?php

declare(strict_types=1);

namespace Inspirum\XML\Formatter;

use DOMNode;
use InvalidArgumentException;
use function array_keys;
use function array_pad;
use function count;
use function explode;
use function in_array;
use function is_bool;
use function is_numeric;
use function preg_match;
use function sprintf;
use function trim;
use const XML_CDATA_SECTION_NODE;
use const XML_TEXT_NODE;

final class Formatter
{
    /**
     * Parse node name to namespace prefix and local name
     *
     * @return array{0: string|null, 1: string}
     */
    public static function parseQualifiedName(string $name): array
    {
        static::validateElementName($name);

        return array_pad(explode(':', $name, 2), -2, null);
    }

    /**
     * Get local name from node name
     */
    public static function getLocalName(string $name): string
    {
        return static::parseQualifiedName($name)[1];
    }

    /**
     * Get namespace prefix from node name
     */
    public static function getNamespacePrefix(string $name): ?string
    {
        return static::parseQualifiedName($name)[0];
    }

    /**
     * Validate element name
     *
     * @throws \InvalidArgumentException
     */
    protected static function validateElementName(string $value): void
    {
        $regex = '/^[a-zA-Z][a-zA-Z0-9_]*(:[a-zA-Z][a-zA-Z0-9_]*)?$/';

        if (preg_match($regex, $value) !== 1) {
            throw new InvalidArgumentException(
                sprintf('Element name or namespace prefix [%s] has invalid value', $value)
            );
        }
    }

    /**
     * Normalize value.
     */
    public static function encodeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * Normalize value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function decodeValue(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return $value + 0;
        }

        if (in_array($value, ['true', 'True'])) {
            return true;
        }

        if (in_array($value, ['false', 'False'])) {
            return false;
        }

        return $value;
    }

    /**
     * Convert DOM node to array
     *
     * @param \DOMNode                       $node
     * @param \Inspirum\XML\Formatter\Config $config
     *
     * @return mixed
     */
    public static function nodeToArray(DOMNode $node, Config $config): mixed
    {
        $result = [
            $config->attributesName => [],
            $config->valueName      => null,
            $config->nodesName      => [],
        ];

        if ($node->hasAttributes()) {
            /** @var \DOMAttr $attribute */
            foreach ($node->attributes ?? [] as $attribute) {
                $result[$config->attributesName][$attribute->nodeName] = $config->autoCast
                    ? self::decodeValue($attribute->nodeValue)
                    : $attribute->nodeValue;
            }
        }

        if ($node->hasChildNodes()) {
            /** @var \DOMNode $child */
            foreach ($node->childNodes ?? [] as $child) {
                if (in_array($child->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE])) {
                    if (trim((string) $child->nodeValue) !== '') {
                        $result[$config->valueName] = $config->autoCast
                            ? self::decodeValue($child->nodeValue)
                            : $child->nodeValue;
                    }

                    continue;
                }

                $result[$config->nodesName][$child->nodeName][] = self::nodeToArray($child, $config);
            }
        }

        if ($config->fullResponse) {
            return $result;
        }

        if (count($result[$config->nodesName]) === 0 && count($result[$config->attributesName]) === 0) {
            return $result[$config->valueName];
        }

        return self::simplifyArray($result, $config, $node);
    }

    /**
     * Remove unnecessary data
     *
     * @param array<int|string,mixed> $result
     *
     * @return array<int|string,mixed>
     */
    private static function simplifyArray(array $result, Config $config, DOMNode $node): array
    {
        $simpleResult = $result[$config->nodesName];
        foreach ($simpleResult as $nodeName => $values) {
            if (
                in_array($nodeName, $config->alwaysArray) === false
                && in_array($node->nodeName . '.' . $nodeName, $config->alwaysArray) === false
                && array_keys($values) === [0]
            ) {
                $simpleResult[$nodeName] = $values[0];
            }
        }

        if (count($result[$config->attributesName]) > 0) {
            $simpleResult[$config->attributesName] = $result[$config->attributesName];
        }

        if ($result[$config->valueName] !== null) {
            $simpleResult[$config->valueName] = $result[$config->valueName];
        }

        return $simpleResult;
    }
}
