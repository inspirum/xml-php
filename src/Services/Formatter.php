<?php

namespace Inspirum\XML\Services;

use InvalidArgumentException;

class Formatter
{
    /**
     * Parse node name to namespace prefix and local name
     *
     * @param string $name
     *
     * @return array<int,string|null>
     */
    public static function parseQualifiedName(string $name): array
    {
        static::validateElementName($name);

        return array_pad(explode(':', $name, 2), -2, null);
    }

    /**
     * Get local name from node name
     *
     * @param string $name
     *
     * @return string|null
     */
    public static function getLocalName(string $name): ?string
    {
        return static::parseQualifiedName($name)[1];
    }

    /**
     * Get namespace prefix from node name
     *
     * @param string $name
     *
     * @return string|null
     */
    public static function getNamespacePrefix(string $name): ?string
    {
        return static::parseQualifiedName($name)[0];
    }

    /**
     * Validate element name
     *
     * @param string $value
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected static function validateElementName(string $value): void
    {
        $regex = '/^[a-zA-Z][a-zA-Z0-9\_]*(\:[a-zA-Z][a-zA-Z0-9\_]*)?$/';

        if (preg_match($regex, $value) !== 1) {
            throw new InvalidArgumentException(
                sprintf('Element name or namespace prefix [%s] has invalid value', $value)
            );
        }
    }

    /**
     * Normalize value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function encodeValue($value)
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        return $value;
    }

    /**
     * Normalize value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function decodeValue($value)
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
}
