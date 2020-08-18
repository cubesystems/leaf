<?
class leafAdminLog extends leafBaseObject
{
	const tableName = 'admin_log';
	// db fields
	protected $request_time, $user_ip, $user_forwarded_ip, $request_uri, $query_string, $request_method, $user_agent, 
			  $http_host, $http_referer, $http_content_type, $http_cookie, 
			  $data_get, $data_post, $data_cookie, $data_files, $data_session, $argv;
	// dynamic properties
	protected $fieldsDefinition = array
	(
		'request_time' => array(),
		'user_ip' => array(),
		'user_forwarded_ip' => array(),
		'request_uri' => array(),
		'query_string' => array(),
		'request_method' => array(),
		'user_agent' => array(),
		'http_host' => array(),
		'http_referer' => array(),
		'http_content_type' => array(),
		'http_cookie' => array(),
		'data_get' => array(),
		'data_post' => array(),
		'data_cookie' => array(),
		'data_files' => array(),
		'data_session' => array(),
		'argv' => array(),
	);
	
	public function get( $property )
	{
		if( property_exists( $this, $property ) )
		{
			$array = unserialize( $this->$property );
			// remove password from array
			foreach( $array as $key => &$item )
			{
				if( strpos( $key, 'password' ) !== false )
				{
					$item = '[ ****** ]';
				}
			}
			return print_r( $array, true );
		}
	}
	
	public static function exists($id)
	{
		if(!is_numeric($id))
		{
			return false;
		}
		$collection = self::getCollection($id);
		if(sizeof($collection) > 0)
		{
			return true;
		}
		return false;
	}
	
	public static function getCollection ($params = array(), $itemsPerPage = NULL, $pageNo = NULL)
	{
		$queryParts['select'][] = 't.*';
		$queryParts['from'][] =  '`' . self::getClassTable(__CLASS__) . '` `t`';
		$queryParts['orderBy'][] = 't.request_time DESC';
		
		if(is_numeric($params))
		{
			$queryParts['where'][] = '`id` = "' . $params . '" ';
		}
		else if (is_array($params))
		{
			
		}
		return new pagedObjectCollection(__CLASS__, $queryParts, $itemsPerPage, $pageNo);
	}
	
}
?>