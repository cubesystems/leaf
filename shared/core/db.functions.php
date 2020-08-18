<?

define( 'MYSQL_ERROR_COLUMN_NOT_FOUND',     '42S22' );
define( 'MYSQL_ERROR_TABLE_NOT_FOUND',      '42S02' );
define( 'MYSQL_ERROR_CONNETION_LOST',       'HY000' );
define( 'MYSQL_ERROR_DUPLICATE_KEY_ENTRY',  '23000' );
define( 'DB_QUERY_TYPE_READ',               'read' );
define( 'DB_QUERY_TYPE_WRITE',              'write' );
define( 'DB_RESOURCE_TYPE',                 'mysql link' );


function dbBuildQuery( $queryParts )
{
	// convert to string
    if (!empty($queryParts['union']))
    {
        $unionParts = array();
        foreach($queryParts['union'] as $unionQueryPart)
        {
            $unionParts[] = dbBuildQuery($unionQueryPart);
        }
        $queryParts['union'] = $unionParts;
    }
	// verify that required parts are not empty
    else if (
        (empty($queryParts['select']))
        ||
        (empty($queryParts['from']))
    )
    {
        return null;
    }

	// define 0 => prefix keyword, 1 => glue, 2 => callback function and 3 => suffix for each part of the query
	// add spaces and newlines for better readability
	$parts = array(
	   'union'      => array("(",               ") UNION (" , null ,  ")"  ),
	   'select'     => array( "SELECT\n    ",               ",\n    "),
	   'from'       => array( "FROM\n    ",                 ",\n    "),
	   'leftJoins'  => array( "    LEFT JOIN\n        ",    "\n    LEFT JOIN\n        "),
	   'rightJoins' => array( "    RIGHT JOIN\n    ",       "\n    RIGHT JOIN\n    "),
	   'innerJoins' => array( "    INNER JOIN\n    ",       "\n    INNER JOIN\n    "), 
	   'where'      => array( "WHERE\n    (",               ")\n    AND\n    ("          , null, ")"),
	   'groupBy'    => array( "GROUP BY\n    ",             ",\n    "                     ),
       'having'     => array( "HAVING\n    (",              ")\n       AND\n    (", null, ")"),
	   'orderBy'    => array( "ORDER BY\n    ",             ",\n    "),
    );

    $partStrings = array();
    foreach( $parts as $partName => $part )
    {
        if( empty( $queryParts[$partName] ) )
        {
            continue;
        }
        
        $prefix     = $part[0];
        $glue       = $part[1];
        $callback   = (!empty($part[2])) ? $part[2] : null;
        $suffix     = (empty($part[3])) ? '' : $part[3];
        
        $partString = $prefix . getArgumentAsString( $queryParts[$partName], $glue, $callback ) . $suffix;
        $partStrings[] =  $partString ;
    }

    $q = implode( "\n", $partStrings );


	if( !empty( $queryParts['orderBy'] ) && !empty( $queryParts['order'] ) )
	{
		$allowedOrders = array( 'asc', 'desc' );
		if( in_array( strtolower( $queryParts['order'] ), $allowedOrders ) )
		{
			$q .= "\n    " . dbSE( $queryParts['order'] );
		}
	}
	if( !empty( $queryParts['limit'] ) )
	{
		$q .= "\nLIMIT\n    ";
		if (
            (is_array($queryParts['limit']))
            &&
            (sizeof($queryParts['limit']) == 2)
        )
		{
		    reset( $queryParts['limit'] );

            $queryParts['limitStart'] = current($queryParts['limit']);
            $queryParts['limit']      = next($queryParts['limit']);
		}

		if(isset($queryParts['limitStart']))
		{
			$q .= intval($queryParts['limitStart']) . ', ';
		}
		$q .= intval($queryParts['limit']);
	}
    
	return $q;
}

function dbInsert($tableName, $fieldsOrRows, $trigerSql = NULL, $skipQuotes = array(), $dbLinkName = NULL, $replaceMode = false, $ignoreSqlErrors = false)
{
	$fieldsOrRowsErrorMessage = 'Processing error: invalid or empty $fieldsOrRows argument. ';
	$fieldsOrRowsErrorLevel = E_USER_ERROR;
	if ($trigerSql)
	{
		$trigerReturn = dbGetOne($trigerSql, false, $dbLinkName);
		if(!$trigerReturn)
		{
			return;
		}
	}

	if (
		(!is_array($fieldsOrRows))
		||
		(empty($fieldsOrRows))
	)
	{
		trigger_error($fieldsOrRowsErrorMessage, $fieldsOrRowsErrorLevel);
		return false;
	}
	if (is_array(current($fieldsOrRows)))
	{
		// multiple rows
		$rows = $fieldsOrRows;
		$exampleRow = current($fieldsOrRows);
		if (
			(!is_array($exampleRow))
			||
			(empty($exampleRow))
		)
		{
			trigger_error($fieldsOrRowsErrorMessage, $fieldsOrRowsErrorLevel);
			return false;
		}
	}
	else
	{
		// one row
		$exampleRow = $fieldsOrRows;
		$rows = array( $fieldsOrRows );

	}
	$keys = array_keys($exampleRow);

	// concatenate row values
	foreach ($rows as $key => $row)
	{
		if (
			(!is_array($row))
			||
			(empty($row))
		)
		{
			trigger_error($fieldsOrRowsErrorMessage, $fieldsOrRowsErrorLevel);
			return false;
		}

		// add quotes
		foreach ($row as $fieldKey => $fieldValue)
		{
			//check for array and serialize it
			if(is_array($fieldValue))
			{
				$row[$fieldKey] = serialize($fieldValue);
			}
			// check for NULL
			if($row[$fieldKey] === NULL)
			{
				$row[$fieldKey] = 'NULL';
			}
			else
			{
				// add escape
				$row[$fieldKey] = dbSE($row[$fieldKey], $dbLinkName, DB_QUERY_TYPE_WRITE);
				//add quotes
				if (!in_array($fieldKey, $skipQuotes))
				{
					$row[$fieldKey] = '"' . $row[$fieldKey] . '"';
				}
			}
		}
		$rows[$key] = implode(', ', $row);
	}

	$mode = ($replaceMode == true ? 'REPLACE' : 'INSERT');
	$q = '
	' . $mode . '
	INTO
		`' . $tableName . '`
	(
		`' . implode('`,`',$keys) . '`
	)
	VALUES
	(
		' . implode('), (', $rows) . '
	)
	';
   
	$r = dbQuery($q, $dbLinkName, null, null, false, $ignoreSqlErrors);
	if (($replaceMode == true) || ($r === false))
	{
		return $r;
	}
    else
	{
		return dbInsertId($dbLinkName);
	}
}

