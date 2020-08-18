<?
class objectCollection extends ArrayIterator{
	protected $collectionTotal = 0;
	
	public function getCollectionTotal(){
		return $this->collectionTotal;
	}
	
	public function __construct($queryOrPartsOrList, $className = NULL, $params = array()){
		if(is_null($className))
		{
			$list = $queryOrPartsOrList;
		}
		else
		{
			if(is_array($queryOrPartsOrList))
			{
				$queryOrPartsOrList = leafBaseObject::buildQuery($queryOrPartsOrList);
			}
			$list = array();
			$i = 1;
			$a = 0;
			if(!isset($params['limitStart']))
			{
				$params['limitStart'] = 0;
			}
			if(!isset($params['limitCount']))
			{
				$params['limitCount'] = 999999999;
			}
			if(!empty($params['keyField']))
			{
				$keyField = $params['keyField'];
			}
			else
			{
				$keyField = NULL;
			}
			$r = dbQuery($queryOrPartsOrList);
			$this->collectionTotal = $r->rowCount();
			while($itemData = $r->fetch())
			{
				if($i > $params['limitStart'])
				{
					if($a < $params['limitCount'])
					{
						$object = getObject($className, $itemData);
						if(!is_null($object))
						{
							if($keyField)
							{
								$list[$itemData[$keyField]] = $object;
							}
							else
							{
								$list[] = $object;
							}
							++$a;
						}
					}
					else
					{
						break;
					}
				}
				++$i;
			}
		}
	 	parent::__construct($list);
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
	public function getSingleInstance(){
		if($this->count() == 1)
		{
			$this->rewind();
			return $this->current();
		}
	}
}
?>