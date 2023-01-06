<?php

declare(strict_types=1);

namespace Inspirum\XML\Exception;

use DOMException;
use Exception;
use function restore_error_handler;
use function set_error_handler;
use function str_contains;

final class Handler
{
    /**
     * @param callable(): T $callback
     *
     * @return T
     *
     * @throws \Exception
     *
     * @template T
     */
    public static function withErrorHandlerForXMLReader(callable $callback): mixed
    {
        return self::withErrorHandler(static function (int $code, string $message): bool {
            if (str_contains($message, 'XMLReader::')) {
                throw new Exception($message, $code);
            }

            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreENd
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
            if (str_contains($message, 'DOMDocument::')) {
                throw new DOMException($message, $code);
            }

            return false;
        }, $callback);
    }

    /**
     * Register custom error handler to throw \Exception on warning message
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
