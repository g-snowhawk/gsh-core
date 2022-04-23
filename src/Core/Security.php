<?php
/**
 * This file is part of Gsnowhawk Framework.
 *
 * Copyright (c)2018 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk;

/**
 * Methods for Authentication.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Security extends \Gsnowhawk\Common\Security
{
    /**
     * Application instance
     *
     * @var Gsnowhawk\App
     */
    private $app;

    /**
     * Object constructer.
     *
     * @param string $source
     * @param Gsnowhawk\Common\Db  $db
     */
    public function __construct($source, Db $db = null, $password_algo = 'sha1')
    {
        foreach (debug_backtrace() as $trace) {
            if (isset($trace['object']) && $trace['object'] instanceof App) {
                $this->app = $trace['object'];
                break;
            }
        }

        parent::__construct($source, $db, $password_algo);
    }

    /**
     * Check User/Password.
     *
     * @param string $uname
     * @param string $upass
     * @param string $secret
     * @param string $expire
     *
     * @return bool
     */
    public function authentication($uname, $upass, $secret = '', $expire = null)
    {
        $authorized = parent::authentication($uname, $upass, $secret, $expire);

        $results = $this->app->execPlugin('afterAuthentication', $uname, $authorized);
        foreach ($results as $result) {
            if ($result === false) {
                $authorized = false;
                break;
            }
        }

        return $authorized;
    }
}