function dbReplace($tableName, $fieldsOrRows, $trigerSql = NULL, $skipQuotes = array(), $dbLinkName = NULL)
{
	return dbInsert($tableName, $fieldsOrRows, $trigerSql, $skipQuotes, $dbLinkName, true);
}

function dbUpdate($tableName, $fields, $where, $skipQuotes = array(), $dbLinkName = NULL)
{
	//check for variables
	if(empty($fields))
	{
		return false;
	}
	$q = '';
	foreach ($fields as $fieldKey => $fieldValue)
	{
		//check for array and serialize it
		if(is_array($fields[$fieldKey]))
		{
			$fields[$fieldKey] = serialize($fields[$fieldKey]);
		}
		//check for NULL and maintain $skipQuotes
		if($fields[$fieldKey] === NULL)
		{
			$fields[$fieldKey] = 'NULL';
		}
		else
		{
			$fields[$fieldKey] = dbSE($fields[$fieldKey], $dbLinkName, DB_QUERY_TYPE_WRITE);
			//add quotes
			if (!in_array($fieldKey, $skipQuotes))
			{
				$fields[$fieldKey] = '"' . $fields[$fieldKey] . '"';
			}
		}
		$q .= ($q ? ', ' : '') . '`' . $fieldKey . '`=' . $fields[$fieldKey];
	}
	//detect where
	if(isPositiveInt($where))
	{
		$key = 'id';
		$value = $where;
		$whereQuery = '`' . $key . '` = "' . $value . '"';
	}
	else if(is_array($where))
	{
		$key =  key($where);
		$value = dbSE(current($where), $dbLinkName, DB_QUERY_TYPE_WRITE);
		$whereQuery = '`' . $key . '` = "' . $value . '"';
	}
	else
	{
		$whereQuery = $where;
	}
	$q = '
        UPDATE
            `' . $tableName . '`
        SET
            ' . $q . '
        WHERE
	' . $whereQuery;
	dbQuery($q, $dbLinkName);
}

function dbDelete($tableName, $where = NULL, $dbLinkName = NULL)
{
	//detect where
	if(isPositiveInt($where))
	{
		$key = 'id';
		$value = $where;
		$whereQuery = '`' . $key . '` = "' . $value . '"';
	}
	else if(is_array($where))
	{
		$key =  key($where);
		$value = dbSE(current($where), $dbLinkName, DB_QUERY_TYPE_WRITE);
		$whereQuery = '`' . $key . '` = "' . $value . '"';
	}
	else
	{
		$whereQuery = $where;
	}
	$q = '
        DELETE
        FROM
            `' . $tableName . '`
	';
	if (!is_null($where))
	{
		$q .= 'WHERE ' . $whereQuery;
	}
    
	dbQuery($q, $dbLinkName);
}


function dbListQueryTypes()
{
    return array(
        DB_QUERY_TYPE_WRITE,
        DB_QUERY_TYPE_READ
    );
}

function dbConnect( $dbConf, $dbLinkName, $queryType = null )
{
    try
    {
        $dbLink = leafPDO::connect( $dbConf );
    }
    catch( PDOException $ex )
    {
        trigger_error( 'DB Error:' . $ex->getMessage(), E_USER_ERROR );
    }
    
    $queryTypes = ( $queryType ) ? array( $queryType ) :  dbListQueryTypes();

    foreach( $queryTypes as $queryType )
    {
        leaf_set( array( '_db', $dbLinkName, $queryType ), $dbLink );
    }
}

function dbChange($dbName, $dbLinkName = NULL, $queryType = null)
{
    $queryTypes = ($queryType) ? array( $queryType ) :  dbListQueryTypes();

    foreach( $queryTypes as $queryType )
    {
        $dbLink = dbGetLink( $dbLinkName, true, $queryType );
        if( !mysql_select_db( $dbName, $dbLink ) )
        {
            trigger_error( mysql_errno( $dbLink ) . ': ' . mysql_error( $dbLink ), E_USER_ERROR );
        }
    }
}

function dbGetLinkName( $dbLink )
{
	$dbLinks = leaf_get('_db');
    
	foreach ($dbLinks as $dbLinkName => $dbLinkTypes)
	{
        foreach ($dbLinkTypes as $queryType => $queryTypeDbLink)
        {
            if ($queryTypeDbLink == $dbLink)
            {
                return $dbLinkName;
            }
        }
	}
    
    trigger_error('DB link name not found for ' . (string) $dbLink, E_USER_ERROR);
}

function dbIsConnected( $dbLinkName = null, $queryType = null)
{
    $queryTypes = ($queryType) ? array( $queryType ) :  dbListQueryTypes();    
    
    foreach( $queryTypes as $queryType )
    {
        $dbLink = dbGetLink( $dbLinkName, false, $queryType);
        
        if( !is_object( $dbLink ) )
        {
            return false;
        }
    }
    
    return true;
}

function dbGetDbName( $dbLinkName, $queryType = null)
{
    $config = dbGetConfig( $dbLinkName );
    
    if ($config && isset($config['database']))
    {
        return $config['database'];
    }
    
    if ($config && $queryType && !empty($config[$queryType]) && isset($config[$queryType]['database']))
    {
        return $config[$queryType]['database'];
    }
   
    trigger_error('DB Error: unknown database name in configuration for link:' . $dbLinkName, E_USER_ERROR);
}

function dbGetConfig( $dbLinkName )
{
    $mainConfig = leaf_get('properties', 'dbconfig');
    
    if ((is_array($mainConfig)) && !empty($mainConfig))
    {
        // in case of one db, the main config array already contains access data without deeper structure
        if (($dbLinkName == 'db') && (!isset($mainConfig[$dbLinkName])))
        {
            $config = $mainConfig;
        }
        else
        {
            $config = get($mainConfig, $dbLinkName);
        }

        if (is_array($config))
        {
            // config may contain access data 
            if (isset($config['username']))
            {
                return $config;
            }
            
            // or arrays of access data for each query type
            if (
                (!empty($config[DB_QUERY_TYPE_READ])) 
                && 
                (!empty($config[DB_QUERY_TYPE_WRITE]))
            )
            {
                return $config;
            }
        }

    }
    
	trigger_error('DB Error: db configuration not found for link:' . $dbLinkName, E_USER_ERROR);
}

