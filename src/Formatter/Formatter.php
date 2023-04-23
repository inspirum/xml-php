<?php

declare(strict_types=1);

namespace Inspirum\XML\Formatter;

use DOMNode;
use Stringable;
use function array_keys;
use function count;
use function in_array;
use function is_bool;
use function is_numeric;
use function is_scalar;
use function rtrim;
use function sprintf;
use function str_starts_with;
use function trim;
use const XML_CDATA_SECTION_NODE;
use const XML_TEXT_NODE;

final class Formatter
{
    /**
     * Prefix namespaces with xmlns
     *
     * @param array<string,string> $namespaces
     *
     * @return array<string,string>
     */
    public static function namespacesToAttributes(array $namespaces): array
    {
        $attributes = [];

        foreach ($namespaces as $namespaceLocalName => $namespaceValue) {
            if (str_starts_with($namespaceLocalName, 'xmlns') === false) {
                $namespaceLocalName = rtrim(sprintf('xmlns:%s', $namespaceLocalName), ':');
            }

            $attributes[$namespaceLocalName] = $namespaceValue;
        }

        return $attributes;
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
        /** @var array<string,mixed> $attributes */
        $attributes = [];
        /** @var array<string,array<mixed>> $nodes */
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
