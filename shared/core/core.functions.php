<?
function _core_add_revision($file)
{
    $revision = getValue('REVISION');
    if($revision)
    {
        if(strpos($file, '?') === false)
        {
            $delimeter = '?';
        }
        else
        {
            $delimeter = '&';
        }
        $file .= $delimeter . 'r=' . $revision;
    }
    return $file;
}

function _core_add_css($file, $ie = '')
{
    $file = preg_replace('/(3rdpart\/jquery\/themes\/base\/)(?!jquery\.)/', '$1jquery.', $file);
    $file = _core_add_revision($file);
	if(!leaf_get('css') || !in_array($file, leaf_get('css')))
	{
		if(@stripos($ie, 'ie') === false)
		{
			leaf_set(array('css', NULL), $file);
		}
		else
		{
			leaf_set(array('css_ie', $ie, NULL), $file);
		}
	}
}

function _core_add_js($file, $ie = '')
{
	if( is_dir( $file ) )
	{
		$dir = $file;
		if( $handle = opendir($dir) )
		{
			while( ($file = readdir( $handle )) !== false)
			{
				if( preg_match( "#.js$#", $file ) )
				{
                    $file = _core_add_revision($file);
					if(!leaf_get('js') || !in_array($dir . '/' . $file, leaf_get('js')))
					{
						if(@stripos($ie, 'ie') === false)
						{
							leaf_set(array('js', NULL), $dir . '/' . $file);
						}
						else
						{
							leaf_set(array('js_ie', $ie, NULL), $dir . '/' . $file);
						}
					}
				}
			}
			closedir( $handle );
		}
	}
	else
	{
	    $file = preg_replace('/(3rdpart\/jquery\/ui\/)(?!jquery\.|i18n)/', '$1jquery.', $file);
        $file = _core_add_revision($file);
		if(!leaf_get('js') || !in_array($file, leaf_get('js')))
		{
			if(@stripos($ie, 'ie') === false)
			{
				leaf_set(array('js', NULL), $file);
			}
			else
			{
				leaf_set(array('js_ie', $ie, NULL), $file);
			}
		}
	}
}

function _core_add_rss($url, $title = 'rss')
{
	if(!leaf_get('rss') || !in_array($url, leaf_get('rss')))
	{
		leaf_set(array('rss', NULL), array('url' => $url, 'title' => $title));
	}
}

function  _core_stripslashes_deep($value){
	return (is_array($value) ? array_map('_core_stripslashes_deep', $value) : stripslashes($value));
}

function _core_init(){
	//remove_slashes
	if (
        (function_exists('get_magic_quotes_gpc'))
        &&
        (get_magic_quotes_gpc())
    )
	{
	  $_GET    = array_map('_core_stripslashes_deep', $_GET);
	  $_POST  = array_map('_core_stripslashes_deep', $_POST);
	  $_COOKIE = array_map('_core_stripslashes_deep', $_COOKIE);
	}
	//load variables from config file
	if (!empty($GLOBALS['config']))
	{
		leaf_set('properties', $GLOBALS['config']);
		unset($GLOBALS['config']);
	}
	//define variables from config file
	if (!empty($GLOBALS['def_config']))
	{
		foreach($GLOBALS['def_config'] as $key=>$value)
		{
			define($key, $value);
		}
		unset($GLOBALS['def_config']);
	}
	//load object types
	if (!empty($GLOBALS['objects_config']))
	{
		leaf_set('objects_config', $GLOBALS['objects_config']);
		unset($GLOBALS['objects_config']);
	}
	//include files
	require_once SHARED_PATH . 'classes/leaf_smarty.class.php';
	require_once SHARED_PATH . 'classes/leaf_module.class.php';
	require_once SHARED_PATH . 'core/db.functions.php';
	leafAutoloader('leafError');

	if(!defined('VERSION'))
    {
		//load variables from db
		$q = '
		SELECT
			`name`,
			`value`
		FROM
			`' . DB_PREFIX . 'variables`
		';
		$result = dbQuery($q);
		while ($entry = $result->fetchRow())
		{
			leaf_set(array('properties', $entry['name']), $entry['value']);
		}
	}
	//preload custom_apis classes paths
	loadValues('custom_apis.');
}

