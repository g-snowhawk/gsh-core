<?php

/**
 * This file is part of Gsnowhawk Framework.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk;

use ErrorException;

/**
 * Custom Exception.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class PermitException extends ErrorException
{
    /**
     * object constructer.
     *
     * @param string    $message
     * @param int       $code
     * @param Exception $previous
     */
    public function __construct($message, $code = 403, $serverity = E_ERROR, $filename = null, $line = null, $previous = null)
    {
        parent::__construct($message, $code, $serverity, $filename, $line, $previous);
    }
}
