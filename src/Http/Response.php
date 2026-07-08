<?php

namespace Zeretei\PHPCore\Http;

/**
 * HTTP response helpers.
 *
 * Provides status code management and redirect utilities.
 * Constants follow the RFC 9110 status code registry.
 */
class Response
{
    // 2xx — Success
    public const HTTP_OK             = 200;
    public const HTTP_CREATED        = 201;
    public const HTTP_NO_CONTENT     = 204;

    // 3xx — Redirection
    public const HTTP_MOVED_PERMANENTLY  = 301;
    public const HTTP_FOUND              = 302;
    public const HTTP_TEMPORARY_REDIRECT = 307;

    // 4xx — Client Errors
    public const HTTP_BAD_REQUEST     = 400;
    public const HTTP_UNAUTHORIZED    = 401;
    public const HTTP_FORBIDDEN       = 403;
    public const HTTP_NOT_FOUND       = 404;
    public const HTTP_METHOD_NOT_ALLOWED  = 405;
    public const HTTP_REQUEST_TIMEOUT = 408;
    public const HTTP_TOO_MANY_REQUESTS   = 429;

    // 5xx — Server Errors
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_BAD_GATEWAY           = 502;
    public const HTTP_SERVICE_UNAVAILABLE   = 503;

    /**
     * Return whether $code falls within the valid HTTP status code range (100–599).
     */
    public static function isValidCode(int $code): bool
    {
        return $code >= 100 && $code <= 599;
    }

    /**
     * Set the HTTP response status code.
     *
     * @throws \Exception when $code is outside the 100–599 range.
     */
    public static function setStatusCode(int $code): void
    {
        if (!static::isValidCode($code)) {
            throw new \Exception(
                sprintf('HTTP status code %d is invalid (must be 100–599).', $code)
            );
        }

        http_response_code($code);
    }

    /**
     * Send a redirect response to $path and terminate.
     *
     * If $path is a fully-qualified URL it is used as-is. Otherwise it is
     * treated as a dot-notation or slash-notation sub-path (dots become slashes).
     *
     * Does nothing when headers have already been sent.
     */
    public function redirect(string $path, int $status = self::HTTP_FOUND): void
    {
        if (headers_sent()) {
            return;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $location = $path;
        } else {
            $location = '/' . trim(str_replace('.', '/', $path), '/');
        }

        header('Location: ' . $location, true, $status);
        exit;
    }
}
