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

use ErrorException;

/**
 * Common accessor methods.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto  <https://www.plus-5.com/>
 */
trait Accessor
{
    /**
     * Getter method.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if ($name === 'app') {
            throw new ErrorException("Cannot access protected property $name");
        }
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        return parent::__get($name);
    }

    /**
     * Check properties exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        if ($name === 'app') {
            return false;
        }
        if (property_exists($this, $name) && !is_null($this->$name)) {
            return true;
        }

        return parent::__isset($name);
    }

    /**
     * Setter Method.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        throw new ErrorException($name.' is no such property');
    }
}
