<?
function leafAutoloader($className)
{
	// xml template
	require_once(SHARED_PATH . 'classes/xmlize.class.php');
	if(substr($className, 0, strlen(xmlize::classPrefix) ) == xmlize::classPrefix )
	{
		require_once(SHARED_PATH . 'classes/leaf_object_module.class.php');
		require_once(SHARED_PATH . 'objects/xml_template/module.php');
		xmlize::loadClass($className);
	}
	elseif($className == 'file')
	{
		require_once(SHARED_PATH . 'classes/leaf_object_module.class.php');
		require_once(SHARED_PATH . 'objects/file/module.php');
	}
	elseif( file_exists( PATH . 'modules/' . $className . '/module.php' ) )
	{
		require_once( PATH . 'modules/' . $className . '/module.php' );
	}
	elseif( $className == 'alias_cache' )
	{
		require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');
	}
	else
	{
	    loadClassIfExists($className);
	}

	if (!class_exists ($className, false) && !interface_exists($className, false) && !trait_exists( $className, false ) )
	{
		$autoloadFunctions = spl_autoload_functions();
		// our autoload is last in list, so trigger error
		if((array_search('leafAutoloader', $autoloadFunctions) + 1) == sizeof($autoloadFunctions))
		{
			trigger_error("Class <em>$className</em> is not defined", E_USER_ERROR);
		}
	}

}
spl_autoload_register('leafAutoloader');


class CustomApisFilterIterator extends RecursiveFilterIterator
{
	public function accept()
	{
		return $this->hasChildren() || (pathinfo($this->current(), PATHINFO_EXTENSION) == 'php');
	}
}

function readCustomApisDir($dir)
{
	$path = realpath($dir);

	$iterator = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
	$iterator = new RecursiveIteratorIterator(new CustomApisFilterIterator($iterator), RecursiveIteratorIterator::SELF_FIRST);
	
	foreach ($iterator as $file)
	{
        $pathName = $iterator->getPathName();
		if (is_file($pathName) && substr($file->getFileName(), 0, 2) != "._")
        {
			setValue('custom_apis.' . $iterator->getBaseName('.class.php'), realpath($pathName));
		}
	}

	return true;
}

function loadClassIfExists( $className )
{
    return leafClassExists( $className, true );
}

function leafClassExists( $className, $load = true )
{
    if(
		class_exists( $className, false )
		||
		interface_exists( $className, false )
		||
		trait_exists( $className, false )
	)
    {
        // already loaded
        return true;
    }

    if (!isValidClassName($className))
    {
        // invalid class name
        return false;
    }

    $classPath = leafGetClassPath( $className );

    if (!$classPath)
    {
        // class file not found
        return false;
    }

    if ($load)
    {
        require_once( $classPath );
    	if (method_exists( $className, '_autoload'))
    	{
            // call_user_func( $className . '::_autoload', $className ); // only since php 5.2.3
            call_user_func( array($className, '_autoload'), $className );
    	}
    }

    return true;
}

function leafGetClassPath( $className )
{
    if (!isValidClassName($className))
    {
        return null;
    }

    $classKey  = 'custom_apis.' . $className;
	$classPath = getValue( $classKey );
    
    // verify, that right path in use

    if(strpos($classPath, SHARED_PATH) !== 0)

    {

        // path no longer accurate
        removeValue( $classKey );
        $classPath = null;

    }

    
	if ($classPath)
    {
        // path found in system values, verify that it is still valid
        if (
            (!file_exists($classPath))
            ||
            (realpath($classPath) != $classPath)
        )
        {
            // path no longer accurate
            removeValue( $classKey );
            $classPath = null;
        }
    }

    if (!$classPath)
    {
        // path not found, rescan folder if allowed
    	$noRescanKey   = $classKey . '.noRescan';
    	if (getValue($noRescanKey))
    	{
    	    // do not rescan
    	    return null;
    	}

        // do full rescan
        readCustomApisDir( SHARED_PATH . 'custom_apis/' );

        $classPath = getValue( $classKey );
        if (!$classPath)
        {
            // path not found after rescan
            return null;
        }
    }

	return $classPath;
}

function isValidClassName( $className )
{
    if (!preg_match('/^[a-zA-Z0-9]+$/', $className))
    {
        return false;
    }
    return true;
}


