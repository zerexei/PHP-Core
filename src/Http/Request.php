<?php

namespace Zeretei\PHPCore\Http;

use \Zeretei\PHPCore\Http\Traits\Validator;

class Request
{
    use Validator;

    /**
     * Sanitized input attributes built from $_REQUEST at construction time.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    public function __construct()
    {
        $this->attributes = $this->sanitize($_REQUEST);
    }

    /**
     * Recursively sanitize request input values.
     * Strings are HTML-entity encoded and stripped of tags.
     * Non-string scalars and arrays are handled recursively or passed through.
     */
    protected function sanitize(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        if (!is_string($data)) {
            return $data;
        }
        return strip_tags(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Return the sanitized request URI path (no query string).
     */
    public static function uri(): string
    {
        $url    = trim($_SERVER['REQUEST_URI'] ?? '', '/');
        $filter = (string) filter_var($url, FILTER_SANITIZE_URL);
        return (string) parse_url($filter, PHP_URL_PATH);
    }

    /**
     * Return the uppercased HTTP request method.
     */
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Return a value from the query string, or all query parameters when $key is null.
     */
    public static function query(?string $key = null): mixed
    {
        if (is_null($key)) {
            return $_GET;
        }

        return $_GET[$key] ?? null;
    }

    /**
     * Return a posted value by key regardless of the effective HTTP method.
     *
     * HTML forms can only POST, so _method-spoofed PUT/PATCH/DELETE requests
     * always arrive as POST bodies. This method checks $_POST first, then $_GET,
     * so it works correctly for both real GETs and method-spoofed requests.
     */
    public static function request(string $key): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? null;
    }

    /**
     * Return the client's best-guess IP address.
     *
     * @note HTTP_CLIENT_IP and HTTP_X_FORWARDED_FOR are user-controlled headers
     *       and must NOT be trusted for security decisions without additional
     *       infrastructure-level validation (e.g., trusted proxy lists).
     *       This method takes only the first IP in a comma-delimited list and
     *       validates it is a syntactically valid IP address.
     */
    public static function ip(): string
    {
        $candidates = [
            $_SERVER['HTTP_CLIENT_IP']       ?? '',
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
            $_SERVER['REMOTE_ADDR']          ?? '',
        ];

        foreach ($candidates as $raw) {
            // X-Forwarded-For may be a comma-separated list; take the first entry.
            $ip = trim(explode(',', $raw)[0]);

            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                return $ip;
            }
        }

        return '127.0.0.1';
    }

    /**
     * Set an attribute on the request bag.
     */
    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get an attribute from the request bag.
     *
     * @throws \Exception when the key does not exist.
     */
    public function __get(string $key): mixed
    {
        if (!array_key_exists($key, $this->attributes)) {
            throw new \Exception(
                sprintf('Request attribute "%s" does not exist.', $key)
            );
        }

        return $this->attributes[$key];
    }

    /**
     * Return all sanitized request attributes.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->attributes;
    }
}
