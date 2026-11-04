<?php

declare(strict_types=1);

namespace Inspirum\XML\Formatter;

use DOMDocument;
use DOMNode;
use InvalidArgumentException;
use Stringable;
use function abs;
use function array_keys;
use function array_merge;
use function count;
use function get_debug_type;
use function in_array;
use function is_array;
use function is_bool;
use function is_scalar;
use function is_string;
use function ltrim;
use function preg_match;
use function rtrim;
use function sprintf;
use function strlen;
use function trim;
use const PHP_FLOAT_MIN;
use const XML_CDATA_SECTION_NODE;
use const XML_ELEMENT_NODE;
use const XML_TEXT_NODE;

final class Formatter
{
    private const FLOAT_DIGITS = 15;

    /**
     * Normalize value.
     *
     * @throws \InvalidArgumentException
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

        throw new InvalidArgumentException(sprintf('Value of type %s cannot be converted to string', get_debug_type($value)));
    }

    /**
     * Normalize value.
     */
    public static function decodeValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if (preg_match('/^-?(0|[1-9]\d*)$/', $value) === 1) {
            return self::decodeInteger($value);
        }

        if (preg_match('/^-?(0|[1-9]\d*)\.(\d+)$/', $value, $matches) === 1) {
            return self::decodeFloat($value, $matches[1], $matches[2]);
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
     * Keep integers beyond PHP_INT_MAX as string
     */
    private static function decodeInteger(string $value): int|string
    {
        $integer = (int) $value;

        if ((string) $integer === $value) {
            return $integer;
        }

        return $value;
    }

    /**
     * Keep decimals with more significant digits than float can hold as string, trailing zeros are not significant
     */
    private static function decodeFloat(string $value, string $integerPart, string $fractionalPart): float|string
    {
        $digits = ltrim($integerPart . rtrim($fractionalPart, '0'), '0');
        $float = (float) $value;

        if (strlen($digits) > self::FLOAT_DIGITS) {
            return $value;
        }

        if ($digits !== '' && abs($float) < PHP_FLOAT_MIN) {
            return $value;
        }

        return $float;
    }

    /**
     * Convert DOM node to array
     */
    public static function nodeToArray(DOMNode $node, Config $config): mixed
    {
        $text = null;
        /** @var array<string,mixed> $attributes */
        $attributes = [];
        /** @var array<string,array<mixed>> $nodes */
        $nodes = [];

        if ($node->hasAttributes()) {
            /** @var \DOMAttr $attribute */
            foreach ($node->attributes ?? [] as $attribute) {
                $attributes[$attribute->nodeName] = $config->isAutoCast()
                    ? self::decodeValue($attribute->nodeValue)
                    : $attribute->nodeValue;
            }
        }

        if ($node->hasChildNodes()) {
            /** @var \DOMNode $child */
            foreach ($node->childNodes ?? [] as $child) {
                if (in_array($child->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE], true)) {
                    if (trim((string) $child->nodeValue) !== '') {
                        $text = ($text ?? '') . $child->nodeValue;
                    }

                    continue;
                }

                if ($child->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }

                $childNodes = self::nodeToArray($child, $config);
                if ($config->isFlatten()) {
                    self::flattenArray($nodes, $config->isWithoutRoot() && $node instanceof DOMDocument ? '' : $child->nodeName, $childNodes, $config);
                } else {
                    $nodes[$child->nodeName][] = $childNodes;
                }
            }
        }

        $value = $text !== null && $config->isAutoCast() ? self::decodeValue($text) : $text;

        if ($config->isFullResponse()) {
            return [
                $config->getAttributesName() => $attributes,
                $config->getValueName() => $value,
                $config->getNodesName() => $nodes,
            ];
        }

        if (count($nodes) === 0 && count($attributes) === 0) {
            return $value;
        }

        return self::simplifyArray($attributes, $value, $nodes, $config, $node);
    }

    /**
     * Flatten node to one-dimensional array
     *
     * @param array<string,array<mixed>> $nodes
     */
    private static function flattenArray(array &$nodes, string $nodeNames, mixed $childNodes, Config $config): void
    {
        if (!is_array($childNodes)) {
            $nodes[$nodeNames][] = $childNodes;

            return;
        }

        foreach ($childNodes as $childNodeName => $childNodeValues) {
            if ($childNodeName === $config->getAttributesName() && is_array($childNodeValues)) {
                foreach ($childNodeValues as $attributeName => $attributeValue) {
                    $nodeKey = sprintf('%s%s%s', $nodeNames, $config->getFlattenAttributes(), $attributeName);
                    $nodes[$nodeKey][] = $attributeValue;
                }
            } elseif ($childNodeName === $config->getValueName()) {
                $nodes[$nodeNames][] = $childNodeValues;
            } else {
                $nodeKey = ltrim(sprintf('%s%s%s', $nodeNames, $config->getFlattenNodes(), $childNodeName), $config->getFlattenNodes());
                if (is_array($childNodeValues)) {
                    $nodes[$nodeKey] = array_merge($nodes[$nodeKey] ?? [], $childNodeValues);
                } else {
                    $nodes[$nodeKey][] = $childNodeValues;
                }
            }
        }
    }

    /**
     * Remove unnecessary data
     *
     * @param array<string,mixed> $attributes
     * @param array<string,array<mixed>> $nodes
     *
     * @return array<int|string,mixed>
     */
    private static function simplifyArray(array $attributes, mixed $value, array $nodes, Config $config, DOMNode $node): array
    {
        $simpleResult = $nodes;
        foreach ($nodes as $nodeName => $values) {
            if (
                !$config->isAlwaysArray()
                && in_array($nodeName, $config->getAlwaysArrayNodeNames(), true) === false
                && in_array($node->nodeName . '.' . $nodeName, $config->getAlwaysArrayNodeNames(), true) === false
                && array_keys($values) === [0]
            ) {
                $simpleResult[$nodeName] = $values[0];
            }
        }

        if (count($attributes) > 0) {
            $simpleResult[$config->getAttributesName()] = $attributes;
        }

        if ($value !== null) {
            $simpleResult[$config->getValueName()] = $value;
        }

        return $simpleResult;
    }
}
