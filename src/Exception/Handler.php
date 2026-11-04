<?php

declare(strict_types=1);

namespace Inspirum\XML\Exception;

use DOMException;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function str_contains;
use function str_starts_with;

final class Handler
{
    /**
     * @param callable(): T $callback
     *
     * @return T
     *
     * @throws \Inspirum\XML\Exception\ReaderException
     *
     * @template T
     */
    public static function withErrorHandlerForXMLReader(callable $callback): mixed
    {
        return self::withErrorHandler(static function (int $code, string $message): bool {
            if (str_contains($message, 'XMLReader::')) {
                throw new ReaderException($message);
            }

            return false;
        }, $callback);
    }

    /**
     * @param callable(): T $callback
     *
     * @return T
     *
     * @throws \DOMException
     *
     * @template T
     */
    public static function withErrorHandlerForDOMDocument(callable $callback): mixed
    {
        return self::withErrorHandler(static function (int $code, string $message): bool {
            if (str_starts_with($message, 'DOM')) {
                throw new DOMException($message);
            }

            return false;
        }, $callback);
    }

    /**
     * Throw \Inspirum\XML\Exception\ReaderException if XMLReader method failed without warning
     *
     * @param T|false $result
     *
     * @return T
     *
     * @throws \Inspirum\XML\Exception\ReaderException
     *
     * @template T
     */
    public static function checkResultForXMLReader(mixed $result, string $method): mixed
    {
        if ($result === false) {
            throw new ReaderException(sprintf('%s method failed', $method));
        }

        return $result;
    }

    /**
     * Throw \DOMException if DOM method failed without warning
     *
     * @param T|false $result
     *
     * @return T
     *
     * @throws \DOMException
     *
     * @template T
     */
    public static function checkResultForDOMDocument(mixed $result, string $method): mixed
    {
        if ($result === false) {
            throw new DOMException(sprintf('%s method failed', $method));
        }

        return $result;
    }

    /**
     * Register custom error handler to throw exception on warning message
     */
    private static function withErrorHandler(callable $errorCallback, callable $functionCallback): mixed
    {
        set_error_handler($errorCallback);

        try {
            return $functionCallback();
        } finally {
            restore_error_handler();
        }
    }
}