function getObject($className, $objectId = NULL, $noCache = false){
    if ( is_null($objectId) )
    {
        $objectId = 0;
    }
	if(is_array($objectId))
	{
		if(isset($objectId['id']))
		{
			$objectKey = $objectId['id'];
		}
		else
		{
			$objectKey = $className;
		}
	}
	else
	{
		$objectKey = $objectId;
	}

	// get from cache
	$objInstance = null;
	if (is_null($objectKey))
	{
		$objInstance = leaf_get('storedObjects', $className);
	}
	elseif (!$noCache)
	{
		$objInstance = leaf_get('storedObjects', $className, $objectKey);
	}

	// get args
	$args = array( $objectId );

	// get new instance
	if(!$objInstance)
	{
        // check and load if not exist
        class_exists($className);

		if(method_exists($className, 'getInstance'))
		{
			$objInstance = call_user_func_array(array($className, 'getInstance'), $args);
		}
		else
		{
			$objInstance = call_user_func_array(array(new ReflectionClass($className), 'newInstance'), $args);
		}
		if(($objInstance instanceof leafBaseObject) && !$objInstance->existing())
		{
			$objInstance = NULL;
		}
		if($objectKey !== 0)
		{
			if(is_null($objectKey))
			{
				leaf_set(array('storedObjects', $className), $objInstance);
			}
			else
			{
				leaf_set(array('storedObjects', $className, $objectKey), $objInstance);
			}
		}
	}
	return $objInstance;
}
function unsetObject(&$object){
	$object = NULL;
}
function putQuery($query, $id, $hash = NULL){
	if(!is_null($hash))
	{
		$hash = md5($hash);
	}
	else
	{
		$hash = md5($query['q']);
	}
	leaf_set(array('querys', $hash, 'query'), $query['q']);
	leaf_set(array('querys', $hash, 'keyField'), $query['keyField']);
	leaf_set(array('querys', $hash, 'list', $id), $id);
}

function getQuery($request, $id = NULL, $hash = NULL, $reload = false){
	if(!is_null($hash))
	{
		$hash = md5($hash);
	}
	elseif(!empty($request['hash']))
	{
		$hash = md5($request['hash']);
	}
	else
	{
		$hash = md5($request['q']);
	}

	if(!($realRequest = leaf_get('querys', $hash)))
	{
		$realRequest = $request;
	}

	if(
		!($results = leaf_get('querys', $hash, 'results'))
		||
		(
			$reload
		)
	)
	{
		$idList = array();
		if(!is_null($id))
		{
			$request['list'][$id] = (string) $id;
		}
		if(!empty($request['list']))
		{
			foreach($request['list'] as $key)
			{
				$idList[] = (string) $key;
			}
			$idList = '"' . implode('","', $idList) . '"';
			$q = str_replace('{$}', $idList, $request['q']);

		}
		else
		{
			$q = $request['q'];
		}
		$r = dbQuery($q);
		$results = array();
		while($row = $r->fetchRow())
		{
			if(true)
			{
				$results[$row[$request['keyField']]] = $row;
			}
		}
		leaf_set(array('querys', $hash, 'results'), $results);
	}
	if($id === NULL)
	{
		return $results;
	}
	elseif(isset($results[$id]))
	{
		return $results[$id];
	}
	else
	{
		return NULL;
	}
}
// compatible check for php4, php5
function is_instance_of($IIO_INSTANCE, $IIO_CLASS){
	if($IIO_INSTANCE instanceof $IIO_CLASS)
	{
		return true;
	}
	else
	{
		return false;
	}
}

######### for debug
function debug($variable, $die=true)
{
    trigger_error('debug() is now called dump()', E_USER_DEPRECATED);
    return dump($variable, $die);
}

function dump($variable, $die=true){
	//output array
    if (
        (is_scalar($variable))
        ||
        (is_null($variable))
    )
    {
    	if (is_null($variable))
    	{
    	    $output = '<i>NULL</i>';
    	}
    	elseif (is_bool($variable))
        {
            $output = '<i>' . (($variable) ? 'TRUE' : 'FALSE') . '</i>';

        }
        else {
            $output = $variable;
        }
    }
    else // non-scalar
    {
		$output = print_r($variable, true);
    }

    if(defined("CLI_MODE"))
    {
        echo 'variable: ' . $output . "\n";
    }
    else
    {
        echo '<pre>variable: ' . $output . '</pre>';
    }

	if ($die)
	{
        die();
	}
}
   
function dumpToFile($variable, $comment = null, $file = null)
{
	ob_start();
	$stamp = date('Y-m-d H:i:s');
	if (!empty($_SERVER['REMOTE_ADDR']))
	{
        $stamp .= '; ' . $_SERVER['REMOTE_ADDR'];
	}
	echo $stamp . "\n";
	if (!empty($comment))
	{
		echo $comment . "\n";
	}
	dump($variable, 0);
	echo "\n\n";
	$content = ob_get_contents();
	if(empty($file))
	{
		if(defined('LOG_PATH'))
		{
			$logDir = LOG_PATH;
			if(!is_dir($logDir))
			{
				@mkdir($logDir, 0777);
			}
		}
		else
		{
			$logDir = CACHE_PATH;
		}
		$file = $logDir . 'log-' . date('Y-m-d') . '.log';
	}
	@file_put_contents($file, $content, FILE_APPEND);
	chmod($file, 0664);
	ob_end_clean();
}
   
