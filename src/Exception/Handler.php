<?php

declare(strict_types=1);

namespace Inspirum\XML\Exception;

use DOMException;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function str_contains;
use function str_starts_with;

/**
 * @internal
 */
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
        return self::withErrorHandler(static function (string $message): ?ReaderException {
            if (str_contains($message, 'XMLReader::')) {
                return new ReaderException($message);
            }

            return null;
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
        return self::withErrorHandler(static function (string $message): ?DOMException {
            if (str_starts_with($message, 'DOM')) {
                return new DOMException($message);
            }

            return null;
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
     * Register custom error handler to throw exception on warning message, other warnings pass to previous error handler
     *
     * @param callable(string): (\Throwable|null) $createException
     * @param callable(): T $callback
     *
     * @return T
     *
     * @template T
     */
    private static function withErrorHandler(callable $createException, callable $callback): mixed
    {
        $previous = null;
        $previous = set_error_handler(static function (int $code, string $message, string $file, int $line) use ($createException, &$previous): bool {
            $exception = $createException($message);
            if ($exception !== null) {
                throw $exception;
            }

            if ($previous === null) {
                return false;
            }

            return $previous($code, $message, $file, $line) !== false;
        });

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }
}