function dbGetLink( $dbLinkName = null, $autoconnect = true, $queryType = null )
{
    if( is_object( $dbLinkName ) )
	{
        $dbLinkName = dbGetLinkName( $dbLinkName );
	}
    
	if( is_null( $dbLinkName ) )
	{
		$dbLinkName  = 'db';
	}
    
    if( is_null( $queryType ) )
    {
        $queryType = DB_QUERY_TYPE_WRITE;
    }
    
    $separateQueriesByType = dbGetQuerySeparation( $dbLinkName );
    
    if( $separateQueriesByType === false )
    {
        // query type separation is turned off for this db link
        // use 'write' connection for all queries
        $queryType = DB_QUERY_TYPE_WRITE;
    }
    
    
	$dbLink = leaf_get( '_db', $dbLinkName, $queryType );
    
    // link does not exist, look in config and try to connect if needed
	if( !$dbLink && $autoconnect )
	{
        // default case
		$configOptions = dbGetConfig( $dbLinkName );
        
		if( $configOptions )
		{
            if( isset( $configOptions['username'] ) ) // same config array for both reads and writes
            {
                dbConnect( $configOptions, $dbLinkName );    
                dbSetQuerySeparation( false, $dbLinkName );
            }
            else // separate links for reads and writes
            {
                $queryTypes = dbListQueryTypes();
                foreach( $queryTypes as $type )
                {
                    $config = get( $configOptions, $type );
                    dbConnect( $config, $dbLinkName, $type );
                }
                dbSetQuerySeparation( true, $dbLinkName );
            }
		}
		else
		{
	       trigger_error('DB Error: db configuration not found for link "' . $dbLinkName . '"', E_USER_ERROR);			
		}
        
        $dbLink = leaf_get( '_db', $dbLinkName, $queryType );
	}
    
	return $dbLink;
}


function dbSetQuerySeparation( $onOrOff , $dbLinkName = null )
{
	if( is_null( $dbLinkName ) )
	{
		$dbLinkName  = 'db';
	}
    
    leaf_set( array( '_db', $dbLinkName, 'separateQueriesByType' ), (bool) $onOrOff );
}

function dbGetQuerySeparation( $dbLinkName = null )
{
	if( is_null( $dbLinkName ) )
	{
		$dbLinkName  = 'db';
	}
    
    return leaf_get( array( '_db', $dbLinkName, 'separateQueriesByType' ) );
}

function dbClose( $dbLinkName = null)
{

}

function dbGetRow( $q, $dbLinkName = null )
{
    if( is_array( $q ) && !isset( $q['limit'] ) )
    {
        $q['limit'] = 1;
    }
    
	$query = dbQuery( $q, $dbLinkName );
	$result = $query->fetch();
	
	if( sizeof( $result ) < 1 )
	{
        return null; 
	}
    
    return $result;    
}

function dbGetOne( $q, $advanced = false, $dbLinkName = null )
{
	if (is_array($q))
	{
        // add/fix limit in query parts
        if (
            (isset($q['limit']))
            && 
            (is_array($q['limit']))
            &&
            (count($q['limit']) == 2)
        )
        {
            $q['limit'][1] = 1;
        }
        else
        {
            $q['limit'] = 1;
        }

		$q = dbBuildQuery($q);
	}
    else
    {
        // always add to queries passed in as strings (this could be improved)
        $q .= ' LIMIT 1';
    }
    
	if (!$advanced)
	{
		$query = dbQuery( $q, $dbLinkName );
		$row = $query->fetch();
        
		if( !$row )
		{
            return false;
		}
	}
	else
	{
	    $row = dbGetRow( $q, $dbLinkName );
        
	    if( !$row )
	    {
	        return null;
	    }
	}
    
    return reset($row);    
}

function dbGetTableDef( $q, $dbLinkName = NULL )
{
	$keyWords = 'SELECT|FROM|UPDATE|REPLACE|INSERT|VALUES|LEFT|RIGHT|INNER|OUTER|ON|OR|AND|WHERE|GROUP|HAVING|ORDER|LIMIT|SET|LOCK(\s)*TABLES|WRITE';
	$pattern = '/(?<=\s|^)(' . $keyWords . ')(\s)(.*?)(\s)(?=' . $keyWords . '|$)/is';

	preg_match_all( $pattern, $q . ' ', $result );

	$processedResult = array();
	foreach ($result[0] as $key => $value)
	{
		$value = preg_replace('/\n|\r/', ' ', $value);
		$value = preg_replace('/\s{2,}/', ' ', $value);
		$processedResult[] = $value;
	}
    
	//get tables from query
	$tables = array();
	foreach( $processedResult as $part )
	{
		//detect from "FROM" string
		if(strtoupper(substr($part, 0, 4)) == 'FROM')
		{
            $tmp = trim(str_replace('`', ' ', $part));
			$tmp = trim( preg_replace('/(?<=\s|^)(FROM\s+)/iu', '', $tmp));
			$tmp = explode(',', $tmp);
			foreach($tmp as $tmpTable)
			{
				$tmpTable = explode(' ', $tmpTable);
				if($tmpTable[0])
				{
					$tables[$tmpTable[0]] = $tmpTable[0];
					continue;
				}
			}
		}
		//detect from "LEFT JOIN" string
		if( strtoupper( substr( $part, 0, 9 ) ) == 'LEFT JOIN' )
		{
            $tmp = trim(str_replace('`', ' ', $part));
			$tmp = trim( preg_replace('/(?<=\s|^)(LEFT JOIN\s+)/iu', '', $tmp));
			$tmp = explode(',', $tmp);
            
			foreach($tmp as $tmpTable)
			{
				$tmpTable = explode(' ', $tmpTable);
				if ($tmpTable[0])
				{
					$tables[$tmpTable[0]] = $tmpTable[0];
					continue;
				}
			}
		}
        
		//detect from "UPDATE" string
		if( strtoupper( substr( $part, 0, 6 ) ) == 'UPDATE' )
		{
            $tmp = trim(str_replace('`', ' ', $part));
			$tmp = trim( preg_replace('/(?<=\s|^)(UPDATE\s+)/iu', '', $tmp));
			$tmp = explode(',', $tmp);
			foreach($tmp as $tmpTable)
			{
				$tmpTable = explode(' ', $tmpTable);
				if($tmpTable[0])
				{
					$tables[$tmpTable[0]] = $tmpTable[0];
					continue;
				}
			}
		}
		//detect from "INSERT" and "REPLACE" string
		if (
            (substr($part, 0, 6) == 'INSERT')
            ||
            (substr($part, 0, 7) == 'REPLACE')
        )
		{
            $tmp = trim( str_replace('`', ' ', $part));
			$tmp = trim( preg_replace('/(?<=\s|^)(UPDATE|INSERT|INTO|REPLACE\s+)/iu', '', $tmp));
			$tmp = explode('(', $tmp);
			$tmpTable = explode(' ', $tmp[0]);
			if($tmpTable[0])
			{
				$tables[$tmpTable[0]] = $tmpTable[0];
				continue;
			}
		}
        
		//detect from "LOCK TABLES" string
		if( strtoupper( substr( $part, 0, 11 ) ) == 'LOCK TABLES' )
		{
            $tmp = trim(str_replace('`', ' ', $part));
			$tmp = trim( preg_replace('/(?<=\s|^)(LOCK TABLES\s+)/iu', '', $tmp));
			$tmp = explode(',', $tmp);
			foreach($tmp as $tmpTable)
			{
				$tmpTable = explode(' ', $tmpTable);
				if($tmpTable[0])
				{
					$tables[$tmpTable[0]] = $tmpTable[0];
					continue;
				}
			}
		}
	}

    $tableDefs = getTableDefinitions();

	//try to get each table definition and check it
	foreach( $tables as $table )
	{
        maintainTable( $table, $dbLinkName, $tableDefs );
	}
}