function get_property($name, $lang = false){
    if (
		!$lang
		||
		(
			(!isset($GLOBALS['properties']))
			||
			(!is_array($GLOBALS['properties']))
			||
			(!isset($GLOBALS['properties']['language_name']))
			||
			(!$GLOBALS['properties']['language_name'])
		)
    )
	{
		$lang = false;
		$lang_prefix = NULL;
	}
	else
	{
		$lang = leaf_get('properties', 'language_name');
		$lang_prefix = $lang . '_';
	}
	//root properties
	if($name == 'root' && !isset($GLOBALS['properties'][$lang_prefix . 'root']))
	{
		if(!isset($GLOBALS['properties']['root']))
		{
			$GLOBALS['properties']['root'] = 0;
		}
		//try to get from db
		if($lang)
		{
			$q = '
			SELECT
				`id`
			FROM
				`objects`
			WHERE
				`rewrite_name` = "' . $lang . '" AND
				`parent_id` = "' .  get_property('root', false) . '"
			';
			if(($root_id = dbGetOne($q)) != NULL)
			{
				$GLOBALS['properties'][$lang_prefix . 'root'] = $root_id;
			}
			else
			{
				die('no ' . $lang . ' root');
			}
		}
	}

	if(isset($GLOBALS['properties'][$lang_prefix . $name]))
	{
		return $GLOBALS['properties'][$lang_prefix . $name];
	}
	else {
		die("Property: <span style=\"text-decoration:underline; font-weight: bold;\">$lang_prefix$name</span> doesn't exist");
	}
}

function object_name($object_id){
	$q = '
	SELECT
		name
	FROM
		objects
	WHERE
		id = "' . dbSE($object_id) . '"
	';
	if(($name = dbGetOne($q)) != NULL)
	{
		return $name;
	}
	return $object_id;
}
function orp($objectIdOrObject){
	if(!isPositiveInt($objectIdOrObject))
	{
	   if(is_object($objectIdOrObject) && is_subclass_of($objectIdOrObject, 'leaf_object_module') && isPositiveInt($objectIdOrObject->object_data['id']))
	   {
            return leafObjectsRewrite::getUrl($objectIdOrObject->object_data['id']);
	   }
		return $objectIdOrObject;
	}
	return leafObjectsRewrite::getUrl($objectIdOrObject);
}
function object_rewrite_path($object_id){
	return orp($object_id);
}
function translate_objects_id($text, $plainText = false){
	if ($plainText)
	{
		$pattern = '/(\?object_id=)(\d*)/i';
	}
	else
	{
		$pattern = '/(href|src)=\"([a-zA-Z0-9|\.|\&|\;|\:|\/\/]*)\?object_id=(\d*)(.*?)(\")/i';
	}

	return preg_replace_callback(
           $pattern,
           "get_object_id_url",
           $text);
}
function get_object_id_url($matches)
{
	if($matches[1] == '?object_id=' && $url = orp($matches[2]))
	{
		return $url;
	}
	$allowedMatches = array('', '/', './', '../', WWW, 'index.php');
	if(in_array($matches[2], $allowedMatches) && $url = orp($matches[3]))
	{
		return $matches[1] . '="' . $url . $matches[4] . '"';
	}
	else
	{
		return $matches[0];
	}
}
function get_object_file_path($object_id,$thumb = false){
        $q='
        SELECT
                file_name,
                extra_info
        FROM
                files
        WHERE
                object_id="'.$object_id.'"
        ';
        $file=dbGetRow($q);
        $file['extra_info']=unserialize($file['extra_info']);
        return ($thumb && isset($file['extra_info']['thumbnail_size'])) ? 'thumb_' . $file['file_name'] : $file['file_name'];
}

function clear_query_string($delete_get, $amp_convert = true, $query_vars = NULL){
	if($query_vars === NULL)
	{
		$query_vars = $_SERVER['QUERY_STRING'];
	}
	$amp = ($amp_convert ? '&amp;' : '&');
	if(!is_array($query_vars))
	{
		//split query string
		$query_vars = explode('&', $query_vars);
		$cnt = sizeof($query_vars);
		for($i = 0; $i < $cnt; $i++)
		{
			$var = explode('=', $query_vars[$i]);
			if(sizeof($var) == 2)
			{
				$tmp_array[$var[0]] = isset($var[1]) ? $var[1] : '';
			}
		}
		$query_vars = $tmp_array;
	}
	//parse string as single variable array
	if (!is_array($delete_get))
	{
	    $delete_get = array($delete_get);
	}
	$query_keys = array_keys($query_vars);
	$cnt = sizeof($query_keys);
	$output = '';
	for($i = 0; $i < $cnt; $i++)
	{
		if(!in_array($query_keys[$i], $delete_get))
		{
			$output .= $query_keys[$i] . '=' . $query_vars[$query_keys[$i]] . $amp;
		}
	}
	return $output;
}