/**
 *  _core_load_module function @global usage
 *
 * @staticvar integer $staticvar return module object on succesful load or false
 * @param string $module_name module name
 * @param string $check_function module access check function, NULL - checking in $global config array, FALSE - skip access check, string = access check function
 * @return integer
 */
function _core_load_module($module_name, $check_function = NULL, $createInstance = true){
	$parts = explode('/', $module_name);
	if(($cnt = sizeof($parts)) > 1)
	{
		$module_path =  $module_name . '/module.php';
		$module_name = $parts[$cnt - 1];
	}
	else
	{
		$module_path = PATH . 'modules/' . $module_name . '/module.php';
	}
	//check for this module access
	if (
			!is_file($module_path)
			||
			(
				($check_function === NULL  && leaf_get('config', 'modules', $module_name))
				||
				($check_function && !$check_function($module_name))
			)
		)
	{
		die('unexisting module: ' . $module_name);
	}
	if (!class_exists($module_name, false))
	{
		require_once($module_path);
	}
    if ($createInstance)
    {
        return new $module_name();
    }
    else
    {
        return true;
    }
}
/**
 * A function for object loading
 * @param integer/array $object object id or array
 * @param string $returnMethod method name for direct output return
 * @param array $apply_properties apply these properties after object loading
 * @return object|method output
 */
function _core_load_object($object, $returnMethod = NULL, $apply_properties = NULL){
	require_once(SHARED_PATH . 'classes/leaf_object_module.class.php');
	if(!is_array($object))
	{
		$q = '
		SELECT
			*
		FROM
			`' . DB_PREFIX . 'objects`
		WHERE
			`id` = "' . dbSE( $object ) . '"
		';
		$object = dbGetRow($q);
        
        if( !$object )
        {
            return NULL;
        }
	}
	if(!empty($apply_properties))
	{
		$object = array_merge($object, $apply_properties);
	}
	$object_module = leaf_get('objects_config', $object['type']);
	$object_module_path = SHARED_PATH . 'objects/' . $object_module['name'] . '/module.php';
	//check for this module access
	if (!is_file($object_module_path))
	{
		return NULL;
	}
	//get xml template
	if(isset($object['type']) && $object['type'] == 22 && !empty($object['template']))
	{
		require_once(SHARED_PATH . 'classes/xmlize.class.php');
		require_once($object_module_path);
		$xmlize = new xmlize(PATH . 'xml_templates/');
		//get object instance
		$instance = $xmlize->getObject($object);
	}
	else
	{
		require_once($object_module_path);
		//get object instance
		$instance = new $object_module['name']($object);
	}
	//returnMethod
	if($returnMethod)
	{
		return $instance->$returnMethod();
	}
	else
	{
		return $instance;
	}
}

function _core_output(){
	$modules_output = array();
	$main_module = false;
	$properties = leaf_get('properties', 'modules');
	foreach($properties as $module_name => $module_config)
	{
		if($module_config['load'])
		{
			//set return function name
			if(!isset($module_config['load_function']))
			{
				$module_config['load_function']  = 'output';
			}
			if(isset($module_config['main']))
			{
				$module_config['module_name'] = $module_name;
				$main_module = $module_config;
				continue;
			}
			//load module
			$module = _core_load_module($module_name);
			//set config
			if(isset($module_config['config']))
			{
				$module->config = array_merge($module->config, $module_config['config']);
			}
			if($module_config['return'])
			{
				$module_output = $module->$module_config['load_function']();

				if(isset($module_config['return_name']))
				{
					if($module_config['return_name'])
					{
						$modules_output[$module_config['return_name']] = $module_output;
					}
					else
					{
						$modules_output = array_merge($modules_output, $module_output);
					}
				}
				else{
					$modules_output[$module_name] = $module_output;
				}
			}
			else
			{
				$module->$module_config['load_function']();
			}
		}
	}
	if($main_module)
	{
		$module = _core_load_module($main_module['module_name']);
		return $module->$main_module['load_function']($modules_output);
	}
}
?>