function getTableDefinitions()
{
	$defFile = SHARED_PATH . 'core/table.definitions.php';

	if( !is_file( $defFile ) )
	{
		return false;
	}
    
	require( $defFile );

    dbLoadRegisteredRawTableDefs();

    $dynamicTables = leaf_get('tableDefinitions');
	
    if( is_array( $dynamicTables ) && !empty( $dynamicTables ) )
    {
        $tableDefs = array_merge( $tableDefs, $dynamicTables );
    }

    return $tableDefs;
}

function maintainTable( $table, $dbLinkName = NULL, $tableDefs = null, $dryRun = false )
{
    if( is_null( $tableDefs ) )
    {
        $tableDefs = getTableDefinitions();
    }

    if( empty( $tableDefs[$table] ) || leaf_get( 'alreadyProcessedTables.' . $table ) )
    {
        return;
    }

    $tableDef = $tableDefs[$table];
    leaf_set('alreadyProcessedTables.' . $table, true);

    // look for referenced tables in relations and re-check those first 
    // (currently only the first relation level (immediate relations of the table in question) is supported)
    if( !empty( $tableDef['relations'] ) )
    {
        foreach( $tableDef['relations'] as $relation )
        {
            // table name is stored in index 2 of the relation array (not sure why named keys are not used there)
            $relatedTableName = $relation[2];
            if( !leaf_get( 'alreadyProcessedTables.' . $relatedTableName ) )
            {
                if( !empty($tableDefs[$relatedTableName] ) )
                {
                    maintainTable( $relatedTableName, $dbLinkName, $tableDefs, $dryRun );
                }
            }
        }
    }
    
    dbTable( $tableDef, $dbLinkName, $dryRun );
}

function dbAffectedRows( $dbLinkName = null )
{
	$dbLink = dbGetLink( $dbLinkName, true, DB_QUERY_TYPE_WRITE );
	return mysql_affected_rows($dbLink);
}

function dbInsertId( $dbLinkName = null )
{
	$dbLink = dbGetLink( $dbLinkName, true, DB_QUERY_TYPE_WRITE );
	return $dbLink->lastInsertId();
}

function dbQuery( $q, $dbLinkOrName = null, $returnId = null, $returnAffectedRows = null, $strictlyError = false, $ignoreSqlErrors = false )
{
 	if( is_array( $q ) )
	{
		$q = dbBuildQuery($q);
    }
    
    $queryType  = dbGetQueryType( $q );
    $dbLink     = dbGetLink( $dbLinkOrName, true, $queryType );    
    $dbLinkName = (is_string($dbLinkOrName)) ? $dbLinkOrName : null;
    $query = null;
    
    try
    {
        $query = $dbLink->query( $q );
    }
    catch( PDOException $ex )
    {
		$mysqlErrorNo   = $ex->getCode();
		$errorMessage   = $ex->getMessage();
        
        if( !$dbLinkName )
        {
            $dbLinkName = dbGetLinkName( $dbLink );
        }
        
        // unexisting tables
		if( !$strictlyError && ( $mysqlErrorNo == MYSQL_ERROR_COLUMN_NOT_FOUND || $mysqlErrorNo == MYSQL_ERROR_TABLE_NOT_FOUND ) )
		{
            dbGetTableDef( $q, $dbLinkName );
            
            // repeat query call
			return dbQuery( $q, $dbLinkOrName, $returnId, $returnAffectedRows, true );
		}
		else if( !$strictlyError && ( !$dbLink->ping()|| $mysqlErrorNo == MYSQL_ERROR_CONNETION_LOST ) )
		{
			return dbQuery($q, $dbLinkOrName, $returnId, $returnAffectedRows, true);
		}
		else
		{
            if (
                ($ignoreSqlErrors)
                &&
                (
                    ($ignoreSqlErrors === true)
                    ||
                    (
                        (is_scalar($ignoreSqlErrors))
                        &&
                        ($ignoreSqlErrors == $mysqlErrorNo)
                    )
                    ||
                    (
                        (is_array($ignoreSqlErrors))
                        &&
                        (in_array($mysqlErrorNo, $ignoreSqlErrors))
                    )
                )
            )
            {
                return false;
            }
            else
            {
                trigger_error( $mysqlErrorNo . ': ' . $errorMessage . ' in query: <pre>' . $q . '</pre>', E_USER_ERROR ); 
            }
		}

    }
    
    if( $returnId )
    {
        return $query->lastInsertId();
    }
    else if( $returnAffectedRows )
    {
        return $query->rowCount();
    }
    else
    {
        return $query;
    }
}


function dbGetQueryType( $query )
{
    if( empty( $GLOBALS['dbQueryTypes'] ) )
    {
        $types = array
        (
            DB_QUERY_TYPE_READ  => array('SELECT', 'SHOW', 'EXPLAIN', 'DESCRIBE'),
            DB_QUERY_TYPE_WRITE => array('INSERT', 'REPLACE', 'UPDATE', 'DELETE', 'CREATE', 'ALTER', 'TRUNCATE', 'DROP', 'LOAD', 'START', 'COMMIT', 'ROLLBACK', 'RENAME', 'SET', 'OPTIMIZE', 'RESET', 'FLUSH' )
        );
        
        $GLOBALS['dbQueryTypes'] = array();
        foreach( $types as $type => $keywords )
        {
            foreach( $keywords as $keyword )
            {
                $GLOBALS['dbQueryTypes'][$keyword] = $type;
            }
        }
    }
    
    
    $query = preg_replace('/^(\s|\()+/', '', $query);
    
    $parts = preg_split('/\s+/', $query, 2, PREG_SPLIT_NO_EMPTY );
    $firstWord  = strtoupper( reset( $parts ) );
	
    if( isset( $GLOBALS['dbQueryTypes'][$firstWord] ) )
    {
        return $GLOBALS['dbQueryTypes'][$firstWord];
    }
    
    trigger_error( 'Unrecognized SQL query type: ' . $query, E_USER_WARNING );
    return DB_QUERY_TYPE_WRITE;
}