function leafError($errno, $errstr, $errfile, $errline, $errcontext, $stackTrace = null)
{
    if (ini_get('html_errors'))
    {
        $errstr = strip_tags($errstr);
    }
    
	$error_reporting = error_reporting();
	if( $error_reporting != 0  )
	{
		// filter smarty notice level errors
		if
		(
			!(
				( $errno == E_NOTICE && preg_match( '/smartycache_/', $errfile ) )
				||
				( $errno == E_RECOVERABLE_ERROR && preg_match( '/smarty_plugins/', $errfile) )
			)
		)
		{
			// error can only be logged if db is functional
			// by this time, there is access to custom apis
            if( function_exists( 'dbIsConnected' ) && dbIsConnected() )
			{
				$params = array
				(
					'message' => $errstr,
					'file' => $errfile,
					'line' => $errline,
					'level' => $errno,
					'context' => $errcontext,
				);
                if ($stackTrace != null)
                {
                    $params['stackTrace'] = $stackTrace;
                }
				leafError::create( $params );
			}
		}
	}
	
	if (($errno == E_USER_ERROR) && ($protocol = get($_SERVER, 'SERVER_PROTOCOL')) && (!headers_sent()))
	{
        $code     = 500;
        $message  = 'Internal Server Error';
        $header   = $protocol . ' ' . $code . ' ' . $message;
        header($header, true, $code);
	}
	
	if ($error_reporting != 0 && ini_get('display_errors') == 1)
	{
		// output error
		if (($errno == E_USER_ERROR) && ($error_reporting & E_USER_ERROR))
		{
			// create error object
			require_once(SHARED_PATH . 'classes/leaf_error/leaf_error.class.php');
			$leaf_error = new leaf_error;
			$message = array
			(
				'header' => 'Leaf error',
				'msg' => $errstr
			);
			$leaf_error->addMessage($message);
			$leaf_error->display();
		}
		else if ($error_reporting & $errno) // current error reporting includes the level of the given error
		{
            // The following error types cannot be handled with a user defined function: 
            // E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING
            switch ( $errno )
            {
                case E_USER_ERROR:
                    $errorName =  'ERROR';
                    break;

                case E_NOTICE:
                case E_USER_NOTICE:
                    $errorName =  'NOTICE';
                    break;
                    
                case E_WARNING:
                case E_USER_WARNING:
                    $errorName =  'WARNING';
                    break;

                case E_STRICT:
                    $errorName =  'STRICT ERRROR';
                    break;

                case E_RECOVERABLE_ERROR:
                    $errorName =  'RECOVERABLE ERROR';
                    break;

                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                    $errorName =  'DEPRECATED ERROR';
                    break;

                default:
                    $errorName =  'UNKNOWN ERROR';
            }
            $errorOutput = "<strong style=\"color: red\">Leaf "  . $errorName . ":</strong> $errstr in <strong>$errfile</strong> on line <strong>$errline</strong><br />\n" ;
            if (php_sapi_name() == 'cli')
            {
                $errorOutput = strip_tags($errorOutput);
                file_put_contents('php://stderr', $errorOutput);
            }
            else
            {
                echo $errorOutput;
            }
        }
    }

    if ($errno == E_USER_ERROR )
    {
        if (php_sapi_name() != 'cli')
        {
            $language = leaf_get('properties', 'language_name');
            $pathBase = SHARED_PATH . '../errors/500.';
            $path = $pathBase . $language . '.html';

            // fallback to en
            if(!file_exists($path))
            {
                $path = $pathBase . 'en.html';
            }
            if(file_exists($path))
            {
                echo file_get_contents($path);
            }
        }
        exit;
    }
}

function leafException( $e )
{
    leafError(E_USER_ERROR, $e->getMessage(), $e->getFile(), $e->getLine(), null, print_r($e->getTrace(), true));
}


