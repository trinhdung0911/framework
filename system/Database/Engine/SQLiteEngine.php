<?php


namespace Smvc\Database\Engine;


class SQLiteEngine extends \PDO implements Engine
{

    private $config;

    /**
     * SQLiteEngine constructor.
     * Please use the Factory to maintain instances of the drivers.
     *
     * @param $config array
     * @throws \PDOException
     */
    public function __construct($config) {
        $this->config = $config;

        parent::__construct("sqlite:" . $config['file']);
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Get the name of the driver
     * @return string
     */
    public function getDriverName()
    {
        return "SQlite Driver";
    }

    /**
     * Get configuration for instance
     * @return array
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * Get native connection. Could be \PDO
     * @return mixed|\PDO
     */
    public function getConnection()
    {
        return $this;
    }


    /**
     * Select from the database
     *
     * @param  string      $sql       sql query, leave out the SELECT word
     * @param  array       $array     named params
     * @param  int         $fetchMode Fetch mode (use \PDO::FETCH_*)
     * @param  string|null $class     class name for using with \PDO::FETCH_CLASS
     * @return array                  returns an array of records
     */
    public function select($sql, $array = array(), $fetchMode = \PDO::FETCH_OBJ, $class = null)
    {
        if (strtolower(substr($sql, 0, 7)) !== 'select ') {
            $sql = "SELECT " . $sql;
        }
        $stmt = $this->prepare($sql);
        foreach ($array as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue("$key", $value, \PDO::PARAM_INT);
            } else {
                $stmt->bindValue("$key", $value);
            }
        }

        $stmt->execute();

        $fetched = array();
        if ($fetchMode === \PDO::FETCH_CLASS) {
            $fetched = $stmt->fetchAll($fetchMode, $class);
        } else {
            $fetched = $stmt->fetchAll($fetchMode);
        }


        if (is_array($fetched) && count($fetched) > 0) {
            return $fetched;
        }
        return false;
    }

    /**
     * Insert data in table
     * @param  string $table table name
     * @param  array $data  array of columns and values
     * @return int|false inserted id or false on failure
     */
    public function insert($table, $data)
    {
        ksort($data);

        $fieldNames = implode(',', array_keys($data));
        $fieldValues = ':'.implode(', :', array_keys($data));

        $stmt = $this->prepare("INSERT INTO $table ($fieldNames) VALUES ($fieldValues)");

        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        if (!$stmt->execute()) {
            return false;
        }
        return $this->lastInsertId();
    }

    /**
     * Update data in table
     * @param  string $table table name
     * @param  array $data  array of columns and values
     * @param  array $where array of columns and values
     * @return int|false Row count or false on failure
     */
    public function update($table, $data, $where)
    {
        ksort($data);

        $fieldDetails = null;
        foreach ($data as $key => $value) {
            $fieldDetails .= "$key = :field_$key,";
        }
        $fieldDetails = rtrim($fieldDetails, ',');

        $whereDetails = null;
        $i = 0;
        foreach ($where as $key => $value) {
            if ($i == 0) {
                $whereDetails .= "$key = :where_$key";
            } else {
                $whereDetails .= " AND $key = :where_$key";
            }
            $i++;
        }
        $whereDetails = ltrim($whereDetails, ' AND ');

        $stmt = $this->prepare("UPDATE $table SET $fieldDetails WHERE $whereDetails");

        foreach ($data as $key => $value) {
            $stmt->bindValue(":field_$key", $value);
        }

        foreach ($where as $key => $value) {
            $stmt->bindValue(":where_$key", $value);
        }

        if (!$stmt->execute()) {
            return false;
        }
        return $stmt->rowCount();
    }

    /**
     * Delete method
     *
     * @param  string $table table name
     * @param  array $where array of columns and values
     * @return int|false Row count or false on failure
     */
    public function delete($table, $where)
    {
        ksort($where);

        $whereDetails = null;
        $i = 0;
        foreach ($where as $key => $value) {
            if ($i == 0) {
                $whereDetails .= "$key = :$key";
            } else {
                $whereDetails .= " AND $key = :$key";
            }
            $i++;
        }
        $whereDetails = ltrim($whereDetails, ' AND ');

        $stmt = $this->prepare("DELETE FROM $table WHERE $whereDetails");

        foreach ($where as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        if (!$stmt->execute()) {
            return false;
        }
        return $stmt->rowCount();
    }
}