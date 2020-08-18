<?php
class aliasSingleton
{
	protected $fieldsDefinition = array();
	
	protected $postChecks = array();
	
	protected $dieOnError = true;
	
	protected $definitions;
	
	protected $properties;
	
	protected $context;
	
	protected $contextDelimiter;
	
	protected $htmlWrap = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><meta http-equiv="content-type" content="text/html; charset=UTF-8"></head><body>[$body]</body></html>';
	
	protected $language; // id or code
	
	/** 
	 * properties needed for caching
	 */
	public $cache = array();
	protected $cacheInsertionTimes = array();
	
	/********************* constructors *********************/
	
	public function __construct( $initData = NULL )
	{
		$this->context = 'singleton:' . get_class( $this );
		$this->contextDelimiter = chr(0);
		
		// construct properties
		$languages = self::getLanguages();
		foreach( $this->fieldsDefinition as $name => $definition )
		{
			foreach( $languages as $languageId => $languageCode )
			{
				if( !isset( $this->properties[ 'i18n:' . $languageCode . ':' . $name ] ) )
				{
					$this->properties[ 'i18n:' . $languageCode . ':' . $name ] = array
					(
						'name' 		   => $name,
						'languageCode' => $languageCode,
					);
				}
			}
		}
	}
	
	/********************* setters *********************/
	
	
	
	/********************* set methods *********************/
	
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
	
	public function setLanguage( $idOrCode )
	{
		$this->language = $idOrCode;
	}
	
	/********************* get methods *********************/
	
	public function __get( $name )
	{
		return $this->getDefinitionFrom( $name );
	}
	
	/* public function __call($methodName, $parameters)
	{
		if (isset($this->i18nProperties))
		{
			list ($memberName, ) = sscanf($methodName, 'get%s');
			$memberName = strtolower(substr($memberName, 0, 1)) . substr($memberName, 1);
			if (array_key_exists($memberName, $this->i18nProperties))
			{
				array_unshift($parameters, $memberName);
				return call_user_func_array(array ($this, 'getI18NValue'), $parameters);
			}
		}
		trigger_error('Undefined method: [' . __CLASS__ . '::' . $methodName . ']', E_USER_ERROR);
	} */
	
	public function getDisplayString()
	{
		return;
	}
	
	public function getContext()
	{
		return $this->context;
	}
	
	public function getDefinition( $name, $languageCode )
	{
		if( $this->hasProperty( $name ) )
		{
			if( !isset( $this->definitions[ $name ] ) )
			{
				$this->definitions[ $name ] = array();
			}
			if( !isset( $this->definitions[ $name ][ $languageCode ] ) )
			{
				$languageId = self::getLanguageIdFrom( $languageCode );
				$q = '
					SELECT 
						d.translation
					FROM
						translations_data d
					LEFT JOIN
						translations t ON t.id=d.translation_id
					WHERE
						t.name="' . $this->getContext() . $this->contextDelimiter . dbSE( $name ) . '"
						AND
						d.language_id="' . $languageId . '"
				';
				$this->definitions[ $name ][ $languageCode ] = dbGetOne( $q );
			}
			return $this->definitions[ $name ][ $languageCode ];
		}
	}
	
	public function getDefinitionFrom( $fullName )
	{
		if( isset( $this->properties[ $fullName ] ) )
		{
			return $this->getDefinition( $this->properties[ $fullName ]['name'], $this->properties[ $fullName ]['languageCode'] );
		}
	}
	
	public function getProduct( $name, $variables = array(), $language = NULL )
	{
		if( !$this->hasProperty( $name ) )
		{
			return;
		}
		if( $language === NULL )
		{
			$language = $this->language;
		}
		$key = 'products_' . sha1( $name . serialize( $variables ) . $language . $this->getContext() );
		if( !$this->hasInCache( $key ) )
		{
			$alias = alias_cache::getAlias( $name, $this->getContext(), false, $language, null, $variables );
			$this->storeInCache( $key, $alias );
		}
		return $this->getFromCache( $key );
	}
	
	protected function getI18NFieldsDefinition()
	{
		$languageList = self::listI18NLanguages();
		foreach( $this->fieldsDefinition as $propertyName => $meta )
		{
			foreach( $languageList as $language )
			{
				$fieldsDefinition[ 'i18n:' . $language . ':' . $propertyName ] = $meta;
			}
		}
		return $fieldsDefinition;
	}
	
