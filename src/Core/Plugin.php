<?php

/**
 * This file is part of Gsnowhawk System.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk;

/**
 * Plugin class.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Plugin extends User
{
    /*
     * Using common accessor methods
     */
    use Accessor;

    protected $caller;

    private static $fileExtension = '.php';
    private static $namespaceSeparator = '\\';

    /**
     * Object constructor.
     */
    public function __construct()
    {
        $params = func_get_args();
        call_user_func_array(parent::class.'::__construct', $params);
    }

    /**
     * Register given function as __autoload() implementation.
     *
     * @return bool
     */
    public static function register()
    {
        return spl_autoload_register(self::class.'::autoLoad', true, true);
    }

    /**
     * auto loader.
     *
     * @param string $class_name
     *
     * @return mixed
     */
    private static function autoLoad($class_name)
    {
        if (strpos($class_name, 'plugin\\') === 0
            && false !== ($path = self::isIncludable($class_name))
        ) {
            include_once $path;
        }
    }

    /**
     * Check class file exists.
     *
     * @param string $class_name
     *
     * @return mixed
     */
    private static function isIncludable($class_name)
    {
        if (false === ($path = self::convertNameToPath($class_name, true))
            || strtolower($path) === strtolower($_SERVER['SCRIPT_FILENAME'])
        ) {
            return false;
        }

        return $path;
    }

    /**
     * Convert ClassName to Path.
     *
     * @param string $name
     * @param bool   $fullpath
     *
     * @return string
     */
    private static function convertNameToPath($name, $fullpath = false)
    {
        $path = '';
        $namespace = '';
        if (false !== ($lastNsPos = strripos($name, self::$namespaceSeparator))) {
            $namespace = substr($name, 0, $lastNsPos);
            $name = substr($name, $lastNsPos + 1);
            $path = str_replace(
                self::$namespaceSeparator,
                DIRECTORY_SEPARATOR,
                $namespace
            ).DIRECTORY_SEPARATOR;
        }
        $path .= str_replace('_', DIRECTORY_SEPARATOR, $name).self::$fileExtension;

        // Search include path.
        if ($fullpath !== false) {
            $dirs = explode(PATH_SEPARATOR, ini_get('include_path'));
            foreach ($dirs as $dir) {
                $file = $dir.DIRECTORY_SEPARATOR.$path;
                if (false !== $realpath = realpath($file)) {
                    return $realpath;
                }

                $dest = preg_replace(
                    "/(([^\." . preg_quote(DIRECTORY_SEPARATOR, '/') . ']+)'
                    . preg_quote(self::$fileExtension, '/') . ')$/',
                    '$2/$1',
                    $file
                );
                if (false !== $realpath = realpath($dest)) {
                    return $realpath;
                }
            }

            return false;
        }

        return $path;
    }

    public function setCaller(object $caller)
    {
        $this->caller = $caller;
    }
}
