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
use Exception;
use LogicException;
use Gsnowhawk\Common\Config\Parser as ConfigParser;
use Gsnowhawk\Common\Environment as Env;
use Gsnowhawk\Common\Error as Err;
use Gsnowhawk\Common\Html\Form;
use Gsnowhawk\Common\Http;
use Gsnowhawk\Common\Lang;
use Gsnowhawk\Common\Session;
use Gsnowhawk\Common\Text;
use Gsnowhawk\Common\Variable;
use Gsnowhawk\Security;
use ReflectionClass;

/**
 * Common accessor methods.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto  <www.plus-5.com>
 */
abstract class Base
{
    /**
     * Default configuration file path.
     */
    public const INIFILE = 'config.php';

    /**
     * The name of guest user
     */
    public const GUESTNAME = 'guest';

    /**
     * Execute default function name
     */
    public const DEFAULT_METHOD = 'default-view';

    /**
     * Default mode on authorization failed
     */
    public const DEFAULT_RESPONSE = 'system.response:failed';

    /**
     * Default session name
     */
    public const SESSION_NAME = 'TMSTICKETID';

    /**
     * Database class.
     *
     * @var Gsnowhawk\Common\Db
     */
    private $db;

    /**
     * Environment class.
     *
     * @var Gsnowhawk\Common\Envitonment
     */
    private $env;

    /**
     * Form class.
     *
     * @var Gsnowhawk\Common\Html\Form
     */
    private $request;

    /**
     * Session class.
     *
     * @var Gsnowhawk\Common\Session
     */
    private $session;

    /**
     * Template Engine.
     *
     * @var Twig_Environment
     */
    private $view;

    /**
     * configuration object.
     *
     * @var Gsnowhawk\Common\Config\Parser
     */
    private $configuration;

    /**
     * System logger.
     *
     * @var object
     */
    private $logger;

    /**
     * Error Messges.
     *
     * @var array
     */
    public $err;

