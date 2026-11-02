<?php

namespace Ninja;

use tv\tvWebsite;
use DateTime;

class DatabaseTable
{
    protected $pdo;
    protected $table;
    protected $primaryKey;
    protected $className;
    protected $constrArgs = [];

    private function processDates($values)
    {
        foreach ($values as $key => $value) {
            if ($value instanceof DateTime) {
                $values[$key] = $value->format('Y-m-d');
            }
        }
        return $values;
    }

    private function isNumerical($column, $value, $except = 'attr_id')
    {
        //allow $value to be null
        if ($column == $except) {
            return true;
        }
        if (is_array($value)) {
            return false;
        }
        if (pregMatch('/id$/i', $column) && !is_null($value) && !is_numeric($value)) {
            return false;
        }
        return true;
    }
    //or why we prefer mysql
    //
    private function postgresInsert($flag = true)
    {
        
            $tbl = $this->table;
            $res = $this->pdo->query("SELECT pg_get_serial_sequence('$tbl', 'id')", \PDO::FETCH_NUM);
            [$seq] = $res->fetchAll(\PDO::FETCH_COLUMN, 0);
            $res = $this->pdo->query("SELECT last_value FROM $seq", \PDO::FETCH_NUM);
            [$i] = $res->fetchAll(\PDO::FETCH_COLUMN, 0);
            $res = $this->pdo->query("SELECT MAX(id) FROM $tbl", \PDO::FETCH_NUM);
            [$max] = $res->fetchAll(\PDO::FETCH_COLUMN, 0);
            $i = intval($i);
            $max = intval($max);
            $sql = $i <= $max ? "SELECT setval('$seq', $max)" : '';
            if ($sql && $flag) {
                  $res = $this->pdo->query($sql)->fetch();
                  $i = current($res);
                  $i = $i ? intval($i) : $max;
            }
            return $i;
    }

    private function insert(array $values, bool $flag = true)
    {
        //make sure no spaces in table name: `user `
        $seq = false;

        $query = 'INSERT INTO `' . $this->table . '` (';
        foreach ($values as $key => $value) {
            $query .= '`' . $key . '`,';
        }

        $query = rtrim($query, ',');
        $query .= ') VALUES (';

        foreach ($values as $key => $value) {
            $query .= ':' . $key . ',';
        }
        $query = rtrim($query, ',');
        $query .= ')';

        if (DBSYSTEM === 'postgres') {
            $query = preg_replace('/`/', '', $query);
            $seq = true;
        }

        $stmt = $this->pdo->prepare($query);
        if ($seq) {
            //BEFORE you execute
            if ($this->primaryKey === 'id') {
                $i = $this->postgresInsert($flag);
                $stmt->execute($values);
                return $i + 1;
            }
            $stmt->execute($values);
        } else {
            $stmt->execute($values);
            return $this->pdo->lastInsertId();
        }
    }

    public function selectLastInsertId($tbl)
    {
        if (DBSYSTEM === 'postgres') {
            $res = $this->pdo->query("SELECT pg_get_serial_sequence('$tbl', 'id')", \PDO::FETCH_NUM);
            [$seq] = $res->fetchAll(\PDO::FETCH_COLUMN, 0);
            $res = $this->pdo->query("SELECT last_value FROM $seq", \PDO::FETCH_NUM);
            [$i] = $res->fetchAll(\PDO::FETCH_COLUMN, 0);
            return $i;
        }
        return $this->pdo->lastInsertId();
    }

