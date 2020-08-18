<?
class massiveLog 
{
    public $tableName = 'admin_log';
	
	public static $lastInsertId = NULL;
    public $tableDefinition = array
    (
		'name' => 'admin_log',
		'fields' => array(
			array(
				'name' => 'id',
				'type' => 'INT',
                'auto_increment' => true
			),
			array(
				'name' => 'request_time',
				'type' => 'DATETIME',
                'null' => true
			),
			array(
				'name' => 'user_ip',
				'type' => 'VARCHAR(255)',
                'null' => true
			),
			array(
				'name' => 'user_forwarded_ip',
				'type' => 'TEXT'
			),
			array(
				'name' => 'http_host',
				'type' => 'VARCHAR(255)',
                'null' => true
			),
			array(
				'name' => 'request_uri',
				'type' => 'TEXT'
			),
			array(
				'name' => 'query_string',
				'type' => 'TEXT'
			),
			array(
				'name' => 'request_method',
				'type' => 'VARCHAR(255)',
                'null' => true
			),
			array(
				'name' => 'http_referer',
				'type' => 'TEXT'
			),
			array(
				'name' => 'user_agent',
				'type' => 'VARCHAR(255)',
                'null' => true
			),
			array(
				'name' => 'http_content_type',
				'type' => 'VARCHAR(255)',
                'null' => true
			),
			array(
				'name' => 'http_cookie',
				'type' => 'MEDIUMTEXT'
			),
			array(
				'name' => 'data_get',
				'type' => 'LONGTEXT'
			),
			array(
				'name' => 'data_post',
				'type' => 'MEDIUMTEXT'
			),
			array(
				'name' => 'data_cookie',
				'type' => 'MEDIUMTEXT'
			),
			array(
				'name' => 'data_files',
				'type' => 'MEDIUMTEXT'
			),
			array(
				'name' => 'data_session',
				'type' => 'MEDIUMTEXT'
			),
			array(
				'name' => 'argv',
				'type' => 'MEDIUMTEXT'
			),
        ),
		'keys' => array(
			'PRIMARY' => array(
				'type' => 'PRIMARY',
				'name' => 'PRIMARY',
				'fields' => array(
					array(
						'name' => 'id',
					)
				)
			),
			'time_ip' => array(
				'type' => 'INDEX',
				'name' => 'time_ip',
				'fields' => array(
					array(
						'name' => 'request_time',
					),
					array(
						'name' => 'user_ip',
					)
				)
			),
		)
    );

    function log() 
	{
        $this->tableDefinition['name'] = $this->tableName;
        leaf_set(array('tableDefinitions', $this->tableName), $this->tableDefinition);
 
        $data = $this->getDataFromRequest();
        $sql = $this->getSqlQuery($data);
        if (dbQuery($sql)) 
        {
            self::$lastInsertId = dbInsertId();
			return true;
        } 
        else 
        {
            return false;
        }
    }

    function getDataFromRequest() 
	{
        $data = array();
        
        $data['request_time']       = date('Y-m-d H:i:s');
        
        $data['user_ip']            = @$_SERVER['REMOTE_ADDR'];
        $data['user_forwarded_ip']  = @$_SERVER['HTTP_X_FORWARDED_FOR'];

        $data['http_host']          = @$_SERVER['HTTP_HOST'];
        $data['request_uri']        = @$_SERVER['REQUEST_URI'];
        $data['query_string']       = @$_SERVER['QUERY_STRING'];

        $data['request_method']     = @$_SERVER['REQUEST_METHOD'];
        
        $data['http_referer']       = @$_SERVER['HTTP_REFERER'];

        $data['user_agent']         = @$_SERVER['HTTP_USER_AGENT'];
        $data['http_content_type']  = @$_SERVER['CONTENT_TYPE'];   
             
        $data['http_cookie']        = @$_SERVER['HTTP_COOKIE'];
        
        $data['data_get']           = isset($_GET) ? serialize($_GET) : null;
        $data['data_post']          = isset($_POST) ? serialize($_POST) : null;
        $data['data_cookie']        = isset($_COOKIE) ? serialize($_COOKIE) : null;
        $data['data_files']         = isset($_FILES) ? serialize($_FILES) : null;
        $data['data_session']       = isset($_SESSION) ? serialize($_SESSION) : null;

        $data['argv']               = (isset($_SERVER['argv'])) ? serialize($_SERVER['argv']) : null;
        
        return $data;
    }
    
    function getSqlQuery($data) 
	{
                
        $fieldList = $valueList = array();
        
        foreach ($data as $fieldName => $value) 
        {
            if (is_null($value)) {
                $value = 'NULL';
            } 
            else 
            {
                $value = '"' . dbse( $value ) . '"';
            }
            $fieldList[] = '`' . $fieldName . '`';
            $valueList[] = $value;
        }
        $fieldList = implode (', ', $fieldList);
        $valueList = implode (', ', $valueList);
        $sqlQuery = '
            INSERT INTO
                `' . $this->tableName . '`
                (' . $fieldList . ')
                VALUES
                (' . $valueList .  ')
        ';
        return $sqlQuery;
    }

}


?>
