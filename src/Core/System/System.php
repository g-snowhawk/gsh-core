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

use ErrorException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

/**
 * User management class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class System extends User
{
    /*
     * Using common accessor methods
     */
    use Accessor;

    public const CLASS_PATH = 'manifesto';
    public const CLASS_FILE = 'setup.php';
    public const CLASS_NAME = 'Setup';

    private $checksum = [];
    private $checksum_plugins = [];

    /**
     * Object constructor.
     */
    public function __construct()
    {
        $params = func_get_args();
        call_user_func_array(parent::class.'::__construct', $params);

        $this->loadChecksum();
    }

    private function loadChecksum()
    {
        $log_dir = $this->app->cnf('global:log_dir');
        if (!file_exists($log_dir)) {
            @mkdir($log_dir, 0777, true);
        }
        $install_log = "{$log_dir}/install.log";
        if (file_exists($install_log) && false !== $fh = fopen($install_log, 'r')) {
            while (false !== $unit = fgetcsv($fh, 0, "\t", '"', '\\')) {
                $this->checksum[$unit[0]] = ['version' => $unit[1], 'md5' => $unit[2]];
            }
        }

        $plugins_log = $this->app->cnf('global:log_dir') . '/plugins.log';
        if (file_exists($plugins_log) && false !== $fh = fopen($plugins_log, 'r')) {
            while (false !== $unit = fgetcsv($fh, 0, "\t", '"', '\\')) {
                $this->checksum_plugins[$unit[0]] = ['version' => $unit[1], 'md5' => $unit[2]];
            }
        }
    }

    protected function saveChecksum($logfile = 'install.log')
    {
        $install_log = $this->app->cnf('global:log_dir') . "/$logfile";

        $checksums = ($logfile === 'install.log') ? $this->checksum : $this->checksum_plugins;
        $src = '';
        foreach ($checksums as $key => $value) {
            $src .= implode("\t", [$key, $value['version'], $value['md5']]) . PHP_EOL;
        }

        return file_put_contents($install_log, $src);
    }

    protected function setChecksum($unit)
    {
        $this->checksum[$unit[0]] = ['version' => $unit[1], 'md5' => $unit[2]];
    }

    protected function getPackageMd5($key)
    {
        return (isset($this->checksum[$key])) ? $this->checksum[$key]['md5'] : null;
    }

    protected function getPackageVersion($key)
    {
        return (isset($this->checksum[$key])) ? $this->checksum[$key]['version'] : null;
    }

    protected function setChecksumPlugins($unit)
    {
        $this->checksum_plugins[$unit[0]] = ['version' => $unit[1], 'md5' => $unit[2]];
    }

    protected function getPluginMd5($key)
    {
        return (isset($this->checksum_plugins[$key])) ? $this->checksum_plugins[$key]['md5'] : null;
    }

    protected function getPluginVersion($key)
    {
        return (isset($this->checksum_plugins[$key])) ? $this->checksum_plugins[$key]['version'] : null;
    }

    public static function getVersion($package)
    {
        try {
            $path = str_replace('\\', DIRECTORY_SEPARATOR, $package) . '/manifesto/setups.php';
            include_once($path);
            $class = "\\$package\\setup";

            return $class::VERSION;
        } catch (ErrorException $e) {
            // Noop
        }

        return 'unknown';
    }

    protected static function loadAllIncludes($path)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path,
                FilesystemIterator::SKIP_DOTS|FilesystemIterator::CURRENT_AS_FILEINFO
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $name => $info) {
            if ($info->isDir() === false) {
                continue;
            }
            if ($info->isLink() !== false) {
                self::loadAllIncludes($info->getRealPath());
                continue;
            }

            if ($info->getBaseName() === self::CLASS_PATH) {
                $include = $info->getPathName() . DIRECTORY_SEPARATOR . self::CLASS_FILE;
                self::includeOnce($include);
            }
        }
    }

    protected static function loadAllByAutoLoader()
    {
        foreach (get_declared_classes() as $class) {
            if (strpos($class, 'ComposerAutoloaderInit') === 0) {
                $loader = $class::getLoader();
                $prefixes = array_merge(
                    $loader->getPrefixesPsr4(),
                    $loader->getPrefixes()
                );
                foreach ($prefixes as $prefix) {
                    foreach ($prefix as $include) {
                        self::loadAllIncludes($include);
                    }
                }
                foreach ($loader->getClassMap() as $include) {
                    if (basename($include) === self::CLASS_FILE
                        && basename(dirname($include)) === self::CLASS_PATH
                    ) {
                        self::includeOnce($include);
                    }
                }
                break;
            }
        }
    }

    protected static function includeOnce($path)
    {
        if (file_exists($path)) {
            $source = php_strip_whitespace($path);
            $class_name = [];
            $patterns = ["/namespace\s+([^\s]+)\s*;/","/class\s+([^\s]+)[^\{]*\{/s"];
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $source, $match)) {
                    $class_name[] = $match[1];
                }
            }
            if (!class_exists(implode('\\', $class_name))) {
                include_once($path);
            }
        }
    }
}