function stringToLatin($string, $replaceNonLetters = false, $objectRewrite = false){
	$translations = array('À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','Å'=>'A','Æ'=>'AE','Ā'=>'A','Ą'=>'A','Ă'=>'A','Ç'=>'C','Ć'=>'C','Č'=>'C','Ĉ'=>'C','Ċ'=>'C','Ď'=>'D','Đ'=>'D','È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E','Ē'=>'E','Ę'=>'E','Ě'=>'E','Ĕ'=>'E','Ė'=>'E','Ĝ'=>'G','Ğ'=>'G','Ġ'=>'G','Ģ'=>'G','Ĥ'=>'H','Ħ'=>'H','Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I','Ī'=>'I','Ĩ'=>'I','Ĭ'=>'I','Į'=>'I','İ'=>'I','Ĳ'=>'IJ','Ĵ'=>'J','Ķ'=>'K','Ľ'=>'K','Ĺ'=>'K','Ļ'=>'K','Ŀ'=>'K','Ł'=>'L','Ñ'=>'N','Ń'=>'N','Ň'=>'N','Ņ'=>'N','Ŋ'=>'N','Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','Ø'=>'O','Ō'=>'O','Ő'=>'O','Ŏ'=>'O','Œ'=>'OE','Ŕ'=>'R','Ř'=>'R','Ŗ'=>'R','Ś'=>'S','Ş'=>'S','Ŝ'=>'S','Ș'=>'S','Š'=>'S','Ť'=>'T','Ţ'=>'T','Ŧ'=>'T','Ț'=>'T','Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'Ue','Ū'=>'U','Ů'=>'U','Ű'=>'U','Ŭ'=>'U','Ũ'=>'U','Ų'=>'U','Ŵ'=>'W','Ŷ'=>'Y','Ÿ'=>'Y','Ý'=>'Y','Ź'=>'Z','Ż'=>'Z','Ž'=>'Z','à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','ā'=>'a','ą'=>'a','ă'=>'a','å'=>'a','æ'=>'ae','ç'=>'c','ć'=>'c','č'=>'c','ĉ'=>'c','ċ'=>'c','ď'=>'d','đ'=>'d','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ē'=>'e','ę'=>'e','ě'=>'e','ĕ'=>'e','ė'=>'e','ƒ'=>'f','ĝ'=>'g','ğ'=>'g','ġ'=>'g','ģ'=>'g','ĥ'=>'h','ħ'=>'h','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ī'=>'i','ĩ'=>'i','ĭ'=>'i','į'=>'i','ı'=>'i','ĳ'=>'ij','ĵ'=>'j','ķ'=>'k','ĸ'=>'k','ł'=>'l','ľ'=>'l','ĺ'=>'l','ļ'=>'l','ŀ'=>'l','ñ'=>'n','ń'=>'n','ň'=>'n','ņ'=>'n','ŉ'=>'n','ŋ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ø'=>'o','ō'=>'o','ő'=>'o','ŏ'=>'o','œ'=>'oe','ŕ'=>'r','ř'=>'r','ŗ'=>'r','ś'=>'s','š'=>'s','ť'=>'t','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ū'=>'u','ů'=>'u','ű'=>'u','ŭ'=>'u','ũ'=>'u','ų'=>'u','ŵ'=>'w','ÿ'=>'y','ý'=>'y','ŷ'=>'y','ż'=>'z','ź'=>'z','ž'=>'z','ß'=>'ss','ſ'=>'ss','Α'=>'A','Ά'=>'A','Ἀ'=>'A','Ἁ'=>'A','Ἂ'=>'A','Ἃ'=>'A','Ἄ'=>'A','Ἅ'=>'A','Ἆ'=>'A','Ἇ'=>'A','ᾈ'=>'A','ᾉ'=>'A','ᾊ'=>'A','ᾋ'=>'A','ᾌ'=>'A','ᾍ'=>'A','ᾎ'=>'A','ᾏ'=>'A','Ᾰ'=>'A','Ᾱ'=>'A','Ὰ'=>'A','Ά'=>'A','ᾼ'=>'A','Β'=>'B','Γ'=>'G','Δ'=>'D','Ε'=>'E','Έ'=>'E','Ἐ'=>'E','Ἑ'=>'E','Ἒ'=>'E','Ἓ'=>'E','Ἔ'=>'E','Ἕ'=>'E','Έ'=>'E','Ὲ'=>'E','Ζ'=>'Z','Η'=>'I','Ή'=>'I','Ἠ'=>'I','Ἡ'=>'I','Ἢ'=>'I','Ἣ'=>'I','Ἤ'=>'I','Ἥ'=>'I','Ἦ'=>'I','Ἧ'=>'I','ᾘ'=>'I','ᾙ'=>'I','ᾚ'=>'I','ᾛ'=>'I','ᾜ'=>'I','ᾝ'=>'I','ᾞ'=>'I','ᾟ'=>'I','Ὴ'=>'I','Ή'=>'I','ῌ'=>'I','Θ'=>'TH','Ι'=>'I','Ί'=>'I','Ϊ'=>'I','Ἰ'=>'I','Ἱ'=>'I','Ἲ'=>'I','Ἳ'=>'I','Ἴ'=>'I','Ἵ'=>'I','Ἶ'=>'I','Ἷ'=>'I','Ῐ'=>'I','Ῑ'=>'I','Ὶ'=>'I','Ί'=>'I','Κ'=>'K','Λ'=>'L','Μ'=>'M','Ν'=>'N','Ξ'=>'KS','Ο'=>'O','Ό'=>'O','Ὀ'=>'O','Ὁ'=>'O','Ὂ'=>'O','Ὃ'=>'O','Ὄ'=>'O','Ὅ'=>'O','Ὸ'=>'O','Ό'=>'O','Π'=>'P','Ρ'=>'R','Ῥ'=>'R','Σ'=>'S','Τ'=>'T','Υ'=>'Y','Ύ'=>'Y','Ϋ'=>'Y','Ὑ'=>'Y','Ὓ'=>'Y','Ὕ'=>'Y','Ὗ'=>'Y','Ῠ'=>'Y','Ῡ'=>'Y','Ὺ'=>'Y','Ύ'=>'Y','Φ'=>'F','Χ'=>'X','Ψ'=>'PS','Ω'=>'O','Ώ'=>'O','Ὠ'=>'O','Ὡ'=>'O','Ὢ'=>'O','Ὣ'=>'O','Ὤ'=>'O','Ὥ'=>'O','Ὦ'=>'O','Ὧ'=>'O','ᾨ'=>'O','ᾩ'=>'O','ᾪ'=>'O','ᾫ'=>'O','ᾬ'=>'O','ᾭ'=>'O','ᾮ'=>'O','ᾯ'=>'O','Ὼ'=>'O','Ώ'=>'O','ῼ'=>'O','α'=>'a','ά'=>'a','ἀ'=>'a','ἁ'=>'a','ἂ'=>'a','ἃ'=>'a','ἄ'=>'a','ἅ'=>'a','ἆ'=>'a','ἇ'=>'a','ᾀ'=>'a','ᾁ'=>'a','ᾂ'=>'a','ᾃ'=>'a','ᾄ'=>'a','ᾅ'=>'a','ᾆ'=>'a','ᾇ'=>'a','ὰ'=>'a','ά'=>'a','ᾰ'=>'a','ᾱ'=>'a','ᾲ'=>'a','ᾳ'=>'a','ᾴ'=>'a','ᾶ'=>'a','ᾷ'=>'a','β'=>'b','γ'=>'g','δ'=>'d','ε'=>'e','έ'=>'e','ἐ'=>'e','ἑ'=>'e','ἒ'=>'e','ἓ'=>'e','ἔ'=>'e','ἕ'=>'e','ὲ'=>'e','έ'=>'e','ζ'=>'z','η'=>'i','ή'=>'i','ἠ'=>'i','ἡ'=>'i','ἢ'=>'i','ἣ'=>'i','ἤ'=>'i','ἥ'=>'i','ἦ'=>'i','ἧ'=>'i','ᾐ'=>'i','ᾑ'=>'i','ᾒ'=>'i','ᾓ'=>'i','ᾔ'=>'i','ᾕ'=>'i','ᾖ'=>'i','ᾗ'=>'i','ὴ'=>'i','ή'=>'i','ῂ'=>'i','ῃ'=>'i','ῄ'=>'i','ῆ'=>'i','ῇ'=>'i','θ'=>'th','ι'=>'i','ί'=>'i','ϊ'=>'i','ΐ'=>'i','ἰ'=>'i','ἱ'=>'i','ἲ'=>'i','ἳ'=>'i','ἴ'=>'i','ἵ'=>'i','ἶ'=>'i','ἷ'=>'i','ὶ'=>'i','ί'=>'i','ῐ'=>'i','ῑ'=>'i','ῒ'=>'i','ΐ'=>'i','ῖ'=>'i','ῗ'=>'i','κ'=>'k','λ'=>'l','μ'=>'m','ν'=>'n','ξ'=>'ks','ο'=>'o','ό'=>'o','ὀ'=>'o','ὁ'=>'o','ὂ'=>'o','ὃ'=>'o','ὄ'=>'o','ὅ'=>'o','ὸ'=>'o','ό'=>'o','π'=>'p','ρ'=>'r','ῤ'=>'r','ῥ'=>'r','σ'=>'s','ς'=>'s','τ'=>'t','υ'=>'y','ύ'=>'y','ϋ'=>'y','ΰ'=>'y','ὐ'=>'y','ὑ'=>'y','ὒ'=>'y','ὓ'=>'y','ὔ'=>'y','ὕ'=>'y','ὖ'=>'y','ὗ'=>'y','ὺ'=>'y','ύ'=>'y','ῠ'=>'y','ῡ'=>'y','ῢ'=>'y','ΰ'=>'y','ῦ'=>'y','ῧ'=>'y','φ'=>'f','χ'=>'x','ψ'=>'ps','ω'=>'o','ώ'=>'o','ὠ'=>'o','ὡ'=>'o','ὢ'=>'o','ὣ'=>'o','ὤ'=>'o','ὥ'=>'o','ὦ'=>'o','ὧ'=>'o','ᾠ'=>'o','ᾡ'=>'o','ᾢ'=>'o','ᾣ'=>'o','ᾤ'=>'o','ᾥ'=>'o','ᾦ'=>'o','ᾧ'=>'o','ὼ'=>'o','ώ'=>'o','ῲ'=>'o','ῳ'=>'o','ῴ'=>'o','ῶ'=>'o','ῷ'=>'o','¨'=>'','΅'=>'','᾿'=>'','῾'=>'','῍'=>'','῝'=>'','῎'=>'','῞'=>'','῏'=>'','῟'=>'','῀'=>'','῁'=>'','΄'=>'','΅'=>'','`'=>'','῭'=>'','ͺ'=>'','᾽'=>'','А'=>'A','Б'=>'B','В'=>'V','Г'=>'G','Д'=>'D','Е'=>'E','Ё'=>'E','Ж'=>'ZH','З'=>'Z','И'=>'I','Й'=>'I','К'=>'K','Л'=>'L','М'=>'M','Н'=>'N','О'=>'O','П'=>'P','Р'=>'R','С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F','Х'=>'KH','Ц'=>'TS','Ч'=>'CH','Ш'=>'SH','Щ'=>'SHCH','Ы'=>'Y','Э'=>'E','Ю'=>'YU','Я'=>'YA','а'=>'A','б'=>'B','в'=>'V','г'=>'G','д'=>'D','е'=>'E','ё'=>'E','ж'=>'ZH','з'=>'Z','и'=>'I','й'=>'I','к'=>'K','л'=>'L','м'=>'M','н'=>'N','о'=>'O','п'=>'P','р'=>'R','с'=>'S','т'=>'T','у'=>'U','ф'=>'F','х'=>'KH','ц'=>'TS','ч'=>'CH','ш'=>'SH','щ'=>'SHCH','ы'=>'Y','э'=>'E','ю'=>'YU','я'=>'YA','Ъ'=>'','ъ'=>'','Ь'=>'','ь'=>'','ð'=>'d','Ð'=>'D','þ'=>'th','Þ'=>'TH');
	$search = array_keys($translations);
	$replace = array_values($translations);
	$string = str_replace($search, $replace, $string);
	if($replaceNonLetters == true)
	{
        $string = trim($string);
        $string = preg_replace('/(n)(\'|`)(t)/i', '$1$3', $string);
		if($objectRewrite)
		{
			$string = preg_replace('/[^a-z0-9\_\!\(\)\=\$\@]/i', '-', $string);
			$string = preg_replace('/-+/', '-', $string);
			$string = preg_replace("/^\-*(.+)\-$/", "\\1", $string);
		}
		else
		{
			$string = preg_replace('/[^a-z0-9_\-\!\(\)\=\$\@]/i', '_', $string);
			$string = preg_replace('/_+/', '_', $string);
			$string = preg_replace("/^_*(.+)_$/", "\\1", $string);
		}
	}
	return $string;
}

