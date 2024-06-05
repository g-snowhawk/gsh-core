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

use PDO;
use PDOException;

/**
 * Database connection class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Db extends \Gsnowhawk\Common\Db
{
    public const GET_RETURN_HASH = 1;
    public const GET_RETURN_ARRAY = 2;
    public const GET_RETURN_STATEMENT = 3;
    public const GET_RETURN_COLUMN = 4;
    public const CHILD_TABLE_ALIAS = 'children';
    public const MAX_ALLOWED_PACKET = 1048576; // 1MB

    /**
     * Database table prefix.
     *
     * @var string
     */
    private $prefix;

    public $fault;

    /**
     * Object Constructor.
     *
     * @param string $driver   Database driver
     * @param string $host     Database server host name or IP address
     * @param string $source   Data source
     * @param string $user     Database user name
     * @param string $password Database password
     * @param string $port     Database server port
     * @param string $enc      Database encoding
     */
    public function __construct($driver, $host, $source, $user, $password, $port = 3306, $enc = '')
    {
        $dbDriver = $driver;
        $dbHost = $host;
        $dbPort = $port;
        $dbSource = $source;
        $dbUser = $user;
        $dbPasswd = $password;
        $dbEnc = $enc;
        parent::__construct(
            $dbDriver,
            $dbHost,
            $dbSource,
            $dbUser,
            $dbPasswd,
            $dbPort,
            $dbEnc
        );
    }

    public function setTablePrefix($prefix)
    {
        return $this->prefix = $prefix;
    }

    /**
     * Table name.
     *
     * @param string $key
     *
     * @return string
     */
    public function TABLE($key)
    {
        if (is_null($key)) {
            return;
        }

        return (strpos($key, $this->prefix) !== 0) ? $this->prefix.strtolower($key) : $key;
    }

    /**
     * Quotation SQL String.
     *
     * @param mixed $value
     * @param bool  $isZero
     * @param bool  $isNull
     *
     * @return string
     */
    public function quote($value, $isZero = false, $isNull = false)
    {
        if (is_string($value)) {
            $value = preg_replace("/\[%%GSH:/", '[$GSH:', $value);
        }

        return parent::quote($value, $isZero, $isNull);
    }

    /**
     * Execute SQL.
     *
     * @param string $sql
     * @param array $options
     * @param array $bind
     *
     * @return mixed
     */
    public function exec($sql, $options = null, $bind = null)
    {
        return parent::exec(str_replace('table::', $this->prefix, $sql), $options, $bind);
    }

    /**
     * Execute SQL.
     *
     * @param string $sql
     * @param array $options
     * @param array $bind
     *
     * @return mixed
     */
    public function query($sql, $options = null, $bind = null)
    {
        return parent::query(str_replace('table::', $this->prefix, $sql), $options, $bind);
    }

    /**
     * exec insert SQL.
     *
     * @param string $table
     * @param array  $data
     * @param array  $raws
     * @param array  $fields
     *
     * @return mixed
     */
    public function insert($table, array $data, $raws = null, $fields = null, bool $ignore = false)
    {
        return parent::insert(self::TABLE($table), $data, $raws, $fields);
    }

    /**
     * exec update SQL.
     *
     * @param string $table
     * @param array  $data
     * @param string $statement
     * @param array  $options
     * @param array  $raws
     * @param array  $fields
     *
     * @return mixed
     */
    public function update($table, $data, $statement = '', $options = [], $raws = null, $fields = null)
    {
        return parent::update(self::TABLE($table), $data, $statement, $options, $raws);
    }

    /**
     * exec insert or update SQL.
     *
     * @param string $table
     * @param array  $data
     * @param array  $unique
     * @param array  $raws
     * @param array  $fields
     *
     * @return mixed
     */
    public function replace($table, array $data, $unique, $raws = [], $fields = null)
    {
        return parent::replace(self::TABLE($table), $data, $unique, $raws);
    }

    public function merge($table, array $data, array $skip = [], $key_name = 'PRIMARY')
    {
        return parent::merge(self::TABLE($table), $data, $skip, self::TABLE($key_name));
    }

    /**
     * exec delete SQL.
     *
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function delete($table, $statement = '', $options = null)
    {
        return parent::delete(self::TABLE($table), $statement, $options);
    }

    /**
     * exec update or insert SQL.
     *
     * @param string $table
     * @param array  $data
     * @param array  $unique
     * @param array  $raws
     *
     * @return mixed
     */
    public function updateOrInsert($table, array $data, $unique, $raws = [])
    {
        return parent::updateOrInsert(self::TABLE($table), $data, $unique, $raws);
    }

    /**
     * Select.
     *
     * @param string $columns
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function select($columns, $table, $statement = '', $options = [])
    {
        return parent::select($columns, self::TABLE($table), $statement, $options);
    }

    /**
     * Select Single.
     *
     * @param string $columns
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function selectSingle($columns, $table, $statement = '', $options = [])
    {
        $result = parent::select($columns, self::TABLE($table), $statement, $options);
        if (is_array($result) && count($result) > 0) {
            return array_shift($result);
        }

        return $result;
    }

    /**
     * Exists Records.
     *
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function exists($table, $statement = '', $options = [])
    {
        return parent::exists(self::TABLE($table), $statement, $options);
    }

    /**
     * Get Value.
     *
     * @param string $columns
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function get($column, $table, $statement = '', $options = [])
    {
        return parent::get($column, self::TABLE($table), $statement, $options);
    }

    /**
     * MIN Value.
     *
     * @param string $column
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function min($column, $table, $statement = '', $options = [])
    {
        return parent::min($column, self::TABLE($table), $statement, $options);
    }

    /**
     * MAX Value.
     *
     * @param string $column
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function max($column, $table, $statement = '', $options = [])
    {
        return parent::max($column, self::TABLE($table), $statement, $options);
    }

    /**
     * Update Modified date.
     *
     * @param string $table
     * @param string $statement
     * @param array  $options
     * @param string $column
     * @param array  $extra
     *
     * @return bool
     */
    public function modified($table, $statement = '', array $options = [], $column = 'modify_date', $extra = []): bool
    {
        return false !== $this->update($table, $extra, $statement, $options, [$column => 'CURRENT_TIMESTAMP']);
    }

    /**
     * RecordCount.
     *
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function count($table, $statement = '', $options = [])
    {
        return parent::count(self::TABLE($table), $statement, $options);
    }

    /**
     * record count of execute query.
     *
     * @return int
     */
    public function recordCount($sql = '', $options = null)
    {
        return parent::recordCount(str_replace('table::', $this->prefix, $sql), $options);
    }

    /**
     * Execute query and return.
     *
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function getAll($statement, $options = [], $return_type = self::GET_RETURN_ARRAY)
    {
        $sql = self::build($statement, $options);
        if (false !== $stat = $this->query($sql)) {
            switch ($return_type) {
                case self::GET_RETURN_HASH:
                    return $stat->fetch(PDO::FETCH_ASSOC);
                case self::GET_RETURN_ARRAY:
                    $fetch = $stat->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($fetch as &$unit) {
                        $keys = array_keys($unit);
                        foreach ($keys as $key) {
                            $newkey = str_replace(self::CHILD_TABLE_ALIAS.'.', '', $key);
                            if (!isset($unit[$newkey])) {
                                $unit[$newkey] = $unit[$key];
                                unset($unit[$key]);
                            }
                        }
                    }
                    unset($unit);

                    return $fetch;
                case self::GET_RETURN_STATEMENT:
                    return $stat;
                case self::GET_RETURN_COLUMN:
                    return $stat->fetchColumn();
                default:
                    return;
            }
        }

        return false;
    }

    /**
     * Build SQL Statement.
     *
     * @param string $sql
     * @param array  $options
     *
     * @return string
     */
    public function build($sql, $options)
    {
        return $this->prepareStatement(str_replace('table::', $this->prefix, $sql), $options);
    }

    /**
     * Prepare.
     *
     * @param string $statement
     *
     * @return mixed
     */
    public function prepare($statement)
    {
        return parent::prepare(str_replace('table::', $this->prefix, $statement));
    }

    /**
     * SQL for Decendant nodes from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $children
     * @param string $extensions
     *
     * @return mixed
     */
    public static function nsmDecendantsSQL($columns, $parent, $children = null, $extensions = '')
    {
        if (is_null($children)) {
            $children = $parent;
        }

        $alias = self::CHILD_TABLE_ALIAS;

        return  "SELECT $columns
                   FROM $parent parent
                        LEFT OUTER JOIN $children {$alias}
                                     ON {$alias}.lft > parent.lft
                                    AND {$alias}.lft < parent.rgt
                  WHERE {$alias}.id IS NOT NULL$extensions";
    }

    /**
     * SQL for Decendant nodes from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $children
     * @param string $extensions
     *
     * @return mixed
     */
    public function nsmGetDecendants($columns, $parent, $children = null, $options = null, $extensions = '')
    {
        return $this->getAll(self::nsmDecendantsSQL($columns, $parent, $children, $extensions), $options);
    }

    /**
     * SQL for child nodes from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $midparent
     * @param string $children
     * @param string $filters
     *
     * @return mixed
     */
    public static function nsmChildrenSQL($columns, $parent, $midparent = null, $children = null, $filters = '')
    {
        if (is_null($midparent)) {
            $midparent = $parent;
        }
        if (is_null($children)) {
            $children = $parent;
        }

        $alias = self::CHILD_TABLE_ALIAS;

        return  "SELECT $columns
                   FROM $parent parent
                        LEFT OUTER JOIN $children {$alias}
                                     ON {$alias}.lft > parent.lft
                                    AND {$alias}.lft < parent.rgt
                  WHERE NOT EXISTS
                        (
                            SELECT *
                              FROM $midparent midparent
                             WHERE midparent.lft BETWEEN parent.lft AND parent.rgt
                               AND {$alias}.lft BETWEEN midparent.lft AND midparent.rgt
                               AND midparent.id NOT IN (children.id, parent.id)
                        ) $filters";
    }

    public function nsmGetChildren($columns, $parent, $midparent = null, $children = null, $filters = '', $options = null)
    {
        return $this->getAll(self::nsmChildrenSQL($columns, $parent, $midparent, $children, $filters), $options);
    }

    /**
     * SQL for root node from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $children
     *
     * @return mixed
     */
    public static function nsmRootSQL($columns, $parent, $children = null)
    {
        if (is_null($children)) {
            $children = $parent;
        }

        $alias = self::CHILD_TABLE_ALIAS;

        return "SELECT $columns
                  FROM $children {$alias}
                 WHERE NOT EXISTS (
                               SELECT *
                                 FROM $parent parent
                                WHERE {$alias}.lft > parent.lft
                                  AND {$alias}.lft < parent.rgt
                           )";
    }

    /**
     * Fetch root node from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $children
     *
     * @return mixed
     */
    public function nsmGetRoot($columns, $parent, $children = null, $options = [], $statement = '')
    {
        return $this->getAll(self::nsmRootSQL($columns, $parent, $children).$statement, $options);
    }

    /**
     * SQL for parent nodes from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $children
     *
     * @return mixed
     */
    public static function nsmParentSQL($columns, $parent, $children = null)
    {
        if (is_null($children)) {
            $children = $parent;
        }

        $alias = self::CHILD_TABLE_ALIAS;

        return "SELECT parent.id
                  FROM ($children) {$alias}
                       LEFT OUTER JOIN ($parent) parent
                                    ON parent.lft < {$alias}.lft
                                   AND parent.lft = (SELECT MAX(lft)
                                                       FROM $parent child
                                                      WHERE {$alias}.lft > child.lft
                                                        AND {$alias}.lft < child.rgt)";
    }

    /**
     * Fetch parent nodes from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $children
     * @param array  $options
     *
     * @return mixed
     */
    public function nsmGetParent($columns, $parent, $children = null, $options = null)
    {
        return $this->getAll(self::nsmParentSQL($columns, $parent, $children), $options, self::GET_RETURN_COLUMN);
    }

    /**
     * SQL for parent nodes from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $children
     * @param string $limit
     *
     * @return mixed
     */
    public static function nsmParentsSQL($columns, $parent, $children = null, $limit = null)
    {
        if (is_null($children)) {
            $children = $parent;
        }
        $filters = (is_null($limit)) ? '' : ' AND parent.lft >= ?';

        $alias = self::CHILD_TABLE_ALIAS;

        return "SELECT $columns
                  FROM $children {$alias}
                       LEFT OUTER JOIN $parent parent
                                    ON {$alias}.lft > parent.lft
                                   AND {$alias}.lft < parent.rgt
                 WHERE {$alias}.id = ? $filters";
    }

    /**
     * Fetch parent nodes from Nested Set Model.
     *
     * @param int    $child_id
     * @param string $columns
     * @param string $parent
     * @param string $children
     * @param int    $parent_id
     *
     * @return mixed
     */
    public function nsmGetParents($child_id, $columns, $parent, $children = null, $parent_id = null)
    {
        return $this->getAll(self::nsmParentsSQL($columns, $parent, $children, $parent_id), [$child_id, $parent_id]);
    }

    /**
     * SQL for position from Nested Set Model.
     *
     * @param string $parent
     * @param string $children
     *
     * @return mixed
     */
    public static function nsmPositionSQL($parent, $children = null)
    {
        if (is_null($children)) {
            $children = $parent;
        }

        $alias = self::CHILD_TABLE_ALIAS;

        return "SELECT CASE WHEN child.rgt IS NULL
                            THEN parent.lft
                            ELSE MAX(child.rgt)
                        END AS lft, parent.rgt AS rgt
                  FROM $parent parent
                       LEFT OUTER JOIN $children child
                                    ON parent.lft = (SELECT MAX(lft)
                                                       FROM $children {$alias}
                                                      WHERE child.lft > {$alias}.lft
                                                        AND child.lft < {$alias}.rgt)";
    }

    public function nsmGetPosition($parent, $children = null, $options = null)
    {
        return $this->getAll(self::nsmPositionSQL($parent, $children), $options, self::GET_RETURN_HASH);
    }

    /**
     * SQL for count children from Nested Set Model.
     *
     * @param string $parent
     * @param string $children
     *
     * @return mixed
     */
    public static function nsmCountSQL($parent, $children = null)
    {
        if (is_null($children)) {
            $children = $parent;
        }

        $alias = self::CHILD_TABLE_ALIAS;

        return "SELECT COUNT(children.id) AS cnt
                  FROM $parent parent
                  LEFT OUTER JOIN $children {$alias}
                               ON parent.lft = (
                                      SELECT MAX(lft)
                                        FROM $children child
                                       WHERE {$alias}.lft > child.lft
                                         AND {$alias}.lft < child.rgt
                                  )
                 GROUP BY parent.id";
    }

    public function nsmGetCount($parent, $children = null, $options = null)
    {
        return $this->getAll(self::nsmCountSQL($parent, $children), $options, self::GET_RETURN_COLUMN);
    }

    /**
     * Path to nodes.
     *
     * @param string $columns
     * @param string $top
     * @param string $middle
     * @param string $bottom
     *
     * @return string
     */
    public static function nsmNodePathSQL($columns, $top, $middle = null, $bottom = null)
    {
        if (is_null($middle)) {
            $middle = $top;
        }
        if (is_null($bottom)) {
            $bottom = $top;
        }

        return "SELECT $columns
                  FROM $top top, $middle middle, $bottom bottom
                 WHERE top.id = ?
                   AND bottom.id = ?
                   AND middle.lft BETWEEN top.lft AND top.rgt
                   AND bottom.lft BETWEEN middle.lft AND middle.rgt
                 ORDER BY middle.lft";
    }

    public function nsmGetNodePath($columns, $top, $middle = null, $bottom = null, $options = [])
    {
        return $this->getAll(self::nsmNodePathSQL($columns, $top, $middle, $bottom), $options);
    }

    public function nsmBeforeInsertChildSQL($table, $option = '')
    {
        return str_replace(
            'table::',
            $this->prefix,
            "UPDATE table::$table
                SET lft = CASE WHEN lft > :parent_rgt
                               THEN lft + :offset
                               ELSE lft END,
                    rgt = CASE WHEN rgt >= :parent_rgt
                               THEN rgt + :offset
                               ELSE rgt END
              WHERE rgt >= :parent_rgt$option"
        );
    }

    public function nsmCleanupSQL($table, $where = '', array &$options = [])
    {
        $lftrgt = "SELECT lft AS seq FROM table::{$table} {$where}
                    UNION ALL
                   SELECT rgt AS seq FROM table::{$table} {$where}";
        $options = array_merge($options, $options, $options, $options, $options);

        return str_replace(
            'table::',
            $this->prefix,
            "UPDATE table::{$table}
                SET lft = (SELECT COUNT(*)
                             FROM ({$lftrgt}) LftRgt
                            WHERE seq <= lft),
                    rgt = (SELECT COUNT(*)
                             FROM ({$lftrgt}) LftRgt
                            WHERE seq <= rgt) {$where}"
        );
    }

    public function nsmCleanup($table, $where = '', array $options = [])
    {
        if (!empty($where) && !preg_match('/^\s*where\s+.+$/i', $where)) {
            $where = "WHERE {$where}";
        }

        return parent::exec(self::nsmCleanupSQL($table, $where, $options), $options);
    }

    /**
     * Copy record.
     *
     * @param array  $cols
     * @param string $dest
     * @param string $source
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function copyRecord($cols, $dest, $source = '', $statement = '', $options = null)
    {
        if (empty($source)) {
            $source = $dest;
        }
        $dest = self::TABLE($dest);
        $source = self::TABLE($source);

        $sql = "INSERT INTO $dest
                     SELECT ".implode(',', $cols)."
                       FROM $source";
        if (!empty($statement)) {
            $sql .= " WHERE $statement";
        }

        return parent::exec(self::build($sql, $options));
    }

    /**
     * Get field list.
     *
     * @param string $table    Table Name
     * @param bool   $property
     * @param bool   $comment
     * @param string $statement
     *
     * @return mixed
     */
    public function getFields($table, $property = false, $comment = false, $statement = '')
    {
        // compatible
        if (strpos($table, $this->prefix) !== 0) {
            $table = self::TABLE($table);
        }

        return parent::getFields($table, $property, $comment, $statement);
    }

    public function execSql($fp)
    {
        if (!is_resource($fp)) {
            if (false === $fp = fopen($fp, 'r')) {
                return false;
            }
        }

        $sql = null;
        $command_type = null;
        $prev_chr = null;
        $quote = 'even';
        $db = parent::getHandler();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $success = 0;

        rewind($fp);
        while (false !== ($buffer = fgets($fp, 32))) {
            $buffer = str_replace("\r", '', $buffer);
            if (is_null($sql)) {
                if (preg_match('/^--[\s]*$/s', $buffer)) {
                    $command_type = 'empty comment';
                    $sql = null;
                } elseif (preg_match('/^(\r\n|\r|\n)$/s', $buffer)) {
                    $command_type = 'empty';
                } elseif (strpos($buffer, '-- ') === 0) {
                    $command_type = 'comment';
                    $sql = $buffer;
                } elseif (strpos($buffer, '/*!') === 0) {
                    $command_type = 'mysql versioning';
                    $sql = $buffer;
                } else {
                    $command_type = 'sql';
                    $quote = 'even';
                    $sql = $sql ?? '';
                    $prev_chr = null;
                    foreach (mb_str_split($buffer) as $chr) {
                        if ($chr === "'" && $prev_chr !== '\\') {
                            $quote = ($quote === 'even') ? 'odd' : 'even';
                        } elseif ($prev_chr === ';' && $chr === "\n") {
                            $sql = null;
                            $quote = 'even';
                            break;
                        } elseif ($chr === ';') {
                            if ($quote !== 'odd') {
                                try {
                                    $db->exec(str_replace('table::', $this->prefix, $sql));
                                } catch (PDOException $e) {
                                    $this->fault = $e->errorInfo;
                                    $this->fault[] = $sql;
                                    if ($db->inTransaction()) {
                                        $db->rollBack();
                                    }

                                    return false;
                                }
                                ++$success;
                                $sql = '';
                                $quote = 'even';
                                $prev_chr = $chr;
                                continue;
                            }
                        }
                        $sql .= $chr;
                        $prev_chr = $chr;
                    }

                    if (feof($fp) && !empty($sql)) {
                        try {
                            $db->exec(str_replace('table::', $this->prefix, $sql));
                        } catch (PDOException $e) {
                            $this->fault = $e->errorInfo;
                            $this->fault[] = $sql;
                            if ($db->inTransaction()) {
                                $db->rollBack();
                            }

                            return false;
                        }
                        ++$success;
                        $sql = '';
                        $quote = 'even';
                        $prev_chr = $chr;
                        continue;
                    }
                }
            } else {
                if ($command_type === 'sql') {
                    foreach (mb_str_split($buffer) as $chr) {
                        if ($chr === "'" && $prev_chr !== '\\') {
                            $quote = ($quote === 'even') ? 'odd' : 'even';
                        } elseif ($prev_chr === ';' && $chr === "\n") {
                            $sql = null;
                            $quote = 'even';
                            break;
                        } elseif ($chr === ';') {
                            if ($quote !== 'odd') {
                                try {
                                    $db->exec(str_replace('table::', $this->prefix, $sql));
                                } catch (PDOException $e) {
                                    $this->fault = $e->errorInfo;
                                    $this->fault[] = $sql;
                                    if ($db->inTransaction()) {
                                        $db->rollBack();
                                    }

                                    return false;
                                }
                                ++$success;
                                $sql = '';
                                $quote = 'even';
                                $prev_chr = $chr;
                                continue;
                            }
                        }
                        $sql .= $chr;
                        $prev_chr = $chr;
                    }

                    if (feof($fp) && !empty($sql)) {
                        try {
                            $db->exec(str_replace('table::', $this->prefix, $sql));
                        } catch (PDOException $e) {
                            $this->fault = $e->errorInfo;
                            $this->fault[] = $sql;
                            if ($db->inTransaction()) {
                                $db->rollBack();
                            }

                            return false;
                        }
                        ++$success;
                        $sql = '';
                        $quote = 'even';
                        $prev_chr = $chr;
                        continue;
                    }
                } elseif ($command_type === 'comment') {
                    $sql .= $buffer;
                    if (preg_match('/.+(\r\n|\r|\n)$/s', $sql)) {
                        $sql = null;
                    }
                } elseif ($command_type === 'mysql versioning') {
                    $sql .= $buffer;
                    if (preg_match('/.+\*\/;(\r\n|\r|\n)$/s', $sql)) {
                        $sql = null;
                    }
                } else {
                    $sql = null;
                }
            }
        }

        return $success;
    }

    public function resetAutoIncrement($table)
    {
        return parent::resetAutoIncrement(self::TABLE($table));
    }

    public function lastInsertId($table = null, $col = null)
    {
        return parent::lastInsertId(self::TABLE($table), $col);
    }
}
