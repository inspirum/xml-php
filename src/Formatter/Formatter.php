<?php

declare(strict_types=1);

namespace Inspirum\XML\Formatter;

use DOMNode;
use InvalidArgumentException;
use Stringable;
use function array_keys;
use function count;
use function explode;
use function in_array;
use function is_bool;
use function is_numeric;
use function is_scalar;
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

        $parsed = explode(':', $name, 2);

        return [
            count($parsed) === 2 ? $parsed[0] : null,
            count($parsed) === 2 ? $parsed[1] : $parsed[0],
        ];
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

        if (is_scalar($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        return null;
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

        if (in_array($value, ['true', 'True'], true)) {
            return true;
        }

        if (in_array($value, ['false', 'False'], true)) {
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
        $value = null;
        /** @var array<string, mixed> $attributes */
        $attributes = [];
        /** @var array<string, array<mixed>> $nodes */
        $nodes = [];

        if ($node->hasAttributes()) {
            /** @var \DOMAttr $attribute */
            foreach ($node->attributes ?? [] as $attribute) {
                $attributes[$attribute->nodeName] = $config->autoCast
                    ? self::decodeValue($attribute->nodeValue)
                    : $attribute->nodeValue;
            }
        }

        if ($node->hasChildNodes()) {
            /** @var \DOMNode $child */
            foreach ($node->childNodes ?? [] as $child) {
                if (in_array($child->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE])) {
                    if (trim((string) $child->nodeValue) !== '') {
                        $value = $config->autoCast
                            ? self::decodeValue($child->nodeValue)
                            : $child->nodeValue;
                    }

                    continue;
                }

                $nodes[$child->nodeName][] = self::nodeToArray($child, $config);
            }
        }

        if ($config->fullResponse) {
            return [
                $config->attributesName => $attributes,
                $config->valueName      => $value,
                $config->nodesName      => $nodes,
            ];
        }

        if (count($nodes) === 0 && count($attributes) === 0) {
            return $value;
        }

        return self::simplifyArray($attributes, $value, $nodes, $config, $node);
    }

    /**
     * Remove unnecessary data
     *
     * @param array<string,mixed>        $attributes
     * @param array<string,array<mixed>> $nodes
     *
     * @return array<int|string,mixed>
     */
    private static function simplifyArray(array $attributes, mixed $value, array $nodes, Config $config, DOMNode $node): array
    {
        $simpleResult = $nodes;
        foreach ($nodes as $nodeName => $values) {
            if (
                in_array($nodeName, $config->alwaysArray, true) === false
                && in_array($node->nodeName . '.' . $nodeName, $config->alwaysArray, true) === false
                && array_keys($values) === [0]
            ) {
                $simpleResult[$nodeName] = $values[0];
            }
        }

        if (count($attributes) > 0) {
            $simpleResult[$config->attributesName] = $attributes;
        }

        if ($value !== null) {
            $simpleResult[$config->valueName] = $value;
        }

        return $simpleResult;
    }
}
