<?php
/**
 * This file is part of Gsnowhawk System.
 *
 * Copyright (c)2016-2017 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk;

/**
 * Package base class interface.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
interface PackageInterface
{
    public function init();

    public function defaultView();

    /**
     * Reference permission.
     *
     * @param string $type
     * @param string $filter1
     * @param string $filter2
     *
     * @return bool
     */
    public function hasPermission($type, $filter1 = null, $filter2 = null);

    /**
     * Identifier of HTML for template engine.
     *
     * @param string $id
     */
    public function setHtmlId($id);

    /**
     * Dynamic navigation for template engine.
     *
     * @return array
     */
    public function navItems();

    /**
     * Return to package name.
     *
     * The return value doesn't include namespace
     *
     * @final
     *
     * @return string The return value must be lowercase
     */
    public static function packageName();

    /**
     * Application name
     *
     * @final
     *
     * @return string
     */
    public static function applicationName();

    /**
     * Application label
     *
     * @final
     *
     * @return string
     */
    public static function applicationLabel();

    /**
     * Return to package version.
     *
     * @final
     * @see Gsnowhawk\System::getVersion()
     *
     * @return string
     */
    public static function version();

    /**
     * Path to templates directory.
     *
     * @final
     *
     * @return string|null
     */
    public static function templateDir();

    /**
     * Application unload action
     */
    public static function unload();
}