function getInt(& $var)
{
    if (!isset($var))
    {
        $returnVar = null;
    }
    else {
        $returnVar = $var;
    }
    return (int) $returnVar;
}

function isPositiveInt (& $var)
{
    if (empty($var))
    {
        return false;
    }
    elseif (
        (is_numeric($var))
        &&
        (intval($var) == $var)
        &&
        ($var > 0)
    )
    {
        return true;
    }
    return false;
}

/**
 * Returns stored variable.
 *
 * @param string|int $arg,...
 *
 * @return mixed|null
 */
function leaf_get()
{
    if ( !isset($GLOBALS['leaf_properties']) ) {
        return null;
    }
    $args = func_get_args();
    if ( !$args )
    {
        return null;
    }
    if ( is_array($args[0]) )
    {
        if ( !$args[0] )
        {
            return null;
        }
        $args = $args[0];
    }
    $value = $GLOBALS['leaf_properties'];
    foreach ( $args as $arg )
    {
        if ( !empty( $arg ) && is_array( $value ) && array_key_exists( $arg, $value ) )
        {
            $value = $value[$arg];
        }
        else
        {
            return null;
        }
    }
    return $value;
}

function leaf_get_property($name, $die = true){
	if(!is_array($name))
	{
		$name = array($name);
	}
	$property = leaf_get(array_merge(array('properties'), $name));
	if($property === NULL && $die)
	{
		die("Property: <span style=\"text-decoration:underline; font-weight: bold;\">$name</span> doesn't exist");
	}
	return $property;
}
function leaf_set($keys, $value){
	if(is_array($keys))
	{
		$max = sizeof($keys);

		$target = & $GLOBALS['leaf_properties'];

		for ($i = 0; $i < $max; $i++)
		{
			$key = $keys[$i];
			$lastKey = ($i + 1 == $max);

			if (!is_array($target))
			{
				$target = array();
			}
			if ($lastKey)
			{
					if($key === NULL)
					{
						$target[] = $value;
					}
					else
					{
						$target[$key] = $value;
					}
			}
			else
			{
				if (
					(!isset($target[$key]))
					||
					(!is_array($target[$key]))
				)
				{
					if($key === NULL)
					{
						$target[] = array();
						$tmpKeys = array_keys($target);
						end($tmpKeys);
						$key = current($tmpKeys);
					}
					else
					{
						$target[$key] = array();
					}
				}
				//$target[$key]
				$newTarget = & $target[$key];
				unset ($target);
				$target = & $newTarget;
			}
		}
		unset ($target);
	}
	else
	{
		$GLOBALS['leaf_properties'][$keys] = $value;
	}
}
function removeValue($name)
{
	dbDelete('system_values', '`name` LIKE "' . dbSE($name) . '%"');
	foreach($GLOBALS['valueCache'] as $key => $value)
	{
		if (
            ($key == $name)
            ||
            (strpos($key, $name . '.') === 0)
		)
		{
			unset($GLOBALS['valueCache'][$key]);
		}
	}
}
function loadValues($name)
{
	// load from db
	$q = '
	SELECT
		`name`,
		`value`
	FROM
		`system_values`
	WHERE
		`name` LIKE "' . dbSE($name) . '%"
	';
	$r = dbQuery($q);
	while($item = $r->fetchRow())
	{
		$GLOBALS['valueCache'][$item['name']] = $item['value'];
	}
}
function setValue($name, $value, $dbLink = NULL)
{
	// update db
	$fields = array
	(
		'name'  => $name,
		'value' => $value,
	);
	dbReplace( 'system_values', $fields, NULL, array(), $dbLink );
	// store in cache
	$GLOBALS['valueCache'][$name] = $value;
}

