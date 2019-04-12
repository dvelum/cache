<?php

use Dvelum\Orm;
use Dvelum\Orm\Model;

/**
 * History logger
 * @author Kirill Egorov 2011
 */
class Model_Historylog extends Model
{
    /**
     * Action types
     * @var array
     */
    static public $actions = array(
        1 => 'Delete',
        2 => 'Create',
        3 => 'Update',
        4 => 'Publish',
        5 => 'Sort',
        6 => 'Unpublish',
        7 => 'New Version'
    );

    const Delete = 1;
    const Create = 2;
    const Update = 3;
    const Publish = 4;
    const Sort = 5;
    const Unpublish = 6;
    const NewVersion = 7;

    /**
     * Log action. Fill history table
     * @param integer $user_id
     * @param integer $record_id
     * @param integer $type
     * @param string $object
     * @throws Exception
     * @return boolean
     */
    public function log($user_id, $record_id, $type, $object)
    {
        if (!is_integer($type))
            throw new Exception('History::log Invalid type');

        $obj = Orm\Record::factory($this->name);
        $obj->setValues(array(
            'user_id' => intval($user_id),
            'record_id' => intval($record_id),
            'type' => intval($type),
            'date' => date('Y-m-d H:i:s'),
            'object' => $object
        ));
        return $obj->save(false);
    }

    /**
     * Get log for the  data item
     * @param string $table_name
     * @param integer $record_id
     * @param integer $start - optional
     * @param integer $limit - optional
     * @return array
     */
    public function getLog($table_name, $record_id, $start = 0, $limit = 25)
    {

        $sql = $this->getSlaveDbConnection()->select()
            ->from(array('l' => $this->table()), ['type', 'date'])
            ->where('l.table_name = ?', $table_name)
            ->where('l.record_id = ?', $record_id)
            ->joinLeft(array('u' => Model::factory('User')->table()),
                ' l.user_id = u.id',
                array('user' => 'u.name)')
            )
            ->order('l.date DESC')
            ->limit($limit, $start);

        $data = $this->getSlaveDbConnection()->fetchAll($sql);

        if (!empty($data)) {
            foreach ($data as &$v) {
                if (isset(self::$actions[$v['type']])) {
                    $v['type'] = self::$actions[$v['type']];
                } else {
                    $v['type'] = 'unknown';
                }
            }
            return $data;
        } else {
            return [];
        }
    }

    /**
     * (non-PHPdoc)
     * @see Model::_queryAddAuthor()
     */
    protected function _queryAddAuthor($sql, $fieldAlias): void
    {
        $sql->joinLeft(
            array('u1' => Model::factory('User')->table()),
            'user_id = u1.id',
            array($fieldAlias => 'u1.name')
        );
    }

    /**
     * Save object state
     * @param integer $operation
     * @param string $objectName
     * @param integer $objectId
     * @param integer $userId
     * @param string $date
     * @param string $before
     * @param string $after
     * @return integer | false
     */
    public function saveState($operation, $objectName, $objectId, $userId, $date, $before = null, $after = null)
    {
        // проверяем, существует ли такой тип объектов
        if (!Orm\Record\Config::configExists($objectName)) {
            $this->logError('Invalid object name "' . $objectName . '"');
            return false;
        }

        try {
            $o = Orm\Record::factory('Historylog');
            $o->setValues(array(
                'type' => $operation,
                'object' => $objectName,
                'record_id' => $objectId,
                'user_id' => $userId,
                'date' => $date,
                'before' => $before,
                'after' => $after
            ));

            $id = $o->save(false);
            if (!$id)
                throw new Exception('Cannot save object state ' . $objectName . '::' . $objectId);

            return $id;
        } catch (Exception $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }
}