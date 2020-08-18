<?
class leafError extends leafBaseObject
{
    protected static $remoteReporting = true;

    const removedValue = 'value_removed_by_leafError';
    
	const tableName = 'errors';
	// db fields
	protected
		$level,
		$message,
		$file,
		$line,
        $hash,
		$context,
        $user_ip,
        $user_forwarded_ip,
        $http_host,
        $request_uri,
        $query_string,
        $request_method,
        $http_referer,
        $user_agent,
        $http_content_type,
        $http_cookie,
        $data_get,
        $data_post,
        $data_cookie,
        $data_files,
        $data_session,
        $argv,
        $add_date,
        $stackTrace
    ;

    protected $count;
	// dynamic properties
	protected $log;
	protected $fieldsDefinition = array
	(
		'level' => array
		(
			'not_empty' => true,
			'type' => 'int'
		),
		'message' => array(),
		'file' => array(),
		'line' => array
		(
			'type' => 'int',
		),
        'hash'              => array('optional' => true),
		'context'           => array('optional' => true),
        'user_ip'           => array('optional' => true),
        'user_forwarded_ip' => array('optional' => true),
        'http_host'         => array('optional' => true),
        'request_uri'       => array('optional' => true),
        'query_string'      => array('optional' => true),
        'request_method'    => array('optional' => true),
        'http_referer'      => array('optional' => true),
        'user_agent'        => array('optional' => true),
        'http_content_type' => array('optional' => true),
        'http_cookie'       => array('optional' => true),
        'data_get'          => array('optional' => true),
        'data_post'         => array('optional' => true),
        'data_cookie'       => array('optional' => true),
        'data_files'        => array('optional' => true),
        'data_session'      => array('optional' => true),
        'argv'              => array('optional' => true),
        'stackTrace'        => array('optional' => true),
    );


    public static function _autoload( $className )
    {
        parent::_autoload( $className );
        
        $_tableDefsStr = array
        (
            constant($className . '::tableName')  => array
            (
                'fields' =>
                '
                    id                  int auto_increment
                    add_date            datetime
                    level               int
                    message             text
                    file                text 
                    line                int
                    hash                varchar(32)
                    context             longtext
                    user_ip             text
                    user_forwarded_ip   text
                    http_host           varchar(255)
                    request_uri         text
                    query_string        text
                    request_method      varchar(255)
                    http_referer        text
                    user_agent          varchar(255)
                    http_content_type   varchar(255)
                    http_cookie         mediumtext
                    data_get            longtext
                    data_post           mediumtext
                    data_cookie         mediumtext
                    data_files          mediumtext
                    data_session        mediumtext
                    argv                mediumtext
                    stackTrace          longtext
                '
                ,
                'indexes' => '
                    primary id
                    index add_date
                    index hash
                ',
                'engine' => 'innodb'
            )
        );

        dbRegisterRawTableDefs( $_tableDefsStr );
        
    }

	public function getLevelName()
	{
		switch( $this->level )
		{
			case 1: 	$name = 'E_ERROR'; break;
			case 2: 	$name = 'E_WARNING'; break;
			case 4: 	$name = 'E_PARSE'; break;
			case 8:     $name = 'E_NOTICE'; break;
			case 16:    $name = 'E_CORE_ERROR'; break;
			case 32:    $name = 'E_CORE_WARNING'; break;
			case 64:    $name = 'E_COMPILE_ERROR'; break;
			case 128:   $name = 'E_COMPILE_WARNING'; break;
			case 256:   $name = 'E_USER_ERROR'; break;
			case 512:   $name = 'E_USER_WARNING'; break;
			case 1024:  $name = 'E_USER_NOTICE'; break;
			case 6143:  $name = 'E_ALL'; break;
			case 2048:  $name = 'E_STRICT'; break;
			case 4096:  $name = 'E_RECOVERABLE_ERROR'; break;
			case 8192:  $name = 'E_DEPRECATED'; break;
			case 16384: $name = 'E_USER_DEPRECATED'; break;
			default: 	$name = 'unknown error code'; break;
		}
		return $name;
	}

