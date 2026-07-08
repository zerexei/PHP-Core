<?php
/**
 *  Die, Dump and debug - better format
 */
if (!function_exists('ddd')) {
    function ddd(...$data)
    {
        if (PHP_SAPI === 'cli') {
            var_dump(...$data);
        } else {
            echo "<pre>";
            var_dump(...$data);
            echo "</pre>";
        }
        exit;
    }
}
