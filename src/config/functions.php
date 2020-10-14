<?php

declare(strict_types=1);

if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param string $key
     * @param null|mixed $default
     */
    function env($key, $default = null)
    {
        if (isset($_ENV[$key])) {
            $value = $_ENV[$key];
        } elseif (isset($_SERVER[$key])) {
            $value = $_SERVER[$key];
        } elseif (function_exists('putenv') && function_exists('getenv')) {
            $value = getenv($key);
        } else {
            $value = false;
        }
        
        if ($value === false) {
            return $default;
        }
        
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null; // 明确返回 null 或者直接 return 均可
        }
        
        $len = strlen($value);
        
        if ($len > 1 && $value[0] == '"' && $value[$len - 1] == '"') {
            return substr($value, 1, -1);
        }
        
        return $value;
    }
}