function dbGetAll( $q, $key = null, $value = null, $dbLinkName = null, $eachCallback = null, $keyArrays = null )
{
    // set default values
    if (is_null($key))
    {
        $key = false;
    }
    if (is_null($value))
    {
        $value = false;
    }
    
	$output = array();
	$result = dbQuery( $q, $dbLinkName );
    
	while( $row = $result->fetch() )
	{
		if($key)
		{
			if($keyArrays)
			{
				$output[$row[$key]][] = $value ? $row[$value] : $row;
			}
			else
			{
				$output[$row[$key]] = $value ? $row[$value] : $row;
			}
		}
		else
		{
			$output[] = $value ? $row[$value] : $row;
		}
	}

	if (
        ( is_array( $eachCallback ) || function_exists( $eachCallback ) )
        &&
        ( is_array( $output ) )
	)
	{
	    foreach( $output as $key => $val )
	    {
	        $output[$key] = call_user_func($eachCallback, $val);
	    }
	}
    
	return $output;
}

function dbSE( $string, $dbLinkOrName = NULL, $queryType = null )
{    
    $dbLink = dbGetLink( $dbLinkOrName, true, $queryType );
    return substr( $dbLink->quote( ( string ) $string, $parameter_type = PDO::PARAM_STR ), 1, -1);
}

