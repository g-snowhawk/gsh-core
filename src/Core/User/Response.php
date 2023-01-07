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
 * User management request response class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Response extends Unauth
{
    /**
     * Object Constructor.
     */
    public function __construct()
    {
        $params = func_get_args();
        call_user_func_array(parent::class.'::__construct', $params);

        $this->view->bind(
            'header',
            ['title' => 'ユーザー管理', 'id' => 'user', 'class' => 'user']
        );
    }

    /**
     * Default view.
     */
    public function defaultView($restriction = null)
    {
        $this->checkPermission('user.read');

        // Clear reissued data
        $this->session->clear('reissued_username');
        $this->session->clear('reissued_password');

        $this->view->bind('users', parent::getUsers($restriction));

        $globals = $this->view->param();
        $form = $globals['form'] ?? [];
        $form['confirm'] = Lang::translate('CONFIRM_DELETE_DATA');
        $this->view->bind('form', $form);

        parent::defaultView('user-default');
    }

    /**
     * Profile Edit.
     */
    public function profile()
    {
        $this->request->param('id', $this->uid);

        $alias = $this->session->param('alias');
        if (!empty($alias)) {
            $alias_id = $this->db->get('id', 'user', 'uname=?', [$alias]);
            $this->request->param('id', $alias_id);
            $this->editAlias();
        }

        $aliases = $this->db->select('id,fullname,fullname_rubi,email,uname', 'user', 'WHERE alias=?', [$this->uid]);
        $this->view->bind('aliases', $aliases);

        $this->edit(true);
    }

    /**
     * Show edit form.
     *
     * @param bool $profile
     */
    public function edit($profile = false)
    {
        if ($profile === false) {
            $this->checkPermission('user.read');
        }

        if ($this->request->method === 'post') {
            $post = $this->request->post();
        } else {
            $fields = $this->db->getFields('user');
            $strict = [
                'alias', 'uname', 'upass', 'pw_type', 'pw_expire',
                'contract', 'expire', 'closing', 'pay',
                'forward', 'type', 'restriction', 'lft', 'rgt',
            ];
            $columns = array_diff($fields, $strict);
            if (false === ($post = $this->db->get(
                implode(',', $columns),
                'user',
                'id = ?',
                [$this->request->param('id')]
            ))) {
                $post = [];
            }

            $stat = $this->db->select(
                '*',
                'permission',
                'WHERE userkey = ? AND application IN (?,?)',
                [$this->request->param('id'), '', $this->currentApp()]
            );

            $perm = [];
            $applications = $this->navItems();
            foreach ($applications as $application) {
                $key = $application['code'].'.exec';
                $perm[$key] = '1';
            }

            foreach ($stat as $unit) {
                $tmp = [];
                $tmp[] = (!empty($unit['filter1'])) ? $unit['filter1'] : '';
                $tmp[] = (!empty($unit['filter2'])) ? $unit['filter2'] : '';
                $tmp[] = $unit['application'];
                $tmp[] = $unit['class'];
                $tmp[] = $unit['type'];
                $key = preg_replace('/^\.+/', '', implode('.', $tmp));
                $perm[$key] = $unit['priv'];
            }
            $post['perm'] = $perm;
        }

        if ($profile) {
            $post['profile'] = 1;
        }

        $this->view->bind('post', $post);

        $perms = [];
        $global = $this->getPrivileges($this->uid);
        foreach ($global as $tmp => $priv) {
            $parent = &$perms;
            $keys = explode('.', $tmp);
            foreach ($keys as $key) {
                if (empty($key)) {
                    continue;
                }
                if (!isset($parent[$key])) {
                    $parent[$key] = [];
                }
                $parent = &$parent[$key];
            }
            $parent = $priv;
            unset($parent);
        }

        $this->view->bind('perms', ['global' => $perms]);

        $globals = $this->view->param();
        $form = $globals['form'] ?? [];
        $form['confirm'] = Lang::translate('CONFIRM_SAVE_DATA');
        $this->view->bind('form', $form);

        $class = self::classFromApplicationName($this->session->param('application_name'));
        if (defined("$class::USER_EDIT_EXTENDS")) {
            $package = $class::USER_EDIT_EXTENDS;
            $this->view->bind('apps', new $package($this->app));
        }

        parent::defaultView('user-edit');
    }

    /**
     * Switch user account.
     */
    public function switchUser()
    {
        $this->checkPermission('system');

        $origin = $this->session->param('origin');
        if (is_array($origin)) {
            $origin[] = $this->session->param('uname');
        } else {
            $origin = [$this->session->param('uname')];
        }
        $this->session->param('origin', $origin);

        $uname = $this->db->get('uname', 'user', 'id=?', [$this->request->param('id')]);
        $securet = bin2hex(openssl_random_pseudo_bytes(16));
        $this->session->param('authorized', $this->app->ident($uname, $securet));
        $this->session->param('uname', $uname);
        $this->session->param('securet', $securet);

        $this->clearUserInfo();

        $mode = 'user.response';
        if (!$this->hasPermission('user.read')) {
            $mode = $this->app->getDefaultMode();
        }

        $this->app->execPlugin('afterSwitchUser');

        Http::redirect($this->app->systemURI()."?mode=$mode");
    }

    /**
     * Rewind user account.
     */
    public function rewind()
    {
        $origin = $this->session->param('origin');
        if (is_null($origin)) {
            return;
        }

        $uname = array_pop($origin);
        if (!empty($uname)) {
            $securet = bin2hex(openssl_random_pseudo_bytes(16));
            $this->session->param('authorized', $this->app->ident($uname, $securet));
            $this->session->param('uname', $uname);
            $this->session->param('securet', $securet);

            if (empty($origin)) {
                $this->session->clear('origin');
            } else {
                $this->session->param('origin', $origin);
            }
            $this->clearUserInfo();
        }

        Http::redirect($this->app->systemURI().'?mode=user.response');
    }

    /**
     * Show alias edit form.
     */
    public function editAlias()
    {
        if ($this->request->param('id') !== $this->uid) {
            $this->checkPermission('user.alias');
        }

        if ($this->request->method === 'post') {
            $post = $this->request->post();
        } else {
            $post = $this->db->get(
                'id, admin, fullname, fullname_rubi, email, url, zip, state, city, town,
                 address1, address2, tel, fax, create_date, modify_date',
                'user',
                'id = ?',
                [$this->request->param('id')]
            );
        }

        $this->view->bind('post', $post);

        $globals = $this->view->param();
        $form = $globals['form'];
        $form['confirm'] = Lang::translate('CONFIRM_SAVE_DATA');
        $this->view->bind('form', $form);

        parent::defaultView('user-alias_edit');
    }

    /**
     * Show alias edit subform with Ajax.
     */
    public function editAliasSubform()
    {
        $status = $this->hasPermission('user.read');

        if ($this->request->method === 'post'
            && $this->request->post('request_type') !== 'response-subform'
        ) {
            $post = $this->request->post();
        } else {
            $post = $this->db->get(
                'id, admin, fullname ,fullname_rubi, email, url, zip, state, city, town,
                 address1, address2, tel, fax, create_date, modify_date',
                'user',
                'id = ?',
                [$this->request->param('id')]
            );
        }
        $this->view->bind('post', $post);

        $plugins = $this->app->execPlugin('beforeRendering', 'alias-edit-subform');
        $response = $this->view->render('user/alias_edit_subform.tpl', true);

        $json = [
            'status' => $status,
            'response' => $response,
        ];

        header('Content-type: text/plain; charset=utf-8');
        echo json_encode($json);
        exit;
    }

    public function aliasList()
    {
        $aliases = $this->db->select('id,fullname,fullname_rubi,email,uname', 'user', 'WHERE alias=?', [$this->uid]);
        $this->view->bind('aliases', $aliases);

        $json = [
            'status' => 0,
            'source' => $this->view->render('user/alias_list.tpl', true)
        ];
        header('Content-type: text/plain; charset=utf-8');
        echo json_encode($json);
        exit;
    }

    public function reissued() //: void
    {
        $reissued_username = $this->session->param('reissued_username');
        $reissued_password = $this->session->param('reissued_password');
        if (empty($reissued_password)) {
            $this->defaultView();
        }

        $userkey = $this->db->get('id', 'user', 'uname = ?', [$reissued_username]);
        if (false === $this->isParent($userkey)) {
            $this->checkPermission('user.read');
        }

        $post = $this->request->POST();
        if (empty($post['mail_subject'])) {
            $post['mail_subject'] = Lang::translate('REISSUED_MAIL_SUBJECT');
        }
        if (empty($post['mail_body'])) {
            $post['mail_body'] = $this->reissuedResult($reissued_username, $reissued_password);
        }
        $this->view->bind('post', $post);

        // form settings
        $form = $this->view->param('form');
        $form['confirm'] = Lang::translate('CONFIRM_SENDMAIL');
        $this->view->bind('form', $form);

        parent::defaultView('user-reissued');
    }

    private function reissuedResult($reissued_username, $reissued_password) //: string
    {
        $view = clone $this->view;
        $view->bind('username', $reissued_username);
        $view->bind('password', $reissued_password);

        return $view->render('user/reissued_mail_body.tpl', true);
    }
}
