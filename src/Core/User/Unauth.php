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

/**
 * User management request response class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Unauth extends \Gsnowhawk\User implements \Gsnowhawk\Unauth
{
    public static function guestExecutables() //: array
    {
        return [
            __CLASS__,
            array_diff(
                get_class_methods(__CLASS__),
                get_class_methods(get_parent_class()),
                [__FUNCTION__]
            )
        ];
    }

    public function reminder() //: void
    {
        echo 'Reminder';
        exit;
    }
}
