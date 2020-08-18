<?php
abstract class leafBaseObject
{
	protected $classProperties 		= array();
	protected $strictMode	 		= true;
	protected $smartSave 			= true;
	protected $variableFunctions 	= array();
	protected $fieldsDefinition 	= array();
	protected $postChecks 			= array();
	protected $table;
	protected $tablePrefix;
	protected $id;
	private   $isObjectExistingVal 	= true;
	protected $objectRelations 		= array();
	protected $extendedTables 		= array();
	protected $modes 				= array();
	protected $currentMode 			= NULL;
	protected $dieOnError 			= true;
	protected $doNotSave 			= false;
    
    protected $multiErrorValidation = false;
    
	const classSettingsVariableName = 'leafBaseObjectClassSettings';

	/* store last getObjectCollection request objects count */
	protected $lastCollectionTotal;

	/* save function exception */
	protected $saveException = array();
	
	/** 
	 * properties needed for caching
	 */
	public $cache = array();
	protected $cacheInsertionTimes = array();
	
	/**
	 * static caching
	 * NOTE: relies on late static binding
	 */ 
	public static $staticCache = array();
	public static $staticCacheInsertionTimes = array();
	
	/**
	 * i18n
	 * TODO: ensure own table is InnoDB
	 */
    protected $i18n; 							// internal
	protected $validI18nValues; 				// internal property, intended for use in post check methods
    protected static $i18nProperties = array(); // define i18n fields
    protected static $i18nIndexes = array(); 	// add extra indexes to i18n table
    
	protected static $collectionClassName = 'pagedObjectCollection';
    
	/* used in fields definition to skip some field saving to db */
	public function emptyMethod(){}
	
	/**
	 * database where all queries are run
	 */
	protected $dbLink = NULL;
	
	/**
	 * database where insert, update and delete queries are duplicated to
	 */
	protected $mirrorDbLink = NULL;
	
	/********************* constructor methods *********************/
	
    /**
     * base constructor
     * @param $initData initialize data
     */
	function __construct($initData = NULL){
		$this->initializeTable();

		$this->getClassProperties();
                
		$this->initEnvironment();
        
		$this->loadInitData($initData);
        
	}
	/**
	 * handle init data
	 */
	 protected function loadInitData($initData){
		if(!is_null($initData))
		{
			if(is_array($initData))
			{
				$data = $initData;
			}
			elseif(!empty($this->table) && ctype_digit((string) $initData) && $initData != 0)
			{
				$data = $this->loadObjectFromDb($initData);
			}
			if(!empty($data) && is_array($data))
			{
				$this->assignData($data);
			}
			elseif(ctype_digit((string) $initData) && $initData == 0)
			{
				$this->id = 0;
			}
			else
			{
				$this->makeNonexistent();
			}
		}
	 }
    /**
     * load object data from table
     */
	 protected function loadObjectFromDb($id){
		$q['select'][] = 'baseTable.*';
		if(!empty($this->tableSelect))
		{
			$q['select'][] = $this->tableSelect;
		}
		$q['from'][] = '`' . $this->table . '` `baseTable`';
		$q['where'][] = 'baseTable.id = ' . $id;
		if(!empty($this->extendedTables))
		{
			$i = 1;
			foreach($this->extendedTables as $table)
			{
				$tableAlias = 'extendedT' . $i;
				$q['select'][] = $tableAlias . '.*';
				$q['leftJoins'][] = '`' . $table['name'] . '` `' . $tableAlias . '` ON ' . $tableAlias . '.' . $table['bindKey'] . ' = baseTable.id';
				++$i;
			}
		}
		$q = $this->buildQuery($q);
		return dbGetRow($q, $this->dbLink);
	 }
    /**
     * empty environment initialize method
     */
	protected function initEnvironment(){
	}
    /**
     * method to get all class defined properties
     */
	protected function getClassProperties(){
		$this->classProperties = get_object_vars($this);
	}
    /**
     * method to initialize object table
     */
	 final public function initializeTable(){
		//get class name
		$className = get_class($this);
		if (defined($className . '::tableName'))
		{
			$this->table = constant($className . '::tableName');
		}
		if (defined($className . '::tablePrefix'))
		{
			$this->tablePrefix = constant($className . '::tablePrefix');
		}
		if (!empty($this->table) && !empty($this->tablePrefix))
		{
			$this->table =  $this->tablePrefix . $this->table;
		}
	 }
	
	/********************* db methods *********************/
	
	public function getDbLink()
	{
		return $this->dbLink;
	}
	
	protected function setDbLink( $dbLink )
	{
		$this->dbLink = $dbLink;
	}
	
	/********************* caching methods *********************/
	
	public function hasInCache( $name )
	{
		if
		(
			leaf_get( 'invalidateCacheBefore' ) !== NULL 
			&&
			isset( $this->cacheInsertionTimes[ $name ] )
			&&
			leaf_get( 'invalidateCacheBefore' ) > $this->cacheInsertionTimes[ $name ] 
		)
		{
			unset( $this->cache[ $name ] );
			unset( $this->cacheInsertionTimes[ $name ] );
		}
		if( array_key_exists( $name, $this->cache ) == true )
		{
			return true;
		}
		return false;
	}
	
	public function storeInCache( $name, $value )
	{
		$this->cache[ $name ] = $value;
		$this->cacheInsertionTimes[ $name ] = microtime( true );
	}
	
	public function getFromCache( $name )
	{
		if( isset( $this->cache[ $name ] ) )
		{
			return $this->cache[ $name ];
		}
		return NULL;
	}
	
	public function removeFromCache( $name )
	{
		unset( $this->cache[ $name ] );
		unset( $this->cacheInsertionTimes[ $name ] );
	}
	
	public function flushCache()
	{
		$this->cache = array();
		$this->cacheInsertionTimes = array();
	}
	
	// static caching
	
	public static function hasInStaticCache( $name )
	{
		if
		(
			leaf_get( 'invalidateCacheBefore' ) !== NULL
			&&
			isset( static::$staticCacheInsertionTimes[ $name ] )
			&&
			leaf_get( 'invalidateCacheBefore' ) > static::$staticCacheInsertionTimes[ $name ]
		)
		{
			unset( static::$staticCache[ $name ] );
			unset( static::$staticCacheInsertionTimes[ $name ] );
		}
		if( array_key_exists( $name, static::$staticCache ) == true )
		{
			return true;
		}
		return false;
	}

	public static function storeInStaticCache( $name, $value )
	{
		static::$staticCache[ $name ] = $value;
		static::$staticCacheInsertionTimes[ $name ] = microtime( true );
	}

	public static function getFromStaticCache( $name )
	{
		if( isset( static::$staticCache[ $name ] ) )
		{
			return static::$staticCache[ $name ];
		}
		return NULL;
	}

	public static function removeFromStaticCache( $name )
	{
		unset( static::$staticCache[ $name ] );
		unset( static::$staticCacheInsertionTimes[ $name ] );
	}

	public static function flushStaticCache()
	{
		static::$staticCache = array();
		static::$staticCacheInsertionTimes = array();
	}
	
	/********************* i18n *********************/
	
	// instance methods
	
	public function assignI18nData( $i18nData, $save = false )
    {
        if (!static::hasI18nFields())
        {
            return;
        }
		
        foreach ($i18nData as $languageCode => $i18nValues)
        {
            if( !leafLanguage::exists( $languageCode ) )
            {
                continue;
            }
			
            if (empty($i18nValues['objectId']))
            {
                $i18nValues['objectId'] = $this->id;
            }
			
            $this->i18n[$languageCode] = $i18nValues;
        }
    }
	
	// static methods
	
