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
use Gsnowhawk\Common\Environment;
use Gsnowhawk\Common\File;

/**
 * Custom Logging class.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Logger
{
    private $app;
    private $current_app;
    private $db = null;
    private $logfile;
    private $logsize = 1048576;
    private $logtable = null;
    private $maxlogs = 7;
    private $separator = ' ';

    public function __construct(string $logdir, Base $app, string $logfile = 'access.log', ?string $table = null)
    {
        if (empty($logfile)) {
            throw new ErrorException('Logfile name is empty', 0, E_USER_ERROR, __FILE__, __LINE__);
        }

        $this->logfile = File::realpath("{$logdir}/{$logfile}");
        $this->app = $app;

        if (!empty($table)) {
            $this->logtable = $table;
            $this->db = new Db(
                $this->app->cnf('database:db_driver'),
                $this->app->cnf('database:db_host'),
                $this->app->cnf('database:db_source'),
                $this->app->cnf('database:db_user'),
                $this->app->cnf('database:db_password'),
                $this->app->cnf('database:db_port'),
                $this->app->cnf('database:db_encoding')
            );
            $this->db->setTablePrefix($this->app->cnf('database:db_table_prefix'));
            if (!$this->db->open()) {
                $this->db = null;
            }
        }
    }

    public function setApp(Common $app): void
    {
        $this->current_app = $app;
    }

    public function syslog(string $format, array $context = [], int $level = 0): void
    {
        $this->app->syslog($format, $context, $level);
    }

    public function log($message, $level = 0)
    {
        if (is_a($this->current_app, 'Gsnowhawk\\User') && $this->current_app->isRoot()) {
            return;
        }

        $log = [
            'remote_addr' => Environment::server('remote_addr'),
            'remote_user' => $this->app->session->param('alias') ?? $this->app->session->param('uname') ?? '-',
            'logtime' => date('Y-m-d H:i:s'),
            'summary' => $message,
            'user_agent' => Environment::server('HTTP_USER_AGENT'),
            'host' => Environment::server('HTTP_HOST') ?? Environment::server('SERVER_NAME'),
        ];

        if ($this->app->cnf('global:log_use_plugin') === 1) {
            $tmp = $this->app->execPlugin('setSyslogOption', $log);
            if (is_array($tmp)) {
                $tmp = array_shift($tmp);
                if (is_array($tmp) && count(array_intersect_key($log, $tmp)) === count($log)) {
                    $log = $tmp;
                }
            }
        } else {
            foreach (debug_backtrace() as $unit) {
                $func = $unit['function'] ?? '';
                if ($func === 'log' || $func === 'syslog') {
                    continue;
                }
                $instance = $unit['object'] ?? null;
                if (is_object($instance)) {
                    if (method_exists($instance, 'setSyslogOption')) {
                        $instance->setSyslogOption($instance, $log);
                        break;
                    }
                }
            }
        }

        if (is_null($this->db)) {
            $log['logtime'] = '['.$log['logtime'].']';
            error_log(implode($this->separator, $log)."\n", 3, $this->logfile);
            $size = filesize($this->logfile);
            if ((int) $size >= $this->logsize) {
                $this->rotate();
            }
        } else {
            $this->db->begin();
            $this->db->insert($this->logtable, $log);
            $err = $this->db->error();
            $this->db->commit();
        }
    }

    private function rotate()
    {
        for ($i = $this->maxlogs; $i > 1; --$i) {
            $j = $i - 1;
            $file_s = $this->logfile.".$j";
            $file_d = $this->logfile.".$i";
            if (file_exists($file_s)) {
                rename($file_s, $file_d);
            }
        }
        rename($this->logfile, $this->logfile.'.1');
    }
}
