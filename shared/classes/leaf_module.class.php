<?
class leaf_module{

	var $module_path = NULL;
	var $actions = array();
	var $output_actions = array();
	var $default_output_function = 'view';
	var $header_string = NULL;
	var $moduleUrlParts = array();

	protected $currentOutputMethod;

	function leaf_module(){
		//get class name
		$this->_class_name = get_class($this);
		//detect module location variables
		if(!($this->module_path  = leaf_get('leaf_class_paths', $this->_class_name)))
		{
			$this->module_path = realpath(PATH) . '/modules/' . $this->_class_name . DIRECTORY_SEPARATOR;
			leaf_set(array('leaf_class_paths', $this->_class_name), $this->module_path);
		}
		//guess current module location under www
		$str1 = explode(DIRECTORY_SEPARATOR , realpath(PATH));
		$str2 = explode(DIRECTORY_SEPARATOR, $this->module_path);
		$cnt = sizeof($str1);
		for($i = 0; $i < $cnt; $i++)
		{
			if($str1[$i] != $str2[$i])
			{
				break;
			}
		}
		if($cnt != $i)
		{
			$current_web_location = str_replace('/' . $str1[$i] . '/', '/', WWW);
		}
		else
		{
			$current_web_location = WWW;
		}
		$path = array_slice($str2, $i);
		//set location variables
		$this->module_www = $current_web_location .  implode('/', $path);
		$this->module_path = $this->module_path;
		if($this->header_string === NULL)
		{
			$this->header_string = '?module=' . $this->_class_name;
		}
		$this->addUrlPart('module', $this->_class_name);
		$this->header_string = WWW . $this->header_string;
		$file = realpath(SHARED_PATH . 'custom_apis') . DIRECTORY_SEPARATOR . $this->_class_name  . DIRECTORY_SEPARATOR . '_init.php';
		if (
            (file_exists($file))
            &&
            (is_readable($file))
        )
		{

		    require_once $file;
		}
		// check assigns
		if(!empty($this->assigns))
		{
			foreach($this->assigns as $name)
			{
				if($name == 'css')
				{
					$file = $this->module_www . 'style.css';
				}
				elseif($name == 'js')
				{
					$file = $this->module_www . 'behaviour.js';
				}
				else
				{
					$file = $this->module_www . $name;
				}
				$fileParts = pathinfo($file);
				$extension = strtolower($fileParts['extension']);
				if($extension == 'js')
				{
					_core_add_js($file);
				}
				elseif($extension == 'css')
				{
					_core_add_css($file);
				}
			}
		}
	}

	function addUrlPart($var, $value){
		$this->moduleUrlParts[$var] = $value;
	}

	function getModuleUrlParts(){
		$skipKeys = func_get_args();
		$parts = array();
		foreach($this->moduleUrlParts as $key => $value)
		{
			if(!in_array($key, $skipKeys))
			{
				$parts[$key] = $value;
			}
		}
		return $parts;
	}

	function getModuleUrl(){
		$skipKeys = func_get_args();
		foreach($this->moduleUrlParts as $key => $value)
		{
			if(!in_array($key, $skipKeys))
			{
				$parts[] = $key . '=' . $value;
			}
		}
		$url = WWW . '?' . implode('&', $parts);
		return $url;
	}

	function _checkLoadMethod($method_name){
		if (
            (isset($method_name))
            &&
            (is_string($method_name))
        )
		{
			if(in_array($method_name, $this->actions))
			{
				//return simple action type
				return 1;
			}
			else if(in_array($method_name, $this->output_actions))
			{
				//return output action type
				return 2;
			}
			else
			{
				die('unallowed/unexisting method: ' . $method_name);
			}
		}
		return FALSE;
	}

	function output(){
		//check _POST action first
		if(isset($_POST['action']) && ($load_method_type = $this->_checkLoadMethod($_POST['action'])))
		{
			$load_method = $_POST['action'];
		}
		else if(isset($_GET['do']) && ($load_method_type = $this->_checkLoadMethod($_GET['do'])))
		{
			$load_method = $_GET['do'];
		}
		else
		{
			$load_method = $this->default_output_function;
			$load_method_type = 2;
		}
		// store output method
		$this->currentOutputMethod = $load_method;

		if ($load_method_type == 1)
		{
			call_user_func(array(&$this, $load_method));
			if(!empty($_POST['redirectUrl']))
			{
				$redirectUrl = $_POST['redirectUrl'];
			}
			//old modules compatibility
			else if($this->header_string != (WWW .'?module=' . $this->_class_name))
			{
				$redirectUrl = $this->header_string;
			}
			else
			{
				$redirectUrl = $this->getModuleUrl();
			}
			leafHttp::redirect($redirectUrl);
		}
		else
		{
			$methodReturn = call_user_func(array(&$this, $load_method));
			if(is_array($methodReturn) || is_null($methodReturn))
			{
				if(!empty($methodReturn['_template']))
				{
					$templateName = $methodReturn['_template'];
				}
				else
				{
					$templateName = $load_method;
				}
				$output = $this->moduleTemplate($templateName, $methodReturn);
			}
			else
			{
				$output = $methodReturn;
			}
			return $output;
		}
	}

	function moduleTemplate($template, $assigns = array())
    {
		$smarty = new leaf_smarty($this->module_path .  'templates/');
        if(!isset($this->aliasContext))
        {
            $this->aliasContext = 'admin:' . get_class($this);
        }

        // set alias context
        require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');
        alias_cache::setContext($smarty, $this->aliasContext);
		$smarty->register_outputfilter(array('alias_cache', 'fillInAliases'));

		$smarty->assign_by_ref('_module', $this);
		$smarty->assign($assigns);
		if(isset($this->options))
		{
			$smarty->assign('options', $this->options);
		}
		return $smarty->Fetch($template . '.tpl');
	}

	public function getCurrentOutputMethod()
	{
		return $this->currentOutputMethod;
	}

	public function getModuleName()
    {
        return $this->_class_name;
    }

}
?>