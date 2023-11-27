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

use Gsnowhawk\Common\Auto\Loader as AutoLoader;
use Gsnowhawk\Common\Environment;
use Gsnowhawk\Common\Http;
use Gsnowhawk\Common\Pagination;
use Gsnowhawk\App;
use Gsnowhawk\Base;
use Gsnowhawk\Validator;

/**
 * Common methods for Gsnowhawk System.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
abstract class Common
{
    public const DEFAULT_TEMPLATES_DIR_NAME = 'templates';

    /**
     * Application.
     *
     * @var Gsnowhawk\App
     */
    protected $app;

    /*
     * Pagination class
     *
     * @ver Gsnowhawk\Common\Pagination
     */
    public $pager;

    /**
     * Template file name.
     *
     * @var string
     */
    public $template_file = 'default.html.twig';

    /**
     * Object Constructor.
     */
    public function __construct()
    {
        $params = func_get_args();
        foreach ($params as $param) {
            if (is_object($param) && (get_class($param) === 'Gsnowhawk\\App' || is_subclass_of($param, 'Gsnowhawk\\App'))) {
                $this->app = $param;
            }
        }

        if (is_null($this->app)) {
            throw new \ErrorException('No such application');
        }

        $this->setCurrentApplication();

        $cl = $this->classFromApplicationName($this->currentApp());
        if (!empty($cl) && is_a($this->view, 'Gsnowhawk\\View')) {
            $this->view->addPath($cl::templateDir());
        }

        $this->pager = new Pagination();
    }

    public function setCurrentApplication()
    {
        if (!is_null($this->session) && method_exists($this, 'packageName')) {
            $this->session->param('application_name', $this->packageName());
        }
    }

    /**
     * check setting variables.
     *
     * @return bool
     */
    public function __isset($name)
    {
        switch ($name) {
            case 'app': return false;
            case 'isAjax': return true;
            case 'view': return true;
        }

        return property_exists($this, $name) && !is_null($this->$name);
    }

    /**
     * Getter method.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'app': return;
            case 'db': return $this->app->db;
            case 'env': return $this->app->env;
            case 'isAjax': return Base::isAjax();
            case 'request': return $this->app->request;
            case 'session': return $this->app->session;
            case 'view': return $this->app->view;
        }
        if (false === property_exists($this, $name)) {
            if ($this->app->cnf('global:debugmode') === '1') {
                trigger_error("property `$name` does not exists.", E_USER_ERROR);
            }

            return;
        }

        return $this->$name;
    }

    /**
     * Getting configuration paramater.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    public function cnf($key, $value = null)
    {
        return $this->app->cnf($key, $value);
    }

    /**
     * Modify HTML ID.
     *
     * @param $id
     */
    public function setHtmlId($id, $view = null)
    {
        if (is_null($view)) {
            $view = $this->view;
        }
        $header = $view->param('header');
        $header['id'] = $id;
        $view->bind('header', $header);
    }

    /**
     * Modify HTML Class.
     *
     * @param $class
     */
    public function setHtmlClass($class, $view = null)
    {
        if (is_null($view)) {
            $view = $this->view;
        }
        $header = $view->param('header');
        $header['class'] = $class;
        $view->bind('header', $header);
    }

    /**
     * Append HTML Class.
     *
     * @param $class
     */
    public function appendHtmlClass($class, $view = null)
    {
        if (is_null($view)) {
            $view = $this->view;
        }
        $header = $view->param('header');
        if (isset($header['class'])) {
            $header['class'] = array_merge((array)$header['class'], (array)$class);
            $header['class'] = array_values(array_filter($header['class']));
        } else {
            $header['class'] = $class;
        }
        $view->bind('header', $header);
    }

    /**
     * Validation form data.
     *
     * @param array $valid
     *
     * @return bool
     */
    protected function validate($valid)
    {
        $plugins = $this->app->execPlugin('beforeValidate', $valid);
        foreach ($plugins as $plugin => $results) {
            if (isset($results['valid'])) {
                $valid = array_merge($valid, (array)$results['valid']);
            }
        }

        $errors = [];
        $validator = new Validator($valid);
        $error = $validator->valid($this->request->param(), $this->request->files());
        foreach ($error as $key => $value) {
            if ($value > 0) {
                $errors[$key] = $value;
            }
            $this->app->err[$key] = $value;
        }

        $plugins = $this->app->execPlugin('afterValidate');
        foreach ($this->app->err as $key => $value) {
            if ($value === 0) {
                if (isset($errors[$key])) {
                    unset($errors[$key]);
                }
                continue;
            }
            $errors[$key] = $value;
        }

        return count($errors) === 0;
    }

    /**
     * Create save data.
     *
     * @param string $table_name
     * @param array  $post
     * @param array  $skip
     * @param string $cast_string
     *
     * @return array
     */
    protected function createSaveData($table_name, array $post, array $skip, $cast_string = null)
    {
        $data = [];
        $fields = $this->db->getFields($this->db->TABLE($table_name));
        foreach ($fields as $field) {
            if (in_array($field, $skip)) {
                continue;
            }
            if (!isset($post[$field])) {
                continue;
            }
            $data[$field] = (empty($post[$field]) && $post[$field] !== '0')
                ? null : $post[$field];

            if (is_array($data[$field])) {
                switch ($cast_string) {
                    case 'json':
                        $data[$field] = json_encode($data[$field]);
                        break;
                    case 'serialize':
                        $data[$field] = serialize($data[$field]);
                        break;
                    case 'implode':
                    case 'join':
                        $data[$field] = implode(',', $data[$field]);
                        break;
                }
            }
        }

        return $data;
    }

    public function navItems()
    {
        $nav = [];
        $install_log = $this->app->cnf('global:log_dir') . '/install.log';

        if (file_exists($install_log)) {
            $tmp = file($install_log);
            foreach ($tmp as $line) {
                list($class, $version, $md5) = explode("\t", $line);
                $nav[] = [
                    'code' => $class::packageName(),
                    'name' => $class::applicationName(),
                    'label' => $class::applicationLabel(),
                    'class' => $class,
                ];
            }
        }

        return $nav;
    }

    public function currentApp($type = null): ?string
    {
        $current_app = $this->session->param('application_name');
        switch ($type) {
            case 'basename':
                if (false !== ($index = strpos($current_app, '#'))) {
                    $current_app = substr($current_app, $index + 1);
                }
                break;
            default:
                break;
        }

        return $current_app;
    }

    public function init()
    {
        $config = [
            'global' => [
                'enable_user_alias' => $this->app->cnf('global:enable_user_alias'),
                'assets_path' => $this->app->cnf('global:assets_path'),
            ]
        ];
        $this->view->bind('config', $config);
        $this->view->bind('apps', $this);
        $this->view->bind('nav', $this->navItems());

        if ($cookie = Environment::cookie('script_referer')) {
            $this->view->bind('referer', $cookie);
            setcookie('script_referer', '', time() - 1);
        } elseif ($this->request->param('script_referer')) {
            $this->view->bind('referer', $this->request->param('script_referer'));
        }
    }

    public function staticPath()
    {
        $path = dirname(AutoLoader::convertNameToPath(self::classFromApplicationName($this->app->currentApplication()), true));
        $path = str_replace(Environment::server('document_root'), '', $path);

        return "$path/";
    }

    public function defaultView()
    {
        $args = func_get_args();

        $id = (isset($args[0])) ? $args[0] : 'default';
        $this->setHtmlId($id);

        $plugins = $this->app->execPlugin('beforeRendering', $id);

        $this->view->bind('err', $this->app->err);

        $template = (isset($args[1])) ? $args[1] : strtr($id, '-', '/') . View::TEMPLATE_EXTENTION;
        $this->view->render($template);
    }

    public static function classFromApplicationName($application_name): ?string
    {
        if (is_null($application_name)) {
            return null;
        }

        $namespace = 'Gsnowhawk';
        if (false !== ($index = strpos($application_name, '#'))) {
            $namespace = substr($application_name, 0, $index);
            $application_name = substr($application_name, $index + 1);
        }

        return "\\{$namespace}\\" . ucfirst(strtolower($application_name));
    }

    public function plugin()
    {
        $args = func_get_args();
        $func = array_shift($args);

        $plugins = $this->app->cnf('plugins:paths');
        if (strpos($func, '@') === 0) {
            $callback = str_replace('@', 'plugin\\', $func);
            array_push($args, $this->app);

            list($class, $func) = explode('::', $callback, 2);
            if (!in_array($class, $plugins)) {
                trigger_error("{$func} is not found.");

                return;
            }

            return call_user_func_array($callback, $args);
        } elseif (strpos($func, '::') > 0) {
            list($plugin, $func) = explode('::', $func, 2);
            $plugins = [$plugin];
        } elseif (strpos($func, '~') > 0) {
            $unit = Base::parseMode($func);
            $plugins = [$unit['namespace']];
            $package = $unit['package'];
            $func = $unit['function'];
        }

        $stacks = debug_backtrace();
        foreach ($stacks as $stack) {
            if ($stack['function'] === __FUNCTION__) {
                continue;
            }
            $caller = null;
            if (isset($stack['class'])) {
                if ($stack['class'] === 'Gsnowhawk\\Common' || $stack['class'] === 'Gsnowhawk\\Base') {
                    continue;
                }
                $caller = $stack['class'];
            }
            array_unshift($args, $caller);
            break;
        }

        foreach ($plugins as $plugin) {
            $class = '\\plugin\\' . preg_replace('/^\\\?plugin\\\/', '', $plugin);
            if (isset($package)) {
                $class .= "\\$package";
            }

            if (method_exists($class, $func)) {
                $inst = new $class($this->app);

                return call_user_func_array([$inst, $func], $args);
            }
        }

        trigger_error("{$class} or {$func} is not found.");
    }

    protected function postReceived($message, $status, $response, array $options = [])
    {
        foreach ($options as $option) {
            if (is_callable($option[0])) {
                call_user_func_array($option[0], (array)$option[1]);
            }
        }

        if (is_array($status)) {
            $number = $status['status'];
            unset($status['status']);
            $arguments = $status;
            $status = $number;
        }

        // Response to javascript XMLHttpRequest
        if ($this->isAjax) {
            $content_type = 'text/plain';
            $result = [
                'status' => $status,
                'message' => $message,
            ];

            if (isset($arguments)) {
                $result['arguments'] = $arguments;
            }

            $ret = call_user_func_array($response[0], (array)$response[1]);
            $result['response'] = (is_array($ret))
                ? $ret
                : ['type' => 'replace', 'source' => $ret];

            $callback = $this->request->param('callback');
            if (!empty($callback)) {
                $result['response'] = ['type' => 'callback', 'source' => $callback];
            }

            switch ($this->request->param('returntype')) {
                case 'json':
                    $content_type = 'application/json';
                    $source = json_encode($result);
                    break;
                case 'xml':
                    $content_type = 'text/xml';
                    // TODO: convert array to XML
                    //$source = {XML source code};
                    break;
            }
            Http::responseHeader('Content-type', "$content_type; charset=utf-8");
            echo $source;
            exit;
        }

        // Response to normal HttpRequest
        if ($status === 0) {
            $this->setMessages($message);
        }
        call_user_func_array($response[0], (array)$response[1]);
    }

    protected function redirect($mode, $type = 'redirect')
    {
        if ($type === 'redirect') {
            $mode = preg_replace_callback(
                '/%5C(%[0-9A-F]{2})/',
                function ($match) {
                    return urldecode($match[1]);
                },
                filter_var($mode, FILTER_SANITIZE_ENCODED, FILTER_FLAG_STRIP_HIGH)
            );
            $url = $this->app->systemURI()."?mode=$mode";
        } else {
            $url = $mode;
        }

        if (!$this->isAjax) {
            Http::redirect($url);
        }

        $response = ['type' => $type, 'source' => $url];

        if ($type !== 'redirect' && $type !== 'referer' && $type !== 'reload') {
            list($instance, $function, $args) = $this->app->instance($mode);
            try {
                $instance->init();
                $response['source'] = (is_null($args))
                    ? $instance->$function()
                    : call_user_func_array([$instance, $function], $args);
            } catch (\Exception $e) {
                trigger_error($e->getMessage());
            }
        }

        return $response;
    }

    protected function classNameToMode($instance = null)
    {
        if (is_null($instance)) {
            $instance = $this;
        }

        $mode = strtolower(strtr(get_class($instance), '\\', '.'));

        if (preg_match('/^plugin\.(.+)$/', $mode, $match)) {
            $mode = $match[1];
        }

        if ($instance instanceof Plugin) {
            $mode = preg_replace('/\./', '~', $mode, 1);
        }

        return $mode;
    }

    protected function startPolling()
    {
        if (touch($this->pollingPath())) {
            return microtime(true);
        }

        return false;
    }

    protected function endPolling()
    {
        $polling_file = $this->pollingPath();

        return (file_exists($polling_file)) ? unlink($polling_file) : true;
    }

    protected function updatePolling($data)
    {
        return file_put_contents($this->pollingPath(), $data);
    }

    protected function echoPolling(array $response = null)
    {
        $polling_file = $this->pollingPath();

        $json = [
            'status' => 'ended',
            'response' => [
                'type' => 'callback',
                'source' => 'TM.subform.ended',
            ],
            'arguments' => []
        ];
        if (file_exists($polling_file) && is_file($polling_file)) {
            $json['response']['source'] = 'TM.subform.progress';
            $json['arguments'] = [file_get_contents($polling_file)];
        } elseif (file_exists("$polling_file.log")) {
            $json['response']['source'] = 'TM.subform.showLog';
            $json['arguments'] = [
                $this->request->param('polling_id'),
                $this->classNameToMode().':showPollingLog'
            ];
        }
        if (!is_null($response)) {
            $json['response'] = $response;
        }

        // TODO: $json['finally'] support dynamically setting
        $finally = "$polling_file.finally";
        if (file_exists($finally)) {
            $json['finally'] = json_decode(file_get_contents($finally), true);
            unlink($finally);
        }

        Http::nocache();
        Http::responseHeader('Content-type', 'application/json');
        echo json_encode($json);
        exit;
    }

    protected function pollingPath()
    {
        return implode(
            DIRECTORY_SEPARATOR,
            [$this->app->cnf('global:tmp_dir'),$this->request->param('polling_id')]
        );
    }

    public function showPollingLog()
    {
        Http::nocache();
        Http::responseHeader('Content-type', 'text/plain; charset=utf-8');

        $logfile = $this->pollingPath().'.log';
        if (file_exists($logfile)) {
            readfile($logfile);
            unlink($logfile);
        } else {
            echo 'Log file '.$logfile.' is not found.';
        }

        exit;
    }

    /**
     * template by other application
     */
    protected function useExtendedTemplate()
    {
        $items = $this->navItems();
        foreach ($items as $item) {
            $class = $item['class'];
            $class = '\\'.ltrim($class, '\\');
            if (method_exists($class, 'extendedTemplatePath')) {
                $path = $class::extendedTemplatePath(Http::getURI(), $this);
                if (!empty($path)) {
                    $this->view->prependPath($path);
                }
            }
        }

        $current_dir = getcwd() . '/templates';
        if (file_exists($current_dir)) {
            $this->view->prependPath($current_dir);
        }
    }

    protected function setMessages($message)
    {
        $this->session->param('messages', $message);
    }

    protected function appendMessages($message)
    {
        $origin = $this->session->param('messages');
        $separator = (empty($origin)) ? '' : PHP_EOL;
        $this->session->param('messages', $origin.$separator.$message);
    }

    protected function intoTrash($table_name, $identifier, array $options = [], $inout = '1'): bool
    {
        $data = ['trash' => $inout];
        $statement = 'id = ?';
        $replaces = [$identifier];
        if (isset($options['statement'])) {
            $statement = $options['statement'];
        }
        if (isset($options['replaces'])) {
            $replaces = $options['replaces'];
        }
        if (isset($options['otherdata'])) {
            $data = array_merge($data, $options['otherdata']);
        }

        return $this->db->update($table_name, $data, $statement, $replaces);
    }

    public static function responseJson($json)
    {
        Http::nocache();
        Http::responseHeader('Content-type', 'application/json');
        echo json_encode($json);
        exit;
    }

    public static function responsePDF($path)
    {
        Http::nocache();
        Http::responseHeader('Content-type', 'application/pdf');
        readfile($path);
        exit;
    }

    public static function pageNotFound(App $app)
    {
        header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
        $view = $app->createView();
        $view->bind('url', $_SERVER['REQUEST_URI']);
        $view->render('404.tpl');
        exit;
    }

    public function dataFromDb($columns, $table, $statement, array $options)
    {
        return $this->db->get($columns, $table, $statement, $options);
    }

    public static function fileExists($path)
    {
        return file_exists($path);
    }

    protected function checkRecords($table, $statement, array $options, int $n = 0): void
    {
        $is_empty = true;
        $option = $options[$n];
        if (!empty($option) || $option === 0 || $option === '0') {
            $is_empty = false;
        }

        if (!$is_empty && !$this->db->exists($table, $statement, $options)) {
            trigger_error('Irrigal operation', E_USER_WARNING);
        }
    }
}
