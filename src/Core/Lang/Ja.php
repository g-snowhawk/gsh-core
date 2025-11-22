<?php

/**
 * This file is part of Gsnowhawk System.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk\Lang;

/**
 * Japanese Languages for Gsnowhawk Framework.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Ja extends \Gsnowhawk\Common\Lang
{
    /**
     * WARNINGS.
     */
    protected $NORECORDS = '※ 該当するデータが見つかりません。';
    protected $INVALID_FORM = '入力内容にエラーがあります。ご確認下さい。';
    protected $EMPTY_INPUT = '未入力の項目があります。ご確認下さい。';
    protected $SYSTEM_ERROR = 'システムエラーです。';
    protected $INVALID_FILENAME = "不正なファイル名です。\nファイル名には半角英数字、ハイフン、アンダースコア、ピリオドのみが使用できます。";
    protected $INVALID_DIRNAME = "不正なディレクトリ名です。\nディレクトリ名には半角英数字、ハイフン、アンダースコア、ピリオドのみが使用できます。";
    protected $NOT_MAKE_DOTFILE = 'ピリオドで始まる名前は指定できません。';
    protected $FAILURE_SAVED = 'データ更新に失敗しました。';
    protected $FAILURE_REMOVED = '削除できませんでした。';
    protected $NOCHANGED = '変更された箇所はありませんでした。';
    protected $SESSION_LIMIT_ERROR = '前回の操作から%s秒以上経過しました。';
    protected $PERMISSION_DENIED = 'リクエストされた操作に対する権限がありません';
    protected $ILLEGAL_OPERATION = '不正な操作が検出されました';
    protected $FAILED_SAVE = 'データ更新に失敗しました。';

    public const IGNORE_DOTFILE = 'ドットで始まるファイルは無視されます';

    /**
     * NOTICE.
     */
    protected $SUCCESS_SAVED = 'データを更新しました。';
    protected $SUCCESS_REMOVED = 'データを削除しました。';

    /**
     * Confirmation.
     */
    protected $CONFIRM_SAVE_DATA = 'データを保存します。よろしいですか？';
    protected $CONFIRM_DELETE_DATA = "データを削除します。取消はできません\nよろしいですか？";

    /**
     * Form elements.
     */
    protected $REMOVE_LABEL = '削除';
    protected $PREVIEW_LABEL = '内容確認';
    protected $SELECT_LABEL = '-- 選択してください --';

    protected $EDIT = '編集';
    protected $DELETE = '削除';

    /**
     * Date format.
     */
    protected $DATE_FORMAT = 'Y年n月j日';

    /**
     * Form Error Message.
     */
    protected $ERROR_SIZEOVER = "送信されたフォームの容量（ %s ）が大き過ぎます。\n送信できる最大サイズは合計 %s までです。";

    protected $EXECTIME = '処理時間%s秒';
    protected $MINUTE = '分';

    /**
     * Ttitles.
     */
    protected $TITLE_SIGNIN = 'ログイン認証';
}
