<?php

declare(strict_types=1);

namespace Gcego\Components\Support;

/**
 * Accepts same-origin relative URLs and the http, https, mailto and tel schemes.
 *
 * Control characters are rejected outright, and whitespace is ignored while
 * reading the scheme, the way browsers do, so "java\tscript:" is still refused.
 * Protocol-relative URLs ("//host/path", and "/\host" or "\\host", which
 * browsers read the same way) leave the current origin, so they are refused:
 * write the scheme explicitly, as in "https://host/path".
 */
final class SafeUrl
{
    /** @var list<string> */
    public const array Schemes = ['http', 'https', 'mailto', 'tel'];

    /**
     * Schemes that run code when a browser follows or loads them.
     *
     * @var list<string>
     */
    public const array ExecutableSchemes = ['javascript', 'vbscript'];

    public const string Description = 'a relative URL or an http, https, mailto or tel URL';

    public static function isSafe(string $url): bool
    {
        if (trim($url) === '' || preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
            return false;
        }

        $compact = (string) preg_replace('/\s+/', '', $url);

        if (preg_match('#\A[/\\\\]{2}#', $compact) === 1) {
            return false;
        }

        $scheme = self::scheme($compact);

        return $scheme === null || in_array($scheme, self::Schemes, true);
    }

    /**
     * The rule form used by property schemas: a string that isSafe().
     */
    public static function accepts(mixed $value): bool
    {
        return is_string($value) && self::isSafe($value);
    }

    /**
     * Whether a value starts with a scheme that executes code, such as
     * "javascript:", ignoring whitespace and control characters the way browsers do.
     */
    public static function hasExecutableScheme(string $value): bool
    {
        $scheme = self::scheme((string) preg_replace('/[\s\x00-\x1F\x7F]+/', '', $value));

        return $scheme !== null && in_array($scheme, self::ExecutableSchemes, true);
    }

    private static function scheme(string $compact): ?string
    {
        if (preg_match('/\A([A-Za-z][A-Za-z0-9+.\-]*):/', $compact, $matches) !== 1) {
            return null;
        }

        return strtolower($matches[1]);
    }
}
