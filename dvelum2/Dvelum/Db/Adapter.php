<?php
/**
 *  DVelum project https://github.com/dvelum/dvelum
 *  Copyright (C) 2011-2017  Kirill Yegorov
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Dvelum\Db;

use Zend\Db;
use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\ResultSet\ResultSet;


/**
 * Class Adapter
 * Db adapter proxy
 */
class Adapter
{
    public const EVENT_INIT = 0;
    public const EVENT_CONNECTION_ERROR =1;

    protected $params;
    protected $adapter;
    protected $listeners;

    private $inited = false;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function init()
    {
        if($this->inited){
            return;
        }

        $this->adapter = new \Zend\Db\Adapter\Adapter($this->params);
        $this->inited = true;
        try{
            $this->adapter->getDriver()->getConnection()->connect();
        }catch (\Exception $e){
            $this->fireEvent(self::EVENT_CONNECTION_ERROR, ['message'=>$e->getMessage()]);
            return;
        }
        $this->fireEvent(self::EVENT_INIT);
    }

    /**
     * @return Db\Adapter\Adapter
     */
    public function getAdapter()
    {
        if(!$this->inited){
            $this->init();
        }
        return $this->adapter;
    }

    /**
     * @return Select
     */
    public function select()
    {
        $select = new Select();
        $select->setDbAdapter($this);
        return $select;
    }

    /**
     * @return Sql
     */
    public function sql() : Sql
    {
        if(!$this->inited){
            $this->init();
        }
        return  new Sql($this->adapter);
    }

    /**
     * Get Query profiler
     * @return Db\Adapter\Profiler\ProfilerInterface|null
     */
    public function getProfiler(): ?Db\Adapter\Profiler\ProfilerInterface
    {
        if(!$this->inited){
            return null;
        }
        return $this->adapter->getProfiler();
    }

    /**
     * Fetch results
     * @param string $sql
     * @return array
     */
    public function fetchAll($sql) : array
    {
        if(!$this->inited){
            $this->init();
        }

        $statement = $this->adapter->createStatement();
        $statement->setSQl($sql);
        $statement->prepare();

        $result = $statement->execute();
        if ($result instanceof ResultInterface && $result->isQueryResult())
        {
            /*
             *  Mysqli performance patch
             */
            if($this->params['adapter'] === 'Mysqli') {
                /**
                 * @var \mysqli_stmt $resource
                 */
                $resource = $result->getResource();
                /**
                 * @var \mysqli_result $result
                 */
                $result = $resource->get_result();
                if($result){
                    $result = $result->fetch_all(MYSQLI_ASSOC);
                    if(!empty($result)){
                        return $result;
                    }
                }
                return [];
            }else{
                $resultSet = new ResultSet(ResultSet::TYPE_ARRAY);
                return $resultSet->initialize($result)->toArray();
            }
        }
        return [];
    }

