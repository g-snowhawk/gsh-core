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

use Gsnowhawk\Common\Lang;
use Gsnowhawk\Common\Mail;
use Gsnowhawk\Common\Security;
use Gsnowhawk\Common\Text;

/**
 * User management class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class User extends Common
{
    /*
     * Using common accessor methods
     */
    use Accessor;

    /**
     * User properties.
     *
     * @var array
     */
    private $userinfo;

    /**
     * Current user ID.
     *
     * @var int
     */
    private $uid;

    /*
     * Permission table keys
     *
     * @var array
     */
    private $permission_table_keys = ['userkey','filter1','filter2','application','class','type'];

    /**
     * private data space
     *
     * @var string
     */
    private $private_save_path = null;

    /**
     * Object constructor.
     */
    public function __construct()
    {
        $params = func_get_args();
        call_user_func_array(parent::class.'::__construct', $params);

        if (is_null($this->userinfo)) {
            $this->setUserInfo();
        }
    }

    /**
     * Load user information from database
     */
    private function setUserInfo()
    {
        $this->userinfo = [];

        $uname = $this->session->param('uname');
        if (empty($uname)) {
            return;
        }

        if (!empty($userinfo = $this->db->get('*', 'user', 'uname = ?', [$uname]))) {
            $this->uid = $userinfo['id'];
            $fields = $this->db->getFields('user', true);
            foreach ($userinfo as $key => $value) {
                if (is_null($value)) {
                    continue;
                }
                $type = strtolower($fields[$key]['Type'] ?? '');
                if (preg_match('/^(integer|int|smallint|tinyint|mediumint|bigint)/', $type)) {
                    $this->userinfo[$key] = intval($value);
                } elseif (preg_match('/^(decimal|numeric|float|double)/', $type)) {
                    $this->userinfo[$key] = floatval($value);
                } else {
                    $this->userinfo[$key] = $value;
                }
            }
            $this->app->mergeConfiguration($this->uid);
        }
    }

    protected function clearUserInfo()
    {
        $this->userinfo = null;
    }

    /**
     * Save the data.
     *
     * @return bool
     */
    protected function save()
    {
        if ($this->request->param('profile') === '1') {
            $this->request->post('id', $this->uid);
        } else {
            $id = $this->request->POST('id');
            $check_type = (empty($id)) ? 'create' : 'update';
            $is_parent = ($check_type === 'update') ? $this->isParent($id) : false;
            if (false === $is_parent) {
                $this->checkPermission('user.'.$check_type);
            }
        }

        $trims = ['uname','upass','retype'];
        foreach ($trims as $trim) {
            $value = $this->request->post($trim);
            if (!empty($value)) {
                $this->request->post($trim, trim(mb_convert_kana($value, 's')));
            }
        }

        $post = $this->request->post();

        $table = 'user';
        $skip = ['id', 'admin', 'create_date', 'modify_date', 'validate_uname'];

        $valid = [];
        $valid[] = ['vl_fullname', 'fullname', 'empty'];
        $valid[] = ['vl_email', 'email', 'empty'];
        if (empty($post['id'])) {
            $valid[] = ['vl_uname', 'uname', 'empty'];
            $this->request->post('validate_uname', $post['uname'] . '@localhost.localdomain');
            $valid[] = ['vl_uname', 'validate_uname', 'rfc822', 2];
        }

        $password = [$post['upass'], $post['retype']];
        $this->request->post('password', $password);
        $valid[] = ['vl_upass', 'password', 'retype'];

        if (!$this->validate($valid)) {
            return false;
        }

        if (!empty($post['reissue'])) {
            if (empty($post['upass'])) {
                $post['upass'] = Security::createPassword(12, 2, 1);
                $post['pw_type'] = $post['reissue'];
            }
        }

        $this->db->begin();

        $fields = $this->db->getFields($this->db->TABLE($table));
        $permissions = [];
        $save = [];
        $raw = [];
        foreach ($fields as $field) {
            if (in_array($field, $skip)) {
                continue;
            }
            if (isset($post[$field])) {
                if ($field === 'upass') {
                    if (!empty($post[$field])) {
                        $save[$field] = Security::encrypt($post[$field], '', $this->app->cnf('global:password_encrypt_algorithm'));
                    }
                    continue;
                }
                if ($field === 'priv') {
                    if (empty($post[$field])) {
                        $post[$field] = [0];
                    } elseif (!is_array($post[$field])) {
                        $post[$field] = [$post[$field]];
                    }
                    $bit = 0;
                    foreach ($post[$field] as $i) {
                        $bit = $bit|$i;
                    }
                    $save[$field] = $bit;
                    continue;
                }
                $save[$field] = $post[$field];
            }
        }

        if ($this->isRoot() && $this->request->param('profile') !== '1') {
            $save['admin'] = $post['admin'] ?? '0';
        }

        if (empty($post['id'])) {
            $parent_rgt = $this->db->get('rgt', 'user', 'id = ?', [$this->uid]);

            $save['lft'] = $parent_rgt;
            $save['rgt'] = $parent_rgt + 1;

            $update_parent = $this->db->prepare(
                $this->db->nsmBeforeInsertChildSQL('user', ' AND rgt IS NOT NULL')
            );

            $raw = ['create_date' => 'CURRENT_TIMESTAMP'];
            if (false !== $update_parent->execute(['parent_rgt' => $parent_rgt, 'offset' => 2])
                && false !== $result = $this->db->insert($table, $save, $raw)
            ) {
                $post['id'] = $this->db->lastInsertId(null, 'id');
            }
        } else {
            $result = $this->db->update($table, $save, 'id = ?', [$post['id']], $raw);
        }
        if ($result !== false) {
            $modified = ($result > 0) ? $this->db->modified($table, 'id = ?', [$post['id']]) : true;
            if ($modified) {
                if ($this->request->param('profile') !== '1'
                    && false === $this->updatePermission($post)
                ) {
                    $result = false;
                }
            } else {
                $result = false;
            }

            if ($this->request->param('profile') === '1'
                && false === $this->removeAlias($post)
            ) {
                $result = false;
            }

            if ($result !== false) {
                if (!empty($post['reissue'])) {
                    $uname = $this->db->get('uname', 'user', 'id=?', [$post['id']]);
                    $this->session->param('reissued_username', $uname);
                    $this->session->param('reissued_password', $post['upass']);
                }

                return $this->db->commit();
            }
        }
        $error = $this->db->error();
        if (preg_match("/Duplicate entry (.+) for key '(.+)'/", $error, $match)) {
            $key = 'vl_' . $match[2];
            $this->app->err[$key] = 301;
        } else {
            trigger_error($error);
        }
        $this->db->rollback();

        return false;
    }

    /**
     * Remove data.
     *
     * @return bool
     */
    protected function remove()
    {
        $result = 0;
        $id = $this->request->param('delete');
        if (false === $this->isParent($id)) {
            $this->checkPermission('user.remove');
        }

        $this->db->begin();

        $plugin_result = $this->app->execPlugin('beforeRemove', $id);
        foreach ($plugin_result as $plugin_count) {
            if (false === $plugin_count) {
                $result = false;
                break;
            }
        }

        if (false !== $result
             && false !== ($result = $this->db->delete('user', '`id` = ?', [$id]))
             && false !== ($result = $this->db->delete('user', '`alias` = ?', [$id]))
             && false !== ($result = $this->db->nsmCleanup('user', '`lft` IS NOT NULL'))
        ) {
            return $this->db->commit();
        }
        trigger_error($this->db->error());
        $this->db->rollback();

        return false;
    }

    /**
     * User ID from uname.
     *
     * @param \Gsnowhawk\Db $db
     *
     * @return int
     */
    public static function getUserID(Db $db)
    {
        return $db->get('id', 'user', 'uname = ?', [$_SESSION['uname']]);
    }

    /**
     * Reference permission.
     *
     * @param string $key
     * @param int    $filter1
     * @param int    $filter2
     *
     * @return bool
     */
    public function hasPermission($key, $filter1 = 0, $filter2 = 0)
    {
        if (is_null($this->userinfo)) {
            $this->setUserInfo();
        }

        if ($key === 'root') {
            return $this->isRoot();
        }

        // Administrators have full control
        if ($key !== 'user.grant' && $this->isAdmin()) {
            // Reverse if key starts with no|not|none.
            return !preg_match('/\.no[^\.]+$/', $key);
        }

        $perm = $this->getPrivilege($key, $filter1, $filter2);

        if (strchr($key, '.exec') === '.exec') {
            return $perm !== '0';
        }

        return $perm === '1';
    }

    /**
     * Reference permission.
     *
     * @param bool   $userkey
     * @param string $key
     * @param int    $filter1
     * @param int    $filter2
     *
     * @return bool
     */
    public function hasPermissionByUser($userkey, $key, $filter1 = 0, $filter2 = 0)
    {
        $perm = $this->privilege($userkey, $key, $filter1, $filter2);

        if (strchr($key, '.exec') === '.exec') {
            return $perm !== '0';
        }

        return $perm === '1';
    }

    /**
     * Checking permission.
     *
     * @param string $type
     * @param int    $filter1
     * @param int    $filter2
     */
    protected function checkPermission($type, $filter1 = null, $filter2 = null)
    {
        if (false === $this->hasPermission($type, $filter1, $filter2)) {
            $trace = debug_backtrace();
            $file = $trace[0]['file'] ?? null;
            $line = $trace[0]['line'] ?? 0;
            throw new PermitException(Lang::translate('PERMISSION_DENIED'), 403, E_ERROR, $file, $line);
        }
    }

    /**
     * Update user permissions.
     *
     * @return bool
     */
    public function updatePermission($post)
    {
        $userkey = $post['id'];

        $class = $this->classFromApplicationName($this->session->param('application_name'));
        if (method_exists($class, 'clearApplicationPermission')) {
            if (false === $class::clearApplicationPermission($this->db, $userkey)) {
                return false;
            }
        }

        if (false === $this->db->delete('permission', 'userkey = ? AND application = ?', [$userkey, ''])) {
            return false;
        }

        $permissions = $this->request->POST('perm');

        $applications = $this->navItems();
        foreach ($applications as $application) {
            $key = $application['code'].'.exec';
            if (!is_array($permissions)) {
                $permissions = [];
            }
            if (!isset($permissions[$key])) {
                $permissions[$key] = '0';
            }
        }

        if (is_array($permissions)) {
            foreach ($permissions as $key => $value) {
                if (strchr($key, '.exec') === '.exec' && $value === '1') {
                    continue;
                }

                $filter1 = 0;
                $filter2 = 0;
                $tmp = explode('.', $key);
                if (count($tmp) > 3) {
                    $filter1 = array_shift($tmp);
                    $filter2 = array_shift($tmp);
                }
                $key = implode('.', $tmp);
                if (false === $this->savePermission($key, $value, $userkey, $filter1, $filter2)) {
                    return false;
                }
            }
        }

        if ($this->request->param('grant') === '1') {
            if (false === $this->savePermission('user.grant', '1', $userkey, 0, 0)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update or insert permission record.
     *
     * @param string $key
     * @param int    $priv    0 or 1
     * @param int    $userkey
     * @param int    $filter1
     * @param int    $filter2
     *
     * @return bool
     */
    private function savePermission($key, $priv, $userkey, $filter1 = 0, $filter2 = 0)
    {
        $value = $this->parsePermissionKey($key);
        $value['userkey'] = $userkey;
        $value['filter1'] = ($filter1 === '' || is_null($filter1)) ? 0 : $filter1;
        $value['filter2'] = ($filter2 === '' || is_null($filter2)) ? 0 : $filter2;
        $value['priv'] = $priv;

        return $this->db->updateOrInsert('permission', $value, $this->permission_table_keys);
    }

    protected static function parsePermissionKey($key)
    {
        $tmp = explode('.', $key);
        while (count($tmp) < 3) {
            array_unshift($tmp, '');
        }

        return ['application' => $tmp[0], 'class' => $tmp[1], 'type' => $tmp[2]];
    }

    /**
     * Reference user privileges.
     *
     * @param int    $userkey
     * @param int    $filter1
     * @param int    $filter2
     * @param string $application
     * @param string $class
     *
     * @return mixed
     */
    protected function getPrivileges($userkey, $filter1 = 0, $filter2 = 0, $application = null, $class = null)
    {
        $priv = [];
        $statement = 'WHERE userkey = ? AND filter1 = ? AND filter2 = ?';
        $options = [$userkey, $filter1, $filter2];
        if (is_array($application)) {
            $statement .= sprintf(
                ' AND application IN(%s)',
                implode(',', array_fill(0, count($application), '?'))
            );
            $options = array_merge($options, $application);
        } elseif (!is_null($application)) {
            $statement .= ' AND application = ?';
            $options[] = $application;
        }
        if (!is_null($class)) {
            $statement .= ' AND class = ?';
            $options[] = $class;
        }
        $data = $this->db->select(
            'application,class,type,priv',
            'permission',
            $statement,
            $options
        );
        foreach ($data as $unit) {
            $key = implode('.', [$unit['application'],$unit['class'],$unit['type']]);
            $priv[$key] = $unit['priv'];
        }

        return $priv;
    }

    public function getPrivilege($key, $filter1, $filter2)
    {
        return (empty($this->uid)) ? '0' : $this->privilege($this->uid, $key, $filter1, $filter2);
    }

    private function privilege($userkey, $key, $filter1 = 0, $filter2 = 0)
    {
        if (is_null($filter1)) {
            $filter1 = 0;
        }
        if (is_null($filter2)) {
            $filter2 = 0;
        }
        $options = array_values(self::parsePermissionKey($key));
        $statement = 'userkey = ? AND filter1 = ? AND filter2 = ? AND application = ? AND class = ? AND type = ?';
        array_unshift($options, $userkey, $filter1, $filter2);

        $priv = $this->db->get('priv', 'permission', $statement, $options);

        // Check global privilege
        if ((empty($priv) && $priv !== '0') && $filter1 !== 0 && $filter2 === 0) {
            $options[1] = 0;
            $priv = $this->db->get('priv', 'permission', $statement, $options);
        }

        return $priv;
    }

    public function isAlias(): bool
    {
        return !empty($this->session->param('alias'));
    }

    public function isAdmin(): bool
    {
        return intval($this->userinfo['admin'] ?? 0) > 0;
    }

    public function isRoot(): bool
    {
        return ($this->userinfo['lft'] ?? 999999) === 1;
    }

    public function isParent($child_id)
    {
        $parent = $this->db->nsmGetParent(
            'parent.id',
            '(SELECT * FROM table::user)',
            '(SELECT * FROM table::user WHERE id = :id)',
            ['id' => $child_id]
        );

        return $this->uid === $parent;
    }

    /**
     * Children of the user.
     *
     * @param int    $id
     * @param string $col
     *
     * @return array
     */
    public function childUsers($id, $col = '*')
    {
        $tmp = Text::explode(',', $col);
        $columns = [];
        foreach ($tmp as $column) {
            $columns[] = 'children.'.$column;
        }
        $columns = implode(',', $columns);

        $parent = '(SELECT * FROM table::user WHERE id = :userkey)';
        $midparent = '(SELECT * FROM table::user)';

        return $this->db->nsmGetChildren($columns, $parent, $midparent, $midparent, 'AND children.id IS NOT NULL', ['userkey' => $id]);
    }

    /**
     * get user alias data.
     *
     * @param int $own
     * @param steing $columns
     *
     * @return array
     */
    protected function getAliases($own, $columns = '*')
    {
        return $this->db->select($columns, 'user', 'WHERE alias = ?', [$own]);
    }

    /**
     * Save user alias data.
     *
     * @return bool
     */
    protected function saveAlias()
    {
        $id = $this->request->POST('id');
        $this->checkPermission('user.alias');

        $post = $this->request->post();

        $table = 'user';
        $skip = ['id', 'alias', 'admin', 'create_date', 'modify_date'];

        $valid = [];
        $valid[] = ['vl_fullname', 'fullname', 'empty'];
        $valid[] = ['vl_email', 'email', 'empty'];
        if (empty($post['id'])) {
            $valid[] = ['vl_uname', 'uname', 'empty'];
        }

        if (!$this->validate($valid)) {
            return false;
        }
        $this->db->begin();

        $fields = $this->db->getFields($this->db->TABLE($table));
        $save = [];
        $raw = [];
        foreach ($fields as $field) {
            if (in_array($field, $skip)) {
                continue;
            }
            if (isset($post[$field])) {
                if ($field === 'upass') {
                    if (!empty($post[$field])) {
                        $save[$field] = Security::encrypt($post[$field], '', $this->app->cnf('global:password_encrypt_algorithm'));
                    }
                    continue;
                }
                $save[$field] = $post[$field];
            }
        }

        if (empty($post['id'])) {
            $save['alias'] = $this->uid;
            $save['lft'] = null;
            $save['rgt'] = null;

            $parent = $this->request->param('real_id');
            if (!empty($parent)) {
                $parent_rgt = $this->db->get('rgt', 'user', 'id = ?', [$parent]);
                $save['lft'] = $parent_rgt;
                $save['rgt'] = $parent_rgt + 1;
            }

            $raw = ['create_date' => 'CURRENT_TIMESTAMP'];
            if (false !== $result = $this->db->insert($table, $save, $raw)) {
                $post['id'] = $this->db->lastInsertId(null, 'id');
            }
        } else {
            $result = $this->db->update($table, $save, 'id = ?', [$post['id']], $raw);
        }
        if ($result !== false) {
            $modified = ($result > 0) ? $this->db->modified($table, 'id = ?', [$post['id']]) : true;
            if ($modified) {
                // ...
            } else {
                $result = false;
            }
            if ($result !== false) {
                return $this->db->commit();
            }
        }
        $error = $this->db->error();
        if (preg_match("/Duplicate entry (.+) for key '(.+)'/", $error, $match)) {
            $key = 'vl_' . $match[2];
            $this->app->err[$key] = 301;
        } else {
            trigger_error($error);
        }
        $this->db->rollback();

        return false;
    }

    /**
     * Remove user alias data.
     *
     * @return bool
     */
    protected function removeAlias($post)
    {
        if (empty($post['remove'])) {
            return true;
        }

        foreach ((array)$post['remove'] as $key => $value) {
            if ($value !== 'on') {
                continue;
            }
            if (false === $this->db->delete('user', 'id=?', [$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Users List
     *
     * @param mixed $restriction    string|array
     *
     * @return array|false
     */
    protected function getUsers($restriction = null, $sort = null, $limit = null, $offset = null)
    {
        $filter = 'restriction IS NULL';
        $options = [$this->uid];
        if (is_array($restriction)) {
            $filter = 'restriction IN ('.implode(',', array_fill(0, count($restriction), '?')).')';
            $options = array_merge($options, $restriction);
        } elseif (!empty($restriction)) {
            $filter = 'restriction = ?';
            $options[] = $restriction;
        }

        $orderby = '';
        if (!empty($sort)) {
            $orderby .= " ORDER BY $sort";
        }

        $extensions = '';
        if (!empty($limit)) {
            $offset = (!empty($offset)) ? (int)$offset.',' : '';
            $extensions .= ' LIMIT '. $offset . (int)$limit;
        }

        return $this->db->nsmGetDecendants(
            'children.id, children.uname, children.fullname, children.company, children.email, children.create_date, children.modify_date',
            '(SELECT * FROM table::user WHERE id = ?)',
            "(SELECT * FROM table::user WHERE $filter$orderby)",
            $options,
            $extensions
        );
    }

    protected function eraseUnusedPermission()
    {
        return $this->db->exec(
            'DELETE p FROM tm_permission p
               LEFT JOIN tm_user u
                 ON p.userkey = u.id
              WHERE u.id IS NULL'
        );
    }

    protected function reissuedMail()
    {
        $post = $this->request->post();

        $valid = [];
        $valid[] = ['vl_mail_subject', 'mail_subject', 'empty'];
        $valid[] = ['vl_mail_body', 'mail_body', 'empty'];

        if (!$this->validate($valid)) {
            return false;
        }

        $to = $this->db->get('email', 'user', 'uname = ?', [$this->session->param('reissued_username')]);
        $from = $this->userinfo['email'];

        $mail = new Mail();
        $mail->setEncoding('utf-8');
        $mail->from($from);
        $mail->subject($post['mail_subject']);
        $mail->message($post['mail_body']);
        if (defined('RETURN_PATH') && RETURN_PATH === 1) {
            $mail->envfrom($from);
        }
        $mail->to();
        $mail->to($to);

        return $mail->send();
    }

    protected function resetUserByCli($uname)
    {
        if (php_sapi_name() !== 'cli') {
            trigger_error('Bad Requiest!', E_USER_ERROR);
        }
        $this->session->param('uname', $uname);
        $this->setUserInfo();
    }

    protected function privateSavePath($private = '', $immutable = false)
    {
        if (!empty($private)) {
            $this->private_save_path = $private;
        }

        if (empty($this->private_save_path) || $immutable) {
            $path = sprintf(
                '%s/%s',
                $this->app->cnf('data_dir'),
                md5($this->uid)
            );

            if ($immutable) {
                return $path;
            }

            $this->private_save_path = $path;
        }

        return $this->private_save_path;
    }

    public function hasPrivateData($filename, $dir = '', $immutable = false)
    {
        if (empty($filename)) {
            return false;
        }
        $filename = '/' . ltrim($filename, './');
        if (!empty($dir)) {
            $dir = '/' . ltrim($dir, './');
        }

        return realpath($this->privateSavePath('', $immutable) . $dir . $filename);
    }
}
