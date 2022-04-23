<?php
/**
 * This file is part of Gsnowhawk System.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk\Install\Lang;

/**
 * Japanese Languages for Gshowhawk installer.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Ja extends \Gsnowhawk\Common\Lang
{
    protected $EMPTY_BASE_URL = 'URLを入力してください';
    protected $EMPTY_DOMAIN_NAME = 'ドメイン名を入力してください';
    protected $EMPTY_DOC_ROOT = 'ドキュメントルートを入力してください';
    protected $EMPTY_SAVE_DIR = 'データ保存パスを入力してください';
    protected $EMPTY_ASSETS_PATH = 'スタティックパスを入力してください';

    protected $NOT_EXISTS_SAVE_DIR = 'データ保存パスが存在しません。あらかじめ作成してください';
    protected $NOT_WRITABLE_SAVE_DIR = 'データ保存パスに書き込み権限がありません';
    protected $NOT_WRITABLE_CONFIG_FILE = '設定ファイル（%s）に書き込み権限がありません。削除または移動してください';

    protected $EMPTY_DB_HOST = 'ホスト名を入力してください';
    protected $EMPTY_DB_SOURCE = 'データベース名を入力してください';
    protected $EMPTY_DB_USER = 'データベースユーザーを入力してください';
    protected $EMPTY_DB_PASSWORD = 'パスワードを入力してください';
    protected $DB_CONNECT_ERROR = 'データベースに接続できません';

    protected $EMPTY_COMPANY = '会社名を入力してください';
    protected $EMPTY_EMAIL = 'E-mail名を入力してください';
    protected $EMPTY_UNAME = 'ログイン名を入力してください';
    protected $EMPTY_UPASS = 'パスワードを入力してください';
    protected $UNMATCH_PASSWORD = '入力値が一致しません';

    protected $EMPTY_TITLE = 'サイト名を入力してください';
}
