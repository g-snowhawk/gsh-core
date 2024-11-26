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

use Gsnowhawk\Common\Text;

/**
 * Data validation class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Validator
{
    /**
     * Error.
     *
     * @var array
     */
    private $errors = [];

    /**
     * Check targets.
     *
     * @var array
     */
    private $checker = [];

    /**
     * POST values.
     *
     * @var array
     */
    private $values = [];

    /**
     * Object Constructor.
     *
     * @param array $checker
     */
    public function __construct(array $checker)
    {
        $this->errors = [];

        foreach ($checker as $check) {
            $key = array_shift($check);

            $n = 1;
            $original_key = $key;
            while (isset($this->checker[$key])) {
                $key = "$original_key.$n";
                ++$n;
            }

            $this->checker[$key] = [
                'source' => array_shift($check),
                'type' => array_shift($check),
                'errnum' => (!empty($check)) ? array_shift($check) : 1,
                'args' => (!empty($check)) ? $check : null,
            ];
        }
    }

    /**
     * Validation.
     *
     * @param array $values
     * @param array $files
     *
     * @return bool
     */
    public function valid($values, $files)
    {
        $this->values = [];
        foreach ($values as $k => $v) {
            if (in_array($k, ['stub','mode'])) {
                continue;
            }
            $this->values[$k] = $v;
        }
        $duplicate = [];
        foreach ($this->checker as $key => $unit) {
            if ($unit['errnum'] === 0) {
                continue;
            }
            $t = null;
            $s = $unit['source'];
            if (preg_match('/^(.+)\[(.+)\]$/', $s, $match)) {
                $s = $match[1];
                $t = $match[2];
            } elseif (strpos($s, ',')) {
                $t = Text::explode(',', $s);
                $s = array_shift($t);
            }

            if (!isset($this->values[$s]) && isset($files[$s])) {
                $this->values[$s] = $files[$s];
            }

            if ($unit['type'] === 'empty' && !isset($this->values[$s])) {
                $this->values[$s] = '';
            }

            if (isset($this->values[$s])) {
                if (isset($duplicate[$s])) {
                    continue;
                }
                if (isset($t)) {
                    if (is_array($t)) {
                        $value = [$this->values[$s]];
                        foreach ($t as $k) {
                            $value[] = $this->values[$k];
                        }
                    } else {
                        $value = $this->values[$s][$t] ?? null;
                    }
                } else {
                    $value = $this->values[$s];
                }

                $error_key = preg_replace('/\.[0-9]+$/', '', $key);
                if (!empty($this->errors[$error_key])) {
                    continue;
                }
                $this->errors[$error_key] = ($this->check($key, $value)) ? 0 : $this->checker[$key]['errnum'];
                if ($this->errors[$error_key] > 0) {
                    $duplicate[$s] = 1;
                }
            }
        }

        return $this->errors;
    }

    /**
     * Validate.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    public function check($key, $value)
    {
        $result = false;
        $type = $this->checker[$key]['type'];
        $args = $this->checker[$key]['args'];

        switch ($type) {
            case 'blank':
                $result = !is_null($value) && $value !== '';
                break;
            case 'date_format':
                if (preg_match('/^(([0-9]{2})?([0-9]{2}))[\/\-\.]([0-9]{1,2})[\/\-\.]([0-9]{1,2})$/', $value, $match)) {
                    $year = $match[1];
                    $month = $match[4];
                    $day = $match[5];
                    $result = checkdate($month, $day, $year);
                }
                break;
            case 'datetime_format':
                if (preg_match('/^(([0-9]{2})?([0-9]{2}))[\/\-\.]([0-9]{1,2})[\/\-\.]([0-9]{1,2})[ T]([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?$/', $value, $match)) {
                    $year = $match[1];
                    $month = $match[4];
                    $day = $match[5];
                    if (checkdate($month, $day, $year)) {
                        $hour = intval($match[6]);
                        $minute = intval($match[7]);
                        $second = (isset($match[9])) ? intval($match[9]) : 0;
                        if ($hour >= 0 && $hour < 60
                            && $minute >= 0 && $minute < 60
                            && $second >= 0 && $second < 60
                        ) {
                            $result = true;
                        }
                    }
                }
                break;
            case 'digit':
                $result = ctype_digit($value);
                break;
            case 'disallowtags':
                $striped = strip_tags($value);
                $result = ($striped === $value);
                break;
            case 'double':
                if (preg_match('/^[0-9]*\.[0-9]+$/', $value)
                    || preg_match('/^[0-9]+$/', $value)
                ) {
                    $result = true;
                }
                break;
            case 'eq':
                if ($value === $stype) {
                    $result = true;
                }
                break;
            case 'exists':
                $result = file_exists($value);
                break;
            case 'float':
                if (is_numeric($value)) {
                    $float = (float)$value;
                    $result = (string)$float === $value;
                }
                break;
            case 'ge':
                $result = floatval($value) >= floatval($args[0]);
                break;
            case 'gt':
                $result = floatval($value) > floatval($args[0]);
                break;
            case 'gt_datetime':
                $result = strtotime($value) > strtotime($args[0]);
                break;
            case 'int':
                if (is_numeric($value)) {
                    $int = (int)$value;
                    $result = (string)$int === $value;
                }
                break;
            case 'le':
                $result = floatval($value) <= floatval($args[0]);
                break;
            case 'lt':
                $result = floatval($value) < floatval($args[0]);
                break;
            case 'lt_datetime':
                $result = strtotime($value) < strtotime($args[0]);
                break;
            case 'mail':
                return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
                break;
            case 'naming':
                $result = (
                    preg_match('/[\._-]{2,}/', $value) !== 1
                    && preg_match('/^[a-z0-9][a-z0-9\._-]+[a-z0-9]$/i', $value) === 1
                );
                break;
            case 'ne':
                if ($value !== $stype) {
                    $result = true;
                }
                break;
            case 'range':
                $result = ((float)$value >= (float)$args[0] && (float)$value <= (float)$args[1]);
                break;
            case 'retype':
                $result = ($value[0] === $value[1]);
                break;
            case 'rfc822':
                return (false !== filter_var($value, FILTER_VALIDATE_EMAIL));
                break;
            case 'time_format':
                if (preg_match('/^([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?$/', $value, $match)) {
                    $hour = intval($match[1]);
                    $minute = intval($match[2]);
                    $second = (isset($match[4])) ? intval($match[4]) : 0;
                    if ($hour >= 0 && $hour < 60
                        && $minute >= 0 && $minute < 60
                        && $second >= 0 && $second < 60
                    ) {
                        $result = true;
                    }
                }
                break;
            case 'upload':
                $result = true;
                if ($value['error'] > 0) {
                    $result = false;
                    $this->checker[$key]['errnum'] = $value['error'];
                } else {
                    if (is_array($this->checker[$key]['args'])) {
                        $ext = pathinfo($value['name'], PATHINFO_EXTENSION);
                        $result = in_array(strtolower(".$ext"), array_map('strtolower', $this->checker[$key]['args']));
                    }
                }
                break;
            case 'upload_graceful':
                $result = true;
                if ($value['error'] !== 4) {
                    if ($value['error'] > 0) {
                        $result = false;
                        $this->checker[$key]['errnum'] = $value['error'];
                    } else {
                        if (is_array($this->checker[$key]['args'])) {
                            $ext = pathinfo($value['name'], PATHINFO_EXTENSION);
                            $result = in_array(strtolower(".$ext"), array_map('strtolower', $this->checker[$key]['args']));
                        }
                    }
                }
                break;
            case 'writable':
                if (is_dir($value)) {
                    $result = true;
                    try {
                        @touch($value.'/writabletest');
                        unlink($value.'/writabletest');
                    } catch (\ErrorException $e) {
                        $result = false;
                    }
                } else {
                    $result = is_writable($value);
                }
                break;
            default:
                if (is_array($type)) { // External validation
                    $class = $type[0];
                    $method = $type[1];
                    if ((is_object($class) || class_exists($class)) && method_exists($class, $method)) {
                        $result = call_user_func($type);
                    }
                } elseif (is_callable($type) && $type instanceof \Closure) { // Use closure
                    $result = $type($key, $value, $this->checker[$key], $this->values);
                } else {
                    $result = !empty($value);
                }
        }

        if ($result === false && in_array('allowempty', (array)$this->checker[$key]['args'])) {
            $result = empty($value);
        }

        return $result;
    }
}