	protected static function hasI18nFields()
    {
        $i18nProperties = static::getI18nProperties();
        return (!empty($i18nProperties));
    }

    protected static function getI18nProperties()
    {
        return static::$i18nProperties;
    }
	
	/**
	 * takes an array of all object's properties (direct + i18n), removes i18n values from
	 * the original array and returns them
	 */
    public static function stealI18nValues( & $data )
    {
        $i18nData = array();
		
        foreach ($data as $key => $value)
        {
            $matches = array();
            if (preg_match('/^(i18n\:)(?P<languageCode>[a-z\-]+)(\:)(?P<fieldName>.*)$/', $key, $matches))
            {
                $languageCode = $matches['languageCode'];
                if( !leafLanguage::exists( $languageCode ) )
                {
                    // language code did not pass validation. skip it
                    continue;
                }
                $fieldName    = $matches['fieldName'];
                $i18nData[$languageCode][$fieldName] = $value;
                unset($data[$key]);
            }
        }
		
        return $i18nData;
    }
	
    protected function saveI18nValues( $validI18nValues )
    {
        if( !static::hasI18nFields() )
        {
            return;
        }
		
        $tableName = static::getI18NTable();
        $i18nFieldNames = array_keys( static::getI18nProperties() );
		
        foreach ($validI18nValues as $languageCode => $fieldValues)
        {
            if( !leafLanguage::exists( $languageCode ) )
            {
                continue;
            }
            
            $fields = $values = $fieldValuePairs = array();
			
            $fields[] = '`objectId`';
            $values[] = $this->id;
			
            $fields[] = '`languageCode`';
            $values[] = '"' . dbse($languageCode) . '"';
			
            foreach ($fieldValues as $field => $value)
            {
                if (!in_array($field, $i18nFieldNames))
                {
                    continue; 
                }
                $fields[] = $field;

                if(is_null($value))
                {
                    $values[] = 'NULL';
                    $fieldValuePairs[] = '`' . $field . '` = NULL';
                }
                else
                {
                    $values[] = '"' . dbse($value) . '"';
                    $fieldValuePairs[] = '`' . $field . '` = "' . dbse($value) . '"';
                }
            }

            $q = '
                INSERT INTO `' . $tableName . '`
                (' . implode(', ' , $fields) . ')
                VALUES
                (' . implode(', ' , $values) . ')
                ON DUPLICATE KEY UPDATE
                ' . implode(', ', $fieldValuePairs) . '
            ';
			
            dbQuery( $q );
        }
    }

    protected static function getI18nTable()
    {
		$tableName = static::getClassTable( get_called_class() );
        if( !$tableName )
        {
            return null;
        }
        $tableName .= 'I18n';
        return $tableName;
    }

    protected static function registerI18nTableDef()
    {
        if (!static::hasI18nFields())
        {
            return false;
        }
		
        $tableName = static::getI18NTable();
        if (!$tableName)
        {
            return false;
        }
		
        $fields  =
        '
            id           int auto_increment
            objectId     int
            languageCode varchar(12)
        ';
		
        $standardIndexes = array
        (
            'primary id',
            'unique idI18n objectId,languageCode'
        );
		
        $fieldIndexes = array();
        $fieldsDef = static::getI18nProperties();
        foreach ($fieldsDef as $propertyName => $fieldDef)
        {
            if (empty($fieldDef['fieldType']))
            {
                trigger_error('Empty fieldType for ' . get_called_class() . '::i18nProperties["' . $propertyName . '"].', E_USER_WARNING);
                continue;
            }
            $fields .= $propertyName . ' ' . $fieldDef['fieldType'] . "\r\n";
			
            // take basic one-column indexes from fields definition
            if (!empty($fieldDef['index']))
            {
                if (
                    ($fieldDef['index'] === true)
                    ||
                    (strtolower($fieldDef['index']) === 'index')
                )
                {
                    $fieldIndexes[] = 'index ' . $propertyName;
                }
                elseif (strtolower($fieldDef['index']) == 'unique')
                {
                    $fieldIndexes[] = 'unique ' . $propertyName;
                }
            }
        }
		
        $indexes = array_merge($standardIndexes, $fieldIndexes, static::$i18nIndexes);
		
        $indexes = implode("\r\n", $indexes);
		
        $tableDef = array
        (
            'fields' => $fields,
            'indexes' => $indexes,
            'foreignKeys' => '
				objectId ' . self::getClassTable( get_called_class() ) . '.id CASCADE CASCADE
				languageCode languages.code CASCADE CASCADE
			',
			'engine' => 'InnoDB'
        );
		
		dbRegisterRawTableDef($tableName, $tableDef);
		
		return true;
    }

    protected function loadI18n()
    {
        $this->i18n = $this->getI18nFromDb();
        return (!is_null($this->i18n));
    }    

    protected function getI18nFromDb()
    {
        if
		(
            empty($this->id)
            ||
            !static::hasI18nFields()
        )
        {
            return null;
        }
		
        $sql = '
            SELECT *
            FROM `' . static::getI18NTable() . '`
            WHERE
	           `objectId` = ' . (int) $this->id . '
        ';
		
        $rows = dbGetAll($sql, 'languageCode');
		
        return $rows;
    }    

    public function getI18nValue( $key, $languageCode = null )
    {
        if( !static::hasI18nFields() )
        {
            return null;
        }
		
		if( empty( $languageCode ) )
		{
			$languageCode = leafLanguage::getCurrentCode();
		}
		
        $i18n = $this->__get('i18n');
		
        if
		(
            !leafLanguage::exists( $languageCode )
            ||
            empty($i18n)
            ||
            empty($i18n[$languageCode])
        )
        {
            return null;
        }
        
        if(
            !isset( $i18n[ $languageCode ][ $key ] )
            &&
            !empty( $this->objectRelations[$key] )
            &&
            isset( $this->objectRelations[$key]['key'] )
            &&
            array_key_exists( $this->objectRelations[$key]['key'], $i18n[$languageCode] )
        )
        {
            $relation = $this->objectRelations[$key];
            
            $relationKey = $this->getI18nValue( $relation['key'], $languageCode );
            $relationObject = null;
            
            if( $relationKey )
            {
                $relationObject = getObject( $relation['object'], $relationKey );
                $this->setI18nValue( $languageCode, $key, $relationObject );
            }
            
            return $relationObject;
        }
        else if ( !array_key_exists( $key, $i18n[$languageCode] ) )
        {
            return null;
        }
		
        return $i18n[ $languageCode ][ $key ];
    }

    public function setI18nValue( $languageCode, $fieldName, $value )
    {
        $this->i18n[ $languageCode ][ $fieldName ] = $value;
    }    
    
	/**
	 * $params['loadLanguages'] determines which languages will be joined
     * may contain array of specific language codes
     * OR a string with a specific language code
     * OR true to load all
     * OR false to not load any
     * OR null / not specified to load currently active language (default)
	 */
    public static function getLanguageCodesFromParams( $params )
    {
        if (!is_array($params))
        {
            $params = array();
        }
        
        $loadLanguages = array_key_exists('loadLanguages', $params) ? $params['loadLanguages'] : null;
		
		$availableLanguageCodes = array();
		foreach( leafLanguage::getLanguages() as $language )
		{
			$availableLanguageCodes[] = $language->code;
		}
		$languageCodes = array();
        if ($loadLanguages === true)
        {
            // load all
            $languageCodes = $availableLanguageCodes;
        }
        elseif ($loadLanguages === false)
        {
            // load none
            return;
        }
        elseif (is_array($loadLanguages))
        {
            // load some (validate language code array)
            foreach ($loadLanguages as $languageCode)
            {
                if (in_array($languageCode, $availableLanguageCodes))
                {
                    $languageCodes[] = $languageCode;
                }
            }
        }
        elseif (is_string($loadLanguages))
        {
            if (in_array($loadLanguages, $availableLanguageCodes))
            {
                $languageCodes[] = $loadLanguages;
            }
        }
        else
        {
            // load current
            $languageCodes = array( leafLanguage::getCurrentCode() );
        }
        
        $languageCodes = array_unique($languageCodes);        
        return $languageCodes;
    }
    
