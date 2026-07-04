<?php

namespace Zeretei\PHPCore;

use \DateTime;
use \Zeretei\PHPCore\Application;
use \Zeretei\PHPCore\Http\Request;

class Log
{
    /**
     * Create a log entry
     */
    public static function set(string $error): void
    {
        $start = "=============================================================\n";
        $end = "\n=============================================================\n";
        $date = (new DateTime())->format('Y-m-d H:i:s');
        $ip = Request::ip();
        $route = Request::uri();
        $method = Request::method();

        // ============================================================
        // 2021-12-31 - [GET] www.samplewebsite.com/users - 192.168.0.1
        //  Table: "users" does not exists on Database: samplewebsite
        // ============================================================
        $message = $start . sprintf('%s - [%s] %s %s  %s', $date, $method, $route, $ip . PHP_EOL, $error) . $end;

        $file = Application::get('path.app') . "/logs/log.txt";
        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($file, $message, FILE_APPEND);
    }
}
