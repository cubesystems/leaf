<?php
class leafLanguage extends leafBaseObject
{
	const tableName = 'languages';
	
	protected
		$short, $code;
	
	protected $fieldsDefinition = array
	(
		'code'  => array( 'not_empty' => true ),        
		'short' => array( 'not_empty' => true ),
	);
	
    protected $postChecks = array
    (
        'uniqueness' => array
        (
            'onlyIfValid' => true, 
            'function' => array('$this', 'uniquenessValidator')
        )
    );
    
	/********************* constructors *********************/
	
	public static function _autoload( $className )
    {
		parent::_autoload( $className );
        
		$_tableDefsStr = array
		(
	        self::tableName => array
	        (
	            'fields' =>
	            '
	                id    int auto_increment
					short varchar(5)
					code  varchar(12)
	            '
	            ,
	            'indexes' => '
	                primary id
	                unique code
                ',
                'engine' => 'InnoDB',
	        ),
	    );
	    
	    dbRegisterRawTableDefs( $_tableDefsStr );
    }
	
	/********************* get methods *********************/
	
	public function getDisplayString()
	{
		return $this->short;
	}

	/********************* static methods *********************/
	
	// get
	
	public static function getCurrentCode()
    {
        return leaf_get('properties', 'language_code');
    }
	
	// boolean
	
	public static function exists( $languageCode )
	{
		foreach( static::getLanguages() as $language )
		{
			if( $language->code == $languageCode )
			{
				return true;
			}
		}
		return false;
	}
	
	/********************* collection related methods *********************/
	
	public static function getByCode( $code )
	{
		$queryParts['select'][] = 't.*';
		$queryParts['from'][]   =  '`' . self::getClassTable( __CLASS__ ) . '` AS `t`';
		$queryParts['where'][]  =  't.code = "' . dbSE($code) . '"';
		$data = dbGetRow($queryParts);
		if($data)
		{
			$language = new leafLanguage($data);
			return $language;
		}
	}
	
	public static function getByName( $name, $collection = NULL )
	{
		if( $collection === NULL )
		{
			$collection = self::getCollection();
		}
		foreach( $collection as $item )
		{
			if( $item->short == $name )
			{
				return $item;
			}
		}
		return NULL;
    }

    public function __toString()
    {
        return $this->code;
    }

	public static function getLanguages( $params = array() )
	{
        $cacheKey = 'languages' . ((!empty($params)) ? '-' . sha1(serialize($params)) : '');
		if (!static::hasInStaticCache( $cacheKey ) )
		{
			static::storeInStaticCache( $cacheKey, static::getCollection( $params ) );
		}
		return clone static::getFromStaticCache( $cacheKey );
	}

    public static function getCodes( $params = array() )
    {
        $languages = self::getLanguages( $params );
        return $languages->getKeys('code'); 
    }
    
    public static function getQueryParts( $params = array())
    {
        $qp = parent::getQueryParts( $params );
        
        $currentCode = leaf_get('properties', 'language_code');
        if (!empty($currentCode))
        {
            $qp['orderBy'][]  = 'IF(t.code="' . dbse($currentCode) . '",0,1) ASC';
        }

        $qp['orderBy'][]  = 't.code ASC';
        
        return $qp;
    }
    
    
    public function uniquenessValidator( $values )
    {
        $code = get($values, 'code');

        $result = true;
        
        foreach (static::getLanguages() as $language)
        {
            if (
                ($language->code == $code)
                &&
                ($language->id != $this->id)
            )
            {
                $result = array
                (
                    'field' => array('name' => 'code'),
                    'errorCode' => 'languageWithThisCodeAlreadyExists',
                );
                break;
            }
        }

        return $result;
	}
	    
}
?>
