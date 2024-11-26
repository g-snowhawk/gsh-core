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
 * Custom Error Handler.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Error extends \Gsnowhawk\Common\Error
{
    /**
     * Error Log Type.
     *
     * @string
     */
    private $_errorType = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Perse',
        E_NOTICE => 'Notice',
        E_USER_ERROR => 'U_Error',
        E_USER_WARNING => 'U_Warning',
        E_USER_NOTICE => 'U_Notice',
    ];

    /**
     * Object Constructor.
     */
    public function __construct($template = null)
    {
        if (!empty($template) && !is_file($template)) {
            $paths = explode(PATH_SEPARATOR, get_include_path());
            foreach ($paths as $path) {
                $fullpath = $path . DIRECTORY_SEPARATOR . $template;
                if (is_file($fullpath)) {
                    $found = $fullpath;
                    break;
                }
            }
            $template = (isset($found)) ? $found : realpath(__DIR__ . '/../templates/Error.tpl');
        }

        parent::__construct($template);
    }

    /**
     * New error handler.
     *
     * @param int    $errno
     * @param string $errstr
     * @param string $errfile
     * @param int    $errline
     * @param array  $errcontext
     */
    public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext = null)
    {
        parent::errorHandler($errno, $errstr, $errfile, $errline, $errcontext);
    }
}
