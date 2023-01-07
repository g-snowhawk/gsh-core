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
use Gsnowhawk\Common\File;
use Gsnowhawk\Common\Http;
use Gsnowhawk\Common\Lang;
use Gsnowhawk\Common\Text;

/**
 * Site management class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Filemanager extends User
{
    /*
     * Using common accessor methods
     */
    use Accessor;

    public const ACL_READONLY = '.htms_readonly';
    public const ACL_WRITABLE = '.htms_writable';
    public const ACL_HIDDEN = '.htms_hidden';

    public const USER_DIRECTORY_NAME = 'usr';

    /*
     * Upload root directory
     *
     * @ver string
     */
    private $rootdir;
    private $chroot_difference;
    private $baseurl;
    private $response;
    private $permission;

    /**
     * Object constructor.
     */
    public function __construct()
    {
        setlocale(LC_ALL, 'ja_JP.UTF-8');
        call_user_func_array(parent::class.'::__construct', func_get_args());

        if (($this->skip_header ?? false) !== true
            && empty($header = $this->view->param('header'))
        ) {
            $this->view->bind('header', [
                'title' => 'ファイル管理',
                'id' => 'filemanager',
                'class' => 'filemanager'
            ]);
        }

        $this->permission = [
            'prefix' => $this->currentApp('basename') . '.file.',
            'filter1' => $this->session->param('filemanager_filter1') ?? 0,
            'filter2' => $this->session->param('filemanager_filter2') ?? 0,
        ];
        if (!$this->view->inRendering()) {
            $this->view->bind('permission', $this->permission);
        }

        $this->response = $this->currentApp('basename') . '.filemanager.response';
    }

    protected function setRootDirectory($rootdir): void
    {
        if (!file_exists($rootdir)) {
            mkdir($rootdir, 0777, true);
        }

        $this->rootdir = realpath($rootdir);
        $this->chroot_difference = null;
    }

    protected function setBaseUrl($url): void
    {
        $this->baseurl = $url;
    }

    protected function chroot($rootdir)
    {
        $rootdir = realpath($rootdir);
        if (strpos($rootdir, $this->rootdir) !== 0) {
            return false;
        }
        $diff = str_replace($this->rootdir, '', $rootdir);
        $diff = trim($diff, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->chroot_difference = $diff;
        $this->rootdir = $rootdir;
    }

    protected function explorer($id_prefix = '')
    {
        $cwd = $this->currentDirectory(true);
        $files = $this->fileList(basename($cwd), dirname($cwd));
        $this->view->bind('files', $files);

        $this->view->bind('cwd', explode('/', $cwd));

        $form = $this->view->param('form');
        $form['confirm'] = Lang::translate('CONFIRM_DELETE_DATA');
        $this->view->bind('form', $form);

        $this->view->bind('err', $this->app->err);

        $this->setHtmlId($id_prefix . 'filemanager-default');
        $this->view->render('filemanager/default.tpl');
    }

    protected function download($path)
    {
        $path = urldecode($path);
        if (!empty($this->chroot_difference)) {
            $path = preg_replace(
                '/^' . preg_quote($this->chroot_difference, '/') . '/',
                '',
                $path,
                1
            );
        }
        $file = $this->rootdir . '/' . urldecode($path);
        if (!file_exists($file)) {
            throw new ErrorException("{$path} is no such file or directory", 404);
            trigger_error("{$path} is no such file or directory", E_USER_ERROR);
        } elseif (is_dir($file)) {
            return false;
        }
        $file_name = basename($file);
        $urlencoded = rawurlencode($file_name);
        $content_length = filesize($file);
        $mime = 'application/octet-stream';
        Http::nocache();
        Http::responseHeader('Content-Disposition', "attachment; filename=\"$file_name\"; filename*=UTF-8''$urlencoded");
        Http::responseHeader('Content-length', "$content_length");
        Http::responseHeader('Content-type', "$mime");
        readfile($file);
        exit;
    }

    protected function fileList($directory = '', $parent = '', $filter = null)
    {
        $parent = trim($parent ?? '', '/') . "/$directory";

        try {
            $cwd = realpath("{$this->rootdir}/$parent");
        } catch (ErrorException $e) {
            return;
        }

        $files = [];
        $directories = [];

        try {
            $entries = (empty($cwd)) ? [] : scandir($cwd);
            foreach ($entries as $entry) {
                $path = "$cwd/$entry";

                $is_hidden = false;
                $dir = (is_dir($path)) ? $path : dirname($path);
                while ($this->rootdir !== rtrim($dir, '/')) {
                    if (file_exists($dir . '/' . self::ACL_READONLY)
                        || file_exists($dir . '/' . self::ACL_WRITABLE)
                    ) {
                        $is_hidden = false;
                        break;
                    }
                    if (file_exists($dir . '/' . self::ACL_HIDDEN)) {
                        $is_hidden = true;
                        break;
                    }
                    $dir = dirname($dir);
                }
                if (is_dir($path) && $is_hidden) {
                    $find = File::find([self::ACL_READONLY, self::ACL_WRITABLE], $path, true);
                    if (!empty($find)) {
                        $is_hidden = false;
                    }
                }

                if ($entry === '.'
                    || $entry === '..'
                    || $entry === self::ACL_READONLY
                    || $entry === self::ACL_WRITABLE
                    || $entry === self::ACL_HIDDEN
                    || ($filter === 'file' && is_dir($path))
                    || ($filter === 'directory' && is_file($path))
                    || $is_hidden
                ) {
                    continue;
                }

                $stat = stat($path);
                $data = [
                    'name' => $entry,
                    'parent' => trim($parent, './'),
                    'path' => str_replace($this->rootdir.'/', '', $path),
                    'modify_date' => $stat['mtime'],
                    'size' => File::size((int)$stat['size']),
                    'kind' => (is_dir($path)) ? 'folder' : 'file',
                    'url' => null,
                ];

                if ($filter === 'directory') {
                    $data['empty'] = self::directoryIsEmpty($data['path']);
                }

                if (!empty($this->baseurl) && !is_dir($path)) {
                    $data['url'] = rtrim($this->baseurl, '/') . str_replace(realpath($this->rootdir), '', $path);
                    $data['uri'] = [
                        'dirname' => dirname($data['url']),
                        'basename' => basename($data['url']),
                    ];
                }

                if (is_dir($path)) {
                    $directories[] = $data;
                } else {
                    $files[] = $data;
                }
            }

            return array_merge($directories, $files);
        } catch (ErrorException $e) {
            //
        }
    }

    protected function setCurrentDirectory($path, $redirect = false): void
    {
        $permission = $this->currentApp('basename') . '.file.noroot';
        if (empty($path) && !$this->isAdmin() && $this->hasPermission($permission)) {
            $files = glob($this->rootdir . '/*');
            foreach ($files as $file) {
                if (is_dir($file)) {
                    if (file_exists($file . '/' . self::ACL_HIDDEN)) {
                        $find = File::find([self::ACL_READONLY, self::ACL_WRITABLE], $file, true);
                        if (empty($find)) {
                            continue;
                        }
                    }
                    $path = basename($file);
                    break;
                }
            }
        }
        $this->session->param('current_dir', ltrim($path ?? '', '/'));
        // Reload file explorer
        if ($redirect) {
            Http::redirect(sprintf('%s?mode=%s', $this->app->systemURI(), $this->response));
        }
    }

    protected function currentDirectory($relative = false)
    {
        $current_dir = $this->session->param('current_dir');
        if (empty($current_dir)) {
            $current_dir = $this->setCurrentDirectory($current_dir);
        }

        $root = ($relative === false) ? $this->rootdir . '/' : '';

        return rtrim($root.ltrim($this->session->param('current_dir'), '/'), '/');
    }

    protected function directoryIsEmpty($directory, $parent = ''): int
    {
        $parent = trim($parent ?? '', '/')."/$directory";

        try {
            $cwd = realpath("{$this->rootdir}/$parent");
            $entries = scandir($cwd);
            $skip = ['.','..'];

            return count(array_diff($entries, $skip));
        } catch (ErrorException $e) {
            // Nop
        }

        return 0;
    }

    protected function addFolder()
    {
        $response = $this->view->render('filemanager/addfolder.tpl', true);
        if ($this->request->method === 'post'
            && $this->request->post('request_type') !== 'response-subform'
        ) {
            return $response;
        }
        $json = [
            'status' => 200,
            'response' => $response,
        ];
        header('Content-type: text/plain; charset=utf-8');
        echo json_encode($json);
        exit;
    }

    protected function addFile()
    {
        $response = $this->view->render('filemanager/addfile.tpl', true);
        if ($this->request->method === 'post'
            && $this->request->post('request_type') !== 'response-subform'
        ) {
            return $response;
        }
        $json = [
            'status' => 200,
            'response' => $response,
        ];
        header('Content-type: text/plain; charset=utf-8');
        echo json_encode($json);
        exit;
    }

    protected function remove()
    {
        $message = 'SUCCESS_REMOVED';
        $status = 0;
        $options = [];
        $response = [[$this, 'redirect'], $this->response];

        if (!self::_remove()) {
            $message = 'FAILED_REMOVE';
            $status = 1;
            $options = [
                [[$this->view, 'bind'], ['err', $this->app->err]],
            ];
        }

        $this->postReceived(Lang::translate($message), $status, $response, $options);
    }

    private function _remove()
    {
        list($kind, $path) = explode(':', $this->request->param('delete'));
        $path = $this->currentDirectory().'/'.ltrim($path, '/');
        if (!file_exists($path)) {
            return true;
        }
        switch ($kind) {
            case 'file':
                return unlink($path);
            case 'folder':
                return File::rmdir($path, true);
        }
    }

    protected function rename()
    {
        $message = 'SUCCESS_SAVED';
        $status = ['status' => 0];
        $options = [];
        $response = [[$this, 'redirect'], $this->response];

        $dotfile = (strpos($this->request->param('newname'), '.') === 0);

        if ($dotfile || false === $result = self::_rename()) {
            $message = ($dotfile) ? 'NOT_MAKE_DOTFILE' : 'FAILED_REMOVE';
            $status = 1;
            $options = [
                [[$this->view, 'bind'], ['err', $this->app->err]],
            ];
        } else {
            $status[] = $result;
        }

        $this->postReceived(Lang::translate($message), $status, $response, $options);
    }

    private function _rename()
    {
        $cwd = $this->currentDirectory();
        $source = $cwd . '/' . $this->request->param('oldname');
        $dest = $cwd . '/' . $this->request->param('newname');

        if (false === @rename($source, $dest)) {
            return false;
        }

        $return_value = ['filename' => basename($dest)];
        if (!empty($this->baseurl) && !is_dir($dest)) {
            $return_value['url'] = rtrim($this->baseurl, '/') . str_replace(realpath($this->rootdir), '', $dest);
        }

        return $return_value;

        return (@rename($source, $dest)) ? basename($dest) : false;
    }

    protected function move()
    {
        $message = 'SUCCESS_SAVED';
        $status = 0;
        $options = [];
        $response = [[$this, 'redirect'], $this->response];

        if (self::_move()) {
            $this->setCurrentDirectory($this->request->param('dest'));
        } else {
            $message = 'FAILED_REMOVE';
            $status = 1;
            $options = [
                [[$this->view, 'bind'], ['err', $this->app->err]],
            ];
        }

        $this->postReceived(Lang::translate($message), $status, $response, $options);
    }

    private function _move()
    {
        $source = $this->currentDirectory() . '/'.$this->request->param('source');
        $dest = $this->rootdir.'/'.$this->request->param('dest').'/'.$this->request->param('source');

        return @rename($source, $dest);
    }

    protected function saveFolder()
    {
        $message = 'SUCCESS_SAVED';
        $status = 0;
        $options = [];
        $response = [[$this, 'redirect'], $this->response];

        try {
            $valid = [
                ['vl_path', 'path', 'empty']
            ];
            if (!$this->validate($valid)) {
                throw new ErrorException('Posted data is invalid');
            }

            $directory_name = str_replace(['/','\\',':'], '-', $this->request->param('path'));
            $directory = $this->currentDirectory().'/'.$directory_name;
            if (false === mkdir($directory, 0777, true)) {
                throw new ErrorException('Make directory error');
            }
        } catch (ErrorException $e) {
            $message = 'FAILED_SAVE';
            $status = 1;
            $options = [
                [[$this->view, 'bind'], ['err', $this->app->err]],
            ];
            $response = [[$this, 'addFolder'], null];
        }

        $this->postReceived(Lang::translate($message), $status, $response, $options);
    }

    public function saveFile()
    {
        $message = 'SUCCESS_SAVED';
        $status = 0;
        $options = [];
        $response = [[$this, 'redirect'], $this->response];

        try {
            $valid = [
                ['vl_file', 'file', 'upload', 9]
            ];
            if (!$this->validate($valid)) {
                throw new ErrorException('Upload file is invalid');
            }

            $uploaded_file = $this->request->FILES('file');
            $source = $uploaded_file['tmp_name'];

            $file_name = $uploaded_file['name'];
            // Convert encoding multibyte characters
            if (mb_strlen($file_name) !== mb_strwidth($file_name)) {
                $file_name = Text::convert($file_name);
            }

            if (strpos($file_name, '.') === 0) {
                throw new ErrorException('Dot file ignored');
            }

            $file_name = str_replace(['/','\\',':'], '-', $file_name);

            $dest = $this->currentDirectory().'/'.$file_name;
            if (false === move_uploaded_file($source, $dest)) {
                throw new ErrorException('File upload error');
            }
        } catch (ErrorException $e) {
            $message = 'FAILED_SAVE';
            $status = 1;
            $options = [
                [[$this->view, 'bind'], ['err', $this->app->err]],
            ];
            $response = [[$this, 'addFile'], null];

            if ($e->getMessage() === 'Dot file ignored') {
                $message = 'IGNORE_DOTFILE';
            }
        }

        $this->postReceived(Lang::translate($message), $status, $response, $options);
    }

    final public static function packageName()
    {
        return $_SESSION['application_name'];
    }

    public function hasPermission($key, $filter1 = 0, $filter2 = 0, ...$args)
    {
        if (method_exists($this, 'extraPermission')) {
            $ret = call_user_func_array([$this, 'extraPermission'], func_get_args());
            if ($ret === true) {
                return $ret;
            }
        }

        $permission = parent::hasPermission($key, $filter1, $filter2);
        if (in_array('Do not use filesystem', $args)) {
            return $permission;
        }

        if ($permission !== false) {
            if (preg_match('/^.+\.file\.(.+)$/', $key, $match)) {
                $path = $this->rootdir . '/' . $this->session->param('current_dir');

                $dir = (is_dir($path)) ? $path : dirname($path);

                switch ($match[1]) {
                    case 'read':
                        $is_hidden = false;
                        while ($this->rootdir !== rtrim($dir, '/')) {
                            if (file_exists($dir . '/' . self::ACL_HIDDEN)) {
                                $is_hidden = true;
                                break;
                            }
                            $dir = dirname($dir);
                        }

                        return ($is_hidden) ? false : is_readable($path);
                    case 'create':
                    case 'delete':
                    case 'update':
                        $is_readonly = false;
                        while ($this->rootdir !== rtrim($dir, '/')) {
                            if (file_exists($dir . '/' . self::ACL_WRITABLE)) {
                                $is_readonly = false;
                                break;
                            }
                            if (file_exists($dir . '/' . self::ACL_READONLY)) {
                                $is_readonly = true;
                                break;
                            }
                            $dir = dirname($dir);
                        }

                        return ($is_readonly) ? false : is_writable($path);
                }
            }
        }

        return $permission;
    }
}
