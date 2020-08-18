<?php
class contentNodeSnapshot extends leafBaseObject
{
	const tableName = 'contentNodeSnapshots';
	
	protected
        $id,
        $objectId,
        $createdAt,
        $current,
        $data
    ;
    
    protected $objectData = null;  // unserialized format
    
    protected static $config = array();
    
	protected $fieldsDefinition = array 
    (
		 'objectId' => array 
         (
            'not_empty' => true,
            'type' => 'id'
		 ),
        
         'createdAt' => array
         (   
            'empty_to_null' => true,
         ),
        
         'current' => array
         (
             'empty_to_null' => true,
             'zero_to_null'  => true
         ),
        
         'data' => array 
         (
            'empty_to_null' => true,
		 ),
	);

	public static function _autoload($className)
	{
		parent :: _autoload($className);
		
		$_tableDefsStr = array (
			self :: tableName => array (
				'fields' => '
					id int auto_increment
                    objectId int
                    createdAt datetime
                    current tinyint
                    data longtext
				',
				'indexes' => '
					primary id
                    index objectId
                    index objectTime objectId,createdAt
				',
				'engine' => 'InnoDB',
			),
		);

		dbRegisterRawTableDefs($_tableDefsStr);
	}
	
	// modes
	protected $currentMode = 'default';
	protected $modes = array (
		'default' => array 
        (
            
		),
        
        'factory' => array
        (
            'objectId',
            'createdAt',
            'data'
        ), 
        
        'current' => array
        (
            'current',
        )    
	);
    
	public static function getCollection($params = array (), $itemsPerPage = null, $page = null)
	{
		$queryParts = self :: getQueryParts($params);
		return new pagedObjectCollection(__CLASS__, $queryParts, $itemsPerPage, $page);
	}
		
	public static function getQueryParts($params = array ())
	{
        $queryParts = array
        (
            'select'   => array('t.*'),
            'from'     => '`' . self::getClassTable(__CLASS__) . '` AS `t`',
        );
        

        if (!empty($params['latestFirst']))
        {
            $queryParts['orderBy'][] = 'createdAt DESC';
            $queryParts['orderBy'][] = 'id DESC';
        }

        $queryParts['orderBy'][] = 'id ASC';
        

        if (array_key_exists('objectId', $params))
        {
            if (!ispositiveint($params['objectId']))
            {
                $queryParts['where'][] = 'false';
            }
            else
            {
                $queryParts['where'][] = 't.objectId = ' . $params['objectId'];
            }
        }   

        if (array_key_exists('id', $params))
        {
            if (!ispositiveint($params['id']))
            {
                $queryParts['where'][] = 'false';
            }
            else
            {
                $queryParts['where'][] = 't.id = ' . $params['id'];
            }
        }          
        
        
        if (!empty($params['exceptId']) && ispositiveint($params['exceptId']))
        {
            $queryParts['where'][] = 't.id != ' . $params['exceptId'];
        }
        
        
        if (array_key_exists('current', $params))
        {
            $queryParts['where'][] = ($params['current']) ? 't.current' : 't.current is null';
        }   
        
        $queryParts['groupBy'] = 't.id';        
        
        // debug (dbbuildquery($queryParts), 0);
        // debug (dbgetall($queryParts));

		return $queryParts;
	}

    public function variablesSave($variables, $fieldsDefinition = NULL, $mode = false)
	{
        $newObject = empty($this->id);

        $result = parent::variablesSave($variables, $fieldsDefinition, $mode);

        return $result;
    }
    
    public function markAsCurrent()
    {
        if (!ispositiveint($this->objectId))
        {
            return false;
        }
        
        // get ids of all other snapshots for this object
        // and mark as not current
        
        $params = array
        (
            'objectId' => $this->objectId,
            'exceptId' => $this->id
        );
        
        $snapshotIds = self::getIds($params);
        
        if (!empty($snapshotIds))
        {
            $q = '
                UPDATE `' . self::getClassTable(__CLASS__) . '` 
                    SET 
                    `current` = null
                WHERE
                    id IN (' . implode(',', $snapshotIds) . ')
            ';
            dbquery($q);
        }
        
        $this->variablesSave( array('current' => 1), null, 'current');
        return;
    }
    
    public function isCurrent()
    {
        return !empty($this->current);
    }

    
    public function getObjectData()
    {
        if (is_null($this->objectData))
        {
            $this->objectData = unserialize( $this->data );
        }
        return $this->objectData;
    }
    
        
}

