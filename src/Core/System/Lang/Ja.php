<?php
/**
 * This file is part of Gsnowhawk System.
 *
 * Copyright (c)2016-2017 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk\System\Lang;

/**
 * Japanese Languages for Gsnowhawk Framework.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Ja extends \Gsnowhawk\Common\Lang
{
    protected $HEADER_TITLE = '追加機能管理';
    protected $SUCCESS_SETUP = '機能追加に成功しました';
    protected $FAILD_ERRORLOG_ROTATE = 'エラーログのローテーションに失敗しました';
    protected $FAILD_ACCESSLOG_ROTATE = 'アクセスログのローテーションに失敗しました';

    const DB_DUMP_FAILED = 'データ生成に失敗しました';
    const DB_NORMALIZE_FAILED = 'テーブルの最適化に失敗しました';
    const DB_NORMALIZE_SUCCESS = 'テーブルの最適化が完了しました';
    const EXEC_SQL_FAILED = 'SQLエラー';
    const EXEC_SQL_SUCCESS = '%d個のSQLを実行しました';
}
