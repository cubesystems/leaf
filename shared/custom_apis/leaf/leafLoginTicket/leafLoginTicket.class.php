<?

class leafLoginTicket extends leafBaseObject
{
	const tableName = 'leafLoginTickets';

	protected
	    $userId,
	    $code,
	    $ip,
	    $expires,
        $add_date,
        $author_ip
    ;

	protected $fieldsDefinition = array
	(
        'userId' => array(
            'type' => 'id'
        ),
		'code' => array(),
		'ip' => array('empty_to_null' => true),
		'expires' => array(
            'type' => 'datetime'
		),
	);

    protected static $_tableDefs = array
	(
	    'leafLoginTickets' => array
        (
            'name'   => 'leafLoginTickets',
            'fields' => array(
    			array(
    				'name' => 'id',
    				'type' => 'INT',
                    'auto_increment' => true
    			),
    			array(
    				'name' => 'userId',
    				'type' => 'INT',
    			),
    			array(
    				'name' => 'code',
    				'type' => 'CHAR(40)',
    			),
    			array(
    				'name' => 'ip',
    				'type' => 'CHAR(15)',
    			),
    			array(
    				'name' => 'expires',
    				'type' => 'DATETIME',
    			),
    			array(
    				'name' => 'add_date',
    				'type' => 'DATETIME',
    			),
    			array(
    				'name' => 'author_ip',
    				'type' => 'VARCHAR(255)',
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
    			'codeip' => array(
    				'type' => 'UNIQUE',
    				'name' => 'codeip',
    				'fields' => array(
    					array(
    						'name' => 'code'
    					),
    					array(
    						'name' => 'ip'
    					),
    				)
    			),
    			'expires' => array(
    				'type' => 'INDEX',
    				'name' => 'expires',
    				'fields' => array(
    					array(
    						'name' => 'code'
    					),
    					array(
    						'name' => 'ip'
    					),
    				)
    			),
    		)
        ),
    );



	public static function _autoload( $className )
    {
        parent::_autoload( $className );
        self::registerTableDefs( self::$_tableDefs );
    }

    public static function & create( $userId, $ip = null, $expirationMinutes = 1 )
    {
        self::deleteExpired();

        $code = self::generateNewCode();

        if (!$code)
        {
            return null;
        }

        $params = array
        (
            'userId'    => $userId,
            'code'      => $code,
            'ip'        => $ip,
            'expires'   => date('Y-m-d H:i:s', strtotime('+' . $expirationMinutes . ' minute'))
        );

        $instance = getObject( __CLASS__, 0);
        $instance->variablesSave( $params );

        return $instance;
    }

	public static function getQueryParts($params = array ())
    {
        $queryParts = parent::getQueryParts($params);
        

		if (!empty($params['expired']))
		{
		    $now = date('Y-m-d H:i:s');
		    $queryParts['where'][] = 'expires < "' . dbse($now) . '"';
        }

        return $queryParts;
    }

    public static function getByCodeAndIp($code, $ip)
    {
        $sql = '
            SELECT id
            FROM `' . self::tableName . '`
            WHERE
                `code` = "' . dbse( $code ) . '"
                AND
                ( ip = "' . dbse( $ip ) . '" OR ip IS NULL)
            ';

        $id = dbGetOne($sql);

        if (!$id)
        {
            return null;
        }

        $instance = getObject(__CLASS__, $id);
        if (!$instance)
        {
            return null;
        }

        return $instance;
    }

    public static function deleteExpired()
    {
        $collection = self::getCollection ( array('expired' => true) );
        foreach ($collection as $item)
        {
            $item->delete();
        }
        return;
    }


    public static function generateNewCode()
    {
        // get new unique code
        $safety = 10000;
        do
        {
            $code = self::getRandomCode();
            $safety--;
        }
        while (
            ( self::getIdByCode( $code ) )
            &&
            ($safety)
        )
        ;

        if (!$safety)
        {
            $code = null; // not found
        }
        return $code;
    }

    public static function getRandomCode()
    {
        $code = sha1( str_repeat( uniqid( time() . rand() ) ,  1000 ) );
        return $code;
    }

    public static function getIdByCode($code)
    {
        $sql = 'SELECT id FROM `' . self::tableName . '` WHERE `code` = "' . dbse($code) . '"';
        return dbGetOne($sql);
    }

    public function getData()
    {
        $return = array
        (
            'id'      => $this->id,
            'code'    => $this->code,
            'ip'      => $this->ip,
            'expires' => $this->expires
        );
        return $return;
    }

    public function isExpired()
    {
        return (date('Y-m-d H:i:s') > $this->expires);
    }
}

?>
