<?php


class leafEvent extends leafBaseObject
{
	const tableName = 'leafEventLog';

	const T_INFO    = 'info';
	const T_WARNING = 'warning';
	const T_ERROR   = 'error';

    const defaultSource = 'leaf';
    const defaultType   = self::T_INFO;

	protected $type, $source, $message, $details, $data, $ip, $datetime, $processId;

	protected static $activeSource = null;
	protected static $sourceStack  = array();

    protected static $currentProcessId = null;
	protected static $lastInsertId = null;

	protected $fieldsDefinition = array
	(
		'type'      => array( 'not_empty' => true ),
		'source'    => array( 'not_empty' => true ),
		'message'   => array( ),
		'details'   => array( ),
		'data'      => array( ),
		'ip'        => array( ),
		'datetime'  => array( ),
		'processId' => array( )
	);

	protected $postChecks = array
	(
	    'type'       => array('$this', 'typeValidator'),
	);


    protected static $_tableDefsStr = array
	(
        self::tableName => array
        (
            'fields' =>
            '
                id          int unsigned auto_increment
                type        enum(<types>)
                source      varchar(32)
                message     text
                details     longtext
                data        longtext
                ip          varchar(255)
                datetime    datetime
                processId   varchar(32)
            '
            ,
            'indexes' => '
                primary id
				index type
				index source
            '
        )
    );

	public static function _autoload( $className )
    {
        $typeString = '"' . self::T_INFO . '","' . self::T_WARNING . '","' . self::T_ERROR . '"';
        self::$_tableDefsStr[self::tableName]['fields'] = str_replace('<types>', $typeString, self::$_tableDefsStr[self::tableName]['fields']);

        parent::_autoload( $className );
        dbRegisterRawTableDefs( self::$_tableDefsStr );
    }

    public static function slog( $source, $message, $details = null, $type = null, $data = null)
    {
        return self::log( $message, $details, $type, $data, $source  );
    }

    public static function log( $message, $details = null, $type = null, $data = null, $source = null )
    {

        if (
            (empty($type))
            ||
            (!self::isTypeValid($type))
        )
        {
            $type = self::getDefaultType();
        }


        if (
            (!empty($details))
            &&
            (!is_scalar($details))
        )
        {
            $details = print_r($details, true);
        }


        if (!is_null($data))
        {
            $data = serialize( $data );
        }


        if (is_null($source))
        {
            $source = self::getActiveSource();
        }


        $ip = null;
        if (isset($_SERVER['REMOTE_ADDR']))
        {
            $ip = $_SERVER['REMOTE_ADDR'];

            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            {
                $ip .= ' (' . $_SERVER['HTTP_X_FORWARDED_FOR'] . ')';
            }
        }


        $vars = array
        (
    		'type'      => $type,
    		'source'    => $source,
    		'message'   => $message,
    		'details'   => $details,
    		'data'      => $data,
    		'ip'        => $ip,
    		'datetime'  => 'NOW()',
    		'processId' => self::getCurrentProcessId()
        );

        self::$lastInsertId = dbInsert( self::getClassTable(__CLASS__), $vars, null, array('datetime') );
        return self::$lastInsertId;
    }

    public static function getLastLogEntry()
    {
        if (empty(self::$lastInsertId))
        {
            return null;
        }

        $q = '
            SELECT * FROM
            `' . self::getClassTable(__CLASS__) . '`
            WHERE id = ' . (int) self::$lastInsertId
        ;
        $row = dbGetRow($q);
        return $row;
    }

    public static function setSource( $name )
    {
        $currentSource = end( self::$sourceStack );
        if ($name == $currentSource)
        {
            return;
        }

        self::$sourceStack[] = $name;
    }

    public static function unsetSource( )
    {
        $currentSource = array_pop( self::$sourceStack ); // remove last item

        if (empty( self::$sourceStack ))  // insert default if empty
        {
            self::setSource( self::defaultSource );
        }
        $source = end ( self::$sourceStack );

    }

    public static function getActiveSource()
    {
        if (empty(self::$sourceStack))
        {
            self::setSource( self::defaultSource );
        }
        return end( self::$sourceStack );
    }

    public static function getTypes()
    {
        return dbGetEnumValuesFromTableDef( self::tableName, 'type');
    }

    protected static function getDefaultType()
    {
        $types = self::getTypes();
        reset ( $types );
        return current( $types );
    }

    public static function isTypeValid ( $type )
    {
        $types = self::getTypes();
        return (in_array( $type, $types ));
    }


	public function typeValidator( $values )
	{
	    if (!empty($values['type']))
	    {
            if (self::isTypeValid($values['type']))
            {
                return true;
            }
        }
        $error['field'] = array('name' => 'type');
		$error['errorCode'] = 'type-invalid';
        return $error;

	}

	protected static function getCurrentProcessId()
	{
	    if (empty(self::$currentProcessId))
	    {
	        self::$currentProcessId = uniqid('', true);
	    }
	    return self::$currentProcessId;
	}

}




