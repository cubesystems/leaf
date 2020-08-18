<?
/**
 * A class for fetching objects from the object tree for (optionally) paged output.
 *
 *
 */
class pagedObjectList extends pagedList
{
    protected $keyMaps = array();

	/**
	 * get a result page from db according to set page params and load each result as an object
	 *
	 * @param int $pageNo
	 * @return array
	 */
	protected function getPage( $pageNo )
	{
		// try to retrieve data from cache
		$cacheOn = leaf_get('objects_config', 'cache');
		$cacheId = $this->getRowsQuery($pageNo);
		if($cacheOn !== FALSE && $page = pagedObjectList::getCache($cacheId))
		{
			return $page;
		}
		// get from db
        $page = $this->getRows( $pageNo );
        foreach ($page as $key => $item)
        {
            $page[$key] = _core_load_object( $item );
        }
		// store data to cache
		if($cacheOn !== FALSE)
		{
			pagedObjectList::setCache($cacheId, $page);
		}
        return $page;
	}

	public static function getCache($id){
		$lastUpdate = getValue('content_objects.last_update');
		if(!$lastUpdate)
		{
			return null;
		}
		/*
		$hash = CACHE_PATH . md5(serialize(func_get_args())) . '.cache';
		if(file_exists($hash))
		{
			$data = file_get_contents($hash);
			return unserialize($data);
		}
		return;
		*/
		$q = '
		SELECT
			`data`
		FROM
			`objects_cache`
		WHERE
			`id` = "' . md5($id) . '" AND
			UNIX_TIMESTAMP(`timestamp`) >= "' . $lastUpdate . '"
		';
		if($list = dbGetOne($q))
		{
			return unserialize($list);
		}
		else
		{
			return null;
		}
	}

	public static function setCache($id, $data){
		/*
		$hash = CACHE_PATH . md5(serialize($hashSource)) . '.cache';
		file_put_contents($hash, serialize($data));
		return;
		*/
		$fields = array(
			'id' => md5($id),
			'data' => serialize($data),
			'timestamp' => 'NOW()',
		);
		dbReplace('objects_cache', $fields, NULL, array('timestamp'));
	}

	public function getObjectIds()
	{
	    $map = $this->getIdMap();
	    if (is_null($map))
	    {
	        return null;
	    }
	    $ids = array_keys($map);
        return $ids;
	}

    public function & getItemById( $idValue )
    {
        $map = $this->getIdMap();
        if (
            (!$map)
            ||
            (!isset($map[$idValue]))
        )
        {
            $null = null;
            return $null;
        }

        return $map[$idValue];
    }

    protected function & getIdMap()
    {
        if (empty($this->keyMaps['id']))
        {
            $this->createKeyMaps('id');
        }
        return $this->keyMaps['id'];
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
                $value = $item->object_data[$key];
                $maps[$key][$value] = & $item;
            }
        }
        $this->keyMaps = array_merge ( $this->keyMaps, $maps);
        return true;
    }

    public function getFieldValues( $fieldName, $idsAsKeys = false )
    {
	    $values = array();
	    foreach ($this as $item)
	    {
	        if (!isset($item->object_data['data'][$fieldName]))
	        {
	            continue;
	        }
	        $value = $item->object_data['data'][$fieldName];
	        if ($idsAsKeys)
	        {
	            $values[$item->object_data['id']] = $value;
	        }
	        else
	        {
	           $values[] = $value;
	        }
	    }
	    return $values;
    }

}
?>