    public function fetchCol($sql)
    {
        if(!$this->inited){
            $this->init();
        }

        $statement = $this->adapter->createStatement();
        $statement->setSQl($sql);
        $statement->prepare();
        $result = $statement->execute();

        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            /*
             *  Mysqli performance patch
             */
            if($this->params['adapter'] === 'Mysqli') {
                /**
                 * @var \mysqli_stmt $resource
                 */
                $resource = $result->getResource();
                /**
                 * @var \mysqli_result $result
                 */
                $result = $resource->get_result();
                if($result){
                    $result = $result->fetch_all(MYSQLI_NUM);
                    if(!empty($result)){
                        $data = [];
                        foreach ($result as $item){
                            $data[] = $item[0];
                        }
                        return $data;
                    }
                }
                return [];
            }else {
                $resultSet = new ResultSet(ResultSet::TYPE_ARRAY);
                $resultSet->initialize($result);
                $result = [];
                foreach ($resultSet as $item) {
                    foreach ($item as $v) {
                        $result[] = $v;
                        break;
                    }
                }
                return $result;
            }
        }
        return [];
    }

    /**
     * @param mixed $sql
     * @return mixed
     */
    public function fetchOne($sql)
    {
        if(!$this->inited){
            $this->init();
        }

        $statement = $this->adapter->createStatement();
        $statement->setSQl($sql);
        $statement->prepare();

        $result = $statement->execute();

        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet = new ResultSet(ResultSet::TYPE_ARRAY);
            $resultSet->initialize($result);
            $result = $resultSet->current();
            if(!empty($result)){
                return  array_values($result)[0];
            }
        }
        return null;
    }

    public function query($sql)
    {
        if(!$this->inited){
            $this->init();
        }
        $this->adapter->query($sql, Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    }

    /**
     * Fetch row from result set
     * @param mixed $sql
     * @return array
     */
    public function fetchRow($sql) : ?array
    {
        if(!$this->inited){
            $this->init();
        }

        $statement = $this->adapter->createStatement();
        $statement->setSQl($sql);
        $statement->prepare();

        $result = $statement->execute();
        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet = new ResultSet(ResultSet::TYPE_ARRAY);
            $resultSet->initialize($result);
            $resultData = $resultSet->current();
            if(empty($resultData)){
                $resultData = [];
            }
            return $resultData;
        }
        return [];
    }


    public function quoteIdentifier($string)
    {
        if(!$this->inited){
            $this->init();
        }

        if($this->adapter->getPlatform()->getName() === 'MySQL'){
            return '`'.str_replace(['`','.'], ['','`.`'], $string).'`';
        }else{
            return $this->adapter->getPlatform()->quoteIdentifier($string);
        }
    }

    public function quote($value)
    {
        if(!$this->inited){
            $this->init();
        }

        return $this->adapter->getPlatform()->quoteValue($value);
    }

    /**
     * @return array
     */
    public function getConfig() : array
    {
        return $this->params;
    }

    /**
     * Get list of DB tables names
     * @return string[]
     */
    public function listTables()
    {
        if(!$this->inited){
            $this->init();
        }

        $metadata = new Db\Metadata\Metadata($this->adapter);
        return $metadata->getTableNames();
    }

    /**
     * Get metadata object for current DB connection
     * @return Metadata
     */
    public function getMeta() : Metadata
    {
        if(!$this->inited){
            $this->init();
        }
        return new Metadata($this->adapter);
    }

    public function beginTransaction()
    {
        if(!$this->inited){
            $this->init();
        }
        $this->adapter->getDriver()->getConnection()->beginTransaction();
    }

    public function rollback()
    {
        if(!$this->inited){
            $this->init();
        }
        $this->adapter->getDriver()->getConnection()->rollback();
    }

    public function commit()
    {
        if(!$this->inited){
            $this->init();
        }
        $this->adapter->getDriver()->getConnection()->commit();
    }

    /**
     * Fix for mysqli driver
     * convert bool into integer
     * @param array $values
     * @return array
     */
    protected function convertBooleanValues(array $values) : array
    {
        foreach ($values as &$value){
            if(is_bool($value)){
                $value = intval($value);
            }
        }
        return $values;
    }

    public function insert($table , $values)
    {
        if(!empty($values) && $this->params['adapter'] === 'Mysqli'){
            $values = $this->convertBooleanValues($values);
        }

        $sql = $this->sql();
        $insert = $sql->insert($table);
        $insert->values($values);

        $statement = $sql->prepareStatementForSqlObject($insert);
        $statement->execute();
    }

    public function delete($table, $where = null)
    {
        $sql = $this->sql();
        $delete = $sql->delete($table);

        if(!empty($where)){
            $delete->where($where);
        }

        $statement = $sql->prepareStatementForSqlObject($delete);
        $statement->execute();
    }

    public function update($table, $values, $where = null )
    {
        if(!empty($values) && $this->params['adapter'] === 'Mysqli'){
            $values = $this->convertBooleanValues($values);
        }

        $sql = $this->sql();
        $update = $sql->update($table);
        $update->set($values);

        if(!empty($where)){
            $update->where($where);
        }

        $statement = $sql->prepareStatementForSqlObject($update);
        $statement->execute();
    }

    public function lastInsertId($tableName = null, $primaryKey = null)
    {
        if(!$this->inited){
            $this->init();
        }
        return $this->adapter->getDriver()->getLastGeneratedValue();
    }

    /**
     * @param array $values
     * @return string
     */
    public function quoteValueList(array $values) : string
    {
        if(!$this->inited){
            $this->init();
        }
        return $this->adapter->getPlatform()->quoteValueList($values);
    }

    /**
     * Add listener
     * @param int $eventCode
     * @param callable $listener
     */
    public function on(int $eventCode, callable $listener) : void
    {
        if(!isset($this->listeners[$eventCode])){
            $this->listeners[$eventCode] = [];
        }
        $this->listeners[$eventCode][] = $listener;
    }

    /**
     * @param int $eventCode
     * @param array|null $data
     */
    protected function fireEvent(int $eventCode , ?array $data = []) : void
    {
        if(isset($this->listeners[$eventCode])){
            foreach ($this->listeners[$eventCode] as $listener){
                /**
                 * @var callable $listener
                 */
                $listener(new Adapter\Event($eventCode , $data));
            }
        }
    }
}