function getValue($name, $cacheOnly = false, $useCache = true)
{
	// look in cache
	if($useCache && isset($GLOBALS['valueCache'][$name]))
	{
		return $GLOBALS['valueCache'][$name];
	}
	if ($useCache && $cacheOnly)
	{
	    // not found in cache, return
	    return null;
	}
	// load from db
	$q = '
	SELECT
		`value`
	FROM
		`system_values`
	WHERE
		`name` = "' . dbSE($name) . '"
	';
    $value = dbGetOne($q);
	// store in cache
    if($useCache)
    {
        $GLOBALS['valueCache'][$name] = $value;
    }
	return $value;
}

function getArgumentAsString( $argValue, $glue, $eachCallback = null)
{
    if (is_array($argValue))
    {
        if (is_callable($eachCallback))
        {
            foreach ($argValue as & $argValuePart)
            {
                $argValuePart = call_user_func($eachCallback, $argValuePart);
            }
        }
        $argValue = implode( $glue, $argValue );
    }
    else
    {
        $argValue = (string) $argValue;
    }
    return $argValue;
}

/**
 * DEPRECATED, use get()
 *
 * Checks whether an array contains the supplied key. Returns the associated
 * value on success and return parameter on failure. Similar in a way to an
 * IFNULL function.
 *
 * @param mixed $keys
 * @param array $array
 * @param mixed $return
 * @param boolean $ane
 * @return mixed
 */
function keane($key, $array, $return = false, $ane = true)
{
	if( VERSION != '1.1' && VERSION > 1.1 )
	{
		trigger_error( 'keane() is deprecated', E_USER_DEPRECATED );
	}
	
	if (is_array($array) and (!$ane or !empty($array)))
	{
		if (array_key_exists($key, $array) and (!$ane or !empty($array[$key])))
		{
			return $array[$key];
		}
	}
	return $return;
}

/**
 * returns value at array[key] or default if it's empty
 */
function get( $array, $key, $default = NULL )
{
	if( !empty( $array[$key] ) )
	{
		return $array[$key];
	}
	return $default;
}

/**
 * returns value from array by key and unset it
 */
function steal( &$array, $key )
{
    if( !is_array( $array ) || !array_key_exists( $key, $array ) )
    {
        return null;
    }
    
    $value = $array[ $key ];
    
    unset( $array[ $key ] );
    
    return $value;
}