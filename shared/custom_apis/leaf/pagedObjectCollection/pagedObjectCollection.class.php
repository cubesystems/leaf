<?
class pagedObjectCollection extends pagedList
{
    protected $keyMaps = array();
    protected $listsByField = array();
    protected $objectClass = null;

	/**
	 * get a result page from db according to set page params and load each result as an object
	 *
	 * @param int $pageNo
	 * @return array
	 */

    /**
     * construct an instance, set list params and optionally load page
     *
     * @param string $className
     * @param array $queryParts
     * @param int $itemsPerPage
     * @param int $pageNo
     * @param boolean $forceAutoLoad
     * @return void
     */
	public function __construct($className, $queryParts = null, $itemsPerPage = null, $pageNo = null, $forceAutoLoad = true)
	{
	    $this->objectClass = $className;
	    // get db link
		$instance = getObject( $className, 0, true );
		$this->dbLink = $instance->getDbLink();
		// blah blah
	    parent::__construct($queryParts, $itemsPerPage, $pageNo, $forceAutoLoad);
	}

	protected function getPage( $pageNo )
	{
        $page = $this->getRows( $pageNo );
        foreach ($page as $key => $item)
        {
            $page[$key] = getObject($this->objectClass, $item, true);
        }

        return $page;
	}

	public function searchByProperty($propertyName, $value){
		$key = NULL;
		$this->rewind();
		while($this->valid())
		{
			if($this->current()->$propertyName == $value)
			{
				return $this->key();
			}
			$this->next();
		}
		return $key;
	}
	/*
	* return single object instance
	*/
	public function getSingleInstance()
	{
		if($this->count() == 1)
		{
			return $this->first();
		}
	}

    protected function & getKeyMap($field)
    {
        if (empty($this->keyMaps[$field]))
        {
            $this->createKeyMaps( $field );
        }
        return $this->keyMaps[$field];
    }

    protected function createKeyMaps()
    {
        $keys = func_get_args();
        if (empty($keys))
        {
            return;
        }

        $maps = array();
        reset ($this);
        foreach ($this as & $item)
        {
            foreach ($keys as $key)
            {
                $value = $item->$key;
                $maps[$key][$value] = & $item;
            }
        }
        $this->keyMaps = array_merge ( $this->keyMaps, $maps);
        return true;
    }
    
    protected function & getListByField($field)
    {
        if (empty($this->listsByField[$field]))
        {
            $this->createListsByField( $field );
        }
        return $this->listsByField[$field];
    }    

    protected function createListsByField()
    {
        $fields = func_get_args();
        if (empty($fields))
        {
            return;
        }
        $lists = array();
        reset ($this);
        foreach ($this as & $item)
        {
            foreach ($fields as $field)
            {
                $value = $item->$field;
                $lists[$field][$value][] = $item;
            }
        }
        $this->listsByField = array_merge ( $this->listsByField, $lists);
        return true;
    }
    
    public function & getItemById( $idValue )
    {
        return $this->getItemBy( 'id', $idValue);
    }

    public function & getItemBy( $key, $keyValue)
    {
        $map = $this->getKeyMap( $key );
        if (
            (!$map)
            ||
            (!isset($map[$keyValue]))
        )
        {
            $null = null;
            return $null;
        }

        return $map[$keyValue];
    }

    public function getItemsBy( $field, $value )
    {
        $list = $this->getListByField( $field );
        if (!$list)
        {
            return null;
        }

        if (!isset($list[$value]))
        {
            return array();
        }
        
        return $list[$value];

    }
            
    public function getKeys( $field )
    {
        $map = $this->getKeyMap( $field );
        if (!$map)
        {
            return null;
        }

        $keys = array_keys( $map );
        return $keys;
    }

    public function getIds()
    {
        return $this->getKeys('id');
    }

    public function getFieldValues( $fieldName, $unique )
    {
	    $values = array();
	    foreach ($this as $item)
	    {
	        if (!isset($item->$fieldName))
	        {
	            continue;
	        }
	        $values[] = $item->$fieldName;
	    }
	    reset ($this);
	    if ($unique)
	    {
            $values = array_unique($values);
	    }
	    return $values;
    }

    

}
?>