	public static function create( $params )
	{
        $errorReportConfig = leaf_get('properties', 'errorReport');
        $disableStackTrace = get($errorReportConfig, 'disableStackTrace', false);
        $disableContext = get($errorReportConfig, 'disableContext', false);

	    $context = NULL;
		if (!empty($params['context']))
        {
            // Context for error scope is not possible (too much 
            // problems - $GLOBALS recursion, dbconfig password removal, etc)
            if( $params['context'] === $GLOBALS )
            {
                $params['context'] = NULL;
            }
            else
            {
                $params['context'] = self::discreetPrintR( $params['context'] );
            }
	    }

	    $params = array_merge( $params, self::getRequestData() );
        
        // try to add stack trace
        if (empty($params['stackTrace']) && !$disableStackTrace)
        {
            if( function_exists( 'xdebug_print_function_stack' ) )
            {
                $stackTrace = array();

                if
                (
                    !empty( $context )
                    &&
                    !empty( $context['e'] )
                    &&
                    is_subclass_of( $context['e'], 'Exception' )
                    &&
                    !empty( $context['e']->xdebug_message )
                )
                {
                    $stackTrace[] = strip_tags( $context['e']->xdebug_message );
                }

                ob_start();
                xdebug_print_function_stack();
                $xdebugStackTrace = ob_get_clean();
                if (ini_get('html_errors'))
                {
                    // remove html from xdebug trace output errors
                    $xdebugStackTrace = preg_replace('/<\/t(h|d)>/', "\t", $xdebugStackTrace);
                    $xdebugStackTrace = trim(strip_tags($xdebugStackTrace));
                }

                $stackTrace[] = $xdebugStackTrace;

                $params['stackTrace'] = implode("\n", $stackTrace); 

            }
            elseif (function_exists('debug_backtrace'))
            {
                $params['stackTrace'] = debug_backtrace();
            }
            else
            {
                $params['stackTrace'] = 'neither xdebug_print_function_stack() nor debug_backtrace() available';
            }
        }

        if( $disableStackTrace )
        {
            $params['stackTrace']  = null;
        }

        if( $disableContext )
        {
            $params['context']  = null;
        }


        if (!empty($params['stackTrace']))
        {
            $params['stackTrace'] = self::discreetPrintR( $params['stackTrace'] );
        }

        $sentToRemote = false;
        if($errorReportConfig && self::$remoteReporting)
        {
            self::$remoteReporting = false;
            $remoteParams = $params;
            
            
            if (defined('BASE_PATH'))
            {
                $path = BASE_PATH;
            }
            elseif (defined('PATH'))
            {
                $path = PATH;
            }
            elseif (!empty($GLOBALS['def_config']['PATH']))
            {
                $path = $GLOBALS['def_config']['PATH'];
            }
            else
            {
                $path = null;
            }
   
            
            $relativePath = get($params, 'file');
            if ($path)
            {
                $pathLen = strlen($path);
                if (substr($relativePath, 0, $pathLen) == $path)
                {
                    $relativePath = substr($relativePath, $pathLen);
                }
            }
            $remoteParams['relativePath'] = $relativePath;

                
                        
            $env = null;
            
            if (function_exists('leaf_get'))
            {
                $env = leaf_get(get_called_class(), 'env');
            }            
            if (!$env)
            {
                // read environment, branch and revision and cache in memory
                
                $env = array();
                // get env
                if(defined("LEAF_ENV"))
                {
                    $environment =  LEAF_ENV;
                }
                elseif (PRODUCTION_SERVER)
                {
                    $environment = "PRODUCTION";
                }
                else
                {
                    $environment = "DEVELOPMENT";
                }                
                $env['environment'] = $environment;
                
                // take revision and branch directly from REVISION and BRANCH files
                // instead of using getValue from db
                // (to avoid triggering more errors in case of db problems)
                $revision = $branch = null;


                if ($path)
                {
                    $gitPath = $path . '.git';
                    
                    $revisionFile = $path . 'REVISION';
                    
                    if (
                        (file_exists($revisionFile))
                        &&
                        (is_readable($revisionFile))
                    )
                    {
                        $revision = trim(file_get_contents($revisionFile));
                    }
                    elseif (file_exists($gitPath))
                    {
                        // attempt to detect current revision from git
                        $lines = array();
                        $command = 'cd ' . realpath(__DIR__) . ' && git rev-parse HEAD';
                        
                        exec($command, $lines, $exitStatus);

                        if (
                            ($exitStatus == 0)
                            &&
                            (count($lines) == 1)
                        )
                        {
                            $line = reset($lines);
                            $line = trim($line);
                            if (!empty($line))
                            {
                                $revision = $line;
                            }
                        }
                    }


                    $branchFile = $path . 'BRANCH';

                    if (
                        (file_exists($branchFile))
                        &&
                        (is_readable($branchFile))
                    )
                    {
                        $branch = trim(file_get_contents($branchFile));
                    }          
                    elseif (file_exists($gitPath))
                    {
                        // attempt to detect branch from git 
                        $branches = array();
                        $command = 'cd ' . realpath(__DIR__) . ' && git branch';

                        exec($command, $branches, $exitStatus);

                        if ($exitStatus == 0)
                        {
                            $branches = preg_grep('/^\*/', $branches);
                            if (!empty($branches))
                            {
                                $branch = trim(substr(reset($branches), 1));
                            }
                        }

                    }
                }
                
                $env['revision'] = $revision;
                $env['branch']   = $branch;

                leaf_set( array(get_called_class(), 'env'), $env );
            }

        
            $remoteParams['environment']    = get($env, 'environment');
            $remoteParams['branch']         = get($env, 'branch');
            $remoteParams['revision']       = get($env, 'revision');
            

            $remoteParams['api_key'] = $errorReportConfig['key'];

            $address = get($errorReportConfig, 'address', 'https://office.cube.lv/services/errorReport.php');
            $proxy = get($errorReportConfig, 'proxy', null);

            $result = self::sendToRemote($address, $remoteParams, $proxy);
            if ($result == 'ok')
            {
                $sentToRemote = true;
            }
            self::$remoteReporting = true;
        }

        if(!$sentToRemote)
        {
            $newObject = getObject( get_called_class(), 0 );
            $newObject->variablesSave( $params );
        }
    }

