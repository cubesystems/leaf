<?php
class embedObject extends leafBaseObject
{
	const tableName = 'embedObjects';
	const defaultWmode = 'transparent';
	// db fields
	protected $add_date, $embedCode, $objectId, $source;
	
	public static $appendWmode = false;
	
	protected $fieldsDefinition = array
	(
		'embedCode' => array( 'optional' => true ),
		'objectId'    => array( 'optional' => true ),
		'source'    => array( 'not_empty' => true ),
	);
	
	public function getHtml()
	{
		switch( $this->source )
		{
			case 'embedCode':
				return $this->embedCode;
			break;
			case 'chooseFromTree':
				return $this->getObjectHtml();
			break;
		}
	}
	
	public function getObjectHtml()
	{
		if( !empty( $this->objectId ) )
		{
			$fakeSmarty = '';
			require_once(SHARED_PATH . 'classes/smarty_plugins/function.banner.php');
			return smarty_function_banner( array( 'objectId' => $this->objectId ), $fakeSmarty );
		}
	}
	
	public static function exists( $id )
	{
		if( !is_numeric( $id ) )
		{
			return false;
		}
		$collection = self::getCollection( $id );
		if( count( $collection ) > 0)
		{
			return true;
		}
		return false;
	}
	
	public static function getCollection( $params = array(), $itemsPerPage = NULL, $pageNo = NULL )
	{
		$queryParts['select'][] = 't.*';
		$queryParts['from'][] =  '`' . self::getClassTable( __CLASS__ ) . '` `t`';
		$queryParts['orderBy'][] = 't.add_date DESC';
		
		if( is_numeric( $params ) )
		{
			$queryParts['where'][] = '`id` = "' . $params . '" ';
		}
		else if( is_array( $params ) )
		{
			
		}
		return new pagedObjectCollection( __CLASS__, $queryParts, $itemsPerPage, $pageNo );
	}
	
	public static function replacePlaceholders( $html, $appendWmode = false )
	{
		self :: $appendWmode = $appendWmode;
		$pattern = '/<img[^>]*class="([^"]*embedObject[^"]*)[^>]*>/i';
		return preg_replace_callback( $pattern, array( __CLASS__, 'replacePlaceholdersCallback' ), $html);
	}
	
	public static function replacePlaceholdersCallback( $matches )
	{
		$classNames = explode( ' ', $matches[1] );
		$id = false;
		foreach( $classNames as $className )
		{
			$parts = explode( '-', $className );
			if( $parts[0] == 'id' )
			{
				$id = $parts[1];
			}
		}
		$item = getObject( __CLASS__, $id );
		
		$return = $item->getHtml();
		if (self :: $appendWmode)
		{
		    $return = self :: appendWmode( $return );
		}
		return $return;
	}
	
	public static function appendWmode($code, $wmode = self :: defaultWmode)
	{
        if (preg_match('/<param name="wmode"(?:.*?\/>|.*?>.*?<\/param>)/', $code))
        {
            $code = preg_replace('/(<param name="wmode".*?value=")(?:.+?)("\s*\/>|".*?>.*?<\/param>)/', '$1' . $wmode . '$2', $code);
        }
        else
        {
            $code = preg_replace('/(<object.*?>)/', '$1' . '<param name="wmode" value="' . $wmode . '"></param>', $code);
        }
        if (preg_match('/<embed(.+?)wmode="(.+)"(?:.*?\/>|.*?>.*?<\/embed>)/', $code))
        {
            $code = preg_replace('/(<embed.+?wmode=")(?:.+?)("\s*\/>|".*?>.*?<\/embed>)/', '$1' . $wmode . '$2', $code);
        }
        else
        {
            $code = preg_replace('/(<embed)/', '$1' . ' wmode="' . $wmode . '"', $code);
        }
        return $code;
	}
}
?>