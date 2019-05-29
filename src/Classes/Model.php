<?php /** @noinspection SqlNoDataSourceInspection */

namespace ShootingStar;

use Log;
use PDO;
use PDOException;
use PDOStatement;

define('DB_MYSQL', 'mysql');
define('DB_SQLITE', 'sqlite');
define('SQL_DATETIME_FORMAT', 'Y-m-d H:i:s');
define('SQL_DATE_FORMAT', 'Y-m-d');

/**
 * Class Model
 *
 * @property PDO $db
 * @property PDO $slaveDB
 * @property PDOStatement $statement
 */
class Model extends Base
{
    public $table = null;
    public $alias = null;
    public $hasNoDateFields = false;
    public $engine = null;
    public $host = null;
    public $hostSlave = null;
    public $user = null;
    public $password = null;
    public $database = null;
    public $charset = null;
    public $db = null;
    public $slaveDB = null;
    public $lastError;
    public $config = 'default';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $sql
     * @param bool $forceMaster
     * @return bool|PDO
     */
    public function getConnection($sql, $forceMaster = false)
    {

        try {
            if (!$this->db) {
                $values = Config::get('database.' . $this->config);
                extract($values);
                /** @noinspection PhpUndefinedVariableInspection */
                $this->engine = isset($engine) ? $engine : 'mysql';
                /** @noinspection PhpUndefinedVariableInspection */
                $this->host = $host;
                /** @noinspection PhpUndefinedVariableInspection */
                $this->hostSlave = $host_slave;
                /** @noinspection PhpUndefinedVariableInspection */
                $this->user = $user;
                /** @noinspection PhpUndefinedVariableInspection */
                $this->password = $password;
                /** @noinspection PhpUndefinedVariableInspection */
                $this->database = isset($database) ? $database : 'localhost';
                /** @noinspection PhpUndefinedVariableInspection */
                $this->charset = isset($charset) ? $charset : 'utf8mb4';
                switch ($this->engine) {
                    case DB_MYSQL:
                        $this->db = new PDO("mysql:host={$this->host};dbname={$this->database};charset={$this->charset}", $this->user, $this->password, [PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                        break;
                    case DB_SQLITE:
                        $databasePath = SHARE_DIR . '/' . $host;
                        if (!file_exists($databasePath)) {
                            mkdir($databasePath);
                            chmod($databasePath, 0777);
                        }
                        $this->db = new PDO("sqlite:{$databasePath}/{$this->database}.db", $this->user, $this->password, [PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                        break;
                }
            }
            if ($forceMaster) {
                return $this->db;
            } else {
                $slaveCommands = [
                    'select',
                ];
                $lowerSql = strtolower($sql);
                foreach ($slaveCommands as $slaveCommand) {
                    if (strpos($lowerSql, $slaveCommand) === 0) {
                        if (!$this->slaveDB) {
                            $this->slaveDB = new PDO("mysql:host={$this->hostSlave};dbname={$this->database};charset={$this->charset}", $this->user, $this->password, [PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                        }
                        return $this->slaveDB;
                    }
                }
                return $this->db;
            }
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    private function prepareSql(&$sql, &$params)
    {
        if (preg_match_all('/:([a-zA-Z0-9_]+)/', $sql, $matches)) {
            $index = 1;
            foreach ($matches[1] as $field) {
                $params[$index] = $params[$field];
                $count = 1;
                $sql = str_replace(':' . $field, '?', $sql, $count);
                $index++;
            }
        }
    }

    /**
     * @param $statement PDOStatement
     * @param $params
     */
    private function prepareParams($statement, $params)
    {
        foreach ($params as $key => $value) {
            if (is_numeric($key)) {
                $statement->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
    }

    public function query($sql, $params = [], $forceMaster = false)
    {
        try {
            $db = $this->getConnection($forceMaster);
            if (!$db) {
                return false;
            }
            $this->prepareSql($sql, $params);
            $statement = $db->prepare($sql);
            $this->prepareParams($statement, $params);
            $statement->execute();
            if (Config::get('debug')) {
                Log::out($sql, 'sql.log');
                Log::out(print_r($params, true), 'sql.log');
            }
            $result = $statement->fetchAll();
            $statement->closeCursor();
            return $result;
        } catch (PDOException $e) {
            if (Config::get('debug')) {
                echo $e->getMessage();
            }
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function execute($sql, $params = [], $forceMaster = false)
    {
        try {
            $db = $this->getConnection($sql, $forceMaster);
            if (!$db) {
                return false;
            }
            $this->prepareSql($sql, $params);
            $statement = $db->prepare($sql);
            $this->prepareParams($statement, $params);
            $statement->execute();
            if (Config::get('debug')) {
                Log::out($sql, 'sql.log');
                Log::out(print_r($params, true), 'sql.log');
            }
            return $statement;
        } catch (PDOException $e) {
            if (Config::get('debug')) {
                echo $e->getMessage();
            }
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * @param $statement PDOStatement
     * @return bool
     */
    public function fetch($statement)
    {
        try {
            $result = $statement->fetch();
            if (empty($result)) {
                $this->close($statement);
            }
            return $result;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * @param $statement PDOStatement
     */
    public function close($statement)
    {
        if ($statement) {
            $statement->closeCursor();
        }
    }

    public function createFields($fields)
    {
        if (is_array($fields)) {
            return implode(',', $fields);
        } else {
            if ($fields) {
                return $fields;
            } else {
                return '*';
            }
        }
    }

    public function createSelectSql($fields = null, $tail = '')
    {
        if ($fields === null) {
            $fields = '*';
        }
        $fieldSql = $this->createFields($fields);
        $sql = "SELECT {$fieldSql} FROM {$this->table}";
        if ($this->alias) {
            $sql .= ' ' . $this->alias;
        }
        if ($tail) {
            $notWheres = ['order', 'limit', 'inner', 'left', 'join', 'right', 'as'];
            $lowerTail = strtolower($tail);
            foreach ($notWheres as $value) {
                if (strpos($lowerTail, $value) === 0) {
                    return $sql . ' ' . $tail;
                }
            }
            return $sql . ' WHERE ' . $tail;
        } else {
            return $sql;
        }
    }

    public function select($fields = '*', $tail = '', $params = [], $forceMaster = false)
    {
        return $this->execute($this->createSelectSql($fields, $tail), $params, $forceMaster);
    }

    public function selectRow($fields = '*', $tail = '', $params = [], $forceMaster = false)
    {
        $statement = $this->execute($this->createSelectSql($fields, $tail), $params, $forceMaster);
        if ($statement) {
            $result = $this->fetch($statement);
            $statement->closeCursor();
            return $result;
        } else {
            return false;
        }
    }

    public function selectOne($fields = null, $tail = '', $params = [], $forceMaster = false)
    {
        $statement = $this->execute($this->createSelectSql($fields, $tail), $params, $forceMaster);
        if (!$statement) {
            return false;
        }
        $result = $statement->fetchColumn();
        $statement->closeCursor();
        return $result;
    }

    public function selectAll($fields = null, $tail = '', $params = [], $forceMaster = false)
    {
        return $this->query($this->createSelectSql($fields, $tail), $params, $forceMaster);
    }

    public function createSets($data)
    {
        $pares = [];
        foreach ($data as $key => $value) {
            if (strpos($key, '_') === 0) {
                continue;
            }
            $pares[] = "{$key}=:{$key}";
        }
        return implode(',', $pares);
    }

    public function update($data, $where = '')
    {
        $now = $this->formattedDatetime();
        if (!$this->hasNoDateFields && !isset($data['modified'])) {
            $data['modified'] = $now;
        }
        $sql = "UPDATE {$this->table} SET " . $this->createSets($data);
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        return $this->query($sql, $data) !== false;
    }

    public function formattedDatetime($time = 0)
    {
        if ($time == 0) {
            $time = time();
        }
        return date(SQL_DATETIME_FORMAT, $time);
    }

    public function insert($data)
    {
        $now = $this->formattedDatetime();
        if (!$this->hasNoDateFields) {
            if (!isset($data['modified'])) {
                $data['modified'] = $now;
            }
            $data['created'] = $now;
        }
        if ($this->engine == DB_SQLITE) {
            $sets = $this->createSets($data);
            if (preg_match_all('/([^=,]+)=(:[^=,]+)/', $sets, $matches, PREG_PATTERN_ORDER)) {
                $sql = "INSERT INTO {$this->table}(" . implode(',', $matches[1]) . ') VALUES(' . implode(',', $matches[2]) . ')';
            } else {
                return false;
            }
        } else {
            $sql = "INSERT INTO {$this->table} SET " . $this->createSets($data);
        }
        return $this->query($sql, $data) !== false;
    }

    public function updateOrInsert($data, $where = '')
    {
        $result = $this->selectOne('COUNT(*)', $where, $data);
        if ($result) {
            return $this->update($data, $where);
        } else {
            return $this->insert($data);
        }
    }

    public function remove($params = [], $where = '')
    {
        return $this->update(array_merge($params, ['deleted' => $this->formattedDatetime()]), $where);
    }

    public function delete($params = [], $where = '')
    {
        $sql = "DELETE FROM {$this->table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        return $this->query($sql, $params);
    }

    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }

    public function beginTransaction()
    {
        try {
            return $this->db->beginTransaction();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function endTransaction($isSuccess = true)
    {
        if ($isSuccess) {
            try {
                $this->db->commit();
                return true;
            } catch (PDOException $e) {
                $this->lastError = $e->getMessage();
                return false;
            }
        } else {
            try {
                $this->db->rollBack();
                return true;
            } catch (PDOException $e) {
                $this->lastError = $e->getMessage();
                return false;
            }
        }
    }

    public function disconnect()
    {
        $this->db = null;
        $this->slaveDB = null;
    }
}
