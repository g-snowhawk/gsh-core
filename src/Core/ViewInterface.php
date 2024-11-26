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
 * Interface for View class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
interface ViewInterface
{
    /**
     * Binding the parameters.
     *
     * @param string $name
     * @param mexed  $value
     *
     * @return mixed
     */
    public function bind($name, $value);

    /**
     * Get the parameters from template engine.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function param($name = null);

    /**
     * Rendering the template.
     *
     * @param string $template
     * @param bool   $source
     * @param bool   $load_string
     *
     * @return mixed
     */
    public function render($template, $source = false, $load_string = false);
}