    public static function sendToRemote($url, $data, $proxy = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_URL, $url);
        if(isset($proxy))
        {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_POST, 1); 
        $result = curl_exec($ch);
        if (curl_errno($ch))
        {
            self::$remoteReporting = false;
            $error = curl_error($ch);
            trigger_error( $error, E_USER_WARNING);
        } else
        {
            curl_close($ch);
            return $result;
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

	protected static function getRequestData()
	{
        $data = array();

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

        // quick and dirty hack to remove phpsessid from raw cookie string
        if (!empty($data['http_cookie']))
        {
            $sessionIdVar = self::getSessionIdVar();

            if (!empty($sessionIdVar))
            {
                $pattern = '/(?<=\b' . $sessionIdVar . '=)(.+)(\b)/U';
                $data['http_cookie'] = preg_replace($pattern, self::removedValue, $data['http_cookie']);
            }
            
        }

        $superGlobals = array('get', 'post', 'cookie', 'session', 'files');
        foreach ($superGlobals as $name)
        {
            $key = 'data_' . $name;
            $var = '_' . strtoupper($name);

            $value = (isset($GLOBALS[$var])) ? self::discreetPrintR( $GLOBALS[$var] ) : null;
            $data[$key] = $value;
        }

        $data['argv']               = (isset($_SERVER['argv'])) ? self::discreetPrintR( $_SERVER['argv'] ) : null;

        
        return $data;
	}

	public function getMessageHash()
	{
	    return $this->hash;
	}

    public function variablesSave( $variables, $fieldsDefinition = null, $mode = false )
    {
        if( !$this->id )
        {
            $hashParams = array(
                'message'   => get( $variables, 'message' ),
                'file'      => get( $variables, 'file' ),
                'line'      => get( $variables, 'line' ),
            );
            
            $variables['hash'] = md5( implode( "|", $hashParams ) );
        }
        
        return parent::variablesSave( $variables, $fieldsDefinition, $mode );
    }
    
	public static function getCollection ($params = array(), $itemsPerPage = NULL, $pageNo = NULL)
	{
		$queryParts['select'][] = 't.*';
		$queryParts['from'][] =  '`' . self::getClassTable(get_called_class()) . '` `t`';

        if(isset($params['hash']))
        {
			$queryParts['where'][] = '`t`.`hash` = "' . dbSE($params['hash']) . '"';
        }


        if (ispositiveint($params['after']))
        {
            $queryParts['where'][] = 't.id > ' . dbSE($params['after']);
        }

        if(isset($params['group']))
        {

            $subselect = $queryParts;
            $subselect['select'] = 'MAX(t.id) `max_id`, MAX(t.add_date) `max_date`, COUNT(t.id) `count`';
            $subselect['groupBy'] = '`t`.`hash`';
            $shitQ = dbBuildQuery($subselect);

            $queryParts['select'][] = 't.*, m.count';
            $queryParts['from'] = '(' . $shitQ . ') AS m';
            $queryParts['leftJoins'][] = '`' . self::getClassTable(get_called_class()) . '` AS `t` ON m.max_id = t.id';
            $queryParts['orderBy'] = 'm.max_date DESC';

        }
        else
        {
    		$queryParts['orderBy'][] = 't.add_date DESC';
        }

		if(is_numeric($params))
		{
			$queryParts['where'][] = '`id` = "' . $params . '" ';
		}

        if(!empty($params['search']))
		{
			$list = explode(' ', $params['search']);
			foreach($list as $word)
			{
				$word = dbSE(trim($word));
				if( !empty($word) )
				{
					$queryParts['where'][] = '(
											`t`.`message`          LIKE "%' . $word . '%"
											OR `t`.`file`          LIKE "%' . $word . '%"
											OR `t`.`context`          LIKE "%' . $word. '%"
						OR `t`.`user_ip`       LIKE "%' . $word . '%"
						OR `t`.`add_date`      LIKE "%' . $word . '%"
									)';
				}
			}
		}

		if (!empty($params['returnRows']))
		{
            return dbgetall( $queryParts, 'id' );
		}
		return new pagedObjectCollection(get_called_class(), $queryParts, $itemsPerPage, $pageNo);
	}