    /**
     * Object constructer.
     */
    public function __construct($errTemplate = null)
    {
        $this->loadConfiguration();

        if (!defined('ERROR_LOG_DESTINATION')) {
            define('ERROR_LOG_DESTINATION', $this->cnf('global:log_dir').'/error.log');
        }

        $this->logger = new Logger(dirname(ERROR_LOG_DESTINATION), $this);
        $this->env = new Env();
        $this->request = new Form();

        Plugin::register();

        if (!is_null($this->cnf('database:db_host'))) {
            $this->createDbInstance();
            // Open database
            if (!$this->db->open()) {
                trigger_error('Could not open database connection. ', E_USER_ERROR);
            }
        }

        $save_path = null;
        if (defined('DONT_CHANGE_SESSION_SAVE_PATH') && DONT_CHANGE_SESSION_SAVE_PATH === 0) {
            $save_path = $this->cnf('global:tmp_dir');
        }

        $path = (string)$this->cnf('session:cookie_path');
        if (false !== strpos($path, '*')) {
            $pattern = preg_quote($path, '/');
            $pattern = '/^('.str_replace(['\\*\\*', '\\*'], ['.+', '[^\/]+'], $pattern).')/';
            if ($s = preg_match($pattern, Env::server('request_uri'), $match)) {
                $path = $match[1];
            }
        }
        $domain = (string)$this->cnf('session:domain');
        $secure = (Env::server('https') === 'on' || Env::server('http_x_forwarded_proto'));
        $httponly = true;

        if (php_sapi_name() === 'cli') {
            $options = getopt('', ['phpsessid:']);
            if (isset($options['phpsessid'])) {
                session_id($options['phpsessid']);
            }
        }

        $this->session = new Session('nocache', $save_path, 0, $path, $domain, $secure, $httponly);
        $name = $this->cnf('session:name');
        if (empty($name)) {
            $name = self::SESSION_NAME;
        }
        $this->session->setName($name);
        $this->session->start();

        // Create based view instance
        $current_appname = $this->session->param('application_name');
        $this->session->clear('application_name');
        $this->view = $this->createView();
        $this->session->param('application_name', $current_appname);
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
        if (false === property_exists($this, $name) &&
            false === property_exists(__CLASS__, $name)
        ) {
            if ((int)DEBUG_MODE > 0) {
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
        return $this->configuration->param($key, $value);
    }

    /**
     * Load configration from file
     */
    public function loadConfiguration()
    {
        $config = (defined('CONFIG_FILE')) ? CONFIG_FILE : self::INIFILE;
        if (!file_exists($config)) {
            throw new ErrorException('Not found configurarion file.', 90990);
        }
        $this->configuration = new ConfigParser($config);
    }

    /**
     * Refreshing configuration file
     *
     * @param array $conf
     *
     * @return bool
     */
    public function refreshConfiguration(array $conf)
    {
        $config_file = (defined('CONFIG_FILE')) ? CONFIG_FILE : self::INIFILE;
        if (!is_writable($config_file)) {
            $config_file = $this->cnf('global:data_dir') . '/config.php';
        }

        if (isset($conf['plugins']) && isset($conf['plugins']['paths'])) {
            $conf['plugins']['paths'] = array_values(
                array_unique($conf['plugins']['paths'])
            );
        }

        $configuration = [
            implode('', ['<', '?php']),
            ';',
            '; System configuration',
            '; modify : ' . date('Y-m-d h:i:s'),
            ';',
            $this->configuration->toString($conf),
            '',
        ];
        if (file_put_contents($config_file, implode(PHP_EOL, $configuration))) {
            $this->configuration = new ConfigParser($config_file);

            return true;
        }

        return false;
    }

    public function mergeConfiguration($uid)
    {
        $prefs = $this->db->select(
            'section,config,value',
            'user_preference',
            'WHERE userkey = ?',
            [$uid]
        );
        $user_preference = [];
        foreach ($prefs as $pref) {
            $user_preference[$pref['section']][$pref['config']] = $pref['value'];
        }
        $this->overrideConfiguration($user_preference);
    }

    public function overrideConfiguration($conf)
    {
        $this->configuration->merge($conf);
    }

    /**
     * Authentication.
     *
     * @param string $authTable
     */
    public function auth($authTable)
    {
        $uname = $this->request->POST('uname');
        $upass = $this->request->POST('upass');
        $ukeep = $this->request->POST('ukeep');

        $err = ['vl_empty' => 0, 'vl_mismatch' => 0, 'vl_nocookie' => 0];
        $auth = new Security($authTable, $this->db, $this->cnf('global:password_encrypt_algorithm'));

        $secret = '';
        $columns = (is_null($this->db)) ? [] : $this->db->getFields('user', false, false, "like 'pw_%'");
        $expire = (in_array('pw_expire', $columns)) ? 'pw_expire' : null;

        if ($this->session->param('authenticationFrequency') === 'everytime') {
            if (empty($uname)) {
                $uname = $this->session->param('uname');
                $secret = $this->session->param('secret');
            }
        }

        if (false === $auth->authentication($uname, $upass, $secret, $expire)) {
            if (!is_null($this->request->POST('authEnabler'))) {
                if (!isset($_COOKIE['enableCookie'])) {
                    $err['vl_nocookie'] = 1;
                } else {
                    if (empty($uname)) {
                        $err['vl_empty'] = 1;
                    }
                    if (empty($upass)) {
                        $err['vl_empty'] = 1;
                    }
                    if ($err['vl_empty'] !== 1) {
                        $err['vl_mismatch'] = 1;
                    }
                }

                // Blocking Brute-force attack
                if (!empty($err)) {
                    sleep(3);
                }
            } else {
                if (is_null($this->session->param('uname'))
                    && strtolower($this->cnf('application:guest') ?? '') === 'allow'
                ) {
                    $uname = self::GUESTNAME;
                    $secret = uniqid();
                    $this->session->param('authorized', self::ident($uname, $secret));
                    $this->session->param('uname', $uname);
                    $this->session->param('secret', $secret);

                    return true;
                } elseif ($this->session->param('uname') === self::GUESTNAME && empty($uname)) {
                    $this->session->clear('authorized');
                    $this->session->clear('secret');
                }
            }
            $this->view->bind(
                'form',
                [
                    'action' => $_SERVER['REQUEST_URI'],
                    'method' => 'post',
                    'enctype' => 'application/x-www-form-urlencoded',
                ]
            );

            $post = $this->request->POST();
            if (!isset($post['uname']) && $uname !== self::GUESTNAME) {
                $post['uname'] = $uname;
            }
            $this->view->bind('post', $post);

            $header = ['id' => 'signin', 'title' => Lang::translate('TITLE_SIGNIN')];
            $this->view->bind('header', $header);

            $this->view->bind('err', $err);
            $this->view->bind('stub', $this->csrf());

            return false;
        }

        // Switch account alias to entity
        $alias = $this->db->get('alias', 'user', 'uname=?', [$uname]);
        if (!is_null($alias)) {
            $this->session->param('alias', $uname);
            $uname = $this->db->get('uname', 'user', 'id=?', [$alias]);
        }

        if (!is_null($this->request->POST('authEnabler'))) {
            session_regenerate_id(true);
        }

        if ($ukeep) {
            $this->session->delay(time() + 60 * 60 * 24 * 365);
            $this->session->param('alive', 'keep');
        }

        $limit = (int) $this->cnf('global:session_limit');
        if ($limit > 0 && $this->session->param('alive') !== 'keep') {
            $this->setcookie('limit', $limit);
        }

        $secret = bin2hex(openssl_random_pseudo_bytes(16));
        $this->session->param('authorized', self::ident($uname, $secret));
        $this->session->param('uname', $uname);
        $this->session->param('secret', $secret);

        $this->logger->log('Signin');

        return $this->reload();
    }

    /**
     * User Identity.
     *
     * @param string $name
     * @param string $secret
     *
     * @return string
     */
    public function ident($name = null, $secret = null)
    {
        if (is_null($name)) {
            $name = $this->session->param('uname');
        }
        if (is_null($secret)) {
            $secret = $this->session->param('secret');
        }

        if (empty($first_contact = $this->session->param('first_contact'))) {
            $first_contact = $this->session->param(
                'first_contact',
                'from ' . filter_input(INPUT_SERVER, 'REMOTE_ADDR') . ' at ' . microtime()
            );
        }

        if ($name === self::GUESTNAME && $this->cnf('application:guest') === 'allow' && !empty($secret)) {
            return $secret;
        }

        if ($this->session->param('authenticationFrequency') === 'everytime' || is_null($secret)) {
            $secret = md5(random_bytes(12));
        }

        return hash('sha256', $name.$first_contact.filter_input(INPUT_SERVER, 'HTTP_USER_AGENT').$secret);

        return openssl_encrypt(
            $name.$first_contact.filter_input(INPUT_SERVER, 'HTTP_USER_AGENT'),
            'aes-128-ecb',
            $secret
        );
    }

    public static function lowerCamelCase($str)
    {
        return preg_replace_callback(
            '/[-_]([a-z])/',
            function ($matches) {
                return strtoupper($matches[1]);
            },
            strtolower($str)
        );
    }

    public static function upperCamelCase($str)
    {
        return ucfirst(self::lowerCamelCase($str));
    }

    /**
     * Create instance.
     *
     * @param mixed $mode
     *
     * @return object
     */
    public function instance($mode = null)
    {
        $unit = self::checkMode($mode, $this->root, $this->cnf('plugins:paths'));
        $package = $unit['namespace'] . '\\' . $unit['package'];

        if ((int)DEBUG_MODE !== 2 && php_sapi_name() === 'cli') {
            $ref = new ReflectionClass($package);
            $method = $ref->getMethod($unit['function']);
            if (!preg_match('/\*\s+@cli available/', $method->getDocComment())) {
                trigger_error('Illegal operation', E_USER_ERROR);
            }
        }

        $instance = new $package($this);
        if (empty($unit['function']) && method_exists($instance, self::DEFAULT_METHOD)) {
            $unit['function'] = self::upperCamelCase(self::DEFAULT_METHOD);
        }

        return [$instance, $unit['function'], $unit['arguments']];
    }

    public function guestExcutable($mode) //: boolean
    {
        $unit = self::checkMode($mode, $this->root, $this->cnf('plugins:paths'));
        $package = $unit['namespace'] . '\\' . $unit['package'];

        if (!is_callable([$package,'guestExecutables'])) {
            return false;
        }

        list($class, $executables) = $package::guestExecutables();

        $implements = array_diff(
            class_implements($class),
            class_implements(get_parent_class($class))
        );
        if (!in_array('Gsnowhawk\\Unauth', $implements)) {
            return false;
        }

        return in_array($unit['function'], $executables);
    }

    private static function checkMode($mode, $root = '', $plugin_paths = [])
    {
        $unit = self::parseMode($mode);
        if (!empty($unit['namespace'])) {
            if (is_null($plugin_paths)) {
                //
            } elseif (in_array($unit['namespace'], $plugin_paths)) {
                $unit['namespace'] = '\\' . $unit['namespace'];
            } else {
                throw new ErrorException("{$unit['namespace']} is not enabled");
            }
        }
        if (empty($unit['namespace'])) {
            $unit['namespace'] = $root;
        }

        $package = $unit['namespace'] . '\\' . $unit['package'];
        if (!class_exists($package)) {
            if (!empty($_SESSION['authorized'])) {
                throw new ErrorException("Class `{$package}' is not found");
            }
            $unit = self::parseMode(self::DEFAULT_RESPONSE);
            $unit['namespace'] = __NAMESPACE__;
        } elseif (false === is_subclass_of($package, 'Gsnowhawk\\PackageInterface')
            && false === is_a($package, 'Gsnowhawk\\User\\Response', true)
            && false === is_a($package, 'Gsnowhawk\\System\\Response', true)
            && false === is_a($package, 'Gsnowhawk\\Filemanager', true)
            && false === is_a($package, 'Gsnowhawk\\Plugin', true)
        ) {
            trigger_error("System Error: Class `$package' is an invalid package.", E_USER_ERROR);
        }

        return $unit;
    }

    /*
     * Parse mode
     *
     * @param string $mode
     *
     * @return array
     */
    public static function parseMode($mode)
    {
        $namespace = null;
        $package = $mode;
        $function = self::DEFAULT_METHOD;
        $arguments = null;
        if (preg_match('/^((.+)[~#])?(.+?)(:+(.+))?$/', $mode, $match)) {
            $namespace = strtolower($match[2]);
            $separator = substr($match[1], -1);
            if ($separator === '~') {
                $namespace = 'plugin\\' . self::upperCamelCase($match[2]);
            }

            // inarray paths
            //
            $package = $match[3];

            if (isset($match[5])) {
                $function = $match[5];
                if (preg_match('/(.+)\((.*)\)/', $function, $pair)) {
                    $function = $pair[1];
                    $arguments = Text::explode(',', $pair[2]);
                }
            }

            $mode = [
                'namespace' => $namespace,
                'package' => $package,
                'function' => self::lowerCamelCase($function),
                'arguments' => $arguments,
            ];

            $mode['plugin'] = (substr($match[1], -1) === '~');
        } else {
            $mode = [
                'namespace' => $namespace,
                'package' => $package,
                'function' => $function,
                'arguments' => $arguments,
            ];
        }

        $dirs = array_map(
            function ($str) {
                return self::upperCamelCase($str);
            },
            explode('.', $mode['package'])
        );
        $mode['package'] = implode('\\', $dirs);

        return $mode;
    }

    /**
     * for CSRF attacks.
     *
     * @param bool $force
     */
    public function csrf($force = false)
    {
        $ticket = $this->session->param('ticket');
        if (empty($ticket) || $force === true) {
            $ticket = bin2hex(openssl_random_pseudo_bytes(16, $cstrong));
            $this->session->param('ticket', $ticket);
        }

        return $ticket;
    }

    /**
     * Select default if mode is empty.
     *
     * @return string
     */
    public function getMode()
    {
        $mode = $this->request->param('mode');
        if (!empty($swap = $this->request->param('swap_mode'))) {
            $mode = $swap;
        }
        if (!$mode || !preg_match("/^([0-9a-z_\-]+[~#])?[0-9a-z\._\-]+(:[0-9a-z_\-]+)?(\(.*\))?$/i", $mode ?? '')) {
            $mode = $this->getDefaultMode();
        }

        $filter = $this->cnf('application:mode_filter');
        if (!empty($filter) && strpos($mode, $filter) !== 0) {
            $mode = $this->cnf('application:default_mode');
        }

        return $mode;
    }

    /**
     * Default mode
     *
     * @return string
     */
    public function getDefaultMode()
    {
        $current_application = $this->session->param('application_name');

        if (false === Variable::isEmpty($current_application)) {
            $class = Common::classFromApplicationName($current_application);
            $mode = method_exists($class, 'getDefaultMode') ? $class::getDefaultMode($this) : $class::DEFAULT_MODE;
        }

        if (empty($mode)) {
            $mode = $this->cnf('application:default_mode') ?? 'user.response';
        }

        $pluginResponse = $this->execPlugin('overrideDefaultMode', $mode);
        if (!empty($pluginResponse)) {
            $mode = array_shift($pluginResponse);
        }

        return $mode;
    }

    /**
     * Response from application
     *
     * @param string $mode
     * @param array $extend_args
     *
     * @return void
     */
    public function response($mode, array $extend_args = null)
    {
        list($instance, $function, $arguments) = $this->instance($mode);

        if (!is_null($extend_args)) {
            $arguments = array_merge((array)$arguments, $extend_args);
        }

        try {
            $instance->init();
            if (is_null($arguments)) {
                $instance->$function();
            } else {
                call_user_func_array([$instance, $function], $arguments);
            }
        } catch (PermitException $e) {
            self::displayError(clone $this->view, $e, 'permitfailure.tpl', 'permission-denied');
        } catch (ViewException $e) {
            if ($e->getCode() === 404) {
                self::displayError(clone $this->view, $e, '404.tpl');
            } else {
                trigger_error($e->getMessage(), E_USER_ERROR);
            }
        } catch (LogicException $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        } catch (Exception $e) {
            self::displayError(clone $this->view, $e, 'systemerror.tpl');
        }
    }

    private static function displayError($view, $exception, $template, $class = '')
    {
        $message = (defined('DEBUG_MODE') && (int)DEBUG_MODE === 1)
            ? $exception->getMessage() : 'System Error!';

        $info = [
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        // Feedback
        Err::feedback(
            sprintf('%s on %s at %s', $message, $info['file'], $info['line']),
            $info['code']
        );

        $header = $view->param('header');
        $header['id'] = 'system-error';
        if (!empty($class)) {
            if (isset($header['class'])) {
                $header['class'] = array_merge((array)$header['class'], (array)$class);
                $header['class'] = array_values(array_filter($header['class']));
            } else {
                $header['class'] = $class;
            }
        }
        $view->bind('header', $header);

        $view->bind('alert', $message);
        $view->bind('error', $info);

        if ($info['code'] >= 400 && $info['code'] < 500) {
            http_response_code($info['code']);
        } else {
            http_response_code(500);
        }

        $view->render($template);
    }

    /**
     * Current application name
     *
     * @param string $application_name
     *
     * @return string
     */
    public function currentApplication($application_name = null)
    {
        if (!is_null($application_name)) {
            $this->session->param('application_name', $application_name);
        }

        $current_appname = $this->session->param('application_name');
        $mode = $this->getMode();

        if (!$current_appname || strpos($mode, $current_appname) !== 0) {
            if (preg_match('/^([0-9a-z_\-]+)~.+$/', $mode, $match)) {
                $this->root = self::upperCamelCase($match[1]);
                $mode = preg_replace('/^[0-9a-z_\-]+~/', '', $mode);
            }
            $tmp = explode(':', $mode);
            $mode = $tmp[0];
            $mode = explode('.', $mode);
            if (count($mode) > 2 || strpos($mode[0], '#') !== false) {
                $this->session->param('application_name', $mode[0]);
            }
        }

        return $this->session->param('application_name');
    }

    /**
     * Set Cookie.
     *
     * @param string $name
     * @param string $value
     * @param int    $expire
     */
    public function setcookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = true, $samesite = 'Lax')
    {
        if (php_sapi_name() === 'cli') {
            return;
        }

        if (is_null($path)) {
            $uri = parse_url($this->env->server('request_uri'));
            $path = preg_replace('/\/+$/', '/', dirname("{$uri['path']}."));
        }

        setcookie($name, (string)$value, [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ]);
    }

    public function restoreView()
    {
        $this->view = $this->createView();
    }

    /**
     * Create view class instance
     *
     * @return Gsnowhawk\View
     */
    public function createView()
    {
        $debug = ((int)DEBUG_MODE > 0);

        $cache_dir = $this->cnf('global:cache_dir');
        if (empty($cache_dir)) {
            $cache_dir = false;
        }

        $paths = [dirname(__DIR__) . '/' . View::TEMPLATE_DIR_NAME];

        $application_name = $this->currentApplication();
        if (!empty($application_name)) {
            $currentAppName = Common::classFromApplicationName($application_name);
            if (class_exists($currentAppName) && method_exists($currentAppName, 'templateDir')) {
                array_unshift($paths, $currentAppName::templateDir());
            }
        }

        $view = new View(array_filter($paths), $debug, $cache_dir);

        $plugins = array_reverse(array_unique((array)$this->cnf('plugins:paths')));
        $plugin_templates = [];
        foreach ($plugins as $plugin) {
            $class = '\\plugin\\' . preg_replace('/^\\\?plugin\\\/', '', $plugin);
            if (class_exists($class) && method_exists($class, 'extendTemplateDir')) {
                $namespace = (method_exists($class, 'extendTemplateNamespace'))
                    ? $class::extendTemplateNamespace()
                    : strtolower(str_replace('\\', '_', $plugin));
                $plugin_templates[] = "@$namespace";
                $view->prependPath($class::extendTemplateDir(), $namespace);
            }
        }
        $view->bind('plugin_templates', $plugin_templates);

        $current_dir = getcwd() . '/templates';
        if (file_exists($current_dir)) {
            $view->prependPath($current_dir);
        }

        return $view;
    }

    public function createDbInstance()
    {
        if (!is_null($this->db)) {
            $this->db->close();
        }
        $this->db = new Db(
            $this->cnf('database:db_driver'),
            $this->cnf('database:db_host'),
            $this->cnf('database:db_source'),
            $this->cnf('database:db_user'),
            $this->cnf('database:db_password'),
            $this->cnf('database:db_port'),
            $this->cnf('database:db_encoding')
        );
        $this->db->setTablePrefix($this->cnf('database:db_table_prefix'));
    }

    /**
     * Execute plugin function.
     *
     * @return mixed
     */
    public function execPlugin()
    {
        $arguments = func_get_args();
        $function = array_shift($arguments);

        $current_app = $this->session->param('application_name');

        $stacks = debug_backtrace();
        foreach ($stacks as $stack) {
            if ($stack['function'] === __FUNCTION__
                || $stack['class'] === 'Gsnowhawk\\Common'
                || $stack['class'] === 'Gsnowhawk\\Base'
            ) {
                continue;
            }
            $caller = $stack['object'] ?? null;
            array_unshift($arguments, $stack['class']);
            break;
        }

        $plugins = array_unique((array)$this->cnf('plugins:paths'));
        $result = [];
        foreach ($plugins as $plugin) {
            $class = '\\plugin\\' . preg_replace('/^\\\?plugin\\\/', '', $plugin);

            if (!class_exists($class)) {
                $path = preg_replace('/^\//', '', str_replace('\\', '/', $class));
                if (false !== ($filename = stream_resolve_include_path($path . '.php'))
                    || false !== ($filename = stream_resolve_include_path("$path/" . basename($path) . '.php'))
                ) {
                    require_once $filename;
                }
            }

            if (class_exists($class) && method_exists($class, $function)) {
                $inst = new $class($this);
                if (!empty($caller)) {
                    $inst->setCaller($caller);
                }
                $result[$plugin] = call_user_func_array([$inst, $function], $arguments);
            }
        }

        $this->session->param('application_name', $current_app);

        return $result;
    }

    public function systemURI($fullpath = false)
    {
        $url = $this->cnf('global:base_url');
        if ($fullpath) {
            return $url;
        }
        $parsed_url = parse_url($url);

        return $parsed_url['path'];
    }

    public function reload($qsa = false)
    {
        $url = Env::server('request_uri');

        if (!empty($this->session->param('direct_uri'))) {
            $url = $this->session->param('direct_uri');
            $this->session->clear('direct_uri');
        } elseif ($qsa === false) {
            $url = preg_replace('/\?.*$/', '', $url);
        }

        if ((int)DEBUG_MODE === 2) {
            return $url;
        }

        Http::redirect($url);
    }

    public static function isAjax()
    {
        return Env::server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    //public static function findClass($class)
    //{
    //    $className = null;
    //    $namespace = null;
    //    $prefixes = \Gsnowhawk\Auto\Loader::getIgnoreNameSpaceToPath();
    //    foreach ($prefixes as $prefix) {
    //        if (class_exists("\\$prefix$class")) {
    //            $className = $class;
    //            $namespace = $prefix;
    //            break;
    //        }
    //    }
    //    return [$className, $namespace];
    //}
}