function dbTable( $table, $dbLinkName = null, $dryRun = false )
{
	$dbLink     = dbGetLink($dbLinkName, true, DB_QUERY_TYPE_WRITE);
	$dbLinkName = dbGetLinkName( $dbLink );
    $dbName     = dbGetDbName($dbLinkName, DB_QUERY_TYPE_WRITE);

    //types default values
    $typesDefault = array
    (
        'tinyint'       => 'tinyint(4)',
        'smallint'      => 'smallint(6)',
        'mediumint'     => 'mediumint(9)',
        'int'           => 'int(11)',
        'bigint'        => 'bigint(20)',
        'boolean'       => 'tinyint(1)',
        'bool'          => 'tinyint(1)',
        'varchar'       => 'varchar(255)',
    );
    
    // get table primary key columns (if exist)
    $primaryKeyColumns = array();
    if( isset( $table['keys'] ) )
    {
        foreach( $table['keys'] as $key )
        {
            if( strtolower( $key['type'] ) == 'primary' )
            {
                foreach( $key['fields'] as $keyField )
                {
                    $primaryKeyColumns[] = $keyField['name'];
                }
            }
        }
    }

    // normalize fields def
    $fields = array();
    $fieldsIndex = array();
    foreach( $table['fields'] as $fieldIndex => $field )
    {
        $field['index'] = $fieldIndex;
        $fieldsIndex[$fieldIndex] = $field['name'];
        // no auto_increment as default value
        $field['auto_increment'] = get( $field, 'auto_increment', false );

        // no null for auto_increment and primary key columns
        if($field['auto_increment'] || in_array($field['name'], $primaryKeyColumns))
        {
            $field['null'] = false;
        }
        else if(isset($field['null']))
        {
            $field['null'] = $field['null'];
        }
        // null as default if not defined
        else
        {
            $field['null'] = true;
        }

        // convert to lower case (only the type part to preserve case of enum values)
        $parenthesisPos = strpos($field['type'], '(');
        if ($parenthesisPos === false)
        {
            $typePart   = $field['type'];
            $paramsPart = '';
        }
        else
        {
            $typePart   = substr($field['type'], 0, $parenthesisPos);
            $paramsPart = substr($field['type'], $parenthesisPos);
        }
        $field['type'] = strtolower($typePart) . $paramsPart;
        
        
        // convert " to ' (otherwise there will be problems with enum comparising)
        $field['type'] = str_replace('"', '\'', $field['type']);
        // remove spaces (otherwise there will be problems with enum comparising)
        $field['type'] = preg_replace("/\s/", '', $field['type']);

        // convert default types
        if (isset($typesDefault[$field['type']]))
        {
            $field['type'] = $typesDefault[$field['type']];
        }
        $fields[$field['name']] = $field;
    }

    // normalize table engine
	if (!empty($table['relations']))
	{
		$table['engine'] = 'INNODB';
    }
    else
    {
        $table['engine'] = strtoupper(get($table, 'engine', 'MYISAM'));
    }

    // normalize relations
    $relations = array();
    if (isset($table['relations']))
    {
        foreach($table['relations'] as $relationRaw)
        {
            $relation = array(
                'column' => $relationRaw[1],
                'foreignTable' => $relationRaw[2],
                'foreignColumn' => $relationRaw[3],
                'onDelete' => $relationRaw[4],
                'onUpdate' => $relationRaw[5],
            );
            $relations[$relation['column']] = $relation;
            if(!isset($table['keys'][$relation['column']]))
            {
                $table['keys'][$relation['column']] = array(
                    'type' => 'INDEX',
                    'name' => $relation['column'],
                    'fields' => array(
                        array(
                            'name' => $relation['column'],
                        )
                    )
                );
            }
        }
    }

	//check for table
	$q = '
	   SHOW
	   TABLE STATUS
	   LIKE "' . $table['name'] . '"
    ';
    $currentTable = dbGetRow($q, $dbLinkName);

	// alter if column changes detected
    $currentFields = array();
    if ($currentTable)
    {
        $r = dbQuery('SHOW COLUMNS FROM `' . $table['name'] . '`', $dbLinkName);
        for ($i = 0; $item = $r->fetch(); $i++)
        {
            if (isset($fields[$item['Field']]))
            {
                $currentFields[$item['Field']] = array
                (
                    'index' => $i,
                    'type' => strtolower($item['Type']),
                    'null' => (strtolower($item['Null']) == "yes"),
                    'default' => $item['Default'],
                    'auto_increment' => strpos($item['Extra'], 'auto_increment') !== FALSE,
                );
            }
            else
            {
                $alterTableList[] = 'DROP `' . $item['Field'] . '`';
            }
        }
    }

    foreach ($fields as $field)
    {
        $currentField = get($currentFields, $field['name'], null);
        $change = false;
        $add = false;

        if (!$currentField)
        {
            $add = true;
        }
        // compare type
        else if(strtolower($field['type']) != $currentField['type'])
        {
            $change = true;
        }
        // compare null
        else if($field['null'] != $currentField['null'])
        {
            $change = true;
        }
        // compare default value
        else if(
            (!isset($field['default']) && !empty($currentField['default']))
            ||
            (isset($field['default']) && $field['default'] != $currentField['default'])
        )
        {
            $change = true;
        }
        // compare order
        elseif($field['index'] != $currentField['index'])
        {
            $change = true;
        }

        if($add || $change)
        {
            if($add)
            {
                $alterString = ($currentTable ? 'ADD ' : '')  . '`' . $field['name'] . '`';
            }
            else
            {
                $alterString = 'CHANGE `' . $field['name'] . '` `' . $field['name'] . '`';
            }
            $alterString .= ' ' . $field['type'] . ' ' . ($field['null'] ? 'NULL' : 'NOT NULL');

            if($field['auto_increment'])
            {
                $alterString .= ' ' . 'AUTO_INCREMENT';
            }

            if(isset($field['default']))
            {
                $alterString .= ' DEFAULT "' . $field['default'] .  '"';
            }
            else if($field['null'])
            {
                $alterString .= ' DEFAULT NULL';
            }

            if($currentTable)
            {
                if($field['index'] == 0)
                {
                    $alterString .= ' FIRST';
                }
                else
                {
                    $alterString .= ' AFTER `' . $fieldsIndex[$field['index'] - 1] . '`';
                }
            }

            $alterTableList[] = $alterString;
        }
    }

    // keys processing
	// read keys from db
    $foreignKeyQuery = '
        SELECT
            `column_name`,
            `CONSTRAINT_NAME`,
            `REFERENCED_TABLE_NAME`,
            `REFERENCED_COLUMN_NAME`
        FROM
            information_schema.key_column_usage
        WHERE
            TABLE_SCHEMA = "' . dbse($dbName, $dbLinkName, DB_QUERY_TYPE_READ). '" 
            AND
            referenced_table_name IS NOT NULL 
            AND
            table_name = "' . dbse($table['name'], $dbLinkName, DB_QUERY_TYPE_READ) . '"
    ';

    $r = dbQuery($foreignKeyQuery, $dbLinkName);
    $currentForeignKeys = array();
    while ($foreignKey = $r->fetch())
    {
        // avoid key duplication and unexisting relations
        $keyColumn = $foreignKey['column_name'];
        
        if (
            (!isset($currentForeignKeys[$keyColumn])) 
            && 
            (isset($relations[$keyColumn]))
            &&
            (
                ($relations[$keyColumn]['foreignTable']  == $foreignKey['REFERENCED_TABLE_NAME'])
                &&
                ($relations[$keyColumn]['foreignColumn'] == $foreignKey['REFERENCED_COLUMN_NAME'])
            )
        )    
        {
            $currentForeignKeys[$keyColumn] = $foreignKey;
        }
        // delete duplicate foreign keys and unexisting relations
        else
        {
            $alterTableList[] = 'DROP FOREIGN KEY `' . $foreignKey['CONSTRAINT_NAME'] . '`';
        }
    }

    $currentKeys = array();
    if ($currentTable)
    {
        $r = dbQuery('SHOW KEYS FROM `' . $table['name'] . '`', $dbLinkName);
        while ($key = $r->fetch())
        {
            $keyName = $key['Key_name'];
            // find name for primary key
            if($keyName == 'PRIMARY')
            {
                foreach ($table['keys'] as $key2)
                {
                    if ($key2['type'] == 'PRIMARY')
                    {
                        $keyName = $key2['name'];
                    }
                    break;
                }
            }

            if (empty($currentKeys[$keyName]))
            {
                $currentKeys[$keyName]['name'] = $keyName;
                if($key['Key_name'] == 'PRIMARY')
                {
                    $currentKeys[$keyName]['type'] = 'PRIMARY';
                }
                elseif($key['Index_type'] == 'FULLTEXT')
                {
                    $currentKeys[$keyName]['type'] = 'FULLTEXT';
                }
                elseif($key['Non_unique'] == 0)
                {
                    $currentKeys[$keyName]['type'] = 'UNIQUE';
                }
                else
                {
                    $currentKeys[$keyName]['type'] = 'INDEX';
                }
            }
            $keyColumn = array(
                'name' => $key['Column_name'],
            );
            if(!empty($key['Sub_part']))
            {
                $keyColumn['length'] = $key['Sub_part'];
            }

            $currentKeys[$keyName]['fields'][] = $keyColumn;
        }
    }

    // delete old and changed keys
	foreach($currentKeys as $keyName => $key)
	{
        $dropKey = false;

		if (empty($table['keys'][$keyName]))
        {
            $dropKey = true;
		}
		else
		{
			$changed = false;
			if($key['type'] != strtoupper($table['keys'][$keyName]['type']))
            {
				$changed = true;
			}
			foreach($key['fields'] as $kIndex => $kField)
			{
				if(empty($table['keys'][$keyName]['fields'][$kIndex]))
				{
					$changed = true;
					break;
				}
				if($table['keys'][$keyName]['fields'][$kIndex]['name'] != $kField['name'])
				{
					$changed = true;
					break;
				}
				if(@$table['keys'][$keyName]['fields'][$kIndex]['length'] != @$kField['length'])
				{
					$changed = true;
					break;
				}
			}
			
			if ($changed)
            {
                $dropKey = true;
				unset($currentKeys[$key['name']]);
			}
        }

        if($dropKey)
        {
            $alterTableList[] = 'DROP ' . ($key['type']  == 'PRIMARY' ? 'PRIMARY KEY' : ' INDEX `' . $key['name'] . '`');
            foreach($currentForeignKeys as $fIndex => $fKey)
            {
                if($fKey['column_name'] == $key['name'])
                {
                    $alterTableList[] = 'DROP FOREIGN KEY `' . $fKey['CONSTRAINT_NAME'] . '`';
                    unset($currentForeignKeys[$fIndex]);
                }
            }
        }
    }

	//check for new keys
	foreach($table['keys'] as $keyName => $key)
    {
		$tmpFields = array();
		if (empty($currentKeys[$keyName]))
		{
			if (strtoupper($key['type']) == 'PRIMARY')
			{
				$nameQ = 'PRIMARY KEY';
			}
			else
			{
				$nameQ = strtoupper($key['type']) . ' `' . $key['name'] . '`';
			}

			foreach ($key['fields'] as $tmpField)
			{
				$tmpFields[] = '`' . $tmpField['name'] . '`' . (!empty($tmpField['length']) ? '( ' . $tmpField['length'] . ' )' : '');
			}
            $alterTableList[] = ($currentTable ? 'ADD ' : '') . $nameQ . ' (' . implode(',', $tmpFields) . ')';
		}
	}


    foreach ($relations as $relationRow)
    {
        if (!isset($currentForeignKeys[$relationRow['column']]))
        {
            // check if table exist already
            $alterTableList[] = ($currentTable ? 'ADD ' : '') .  
                '
                    FOREIGN KEY
                    (
                        `' . $relationRow['column'] . '`
                    )
                    REFERENCES
                        `' . $relationRow['foreignTable'] . '`(`' . $relationRow['foreignColumn'] . '`)
                        ON DELETE ' . $relationRow['onDelete'] . '
                        ON UPDATE ' . $relationRow['onUpdate'] . '
                '
            ;
        }
    }

    // engine checking
    if (!$currentTable || ($table['engine'] != strtoupper($currentTable['Engine'])))
    {
        $alterTableList['engine'] = 'ENGINE = ' . $table['engine'];
    }
	
	
    if( !empty( $table['collation'] ) && !empty( $table['encoding'] ) )
    {
        if( !$currentTable || $currentTable['Collation'] != $table['collation'] )
        {
            $alterTableList['collation']    = get( $table, 'collation' );
            $alterTableList['encoding']     = get( $table, 'encoding' );
        }
    }
	

    // alter table with all changes
    if(!empty($alterTableList))
    {
        $collation = get( $alterTableList, 'collation' );
        $encoding = get( $alterTableList, 'encoding' );
        
        unset( $alterTableList['collation'] );
        unset( $alterTableList['encoding'] );
		
		if(!$currentTable)
        {
            $engine = $alterTableList['engine'];
            unset($alterTableList['engine']);
            $q = '
            CREATE
            TABLE
            `' . $table['name'] . '`
            (
                ' . implode($alterTableList, ', ') . '
            ) ' . $engine;
        }
        else
        {
            $q = '
            ALTER
            TABLE
                `' . $table['name'] . '`
                ' . implode($alterTableList, ', ');
        }
		
		
        if( $encoding )
        {
            if( $currentTable )
            {
                $q .= ' CONVERT TO';
            }
            
            $q .= ' CHARACTER SET ' . dbSE( $encoding );
            
            if( $collation )
            {
                $q .= ' COLLATE ' . dbSE( $collation );
            }
        }
		
		
        if (!$dryRun)
        {
            dbQuery($q, $dbLinkName);
        }
        if (defined('CLI_MODE'))
        {
            message($q);
        }
    }
}

