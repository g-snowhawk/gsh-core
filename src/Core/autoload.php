<?php
/**
 * This file is part of Gsnowhawk Framework.
 *
 * Copyright (c)2019 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */
spl_autoload_register(function ($className) {
    $directories = explode(PATH_SEPARATOR, ini_get('include_path'));
    if (preg_match('/^\\\\?(Gsnowhawk|plugin)\\\\/', $className)) {
        $filename = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

        // Search include path.
        foreach ($directories as $dir) {
            $fullpath = $dir . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($fullpath)) {
                require_once $fullpath;
                break;
            }

            $fullpath = preg_replace('/\.php$/', '', $fullpath) . DIRECTORY_SEPARATOR . basename($fullpath);
            if (file_exists($fullpath)) {
                require_once $fullpath;
                break;
            }
        }
    } else {
        $classname_separator = (false !== strpos($className, '\\')) ? '\\' : '_';
        $filename = str_replace($classname_separator, DIRECTORY_SEPARATOR, $className) . '.php';

        // Search include path.
        foreach ($directories as $dir) {
            $fullpath = $dir . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($fullpath)) {
                require_once $fullpath;
                break;
            }
        }
    }
});
