<?php

/**
 * This file is part of Gsnowhawk System.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk\User;

use Gsnowhawk\Common\Http;
use Gsnowhawk\Common\Lang;

/**
 * User management request receive class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Receive extends Response
{
    /**
     * Save the data receive interface.
     */
    public function save()
    {
        if (parent::save()) {
            $this->session->param('messages', Lang::translate('SUCCESS_SAVED'));
            $url = $this->app->systemURI().'?mode=user.response';
            if ($this->request->param('profile') === '1') {
                $url .= ':profile';
            } elseif (!empty($this->session->param('reissued_password'))) {
                $url .= ':reissued';
            }
            Http::redirect($url);
        }
        $this->edit();
    }

    /**
     * Remove the data receive interface.
     */
    public function remove()
    {
        if (parent::remove()) {
            $this->session->param('messages', Lang::translate('SUCCESS_REMOVED'));
        }
        Http::redirect($this->app->systemURI().'?mode=user.response');
    }

    /**
     * Save user alias.
     */
    public function saveAlias()
    {
        $message = 'SUCCESS_SAVED';
        $status = 0;
        $options = [];
        $response = [[$this, 'redirect'], 'user.response:profile'];

        if (false === parent::saveAlias()) {
            $message = 'FAILED_SAVE';
            $status = 1;
            $options = [
                [[$this->view, 'bind'], ['err', $this->app->err]],
            ];
            $response = [[$this, 'editAliasSubform'], null];
        }

        $this->postReceived(Lang::translate($message), $status, $response, $options);

        //if (parent::saveAlias()) {
        //    $this->session->param('messages', Lang::translate('SUCCESS_SAVED'));
        //    $url = $this->app->systemURI().'?mode=user.response:profile';
        //    Http::redirect($url);
        //}
        //$this->edit();
    }

    public function reissuedMail()
    {
        $message = 'SUCCESS_REISSUED_MAIL';
        $status = 0;
        $options = [];

        $returnmode = $this->request->post('returnmode');
        if (empty($returnmode)) {
            $returnmode = 'user.response';
        }
        $response = [[$this, 'redirect'], $returnmode];

        if (false === parent::reissuedMail()) {
            $message = 'FAILED_REISSUED_MAIL';
            $status = 1;
            $options = [
                [[$this->view, 'bind'], ['err', $this->app->err]],
            ];
            $response = [[$this, 'reissued'], null];
        }

        $this->postReceived(Lang::translate($message), $status, $response, $options);
    }
}