function dbRegisterTableDef( $tableName, $tableDef )
{
    leaf_set(array('tableDefinitions', $tableName), $tableDef);
}
function dbRegisterTableDefs( $tableDefs )
{
    foreach( $tableDefs as $key => $def )
    {
        if( empty( $def ) )
        {
            continue;
        }
        dbRegisterTableDef( $key, $def );
    }
    return;
}

function dbRegisterRawTableDef( $tableName, $rawTableDef )
{
    leaf_set( array( 'rawTableDefinitions', $tableName ), $rawTableDef );
}
function dbRegisterRawTableDefs( $rawTableDefs )
{
    foreach( $rawTableDefs as $key => $rawTableDef )
    {
        dbRegisterRawTableDef( $key, $rawTableDef );
    }
    return;
}

function dbLoadRegisteredRawTableDef( $tableName )
{
    $rawTableDefs = leaf_get('rawTableDefinitions');
    if (!is_array($rawTableDefs))
    {
        return false;        
    }
    
    if (empty($rawTableDefs[$tableName]))
    {
        return false;
    }
    $rawTableDef = $rawTableDefs[$tableName];

    $tableDef = dbParseRawTableDef( $tableName, $rawTableDef);

    dbRegisterTableDef( $tableName, $tableDef );

    // unset in raw defs
    leaf_set( array( 'rawTableDefinitions', $tableName), null );

    return true;
}

function dbLoadRegisteredRawTableDefs()
{
    // parse and load all registered raw definitions
    $rawTableDefs = leaf_get('rawTableDefinitions');
    if (empty($rawTableDefs))
    {
        return;
    }
    $tableDefs = dbParseRawTableDefs( $rawTableDefs );
    dbRegisterTableDefs( $tableDefs );

    // remove all raw defs
    leaf_set( 'rawTableDefinitions', null );
    return true;
}


function dbGetRegisteredTableDef( $tableName )
{
    // this should be named "dbGetTableDef()", but the name is already taken

    if (empty($tableName))
    {
        return null;
    }
    // parse and load from raw def, if available
    dbLoadRegisteredRawTableDef( $tableName );

    $tableDef = leaf_get(array('tableDefinitions', $tableName) );
    return $tableDef;
}

