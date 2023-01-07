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

use Exception;
use Twig\Extension\DebugExtension;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use cebe\markdown\GithubMarkdown;

use Gsnowhawk\Common\File;
use Gsnowhawk\Common\Html\Format;
use Gsnowhawk\Common\Http;

/**
 * View methods.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class View implements ViewInterface
{
    /**
     * Directory name
     */
    public const TEMPLATE_DIR_NAME = 'templates';

    /**
     * Template extention
     */
    public const TEMPLATE_EXTENTION = '.tpl';

    /**
     * Template Engine.
     *
     * @var Twig_Environment
     */
    private $engine;

    /**
     * Templates directory.
     *
     * @var string
     */
    private $template_dir;
    private $cache_dir;

    /**
     * Template name.
     *
     * @var string
     */
    private $template_name = 'default.tpl';

    /**
     * Twig loader
     *
     * @var object
     */
    private $loader;
    private $context;
    private $rendering = false;

    /**
     * Object Constructor.
     *
     * @param string $path      Templates directory path
     * @param bool   $debugmode Debug mode
     * @param mixed  $cache_dir Cache directory path
     */
    public function __construct($path = null, $debugmode = false, $cache_dir = false)
    {
        $this->setPaths($path);

        // Check cache directory
        if ($debugmode === false) {
            if (is_null($cache_dir)) {
                $cache_dir = dirname(filter_input(INPUT_SERVER, 'SCRIPT_FILENAME')).'/cache';
            }
            if (!file_exists($cache_dir)) {
                try {
                    File::mkdir($cache_dir);
                } catch (\ErrorException $e) {
                    trigger_error("Can't create directory `$cache_dir'", E_USER_ERROR);
                }
            }
        }
        $this->cache_dir = $cache_dir;

        // Using TWIG
        $this->context = ['cache' => $cache_dir, 'debug' => $debugmode];
        $this->resetEngine();
    }

    public function __clone()
    {
        $globals = $this->param();
        $this->resetEngine();
        if (is_array($globals)) {
            foreach ($globals as $key => $value) {
                $this->bind($key, $value);
            }
        }
        $this->rendering = false;
    }

    public function resetEngine()
    {
        $this->loader = new FilesystemLoader($this->template_dir);
        $this->engine = new Environment($this->loader, $this->context);

        if ($this->context['debug']) {
            $this->engine->addExtension(new DebugExtension());
        }

        // Markdown Extension
        $markdown = new GithubMarkdown();
        $markdown->enableNewlines = true;
        $this->engine->addExtension(new View\Markdown\Extension($markdown));
        $this->engine->addExtension(new StringLoaderExtension());
    }

    /**
     * Binding the parameters.
     *
     * @param string $name
     * @param mexed  $value
     *
     * @return mixed
     */
    public function bind($name, $value)
    {
        if ($this->rendering) {
            $dbt = debug_backtrace();
            $trace = array_shift($dbt);
            trigger_error(sprintf(
                "WARNING: Bind `%s' in rendering on %s at %s",
                $name,
                $trace['file'],
                $trace['line']
            ));

            return;
        }

        return $this->engine->addGlobal($name, $value);
    }

    /**
     * Get the parameters from template engine.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function param($name = null)
    {
        $params = $this->engine->getGlobals();
        if (is_null($name)) {
            return $params;
        }

        return (isset($params[$name])) ? $params[$name] : null;
    }

    /**
     * Rendering the template.
     *
     * @param string $template
     * @param bool   $source
     * @param bool   $load_string
     *
     * @return mixed
     */
    public function render($template = null, $nooutput = false, $load_string = false)
    {
        if (is_null($template)) {
            $template = $this->template_name;
        }
        if ($load_string) {
            $this->loader = new ArrayLoader([$this->template_name => $template]);
            $template = $this->template_name;
            $this->engine->setLoader($this->loader);
        }

        $this->bind('session', $_SESSION);

        $this->rendering = true;
        try {
            $source = $this->engine->render($template);
        } catch (Exception $e) {
            $this->rendering = false;
            throw new ViewException($e->getMessage(), $e->getCode(), $e);
        }
        $this->rendering = false;

        // format HTML
        $formatter = new Format('  ', false);
        $source = $formatter->start($source);

        $this->resetEngine();

        if ($nooutput) {
            return $source;
        }

        Http::responseHeader('X-Frame-Options', 'SAMEORIGIN');
        Http::responseHeader('X-XSS-Protection', '1; mode=block');
        Http::responseHeader('X-Content-Type-Options', 'nosniff');

        header_remove('x-powered-by');

        echo $source;

        if ((int)DEBUG_MODE !== 2) {
            exit;
        }
    }

    /**
     * Returns the paths to the templates.
     *
     * @see Twig_Loader_Filesystem::getPaths()
     *
     * @return array
     */
    public function getPaths()
    {
        if (!is_null($this->loader) && get_class($this->loader) === 'Twig\\Loader\\FilesystemLoader') {
            return $this->engine->getLoader()->getPaths();
        }
    }

    /**
     * Set template path
     *
     * @param string $path
     */
    public function setPath($path, $ns = FilesystemLoader::MAIN_NAMESPACE)
    {
        if (is_array($path)) {
            foreach ($path as &$dir) {
                if (!is_dir($dir)) {
                    $dir = null;
                }
            }
            unset($dir);
            $path = array_filter($path);
        } elseif (!is_dir($path)) {
            return false;
        }
        $this->loader->setPaths($path, $ns);
    }

    /**
     * Set template paths
     *
     * @param mixed $path
     */
    public function setPaths($path = null, $ns = FilesystemLoader::MAIN_NAMESPACE)
    {
        $paths = [];
        foreach ((array)$path as $dir) {
            if (is_dir($dir)) {
                $paths[] = $dir;
            }
        }
        $directories = explode(PATH_SEPARATOR, ini_get('include_path'));
        foreach ($directories as $directory) {
            if (false !== $dir = @realpath("$directory/" . self::TEMPLATE_DIR_NAME)) {
                if (preg_match("/^\.+$/", $directory)) {
                    array_unshift($paths, $dir);
                } else {
                    $paths[] = $dir;
                }
            }
        }
        $this->template_dir = array_unique($paths);
        if (!is_null($this->loader) && get_class($this->loader) === 'Twig\\Loader\\FilesystemLoader') {
            $this->loader->setPaths($this->template_dir, $ns);
        }
    }

    /**
     * Adds a path where templates are stored.
     *
     * @see Twig_Loader_Filesystem::addPath()
     */
    public function addPath($path, $ns = FilesystemLoader::MAIN_NAMESPACE)
    {
        if (!empty($path)
            && is_dir($path)
            && !is_null($this->loader)
            && get_class($this->loader) === 'Twig\\Loader\\FilesystemLoader'
        ) {
            $paths = (array)$this->getPaths();
            if (!in_array($path, $paths)) {
                $this->engine->getLoader()->addPath($path, $ns);
            }
        }
    }

    /**
     * Prepends a path where templates are stored.
     *
     * @see Twig_Loader_Filesystem::prependPath()
     */
    public function prependPath($path, $ns = FilesystemLoader::MAIN_NAMESPACE)
    {
        if (!empty($path)
            && is_dir($path)
            && !is_null($this->loader)
            && get_class($this->loader) === 'Twig\\Loader\\FilesystemLoader'
        ) {
            $paths = (array)$this->getPaths();
            if (!in_array($path, $paths)) {
                $this->engine->getLoader()->prependPath($path, $ns);
            } else {
                $paths = array_values(array_diff($paths, [$path]));
                array_unshift($paths, $path);
                $this->engine->getLoader()->setPaths($paths, $ns);
            }
        }
    }

    /**
     * Check template exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function exists($name)
    {
        return $this->engine->getLoader()->exists($name);
    }

    /**
     * Cache directory
     *
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cache_dir;
    }

    /**
     * Clear cache generated by Twig template engine
     *
     * @param string $template_path
     *
     * @return bool
     */
    public function clearCache($template_path)
    {
        if (!file_exists($template_path)) {
            return true;
        }

        $origin = $this->getPaths();

        $cache = $this->engine->getCache(false);
        $this->setPath(dirname($template_path));
        $name = basename($template_path);
        $cache_file = $cache->generateKey($name, $this->engine->getTemplateClass($name));

        $this->setPaths($origin);

        if (!file_exists($cache_file)) {
            return true;
        }

        return File::rmdirs(dirname($cache_file), true);
    }

    /**
     * Clear cache directory
     *
     * @return bool
     */
    public function clearAllCaches()
    {
        $cache_dir = $this->getCacheDir();
        if (empty($cache_dir) || !file_exists($cache_dir)) {
            return true;
        }

        return File::rmdirs($cache_dir, true);
    }

    public function inRendering(): bool
    {
        return $this->rendering;
    }
}
