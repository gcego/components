<?php

declare(strict_types=1);

namespace Gcego\Components\Support;

/**
 * The rules extra HTML attributes follow, shared by Definitions\Attributes
 * and the extraAttributes property schema so both refuse the same input.
 *
 * Names are letters, digits, "_", ":", "." and "-", starting with a letter
 * or "_". Attributes that execute code are refused: event handlers ("on*"),
 * framework directives ("@*", ":*", "x-*", "wire:*", "hx-*", "v-*"),
 * "srcdoc" and "style". Values are scalars or null. URL attributes, and each
 * candidate of "srcset" and "imagesrcset", must be safe URLs, and no value
 * may start with an executable scheme such as "javascript:", which also
 * covers data-* attributes that scripts copy into URLs.
 */
final class SafeAttributes
{
    public const string NamePattern = '/\A[A-Za-z_][A-Za-z0-9_:.\-]*\z/';

    public const string ExecutablePattern = '/\A(?:on|@|:|x-|wire:|hx-|v-)|\A(?:srcdoc|style)\z/i';

    public const string Description = 'attribute names mapped to scalar or null values, without event handlers, framework directives, srcdoc, style or unsafe URLs';

    /** @var list<string> */
    public const array UrlAttributes = ['href', 'src', 'action', 'formaction', 'poster', 'xlink:href', 'cite', 'background', 'data', 'ping'];

    /** @var list<string> */
    public const array SourceSetAttributes = ['srcset', 'imagesrcset'];

    /**
     * Why the attributes are refused, or null when they are safe.
     *
     * @param  array<array-key, mixed>  $attributes
     * @return array{expectation: string, value: mixed}|null
     */
    public static function violation(array $attributes): ?array
    {
        foreach ($attributes as $name => $value) {
            if (! is_string($name) || preg_match(self::NamePattern, $name) !== 1) {
                return ['expectation' => 'attribute names of letters, digits, "_", ":", "." and "-"', 'value' => $name];
            }

            if (preg_match(self::ExecutablePattern, $name) === 1) {
                return ['expectation' => 'attributes that do not execute code (event handlers, framework directives, srcdoc and style are refused)', 'value' => $name];
            }

            if ($value !== null && ! is_scalar($value)) {
                return ['expectation' => sprintf('a scalar or null value for attribute "%s"', $name), 'value' => $value];
            }

            if (is_string($value) && ! self::isSafeValue(strtolower($name), $value)) {
                return ['expectation' => sprintf('a safe URL for attribute "%s"', $name), 'value' => $value];
            }
        }

        return null;
    }

    public static function isSafe(mixed $attributes): bool
    {
        return is_array($attributes) && self::violation($attributes) === null;
    }

    private static function isSafeValue(string $name, string $value): bool
    {
        if (SafeUrl::hasExecutableScheme($value)) {
            return false;
        }

        if (in_array($name, self::UrlAttributes, true)) {
            return SafeUrl::isSafe($value);
        }

        if (in_array($name, self::SourceSetAttributes, true)) {
            foreach (explode(',', $value) as $candidate) {
                $url = preg_split('/\s+/', trim($candidate))[0] ?? '';

                if ($url !== '' && ! SafeUrl::isSafe($url)) {
                    return false;
                }
            }
        }

        return true;
    }
}
