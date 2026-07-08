<?php

namespace Zeretei\PHPCore;

use \DateTime;
use \Zeretei\PHPCore\Application;
use \Zeretei\PHPCore\Http\Request;

/**
 * Simple file logger.
 *
 * Appends structured log entries to `<path.app>/logs/log.txt`.
 * The logs directory is created automatically with mode 0755 if it does not exist.
 */
class Log
{
    /**
     * Append an error entry to the log file.
     *
     * Entry format:
     * ```
     * =============================================================
     * 2024-01-01 12:00:00 - [GET] /users 192.168.1.1
     * [500] /path/to/file.php
     * The error message here
     * =============================================================
     * ```
     */
    public static function set(string $error): void
    {
        $separator = str_repeat('=', 61);
        $date      = (new DateTime())->format('Y-m-d H:i:s');
        $ip        = Request::ip();
        $route     = Request::uri();
        $method    = Request::method();

        $message = implode(PHP_EOL, [
            $separator,
            sprintf('%s - [%s] %s %s', $date, $method, $route, $ip),
            $error,
            $separator,
            '',
        ]);

        $file = Application::get('path.app') . '/logs/log.txt';
        $dir  = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($file, $message, FILE_APPEND);
    }
}