    public static function addI18nQueryParts( & $queryParts, $params = null )
    {
        $languageCodes = self::getLanguageCodesFromParams( $params );
        if (empty($languageCodes))
        {
            return;
        }
		
        // list columns to select
        $i18nProperties = static::getI18nProperties();
        if (empty($i18nProperties))
        {
            return;
        }
        
        $i18nFieldNames = array_keys( $i18nProperties );
        $i18nTableName  = static::getI18NTable();
		
        foreach ($languageCodes as $languageCode)
        {
            $i18nTableNameInner = 'i18n:' . $languageCode ;
			
            $queryParts['leftJoins'][] = '
                `' . $i18nTableName . '` AS `' . $i18nTableNameInner . '`
                ON
                `' . $i18nTableNameInner . '`.objectId = t.id
                AND
                `' . $i18nTableNameInner . '`.languageCode = "' . dbse($languageCode) .  '"
            ';
			
            $fieldsInSelect = array();
            foreach ($i18nFieldNames as $fieldName)
            {
                $fieldsInSelect[] = '`' . $i18nTableNameInner . '`.`' . $fieldName . '` AS `' . $i18nTableNameInner . ':' . $fieldName . '`';
            }
			
            $queryParts['select'][] = implode(', ' , $fieldsInSelect);
        }
    }
	
	protected function getI18nDefinitions($mode = null)
	{
		if (is_null($mode))
		{
			$fieldsDefinition = static::getI18nProperties();
		}
		else
		{
			$fieldsDefinition = $this->getI18nModeVariables($mode);
		}
		return $fieldsDefinition;
	}
    
	protected function getI18nModeVariables($mode = null)
	{
	    return $this->getModeVariables($mode, true);
	}
	
	/********************* get methods *********************/
	
	public function getDisplayString()
	{
		if( property_exists( $this, 'name' ) )
		{
			return $this->__get('name');
        }
        elseif(method_exists($this, '__toString'))
        {
            return $this->__toString();
        }
		return '';
	}
	
	public function getDisplayStringFor( $property )
	{
		if( is_object( $this->__get( $property ) ) && method_exists( $this->__get( $property ), 'getDisplayString' ) )
		{
			return $this->__get( $property )->getDisplayString();
		}
		return NULL;
	}
	
	public function getEncodableRepresentation()
	{
		$representation = new stdClass();
		$representation->id = $this->id;
		foreach( $this->fieldsDefinition as $name => $definition )
		{
			$representation->$name = $this->$name;
		}
		if( isset( $this->add_date ) )
		{
			$representation->add_date = $this->add_date;
		}
		return $representation;
	}
	
	public function getTableValues()
	{
		$values = $this->collectValues();
		$values = $values['__standart__'];
		$values['id'] = $this->id;
		if( isset( $this->add_date ) )
		{
			$values['add_date'] = $this->add_date;
		}
		return $values;
	}
	
	/********************* collection related methods *********************/
	
    public static function getCollectionClassName()
    {
        return static::$collectionClassName;
    }
    
	public static function getCollection( $params = array (), $itemsPerPage = null, $page = null )
    {
        $queryParts = static::getQueryParts( $params) ;
        
        $collectionClassName = static::getCollectionClassName();
        
        return new $collectionClassName( get_called_class(), $queryParts, $itemsPerPage, $page );
    }
	
	public static function getIds( $params = array (), $itemsPerPage = null, $page = null )
    {
        $queryParts = static::getQueryParts( $params) ;
        $queryParts['select'] = 't.id as id';
        return dbgetall($queryParts, null, 'id');
    }    
    
    protected static function getSearchPattern( $userInputStr )
    {
        $search = trim($userInputStr);
        if ($search === '')
        {
            return $search;
        }
        $search = str_replace('%', '\%', $search);
        $search = str_replace('_', '\_', $search);
        $search = preg_replace('/\s+/u', ' ', $search);
        $search = explode(' ', $search);
        $search = dbse('%' . implode('%', $search) . '%');
        return $search;
    }

    public static function getQueryPartsSearch($queryParts, $params, $allowedSearchFields)
    {
        // basic search
        if (
            (isset($params['search']))
            &&
            (
                (!empty($allowedSearchFields))
                &&
                (is_array($allowedSearchFields))
            )
        )
        {
    	    $search = self::getSearchPattern( get($params, 'search', ''));
    	    
    	    if (strlen($search) > 0)
    	    {
    	        
                $i18nProps = static::getI18nProperties();
        	    $searchFields = $i18nSearchFields = array();
        	    foreach ($allowedSearchFields as $fieldName)
        	    {
        	        if (isset($i18nProps[$fieldName]))
        	        {
        	            $i18nSearchFields[] = $fieldName;
        	        }
        	        else
        	        {
        	            $searchFields[] = $fieldName;
        	        }
                }
        	    
        	    $searchConditions = array();
        	    
        	    // add base table search parts
        	    if (!empty($searchFields))
                {
                    foreach($searchFields as $fieldName)
                    {
                        $searchConditions[] = '(' . $fieldName . ' LIKE "' . $search . '")';
                    }
        	    }
        	    
                $languageCodes = self::getLanguageCodesFromParams($params);
        	    // add i18n search parts
        	    if (!empty($languageCodes) && !empty($i18nSearchFields))
        	    {
                    foreach ($languageCodes as $languageCode)
            	    {
                        foreach ($i18nSearchFields as $fieldName)
                        {
                            $searchConditions[] = '(`i18n:' . $languageCode . '`.`' . $fieldName . '` LIKE "' . $search . '")';
            	        }
        	        }
                }
        	    
    		    if (empty($searchConditions))
    		    {
    		        $searchConditions[] = '(1 = 0)';
    		    }        	   
    		    $queryParts['where']['search'] = implode(' OR ', $searchConditions); 
    	    }            
        }

        return $queryParts;
    }
    
	public static function getQueryParts($params = array ())
    {
        $queryParts['select'][] = 't.*';
        $queryParts['from'][]   =  '`' . static::tableName . '` AS `t`';
		

        // example for automated search query addition
        //$queryParts = self::getQueryPartsSearch($queryParts, $params, array('name'));
          
        // join i18n
        if( static::hasI18nFields() )
        {
            static::addI18nQueryParts($queryParts, $params);
        }
		
        return $queryParts;
    }
    
	public static function getObject($params)
	{
	    $queryParts = static::getQueryParts($params);
	    $queryParts['select'] = 't.id';
	    $id = dbgetone($queryParts);
	    if (!$id)
	    {
	        return null;
	    }
	    return getObject(get_called_class(), $id);
	}    
    
    
	
	
	
	
	
	
	
	
	
	
	
    /**
     * method to check if objec is existing
     */
	final public function existing(){
	 	return $this->isObjectExistingVal;
	}
    
	/**
     * method to set object is deleted
     */
	final protected function makeNonexistent(){
	 	$this->isObjectExistingVal = false;
	}
    
	/**
     * method to get last getObjectCollection request objects count
     */
	public function getLastCollectionTotal(){
		return $this->lastCollectionTotal;
	}
	