function dbParseRawTableDef( $tableName, $rawTableDef)
{
    $parsedTableDef = array(
        'name' => $tableName
    );

    // parse fields
    $fieldDefsAsArrays = array();

    $fields = preg_split('/\n|\r/u', trim( $rawTableDef['fields'] ));

    foreach ($fields as $fieldStr)
    {
        $fieldStr = trim(preg_replace('/\s+/', ' ', $fieldStr));

        if (empty($fieldStr))
        {
            continue;
        }

        $firstSpace = strpos($fieldStr, ' ');
        if ($firstSpace === false)
        {
            // bad table def
            die('error parsing tabledef: ' . $rawTableDef);
        }
        $fieldName = substr($fieldStr, 0, $firstSpace);
        $fieldDef = substr($fieldStr, $firstSpace + 1);

        // read type

        // possible types:
        // int
        $firstFourChars = strtolower(substr($fieldDef, 0, 4));

        /*
            // possible types:

            bigint
            blob
            bool
            char
            date
            datetime
            decimal
            double
            enum
            float
            int
            longblob
            longtext
            mediumblob
            mediumint
            mediumtext
            set
            smallint
            text
            time
            timestamp
            tinyblob
            tinyint
            tinytext
            varchar
            year
        */

        switch ($firstFourChars)
        {
            // types with allowed spaces and required closing brackets in type def
            // will be used up to first closing bracket
			case 'set':  // SET("Status 1", "Status 2")...
            case 'enum': // ENUM("Status 1", "Status 2")...
            case 'deci': // DECIMAL(13, 5)
                // look for first closing bracket
                $closingBracket = strpos($fieldDef, ')');
                if ($closingBracket === false)
                {
                    die('error parsing tabledef: missing closing bracket: ' . $fieldName . ': ' . $fieldDef);
                }
                $typeStr = substr( $fieldDef, 0, $closingBracket + 1);
            break;

            default:
                // all other cases
                // type is string until first space
                $firstSpaceInDef = strpos($fieldDef, ' ');
                if ($firstSpaceInDef > 0)
                {
                    $typeStr = substr($fieldDef, 0, $firstSpaceInDef);
                }
                else
                {
                    $typeStr = $fieldDef;
                }
            break;
        }

        // the rest of the line consists of additional space-separated keywords
        $def = substr($fieldDef, strlen($typeStr) + 1);
        $keywords = explode(' ', $def);
        $autoIncrement = false;
        $null = true;
        $default = null;

        foreach ($keywords as $keyword)
        {
            $keyword = strtolower($keyword);
            if($keyword == 'auto_increment')
            {
				$autoIncrement = true;
				$null = false;
            }
			else if($keyword == 'notnull')
			{
				$null = false;
			}
			else if(substr($keyword, 0, 7) == 'default')
			{
				$default = substr($keyword, 8, -1);
			}
		}

        $defAsArray = array(
            'name' => $fieldName,
            'type' => $typeStr,
            'null' => $null,
            'default' => $default
        );

        if ($autoIncrement)
        {
            $defAsArray['auto_increment'] = $autoIncrement;
        }

        $fieldDefsAsArrays[] = $defAsArray;
    }

    $parsedTableDef['fields'] = $fieldDefsAsArrays;


    // done with fields
    // process indexes
    $indexesAsArrays = array();
    if (array_key_exists('indexes', $rawTableDef))
    {
        $indexes = explode("\n", trim( $rawTableDef['indexes'] ));
        foreach ($indexes as $indexDefStr)
        {
            $indexDefStr = trim(preg_replace('/\s+/', ' ', $indexDefStr));

            $firstSpace = strpos($indexDefStr, ' ');
            if ($firstSpace === false)
            {
                die('error parsing tabledef index: ' .  $indexDefStr);
            }

            $indexType = strtoupper(substr($indexDefStr, 0, $firstSpace));
            if (!in_array($indexType, array('PRIMARY', 'UNIQUE', 'INDEX', 'FULLTEXT')))
            {
                die('error parsing tabledef index type: ' .  $indexDefStr);
            }

            $indexDefStr = substr($indexDefStr, $firstSpace + 1);

            $nextSpace = strpos($indexDefStr, ' ');
            if ($nextSpace === false)
            {
                // no space found,
                // single field index, name same as field name
                $indexName      = $indexDefStr;
                $indexFieldsDef = $indexDefStr;
            }
            else
            {
                // first word is index name
                // followed by space and index def
                $indexName      = substr($indexDefStr, 0, $nextSpace );
                $indexFieldsDef = substr($indexDefStr, $nextSpace + 1 );
            }

            if ($indexType == 'PRIMARY')
            {
                $indexName = 'PRIMARY';
            }

            $indexFields = array();

            $indexFieldsDef = explode(',', $indexFieldsDef);
            foreach ($indexFieldsDef as $indexFieldDef)
            {
                $indexFieldDef = preg_replace('/\s+/', ' ', trim($indexFieldDef));
                $indexFieldDef = explode(' ', $indexFieldDef);

                $indexField = array(
                    'name' => trim($indexFieldDef[0])
                );

                if (isset($indexFieldDef[1]))
                {
                    $indexField['length'] = trim($indexFieldDef[1]);
                }

                $indexFields[] = $indexField;
            }

            $index = array
            (
                'type' => $indexType,
                'name' => $indexName,
                'fields' => $indexFields
            );

            $indexesAsArrays[ $indexName ] = $index;
        }

    }
    $parsedTableDef['keys'] = $indexesAsArrays;

    $foreignRelations = array ();
    if (array_key_exists('foreignKeys', $rawTableDef))
    {
    	$pattern = '/^([^ ]+)\s+([^.]+)\.([^ ]+)\s+(CASCADE|SET NULL|NO ACTION|RESTRICT)\s+(CASCADE|SET NULL|NO ACTION|RESTRICT)$/';

		$foreignKeys = array_filter(array_map('trim', explode("\n", $rawTableDef['foreignKeys'])));
		foreach ($foreignKeys as $foreignKeyDefinition)
		{
			$matches = array ();
            preg_match($pattern, $foreignKeyDefinition, $matches);
			$foreignRelations[] = array_values($matches);
		}
		$foreignRelations = array_filter($foreignRelations);
    }
    $parsedTableDef['relations'] = $foreignRelations;

    if(!empty($foreignRelations))
    {
    	$parsedTableDef['engine'] = 'INNODB';
    }
    else if (array_key_exists('engine', $rawTableDef))
    {
    	$parsedTableDef['engine'] = $rawTableDef['engine'];
    }

	
    $parsedTableDef['encoding'] = leaf_get( 'properties', 'dbconfig', 'encoding' );
    $parsedTableDef['collation'] = leaf_get( 'properties', 'dbconfig', 'collation' );

    if( !empty( $rawTableDef['encoding'] ) )
    {
        $parsedTableDef['encoding'] = $rawTableDef['encoding'];
    }

    if( !empty( $rawTableDef['collation'] ) )
    {
        $parsedTableDef['collation'] = $rawTableDef['collation'];
    }
    
    return $parsedTableDef;
}

function dbParseRawTableDefs( $rawTableDefs )
{
    $parsedTableDefs = array();
    foreach ($rawTableDefs as $tableName => $rawTableDef)
    {
        if (empty($rawTableDef))
        {
            continue;
        }
        $parsedTableDefs[$tableName] = dbParseRawTableDef( $tableName, $rawTableDef);
    }

    return $parsedTableDefs;
}

function dbGetEnumValuesFromTableDef( $tableDef, $fieldName )
{
    if (is_string($tableDef))
    {
        $tableDef = dbGetRegisteredTableDef($tableDef);
    }
    if (
        (!is_array($tableDef))
        ||
        (empty($tableDef['fields']))
    )
    {
        return null;
    }
    $fields = $tableDef['fields'];

    foreach ($fields as $field)
    {
        if ($field['name'] != $fieldName)
        {
            continue;
        }

        $fieldParts = preg_split('/\(|\)/', $field['type'] );
        if (strtoupper(trim($fieldParts[0])) != 'ENUM')
        {
            continue;
        }

        $unquotedValues = preg_replace('/^\"|\"$/', '', trim($fieldParts[1]));
        $unquotedValues = preg_replace('/(\")(\s*)(,)(\s*)(\")/', ',', trim($unquotedValues) );
        $values = explode(',', $unquotedValues);
        return $values;
    }

    return null;
}


class leafPDO extends PDO
{
    public static function connect( $dbConfig )
    {
        $host       = get( $dbConfig, 'hostspec' );
        $db         = get( $dbConfig, 'database' );
        $charset    = get( $dbConfig, 'charset', 'utf8' );
        $port = get( $dbConfig, 'port' );
        
        $connectString = 'mysql:host=' . $host . ';dbname=' . $db . ';charset=' . $charset;
        $connectString .= $port ? ';port=' . $port : '';

        $dbLink = new leafPDO(
            $connectString,
            $dbConfig['username'],
            $dbConfig['password'],
            $driver_options = array(
                PDO::ATTR_STATEMENT_CLASS => array( 'leafPDOStatement' ),
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            )
        );
        
        return $dbLink;
    }
    
    public function ping()
    {
        return ( bool ) $this->query('SELECT 1')->fetch();
    }
}

class leafPDOStatement extends PDOStatement
{
    public function fetchRow()
    {
        return $this->fetch();
    }
    
    public function RecordCount()
    {
        return $this->rowCount();
    }
}