	public function getI18NValue( $name, $language = NULL )
	{
		if( $language === NULL )
		{
			$language = self::getCurrentLanguage();
		}
		
		return $this->getDefinition( $name, $language );
	}
	
	public function getGroupId()
	{
		$q = 'SELECT id FROM translations_groups WHERE name="' . $this->getContext() . '"';
		$id = dbGetOne( $q );
		if( !$id )
		{
			$q = 'INSERT INTO translations_groups(name) VALUES("' . $this->getContext() . '")';
			dbQuery( $q );
			$id = dbInsertId();
		}
		return $id;
	}
	
	public function wrapHtml( $body )
	{
		return str_replace( '[$body]', $body, $this->htmlWrap );
	}
	
	/********************* boolean methods *********************/
	
	public function hasProperty( $name )
	{
		if( in_array( $name, array_keys( $this->fieldsDefinition ) ) )
		{
			return true;
		}
		return false;
	}
	
	/********************* internal updating methods *********************/
	
	/**
	 * fieldsDefinition and saveMode are not currently implemented
	 */
	public function variablesSave( $variables, $fieldsDefinition = NULL, $saveMode = NULL )
	{
		$p = new processing;
		if( $this->dieOnError == false )
		{
			$p->error_cast = 'return';
		}
		foreach( $this->postChecks as $checkMethod )
		{
			if( !empty( $checkMethod ) )
			{
				if( is_array( $checkMethod ) && !empty( $checkMethod[0] ) && $checkMethod[0] === '$this' )
				{
					$checkMethod[0] = $this;
				}
				$p->addPostCheck( $checkMethod );
			}
		}
		$p->setVariables( $variables );
		
		$fieldsDefinition = $this->getI18NFieldsDefinition();
		
		if( !empty( $variables['getValidationXml'] ) )
		{
			$p->getValidationXml( $fieldsDefinition );
		}
		$values = $p->check_values( $fieldsDefinition );
		if( $this->dieOnError == false && isset( $p->errorCode ) )
		{
			return $p;
		}
		
		// group id
		$groupId = $this->getGroupId();
		
		//clear out old data
		$q = '
			DELETE 
				d.*, t.*
			FROM 
				translations_data d
			LEFT JOIN
				translations t ON d.translation_id=t.id
			LEFT JOIN
				translations_groups g ON g.id=t.group_id
			WHERE
				g.id = "' . $groupId . '"
		';
		dbQuery( $q );
		
		$prefix = $this->getContext() . $this->contextDelimiter;
		
		// insert new
		foreach( $this->fieldsDefinition as $name => $definition )
		{
			$q = '
				INSERT INTO 
					translations(group_id,name,type)
				VALUES
					("' . $groupId . '","' . $prefix . $name . '",0)
			';
			dbQuery( $q );
			$translationId = dbInsertId( dbGetLink() );
			foreach( self::getLanguages() as $languageId => $languageCode )
			{
				$value = '';
				if( isset( $variables[ 'i18n:' . $languageCode . ':' . $name ] ) )
				{
					$value = $variables[ 'i18n:' . $languageCode . ':' . $name ];
				}
				$q = '
					INSERT INTO
						translations_data(translation_id,language_id,translation)
					VALUES
						("' . $translationId . '","' . $languageId . '","' . dbSE( $value ) . '")
				';
				dbQuery( $q );
			}
		}
		
		return true;
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
	
	/********************* static methods *********************/
	
	// get
	
	public static function listI18NLanguages()
	{
		$sql = '
			SELECT
				`code`
			FROM
				`languages`
		';
		return dbGetAll($sql, null, 'code');
	}
	
	public static function getLanguages()
	{
		$sql = '
			SELECT
				`id`,`code`
			FROM
				`languages`
		';
		$result = dbGetAll( $sql );
		$languages = array();
		foreach( $result as $row )
		{
			$languages[ $row['id'] ] = $row['code'];
		}
		return $languages;
	}
	
	public static function getLanguageIdFrom( $code )
	{
		$languages = self::getLanguages();
		$languages = array_flip( $languages );
		return $languages[ $code ];
	}
	
	public static function getCurrentLanguage()
	{
		$language = leaf_get_property('language_code');
		return $language;
	}
}
?>