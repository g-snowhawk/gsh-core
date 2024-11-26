<?php

/**
 * This file is part of Gsnowhawk System.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk\System;

use Gsnowhawk\Common\Http;
use Gsnowhawk\Common\Lang;

/**
 * User management request response class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Receive extends Response
{
    /**
     * Execute setup
     */
    public function setup()
    {
        $paths = $this->request->POST('paths');
        $classes = $this->request->POST('classes');
        if (empty($paths)) {
            $paths = [];
            $this->init();
        }
        $configuration = $this->app->cnf(null);
        $failed = [];
        foreach ($paths as $i => $classfile) {
            include_once($classfile);
            $checksum = md5_file($classfile);

            $class = $classes[$i];
            $namespace = $class::getNameSpace();
            $current_version = $this->getPackageVersion($namespace) ?? 0;
            $instance = new $class($this->app, $current_version);
            if (get_parent_class($instance) !== 'Gsnowhawk\\PackageSetup') {
                continue;
            }
            if (false !== $instance->update($configuration, $current_version)) {
                $this->setChecksum([$namespace, $class::VERSION, $checksum]);
            } else {
                $failed[] = $instance->getMessage();
            }
        }
        $this->saveChecksum();
        $this->app->refreshConfiguration($configuration);

        $tmpfile = $this->app->cnf('global:data_dir') . '/config.php';
        if (!file_exists($tmpfile)) {
            $this->session->param('messages', Lang::translate('SUCCESS_SETUP'));
        }

        if (!empty($failed)) {
            $this->session->param('messages', implode(PHP_EOL, $failed));
        }

        $bundles = $this->navItems();
        if (count($bundles) === 1) {
            $this->app->currentApplication($bundles[0]['name']);
        }

        if ((int)DEBUG_MODE !== 2) {
            $url = $this->app->systemURI().'?mode=system.response';
            Http::redirect($url);
        }

        return empty($failed);
    }

    /**
     * Execute setup
     */
    public function setupPlugin()
    {
        $paths = $this->request->POST('paths');
        $classes = $this->request->POST('classes');
        if (empty($paths)) {
            $paths = [];
            $this->init();
        }
        $configuration = $this->app->cnf(null);
        $failed = [];
        foreach ($paths as $i => $classfile) {
            include_once($classfile);
            $checksum = md5_file($classfile);

            $class = $classes[$i];
            $namespace = $class::getNameSpace();
            $current_version = $this->getPackageVersion($namespace) ?? 0;
            $instance = new $class($this->app, $current_version);
            if (get_parent_class($instance) !== 'Gsnowhawk\\PackageSetup') {
                continue;
            }
            if (false !== $instance->update($configuration, $current_version)) {
                $this->setChecksumPlugins([$namespace, $class::VERSION, $checksum]);
            } else {
                $failed[] = $instance->getMessage();
            }
        }
        $this->saveChecksum('plugins.log');
        $this->app->refreshConfiguration($configuration);

        $tmpfile = $this->app->cnf('global:data_dir') . '/config.php';
        if (!file_exists($tmpfile)) {
            $this->session->param('messages', Lang::translate('SUCCESS_SETUP'));
        }

        $url = $this->app->systemURI().'?mode=system.response:plugins';
        Http::redirect($url);
    }

    /**
     * Download or delete configuration file
     */
    public function download()
    {
        $config_file = $this->app->cnf('global:data_dir') . '/config.php';
        $filename = basename($config_file);
        $content_length = filesize($config_file);
        $start = $this->request->POST('start_cookie');
        $end = $this->request->POST('end_cookie');
        setcookie($end, $_COOKIE[$start]);
        Http::responseHeader("Content-Disposition: attachment; filename=\"$filename\"");
        Http::responseHeader("Content-length: $content_length");
        Http::responseHeader('Content-Type: text/plain; charset=utf-8');
        readfile($config_file);
        unlink($config_file);
        exit;
    }

    /**
     * Log rotation
     */
    public function logRotate()
    {
        $error_log = ERROR_LOG_DESTINATION;
        $access_log = dirname($error_log).'/access.log';
        $ext = date('YmdHis');

        if ($this->request->POST('errorlog_rotate') === '1' && file_exists($error_log)) {
            if (false === rename($error_log, preg_replace("/^(.+)\.log$/", "$1.$ext.log", $error_log))) {
                $this->session->param('messages', Lang::translate('FAILD_ERRORLOG_ROTATE'));
            }
        }

        if ($this->request->POST('accesslog_rotate') === '1' && file_exists($access_log)) {
            if (false === rename($access_log, preg_replace("/^(.+)\.log$/", "$1.$ext.log", $access_log))) {
                $this->session->param('messages', Lang::translate('FAILD_ACCESSLOG_ROTATE'));
            }
        }

        $url = $this->app->systemURI().'?mode=system.response:log';
        Http::redirect($url);
    }

    public function execSql()
    {
        if (defined('MEMORY_FOR_DOWNLOAD')) {
            ini_set('memory_limit', MEMORY_FOR_DOWNLOAD);
        }

        set_time_limit(0);

        $success = 0;
        $status = 0;
        $sql = $this->request->post('sql');
        if (!empty($sql)) {
            $fp = tmpfile();
            fwrite($fp, $sql);
            rewind($fp);
            if (false === ($count = $this->db->execSql($fp))) {
                if ($this->app->isAjax()) {
                    $status = 1;
                    $json = [
                        'status' => $status,
                        'message' => Lang::translate('EXEC_SQL_FAILED'),
                    ];
                    $fault = $this->db->fault;
                    if (!empty($fault[2] ?? null)) {
                        $json['description'] = $fault[2];
                        trigger_error($fault[2]);
                    }
                    $this->responseJson($json);
                }
                $this->dbmanage();

                return;
            }
            $success += $count;
        }

        $sqlfile = $this->request->files('sqlfile');
        if ($sqlfile['error'] !== UPLOAD_ERR_NO_FILE) {
            $count = 0;
            if ($sqlfile['error'] !== UPLOAD_ERR_OK
                || false === ($fp = @fopen($sqlfile['tmp_name'], 'r'))
                || false === ($count = $this->db->execSql($fp))
            ) {
                if ($this->app->isAjax()) {
                    $status = 1;
                    $json = [
                        'status' => $status,
                        'message' => Lang::translate('EXEC_SQL_FAILED'),
                    ];
                    $fault = $this->db->fault;
                    if (!empty($fault[2] ?? null)) {
                        $json['description'] = $fault[2];
                        trigger_error($fault[2]);
                    }
                    $this->responseJson($json);
                }
                $this->dbmanage();

                return;
            }
            $success += $count;
        }

        if ($this->app->isAjax()) {
            $this->responseJson([
                'status' => $status,
                'message' => sprintf(Lang::translate('EXEC_SQL_SUCCESS'), $success),
            ]);
        }

        $url = $this->app->systemURI().'?mode=system.response:dbmanage';
        Http::redirect($url);
    }

    public function dumpDb()
    {
        if (defined('MEMORY_FOR_DOWNLOAD')) {
            ini_set('memory_limit', MEMORY_FOR_DOWNLOAD);
        }

        set_time_limit(0);

        $tables = $this->request->post('tables');
        $options = [];
        $dump_option = $this->request->post('no_data');
        $options['no-data'] = ($dump_option === 'no-data') ? 1 : 0;
        $options['no-create-info'] = ($dump_option === 'no-create-info') ? 1 : 0;

        if (false === $this->db->dump($tables, $options)) {
            if ($this->app->isAjax()) {
                $this->responseJson([
                    'status' => 1,
                    'message' => Lang::translate('DB_DUMP_FAILED'),
                ]);
            }
            $this->dbmanage();

            return;
        }

        $url = $this->app->systemURI().'?mode=system.response:dbmanage';
        Http::redirect($url);
    }

    public function normalizeTable()
    {
        $tables = $this->request->post('normalizes');
        foreach ($tables as $table) {
            if (false === $this->db->resetAutoIncrement($table)) {
                trigger_error($this->db->error());
                if ($this->app->isAjax()) {
                    $this->responseJson([
                        'status' => 1,
                        'message' => Lang::translate('DB_NORMALIZE_FAILED'),
                    ]);
                }
                $this->dbmanage();

                return;
            }
        }

        if ($this->app->isAjax()) {
            $this->responseJson([
                'status' => 0,
                'message' => Lang::translate('DB_NORMALIZE_SUCCESS'),
            ]);
        }

        $url = $this->app->systemURI().'?mode=system.response:dbmanage';
        Http::redirect($url);
    }
}