    public static function deleteByHash($hash)
    {
        $q = 'DELETE FROM `' . self::getClassTable(get_called_class()) . '` WHERE `hash` = "' . dbSE($hash) . '"';
        dbQuery($q);
    }

    public static function deleteAll()
    {
        $q = 'TRUNCATE TABLE `' . self::getClassTable(get_called_class()) . '`';
        dbQuery($q);
    }

    public static function deleteBefore($timeStamp)
    {
        $q = 'DELETE FROM `' . self :: getClassTable(get_called_class()) . '` WHERE `add_date` <= \'' . dbSE(date('Y-m-d H:i:s', $timeStamp)) . '\'';
        dbQuery($q);
		if (dbGetOne('SELECT COUNT(*) FROM `' . self :: getClassTable(get_called_class()) . '`') == 0)
		{
			$q = 'OPTIMIZE TABLE `' . self :: getClassTable(get_called_class()) . '`';
			dbQuery($q);
		}
    }
    
    public static function listSensitiveKeyPatterns()
    {
        $patterns = array
        (
            '(\S*)password(\S*).*'
        );

        if( leaf_get( 'properties', 'leafError', 'customSensitiveKeyPatterns' ) )
        {
            $patterns = array_merge($patterns, leaf_get( 'properties', 'leafError', 'customSensitiveKeyPatterns' ));
        }
        
        $sessionIdVar = self::getSessionIdVar();
        if (!empty($sessionIdVar))
        {
            $patterns[] = $sessionIdVar;
        }

        return $patterns;
    }
    
    public static function getSessionIdVar()
    {
        return ini_get('session.name'); // PHPSESSID
    }
        
    
    protected static function isSensitiveDataKey( $key )
    {
        $patterns = self::listSensitiveKeyPatterns();
        
        foreach ($patterns as $pattern)
        {
            if (preg_match('/^' . $pattern . '$/i',  $key))
            {
                return true;
            }
        }
        return false;
    }
    
    protected static function discreetPrintR( $var )
    {
        // at first remove all array values with sensitive keys
        if (is_array($var))
        {
            $var = self::removeSensitiveArrayValues( $var );
        }
        
        $output = print_r( $var, true );
        
        // then attempt to remove any other sensitive values from print_r output        
        $patterns = self::listSensitiveKeyPatterns();
        
        foreach ($patterns as $pattern)
        {
            // attempt to catch strings like [password] => secret
            // and remove the value (single line only)
            $pattern = '/(^\s*\[' . $pattern . '\]\s\=\>\s)(?<valuePart>.*)$/mUi';
            $output = preg_replace($pattern, '$1' . self::removedValue , $output);
        }
       
        return $output;
    }
    
    
    protected static function isSensitiveArrayKey( $key )
    {
        $patterns = self::listSensitiveKeyPatterns();
        
        foreach ($patterns as $pattern)
        {
            if (preg_match('/^' . $pattern . '$/i', $key))
            {
                return true;
            }
        }
        return false;
    }
    
    protected static function removeSensitiveArrayValues( $array )
    {
        if (is_array($array))
        {
            foreach ( $array as $key => $value )
            {
                if (self::isSensitiveArrayKey( $key ))
                {
                    $array[$key] = self::removedValue;
                    continue;
                }

                if (is_array($value))
                {
                    $array[$key] = self::removeSensitiveArrayValues( $value );
                }
            }
        }
     
        return $array;
    }    

	
}