    private function update(array $values)
    {
        $k = $this->primaryKey;
        $postgres = DBSYSTEM === 'postgres';

        $query = 'UPDATE `' . $this->table . '` SET ';

        foreach ($values as $key => $value) {
            if ($postgres && $key === $k) {
                $values['pk'] = $values[$k];
                unset($values[$k]);
                continue;
            }
            $query .= '`' . $key . '` = :' . $key . ', ';
        }
        $query = rtrim($query, ', ');
        $query .= ' WHERE `' . $k . '` = :pk';

        if ($postgres) {
            $query = preg_replace('/`/', '', $query);
        } else {
            $values['pk'] = $values[$k];
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($values);
    }
    public function __construct(tvWebsite $www, string $t, string $pk, string $name = '\stdClass', array $args = [])
    {
        $this->pdo = $www->getPDO();
        $this->table = trim($t);
        $this->primaryKey = strpos($pk, ',') ? null : $pk;
        $this->className = trim($name);
        $this->constrArgs = $args;
    }

    public function delete($field, $v)
    {
        $query = 'DELETE FROM `' . $this->table . '` WHERE `' . $field . '` = :value';
        if (DBSYSTEM === 'postgres') {
            $query = preg_replace('/`/', '', $query);
        }
        $stmt = $this->pdo->prepare($query);
        $values = [
            ':value' => $v
        ];
        $stmt->execute($values);
    }

    protected function orderBy($ob, $query)
    {
        $o = explode('_', $ob);
        $listby = $o[1] ?? ''; //ASC|DESC
        if ($listby) {
            $query .= ' ORDER BY ' . $o[0];
            $query .= " $listby";
        } else {
            $query .= ' ORDER BY ' . $ob;
        }
        return $query;
    }

    public function findAll(?string $ob = null, int $lt = 0, int $ofst = 0, $mode = \PDO::FETCH_CLASS)
    {

        $query = 'SELECT * FROM ' . $this->table;

        if ($ob) {
            $query = $this->orderBy($ob, $query);
        } else {
            $query .= ' ORDER BY ' . $this->primaryKey;
        }

        if ($lt > 0) {
            $query .= ' LIMIT ' . $lt;
        }
        if ($ofst > 0) {
            $query .= ' OFFSET ' . $ofst;
        }

        if (DBSYSTEM === 'postgres') {
            $query = preg_replace('/`/', '', $query);
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        if ($mode === \PDO::FETCH_CLASS) {
            return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $this->className, $this->constrArgs);
        }
        return $stmt->fetchAll($mode);
    }

    public function filterNull($col, $flag = false, $ob = null, int $lt = 0, int $ofst = 0, $mode = 8)
    {
        $nullish = $flag ? ' IS NULL' : ' IS NOT NULL';
        $query = "SELECT * FROM $this->table WHERE $col $nullish";

        if ($ob != null) {
            $query .= ' ORDER BY ' . $ob;
        }

        if ($lt > 0) {
            $query .= ' LIMIT ' . $lt;
        }

        if ($ofst > 0) {
            $query .= ' OFFSET ' . $ofst;
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        if ($mode === \PDO::FETCH_CLASS) {
            return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $this->className, $this->constrArgs);
        }
        return $stmt->fetchAll($mode);
    }

    public function find(string $column, mixed $value, ?string $ob = null, int $lt = 0, int $ofst = 0, $mode = 8, $op = ' = :value')
    {
        if ($this->isNumerical($column, $value)) { //if $column ENDS with id make sure int or null
            $query = 'SELECT * FROM ' . $this->table . ' WHERE ' . $column . $op;
            $parameters = [];
            if (!is_null($value)) {
                $parameters = [
                    'value' => $value
                ];
            }

            if ($ob) {
                $query = $this->orderBy($ob, $query);
            } else {
                $query .= ' ORDER BY ' . $this->primaryKey;
            }

            if ($lt > 0) {
                $query .= ' LIMIT ' . $lt;
            }

            if ($ofst > 0) {
                $query .= ' OFFSET ' . $ofst;
            }
            $stmt = $this->pdo->prepare($query);
            //if(preg_match('/like/i', $op)) dump($parameters);
            $stmt->execute($parameters);
            if ($mode === \PDO::FETCH_CLASS) {
                return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $this->className, $this->constrArgs);
            }
            return $stmt->fetchAll($mode);
        }
        return [];
    }

    public function save(array $record, mixed $arg = true)
    {
        $entity = new $this->className(...$this->constrArgs);
        if (empty($record)) {
            return $entity;
        }
        //force insert?? join tables may not auto_increment so primaryKey is required force insert
        if ($arg && \is_bool($arg)) {
            return $this->insert($record);
        }

        if (empty($record[$this->primaryKey])) {
            unset($record[$this->primaryKey]);
            $insertId = $this->insert($record, $arg);
            $entity->{$this->primaryKey} = $insertId;
        } else {
            $this->update($record);
        }
        /*
        $args = [...$this->constrArgs, isset($insertId) ? 'insert' : 'update'];
        $entity = new $this->className(...$args);

        if(isset($insertId)){
            $entity->{$this->primaryKey} = $insertId;
        }
        */
        foreach ($record as $key => $value) {
         
            if (!empty($value)) {
              //  if($key === 'recdate')  dump(get_class($value));
                if ($value instanceof DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                }
                $entity->$key = $value;
            }
        }
        return $entity;
        //return $entity->complete();
    }
    //give access to functionality without an actual record
    public function getEntity(string $classname = '')
    {
        if ($classname) {
            return new $classname(...$this->constrArgs);
        }
        return new $this->className(...$this->constrArgs);
    }

    public function setEntity($classname)
    {
        $this->className = $classname ?: $this->className;
    }

    public function setConstructorArgs(...$args)
    {
        $this->constrArgs = $args;
    }

    public function setConstructorArgy(...$args)
    {
        $this->constrArgs = [...$this->constrArgs, ...$args];
    }


    public function setMinToNull($colname, $colval)
    {
        $q1 = "UPDATE $this->table INNER JOIN (SELECT min(id) AS target";
        $q2 = " from $this->table where $colname = $colval) AS tmp ON tmp.target = id SET $colname = NULL";
        $stmt = $this->pdo->prepare("$q1$q2");
        $stmt->execute();
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getProp($prop)
    {
        return $this->{$prop};
    }

    public function truncate()
    {
        $query = "TRUNCATE TABLE $this->table";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
    }

    public function setName($name = '')
    {
        $this->table = $name ?: $this->table;
    }

    public function getName()
    {
        return $this->table;
    }

    public function trigger()
    {
        $q1 = "DELIMITER $$ CREATE TRIGGER article_new AFTER INSERT ON polo FOR EACH ROW";
        $q2 = "BEGIN INSERT INTO polo (title) VALUES(NEW.title) END$$ DELIMITER";
        // $stmt = $this->pdo->prepare("$q1$q2");
        //$stmt->execute();
    }

    public function count($agg, $col = 'id', $mode = \PDO::FETCH_CLASS)
    {
        $t = $this->table;
        $query = "SELECT count($col) from $this->table GROUP BY $agg";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll($mode);
    }

    public function orderBySlot($col, $t, $us, $them, $ob = '.id')
    {
        $_us = $this->table . $us;
        $_them = $t . $them;
        $_id = $t . $ob;
        $_col = $this->table . $col;
        $query = "SELECT $_col FROM $this->table INNER JOIN $t ON $_us = $_them ORDER BY $_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