    /**
     * method to get object set in array
     * @param string $queryOrParts request query or query array
     * @param string $className object class name
     * @param array $params additional params
     */
	 //TODO: $dbLink
	public static function getObjectCollection($queryOrParts, $className, $params = array()){
	 	if(is_array($queryOrParts))
		{
			$queryOrParts = self::buildQuery($queryOrParts);
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
		$r = dbQuery($queryOrParts);
		if(isset($this))
		{
			$this->lastCollectionTotal = $r->rowCount();
		}
		while($itemData = $r->fetch())
		{
			if($i > $params['limitStart'])
			{
				if($a < $params['limitCount'])
				{
					if($keyField)
					{
						$list[$itemData[$keyField]] = getObject($className, $itemData);
					}
					else
					{
						$list[] = getObject($className, $itemData);
					}
					++$a;
				}
				else
				{
					return $list;
				}
			}
			++$i;
		}

		return $list;
	}
	
    /**
     * method to load and assign object information
     * @param integer $id object id
     */
	public function loadData($id = NULL){
		if($id !== NULL)
		{
			$this->id = intval($id);
		}
		$q = '
		SELECT
			' .
			(
				isset($this->tableQuery['select'])
				?
				implode(', ', $this->tableQuery['select'])
				:
				't.*'
			)
			 . '
		FROM
			`' . $this->table . '` `t`
		WHERE
			t.id = "' . intval($this->id) . '"
		';
		if($data = dbGetRow($q, $this->dbLink ))
		{
			$this->assignData($data);
		}
		elseif($this->id !== 0)
		{
			trigger_error('Nonexistent entry. id: ' . $this->id, E_USER_ERROR);
		}
	}

	public function __isset($nm)
	{
		return isset($this->$nm);
	}
 
	public function __unset($nm)
	{
		unset($this->$nm);
	}

	public function __get($nm)
	{
		// cache
        if (isset($this->$nm))
        {
            return $this->$nm;
        }
		
        // i18n
        $i18nProperties = static::getI18nProperties();
        
        if (!empty($i18nProperties))
        {
            if ($nm == 'i18n')
            {
                if (is_null($this->i18n))
                {
                    $this->loadI18n();
                }
                return $this->i18n;
            }
            elseif (array_key_exists($nm, $i18nProperties))
            {
                return $this->getI18NValue($nm);
            }
            else if(
                !empty( $this->objectRelations[$nm] )
                &&
                isset( $this->objectRelations[$nm]['key'] )
                &&
                array_key_exists( $this->objectRelations[$nm]['key'], $i18nProperties )
            )
            {
                return $this->getI18NValue( $nm );
            }
        }
        
		if(array_key_exists($nm, $this->classProperties) && $this->$nm === NULL)
		{
			if(isset($this->variableFunctions[$nm]))
			{
				$functionName = $this->variableFunctions[$nm];
				$this->$functionName();
			}
			else if(method_exists($this, $functionName = 'set' . ucfirst($nm)))
			{
				$this->$functionName();
			}
			else if(!empty($this->objectRelations[$nm]))
			{
                $this->$nm = $this->getRelation($this->objectRelations[$nm]);
			}
		}
		
		if(array_key_exists($nm, $this->classProperties) || isset($this->$nm))
		{
			return $this->$nm;
		}
	}


	public function __set($nm, $val){
		if($this->strictMode && !array_key_exists($nm, $this->classProperties))
		{
			$this->error('Undefined property: ' .  $nm);
		}
		if(isset($this->fieldsDefinition[$nm]))
		{
			$p = new processing;
			$p->setVariables(array($nm => $val));
			$values = $p->check_values(array($nm => $this->fieldsDefinition[$nm]));
			if( isset( $values[$nm] ) )
			{
				$val = $values[$nm];
			}
		}
		$this->$nm = $val;
	}
	
	// TODO: $dbLink
	protected function getRelation($relation){
        
		if(!empty($relation['key']))
		{
			$relationKey = $relation['key'];
			if(!is_null($this->$relationKey))
			{
				return getObject($relation['object'], $this->$relationKey);
			}
		}
		elseif(!empty($relation['objectKey']) && $tableName = self::getClassTable($relation['object']))
		{
			$queryParts['select'][] = 'child.*';
			$queryParts['from'][] = '`' . $tableName . '` `child`';
            if ($this->id)
            {
                $queryParts['where'][] = 'child.' . dbSE($relation['objectKey']) . ' = ' . $this->id;               
            }
            else
            {
                $queryParts['where'][] = 'false';
            }

            if(isset($relation['conditionalQuery']))
            {
                $queryParts['where'] = array_merge($queryParts['where'], $relation['conditionalQuery']);
            }
			if(!empty($relation['orderField']))
			{
				$queryParts['orderBy'][] = 'child.' . dbSE($relation['orderField']);
				if(!empty($relation['order']))
				{
					$queryParts['order'] = dbSE($relation['order']);
				}
				//add default order type
				else
				{
					$queryParts['order'] = 'desc';
				}
			}
			$q = $this->buildQuery($queryParts);
			$params = array();
			if(!empty($relation['keyField']))
			{
				$params['keyField'] = $relation['keyField'];
			}
			$collection = $this->getObjectCollection($q, $relation['object'], $params);
            
			// object to object relation
			if(!empty($relation['singleObject']) && $relation['singleObject'] == true)
			{
				if(sizeof($collection))
				{
					return $collection[0];
				}
				else
				{
					return NULL;
				}
			}
			// object to child objects relation
			else
			{
				return $collection;
			}
		}
		elseif(!empty($relation['linkedChildRows']))
		{
			$childCol = !empty($relation['childCol']) ? $relation['childCol'] : 'child_id';
			$parentCol = !empty($relation['parentCol']) ? $relation['parentCol'] : 'parent_id';
			$queryParts['select'][] = 'linked.' . $childCol;
			$queryParts['from'][] = '`' . dbSE($relation['linkedChildRows']) . '` `linked`';
			$queryParts['where'][] = 'linked.' . $parentCol . ' = ' . $this->id;
			$q = $this->buildQuery($queryParts);
			$result = dbGetAll($q, false, $childCol);
			return $result;
		}
		elseif(!empty($relation['linkedChildObject']))
		{
			$linkData = $this->getLinkedChildData($relation);
			$queryParts['select'][] = 'obj.*';
			$queryParts['from'][] = '`' . $linkData['link'] . '` `link`';
			$queryParts['leftJoins'][] = '`' . $linkData['base'] . '` `obj` ON obj.id = link.' . $linkData['slaveKey'] . '';
			$queryParts['where'][] = 'link.' . $linkData['masterKey'] . ' = ' . $this->id;
			if(isset($relation['singleObject']))
			{
				return current($this->getObjectCollection($queryParts, $relation['linkedChildObject']));
			}
			else
			{
				return $this->getObjectCollection($queryParts, $relation['linkedChildObject']);
			}
		}
	}
	/*
	* get class table
	*/
	public static function getClassTable($class){
		if(is_object($class))
		{
			$tableName = constant(get_class($class) . '::tableName');
			$tablePrefix = defined (get_class($class) . '::tablePrefix') ? constant(get_class($class) . '::tablePrefix') : '';
		}
		else
		{
			$tableName = constant($class . '::tableName');
			$tablePrefix = defined ($class . '::tablePrefix') ? constant($class . '::tablePrefix') : '';
		}
		return $tablePrefix . $tableName;
	}

	/**
	 * method for generating linked child relation data
	 */
	protected function getLinkedChildData($relation)
	{
		if(isset($relation['slave']))
		{
			$masterClass = $relation['linkedChildObject'];
			$slaveClass = get_class($this);
		}
		else
		{
			$masterClass = get_class($this);
			$slaveClass = $relation['linkedChildObject'];
		}
		$slaveTableName = constant($slaveClass . '::tableName');
		$slaveTablePrefix = defined ($slaveClass . '::tablePrefix') ? constant($slaveClass . '::tablePrefix') : '';
		$masterTableName = constant($masterClass . '::tableName');
		$masterTablePrefix = defined ($masterClass . '::tablePrefix') ? constant($masterClass . '::tablePrefix') : '';
		if(isset($relation['slave']))
		{
			$data['base'] = $masterTablePrefix . $masterTableName;
		}
		else
		{
			$data['base'] = $slaveTablePrefix . $slaveTableName;
		}
		$data['link'] = $masterTablePrefix . $masterTableName . '_' . $slaveTableName;
		if(!empty($relation['masterKey']))
		{
			$data['masterKey'] = $relation['masterKey'];
		}
		else
		{
			$data['masterKey'] = 'parent_id';
		}
		if(!empty($relation['slaveKey']))
		{
			$data['slaveKey'] = $relation['slaveKey'];
		}
		else
		{
			$data['slaveKey'] = 'child_id';
		}
		// swap keys
		if(isset($relation['slave']))
		{
			$tmp = $data['slaveKey'];
			$data['slaveKey'] = $data['masterKey'];
			$data['masterKey'] = $tmp;
		}
		return $data;
	}
	
	/**
	 * method for generating linked child relation table name
	 */
	public static function getLinkedChildTables($classNameBase, $classNameChild)
	{
		$linkedObjectTableName = constant($classNameChild . '::tableName');
		$linkedObjectTablePrefix = defined ($classNameChild . '::tablePrefix') ? constant($classNameChild . '::tablePrefix') : '';
		$thisTableName = constant($classNameBase . '::tableName');
		$thisTablePrefix = defined ($classNameBase . '::tablePrefix') ? constant($classNameBase . '::tablePrefix') : '';
		$tables['base'] = $linkedObjectTablePrefix . $linkedObjectTableName;
		$tables['link'] = $thisTablePrefix . $thisTableName . '_' . $linkedObjectTableName;
		return $tables;
	}
	
	/**
	 * method for adding relation child object
	 */
	// TODO: $dbLink
	final public function addRelationChild($relationName, $childId)
	{
	 	if(!empty($this->objectRelations[$relationName]['linkedChildObject']))
		{
			$className = $this->objectRelations[$relationName]['linkedChildObject'];
			$tables = self::getLinkedChildTables(get_class($this), $className);
			$values = array(
				'parent_id' => $this->id,
				'child_id' => $childId
			);
			dbReplace($tables['link'], $values);
		}
		else
		{
			$this->error('Nonexistent relation: ' . $relationName);
		}
	}

	/**
	 * method for removing relation child object
	 */
	// TODO: $dbLink
	final public function removeRelationChild($relationName, $childId)
	{
		if(!empty($this->objectRelations[$relationName]['linkedChildObject']))
		{
			$className = $this->objectRelations[$relationName]['linkedChildObject'];
			$tables = self::getLinkedChildTables(get_class($this), $className);
			$where_q = '
				`parent_id` = "' . $this->id . '" AND
				`child_id` = "' . $childId . '"
			';
			dbDelete($tables['link'], $where_q);
		}
		else
		{
			$this->error('Nonexistent relation: ' . $relationName);
		}
	}
	
	/**
	 * method for updating relation child rows
	 */
	// TODO: $dbLink overrides
	protected function updateChildRows($relationName, $values){
		$table = false;
		$relation = $this->objectRelations[$relationName];
		if(!empty($relation['linkedChildRows']))
		{
			$table = $relation['linkedChildRows'];
			$masterKey = 'parent_id';
			$slaveKey = 'child_id';
		}
		elseif(!empty($relation['linkedChildObject']))
		{
			$linkData = $this->getLinkedChildData($relation);
			$table = $linkData['link'];
			$masterKey = $linkData['masterKey'];
			$slaveKey = $linkData['slaveKey'];
		}
		if(!empty($table))
		{
			dbDelete($table, array($masterKey => $this->id));
			// insert new
			if(!empty($values))
			{
				$fields = array();
				foreach($values as &$val)
				{
					$fields[] = array(
						$masterKey => $this->id,
						$slaveKey => dbSE($val),
					);
				}
				dbInsert($table, $fields);
			}
		}
	}
	
	/**
	 * method for object deleting
	 */
	public function delete()
	{
	    $this->deleteAllFiles();

		// check for existing table and object id
		if(isset($this->table) && $this->id)
		{
			dbDelete($this->table, $this->id, $this->dbLink);
			if( $this->mirrorDbLink !== NULL )
			{
				dbDelete($this->table, $this->id, $this->mirrorDbLink);
			}
			$this->makeNonexistent();
		}
	}

	public function assignData( $data )
	{
		$i18nData = null;
		if( static::hasI18nFields() )
        {
            // filter out i18n columns
            $i18nData = static::stealI18nValues( $data );
        }
		
		foreach($data as $key => $value)
		{
			if(!empty($this->fieldsDefinition[$key]['type']))
			{
				if($this->fieldsDefinition[$key]['type'] == 'options')
				{
					$value = explode(',', $value);
				}
				elseif($this->fieldsDefinition[$key]['type'] == 'array' && !empty($value))
				{
					$value = unserialize($value);
				}
			}
			$this->$key = $value;
		}
		
		if (!is_null( $i18nData ))
        {
            $this->assignI18nData( $i18nData, false );
        }
	}

	public static function buildQuery($queryParts){
		if(empty($queryParts['from']) && isset($this) && isset($this->table))
		{
			$queryParts['from'][] = $this->table;
		}
		$q = dbBuildQuery($queryParts);
		return $q;
	}

	// this is not a "__set()" type of setter
	public function setDieOnError( $die )
	{
		if( $die == false )
		{
			$this->dieOnError = false;
		}
		else
		{
			$this->dieOnError = true;
		}
	}
	
	public function getVariablesSaveProcessing(&$variables, &$fieldsDefinition = NULL, &$mode = false)
	{
		$p = new processing();
        if ($this->multiErrorValidation)
        {
            $p->setMultiError(true);
        }
		return $p;
	}

	public function variablesSave($variables, $fieldsDefinition = NULL, $mode = false)
	{
	    $ajaxCall = !empty($variables['getValidationXml']);

		// i18n //
		
		// extract all i18n variables from main array into a separate one
        $i18nVariables = static::stealI18nValues( $variables );
		
		// get i18n fieldsdef according to $fieldsDefinition / $mode
        $saveMode = ($mode === false) ?  $this->currentMode : $mode;
        
        $i18nFieldsDefinition = array();
        $allI18nFields = static::getI18nProperties();
        
        if (!empty($allI18nFields))
        {
            // class has i18n fields
            
            // get i18n fields definition
            
            if (!empty($fieldsDefinition))
            {
                // full fieldsdef given as argument. extract all i18n fields into a separate array
                foreach ($fieldsDefinition as $name => $def)
                {
                    if (array_key_exists($name, $allI18nFields))
                    {
                        $i18nFieldsDefinition[$name] = $def;
                        unset($fieldsDefinition[$name]);
                    }
                }
            }        
            else
            {   
                // fieldsdef not explicitliy given, detect fields from mode
                $i18nFieldsDefinition = $this->getI18nDefinitions($saveMode);
            }
        }

        $validI18nValues = array();
        if (!empty($i18nFieldsDefinition))
        {
            // some i18n fields need to be saved
            // perform validation on them but do not save yet. 
            // for ajax calls - return xml/json only in case of errors (dont return ok xml/json, allow script to continue)
            // for non-ajax calls - on error die or not depending on $this->dieOnError setting
                    
            foreach ($i18nVariables as $languageCode => $values)
            {
                // rename variables in post and definition to i18n:<lang>:<varName> pattern for the time of validation
                // (so that field names in processing error data match user input)
                $languageFieldsDefinition = array();
                $fieldNameMap = array();
                foreach ($i18nFieldsDefinition as $name => $definition)
                {
                    $fieldName = 'i18n:' . $languageCode . ':' . $name;
                    $languageFieldsDefinition[$fieldName] = $definition;
                    $fieldNameMap[$fieldName] = $name;
                }
                $languageValues = array();
                
                if (
                    ($ajaxCall)
                    &&
                    (!empty($variables['validation']))
                )
                {
                    // pass validation format to i18n validators also
                    $languageValues['validation'] =  $variables['validation'];
                }

                foreach ($values as $name => $value)
                {
                    $fieldName = 'i18n:' . $languageCode . ':' . $name;
                    $languageValues[$fieldName] = $value;
                }
                
                $p = $this->getVariablesSaveProcessing($variables, $fieldsDefinition, $mode);
                if ($ajaxCall || !$this->dieOnError)
                {
                    $p->error_cast = 'return';                    
                }            
                $p->setVariables($languageValues);                
                $languageValues = $p->check_values($languageFieldsDefinition);    
                
                if ($p->hasErrors())
                {
                    // error happenned. if code has reached this line, it means error_cast == 'return'
                    if ($ajaxCall)
                    {
                        // output error xml/json
                        $p->outputResult();
                    }
                    else
                    {
                        // return error instance
                        return $p;
                    }
                }
                
                // language values ok. rename back to default names
                $validLanguageValues = array();

                foreach ($languageValues as $fieldName => $value)
                {
                    $name = $fieldNameMap[$fieldName];
                    $validLanguageValues[$name] = $value;
                }
                
                $validI18nValues[$languageCode] = $validLanguageValues;
            }
            
            // assign to property so that post checks from parent::variablesSave can have access to i18n values
            $this->validI18nValues = $validI18nValues; 
        }
		
		//-- i18n //
		
		if($mode !== false)
		{
			$oldMode = $this->currentMode;
			$this->setMode($mode);
		}

		if(empty($fieldsDefinition))
		{
			$fieldsDefinition = $this->getDefinitions();
		}
		
		$p = $this->getVariablesSaveProcessing($variables, $fieldsDefinition, $mode);
		if ($ajaxCall || !$this->dieOnError)
		{
			$p->error_cast = 'return';
		}
		foreach( $this->postChecks as $postCheck )
		{
			if( !empty( $postCheck ) )
			{
                if (is_array($postCheck) && array_key_exists('function', $postCheck))
                {
                    $function       = $postCheck['function'];
                    $onlyIfValid = (array_key_exists('onlyIfValid', $postCheck)) ? $postCheck['onlyIfValid'] : false;
                }
                else
                {
                    $function       = $postCheck;
                    $onlyIfValid = false;
                }
                
				if( is_array( $function ) && !empty( $function[0] ) && $function[0] === '$this' )
				{
					$function[0] = $this;
				}
                
				$p->addPostCheck( $function, $onlyIfValid );
			}
		}
		$p->setVariables($variables);
		$values = $p->check_values($fieldsDefinition);
		if ($ajaxCall)
        {
            if ($this->dieOnError || $p->hasErrors())
            {
                $p->outputResult();
            }
            $this->doNotSave = true;
            return true;
		}
        
		if ($this->dieOnError == false && $p->hasErrors() )
		{
			return $p;
		}

		if (empty($this->doNotSave))
		{		
            $this->removeDeletedFiles($values, $fieldsDefinition);
        }
        
        $this->assignArray($values);
        
		if (empty($this->doNotSave))
        {
    		$this->save();
    		$this->saveFileFields( $variables, $fieldsDefinition );
		}

		if ($mode !== false)
		{
			$this->setMode($oldMode);
		}
		
		// i18n //
		if
		(
            !empty($i18nFieldsDefinition)
            && 
            empty($this->doNotSave)
        )
        {
            $this->removeDeletedI18nFiles( $i18nFieldsDefinition );
            $this->saveI18nFileFields( $i18nVariables, $i18nFieldsDefinition );
            
            // some i18n fields need to be saved
    		$this->saveI18nValues( $this->validI18nValues );
        }
		//-- i18n //
		
		return true;
	}

	protected function getDefinitions(){
		if(is_null($this->currentMode))
		{
			$fieldsDefinition = $this->fieldsDefinition;
		}
		else
		{
			$fieldsDefinition = $this->getModeVariables();
		}
		return $fieldsDefinition;
	}

	protected function collectValues()
	{
		$values = array();
		$fieldsDefinition = $this->getDefinitions();
		foreach($fieldsDefinition as $key => $val)
		{
			try{
				if(!is_array($val))
				{
					$key = $val;
				}
				if(!isset($this->$key))
				{
					if(!isset($val['zero_to_null']) || $val['zero_to_null'] == true)
					{
						$this->$key = NULL;
					}
					else
					{
						$error = 'Nonexistent property: ' . $key;
						throw new Exception($error);
					}
				}
				if(is_array($val) && !empty($val['saveWith']))
				{
					$values[$val['saveWith']][$key] = $this->$key;
				}
				else if((!empty($this->objectRelations[$key]['linkedChildRows']) || !empty($this->objectRelations[$key]['linkedChildObject'])) && is_array($val))
				{
					$values['updateChildRows'][$key] = $this->$key;
				}
				elseif(is_array($val) && !empty($val['table']))
				{
					$values[$val['table']][$key] = $this->$key;
				}
				else
				{
					$value = $this->$key;
					if(!empty($val['type']))
					{
						if($val['type'] == 'options' && is_array($value))
						{
							$value = implode(',', $value);
						}
						elseif(is_array($value) && $val['type'] == 'array')
						{
							$value = serialize($value);
						}
					}
					$values['__standart__'][$key] = $value;
				}
			}
			catch (Exception $e)
			{
				$this->error('Caught exception: ' .  $e->getMessage());
			}
		}
		return $values;
	}

	protected function saveToDB($values){
		if(isset($this->saveException))
		{
			$exceptions = $this->saveException;
		}
		else
		{
			$exceptions = array();
		}
		if($this->id)
		{
			if(!empty($values['__standart__']))
		 	{
				dbUpdate($this->table, $values['__standart__'], $this->id, $exceptions, $this->dbLink);
				if( $this->mirrorDbLink !== NULL )
				{
					dbUpdate($this->table, $values['__standart__'], $this->id, $exceptions, $this->mirrorDbLink);
				}
			}
		}
		else
		{
			// check add_date property
			if(array_key_exists('add_date', $this->classProperties) && !isset($values['__standart__']['add_date']))
			{
				$this->add_date = date("Y-m-d H:i:s");
				$values['__standart__']['add_date'] = $this->add_date;
			}
			// check author_ip property
			if(array_key_exists('author_ip', $this->classProperties) && !isset($values['__standart__']['author_ip']) && !empty($_SERVER['REMOTE_ADDR']))
			{
				$values['__standart__']['author_ip'] = leafIp::getIp();
                $this->author_ip = leafIp::getIp();
			}
			$this->id = dbInsert($this->table, $values['__standart__'], NULL, $exceptions, $this->dbLink);
			if( $this->mirrorDbLink !== NULL )
			{
				dbInsert($this->table, $values['__standart__'], NULL, $exceptions, $this->mirrorDbLink);
			}
		}
		unset($values['__standart__']);
		if(!empty($values))
		{
			foreach($values as $key => $functionValues)
			{
				if($key == 'updateChildRows')
				{
					foreach($functionValues as $relationName => $relationValues)
					{
						$this->$key($relationName, $relationValues);
					}
				}
				elseif(!empty($this->extendedTables[$key]))
				{
					$tableName = $this->extendedTables[$key]['name'];
					$bindKey = $this->extendedTables[$key]['bindKey'];
					$functionValues[$bindKey] = $this->id;
					dbReplace($tableName, $functionValues, NULL, $exceptions, $this->dbLink);
					if( $this->mirrorDbLink !== NULL )
					{
						dbReplace($tableName, $functionValues, NULL, $exceptions, $this->mirrorDbLink);
					}
				}
				else
				{
					$this->$key($functionValues);
				}
			}
		}
	}

	public function save()
	{
	    if (empty($this->doNotSave))
        {	    
            $values = $this->collectValues();
            $this->saveToDB($values);
        }
	}

	protected function assignArray($array){
		if(!empty($array))
		{
			foreach($array as $key => $val)
			{
				$this->$key = $val;
			}
		}
	}

	protected function error($errorMsg){
		trigger_error($errorMsg, E_USER_ERROR);
	}

	public function setMode($modeOrModeFields)
	{
		if(is_array($modeOrModeFields))
		{
			$modeName = 'dynamic' . md5(serialize($modeOrModeFields));
			$this->modes[$modeName] = array_values($modeOrModeFields);
			$modeOrModeFields = $modeName;
		}
		else
		{
			if(!isset($this->modes[$modeOrModeFields]) && !is_null($modeOrModeFields))
			{
				$this->error('Nonexistent mode: ' . $modeOrModeFields);
			}
		}
		$this->currentMode = $modeOrModeFields;
	}

	protected function getModeVariables($mode = null, $i18n = false)
	{
		if(is_null($mode))
		{
			$mode = $this->currentMode;
		}
        $modeFields = array();
		if (is_array($mode))
		{
		    $modeFields = $mode;
		}
		elseif (isset($this->modes[$mode]))
		{
		    $modeFields = $this->modes[$mode];
		}		
		$variables = array();
		
		if ($i18n)
		{
		    $source = static::getI18nProperties();
		}
		else
		{
		    $source = $this->fieldsDefinition;
		}
		
		foreach($source as $key => $value)
		{
			if(is_numeric($key))
			{
				if(is_array($value))
				{
					$name = $value['name'];
				}
				else
				{
					$name = $value;
				}
			}
			else
			{
				$name = $key;
			}
			if(in_array($name, $modeFields))
			{
				$variables[$key] = $value;
			}
		}
		return $variables;
	}
	
	/*
	* reset internal variable
	*/
	public function reset($name)
	{
		$this->$name = null;
	}

    public static function _autoload( $className )
    {
        if ($className != __CLASS__) // subclasss
        {
			$reflection = new ReflectionClass($className);
			if(!$reflection->isAbstract())
			{
	            self::initFileFields( $className );
			}
        }
        
        $tablesOk = static::registerMainTableDefs();
        if ($tablesOk)
        {
            static::registerI18nTableDef();
        }
        
    }

    public static function registerMainTableDefs()
    {
        if (empty(static::$_tableDefsStr))
        {
            return false;
        }

        $tableDefsStr = static::$_tableDefsStr;
        if (static::hasI18nFields())
        {
            // make sure the main baseobject table is innodb if the class has i18n
            if (!empty($tableDefsStr[static::tableName]))
            {
                $tableDefsStr[static::tableName]['engine'] = 'InnoDB';
            }
        }

        dbRegisterRawTableDefs($tableDefsStr);
        return true;
    }
        
    
    private static function getClassSettingKey( $className, $setting )
    {
        if (is_null($setting))
        {
            return array( self::classSettingsVariableName, $className );
        }

        if (!is_array($setting))
        {
            $setting = array($setting);
        }

        // this line crashes php for some reason:
        // array_unshift($setting, self::classSettingsVariableName, $className );

        $unshifted = array( self::classSettingsVariableName, $className );
        foreach ($setting as $key)
        {
            $unshifted[] = $key;
        }

        return $unshifted;
    }

    protected static function setClassSetting( $className, $setting, $value )
    {
        $settingKey = self::getClassSettingKey( $className, $setting );
        leaf_set($settingKey, $value);
    }

    protected static function getClassSetting( $className, $setting = null )
    {
        $settingKey = self::getClassSettingKey( $className, $setting );
        $value = leaf_get($settingKey);
        return $value;
    }

    /* file field stuff */

    protected static function initFileFields( $className )
    {
        $temp = new $className();
        $fieldDefs = $temp->fieldsDefinition;
        if (!$fieldDefs)
        {
            return;
        }

        $fileFields = array();
        foreach ($fieldDefs as $name => $def)
        {
            if (
                (is_array($def))
                &&
                (!empty($def['type']))
                &&
                ($def['type'] == 'file')
            )
            {
                // set default settings for paths
                if (!isset($def['path']))
                {
                    $def['path'] = $className . '/';
                }

                if (!isset($def['useIdAsFolder']))
                {
                    $def['useIdAsFolder'] = true;
                }
                $def['useIdAsFolder'] = (bool) $def['useIdAsFolder'];

                $fileFields[$name] = $def;
            }
        }
        self::setClassSetting( $className, 'fileFields', $fileFields );
    }

	protected function getFileFieldNames()
	{
	    $fields = self::getClassSetting( get_class($this), 'fileFields');

	    if (empty($fields))
	    {
	        return array();
	    }
	    return array_keys($fields);
	}

    protected function getI18nFileFieldNames()
    {
        $fileFields = array();
        $i18nFieldsDefinition = self::getI18nDefinitions();
        
	    if( empty( $i18nFieldsDefinition ) )
	    {
	        return array();
	    }
        
        foreach( $i18nFieldsDefinition as $fieldName => $fieldDefinition )
        {
            if( get( $fieldDefinition, 'type' ) != 'file' )
            {
                continue;
            }
            
            $fileFields[] = $fieldName;
        }
        
	    return $fileFields;
    }
    
	protected function saveFileFields($filesArray, $fieldDefs)
	{
	    $fileFields = $this->getFileFieldNames();
        
	    $fieldDefs = array_keys($fieldDefs);
	    $fileFields = array_intersect( $fileFields, $fieldDefs);
        
	    if (
            (empty($fileFields))
            ||
            (!is_array($fileFields))
        )
        {
            return true; // file fields saved ok
        }

        foreach ($fileFields as $fieldName)
        {
            $postName = $fieldName . leafFile::inputFieldSuffix;

            if (
                (empty($filesArray[$postName]))
                ||
                (!is_array($filesArray[$postName]))
            )
            {
                continue;
            }

            $uploadData = $filesArray[$postName];

            if (!isset($uploadData['error']))
            {
                continue;
            }

            if ($uploadData['error'] == 4) // no file uploaded
            {
                continue;
            }
            elseif ($uploadData['error'] == 0) // uploaded ok, process file!
            {

                $file = leafFile::create( $uploadData, $this, $fieldName);

                if (!$file)
                {
                    // ..?
                    continue;
                }

                $currentFileId = $this->$fieldName;
                if (!empty($currentFileId))
                {
                    $currentFile = getObject('leafFile', $currentFileId);
                    if ($currentFile)
                    {
                        $currentFile->delete();
                    }
                }

                $this->saveToDB(array(
                    '__standart__' => array(
                        $fieldName => $file->id
                    )
                ));
            }
            else
            {
                // ..?
            }
        }
        return true;
	}

    protected function saveI18nFileFields( $variables, $fieldDefs )
    {
        $fileFields = $this->getI18nFileFieldNames();
	    $fieldDefs = array_keys($fieldDefs);
	    $fileFields = array_intersect( $fileFields, $fieldDefs);
        
	    if (
            (empty($fileFields))
            ||
            (!is_array($fileFields))
        )
        {
            return true; // file fields saved ok
        }
        
        foreach( leafLanguage::getLanguages() as $language )
        {
            $languageCode = $language->code;
            
            foreach( $fileFields as $fieldName )
            {
                $postName = $fieldName . leafFile::inputFieldSuffix;
                
                if (
                    ( empty( $variables[ $languageCode ][ $postName ] ) )
                    ||
                    ( !is_array( $variables[ $languageCode ][ $postName ] ) )
                )
                {
                    continue;
                }
                
                $uploadData = $variables[ $languageCode ][ $postName ];
                
                $errorCode = get( $uploadData, 'error' );
                
                if( $errorCode == 4 ) // no file uploaded
                {
                    continue;
                }
                elseif( $errorCode == 0 ) // uploaded ok, process file!
                {
                    $file = leafFile::create( $uploadData, $this, $fieldName );
                    
                    if( !$file )
                    {
                        continue;
                    }
                    
                    $currentFileId = $this->getI18nValue( $fieldName, $languageCode );
                    
                    if( !empty( $currentFileId ) )
                    {
                        $currentFile = getObject('leafFile', $currentFileId);
                        
                        if( $currentFile )
                        {
                            $currentFile->delete();
                        }
                    }
                    
                    $this->validI18nValues[$languageCode][$fieldName] = $file->id;
                }
            }
        }
        
        return true;
    }
    
	protected function removeDeletedFiles( & $values, $fieldDefs )
	{
	    $fileFields = $this->getFileFieldNames();
	    $fieldDefs = array_keys($fieldDefs);
	    $fileFields = array_intersect( $fileFields, $fieldDefs);

        if (
            (empty($fileFields))
            ||
            (!is_array($fileFields))
        )
        {
            return true; // nothing to remove
        }

        foreach ($fileFields as $fieldName)
        {
            if (
                (!isset($values[$fieldName]))
                ||
                ($values[$fieldName] != '-1')
            )
            {
                continue;
            }
            $this->deleteFile( $fieldName, true );

            $values[$fieldName] = null;
        }
        return true;
	}
    
	protected function removeDeletedI18nFiles( $fieldDefs )
	{
        $fileFields = $this->getI18nFileFieldNames();
	    $fieldDefs = array_keys($fieldDefs);
	    $fileFields = array_intersect( $fileFields, $fieldDefs);
        
        if (
            ( empty( $fileFields ) )
            ||
            ( !is_array($fileFields ) )
        )
        {
            return true;
        }
        
        foreach( leafLanguage::getLanguages() as $language )
        {
            $languageCode = $language->code;
            
            foreach( $fileFields as $fieldName )
            {
                if (
                    (!isset($this->validI18nValues[ $languageCode ][ $fieldName ]))
                    ||
                    ($this->validI18nValues[ $languageCode ][ $fieldName ] != '-1')
                )
                {
                    continue;
                }
                
                $oldFileId = $this->getI18nValue( $fieldName, $languageCode );
                
                if ( isPositiveInt( $oldFileId ) )
                {
                    $file = leafFile::get( get_class($this), $this->id, $oldFileId );
                    
                    if( $file )
                    {
                        $file->delete( $deleteFolderIfLastFile = true );
                    }
                }
                
                $this->validI18nValues[ $languageCode ][ $fieldName ] = null;
            }
        }
        
        return true;
	}

    protected function deleteAllFiles()
    {
        if (empty($this->id))
        {
            return;
        }

        $files = leafFile::getAllByOwner( get_class($this), $this->id );

        // sort files by path level, so that the files in deeper paths get deleted first
        // this is needed to allow deletion of the empty folders in case some files are stored in subfolders
        
        $sortedFiles = array();
        
        foreach ($files as $file )
        {
            $path = $file->getFullPath();
            $numberOfSlashes = substr_count ($path , DIRECTORY_SEPARATOR);
            $sortedFiles[$numberOfSlashes][] = $file;
        }
        krsort( $sortedFiles );
        
        foreach ($sortedFiles as $numberOfSlashes => $files)
        {
            foreach ($files as $file)
            {
                $file->delete( true );
            }
        }

        return;
    }

    protected function deleteFile( $fieldNameOrFileId, $deleteFolderIfLastFile = false )
    {
        if (isPositiveInt($fieldNameOrFileId))
        {
            $fileId = $fieldNameOrFileId;
        }
        else
        {
            $fileId = $this->$fieldNameOrFileId;
        }

        if (empty($fileId))
        {
            return;
        }

        $file = leafFile::get(get_class($this), $this->id, $fileId);
        if (!$file)
        {
            return;
        }

        return $file->delete( $deleteFolderIfLastFile ) ;
    }

    public function getRelativeFilePath( $fileData )
    {
        // by default return class file path

        if (!empty($fileData['ownerObjectField']))
        {
            $fieldSettings = self::getClassSetting( $fileData['ownerClass'], array( 'fileFields', $fileData['ownerObjectField']) );
            if (!$fieldSettings)
            {
                return null;
            }

            $relativePath = $fieldSettings['path'];

            if (
                (!empty($fieldSettings['useIdAsFolder']))
                &&
                (!empty($fileData['ownerObjectId']))
            )
            {
                $relativePath .= $fileData['ownerObjectId'] . '/';
            }

        }
        else
        {
            $relativePath = $fileData['ownerClass'] . '/';
        }

        return $relativePath;
    }

	protected function getFileFieldSettings( $fieldName )
	{
	    $fields = self::getClassSetting( get_class($this), 'fileFields');

	    if (
	       (empty($fields))
	       ||
	       (!isset($fields[$fieldName]))
        )
	    {
	        return null;
	    }
	    return $fields[$fieldName];
	}
	
	public function validateVariables( $variables, $fieldsDefinition = NULL, $mode = false )
	{
        $this->doNotSave = true;
	    $result = $this->variablesSave( $variables, $fieldsDefinition, $mode);
	    $this->doNotSave = false;
	    return $result;
	}
	
	// deprecated
    protected static function registerTableDefs( $tableDefs )
    {
        return dbRegisterTableDefs( $tableDefs );
    }

    // deprecated
    protected static function parseTableDefsStr( $tableDefsStr )
    {
        return dbParseRawTableDefs( $tableDefsStr );
    }

    public static function getQueryIdsFromParams( $params, $multipleIdsParam, $singleIdParam = null)
    {
        // always returns non-empty array with ids
        $ids = array();
        
        if (is_array($params) && (!empty($multipleIdsParam)))
        {

            if (!empty($singleIdParam) && array_key_exists($singleIdParam, $params))
            {
                $params[$multipleIdsParam] = $params[$singleIdParam];
            }        

            if (array_key_exists($multipleIdsParam, $params))
            {
                if (ispositiveint($params[$multipleIdsParam]))
                {
                    $ids = array( $params[$multipleIdsParam] );
                }
                elseif (is_array($params[$multipleIdsParam]))
                {
                    foreach ($params[$multipleIdsParam] as $id)
                    {
                        if (ispositiveint($id))
                        {
                            $ids[] = $id;
                        }
                    }
                }
            }           
        }
        
        if (empty($ids))
        {
            $ids[] = -1;
        }
        
        return $ids;
    }  
}
?>
