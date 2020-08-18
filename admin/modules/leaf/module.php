<?

class leaf extends leaf_module{

	var $module_path='modules/leaf/';
	protected $authorized = false;

	function leaf(){
		parent::leaf_module();
		require_once 'objects.functions.php';
		require_once SHARED_PATH . 'classes/stopwatch.class.php';

		// jquery core
        _core_add_js(SHARED_WWW . '3rdpart/jquery/jquery-core.js');
        // css
         _core_add_css( 'styles/select-min-width.css' );

		//session
		session_start();
		//refresh sid
		if(@$_GET['do'] == 'refresh_sid')
		{
			exit;
		}
		//admin log system
		if(leaf_get('properties', 'modules', 'leaf', 'log') === true)
		{
			//skip profile css
			require_once(SHARED_PATH . 'classes/massivelog.class.php');
			$m_log = new massiveLog;
			$m_log->log();
		}
		//load config variables from cache
		if(isset($_SESSION['leafCMS']['cache']['timestamp']))
		{
			if(
				!empty($_SESSION['leafCMS']['cache']['uid']) && !empty($_SESSION[SESSION_NAME]['user']['id']) &&
				$_SESSION['leafCMS']['cache']['uid'] == $_SESSION[SESSION_NAME]['user']['id']
			)
			{
				leaf_set('object_types', $_SESSION['leafCMS']['cache']['object_types']);
				leaf_set('cache', $_SESSION['leafCMS']['cache']);
			}
			else
			{
				unset($_SESSION['leafCMS']['cache']);
			}
		}
		//read type information
		if(!leaf_get('object_types'))
        {
			leaf_set('object_types', leafObject::$types);
        }

        $userClass = leafAuthorization::getUserClass();

        //authorize
        if(isset($_GET['leafDeauthorize']))
        {
            call_user_func(array($userClass, 'deauthorize'));
            leafHttp::redirect(WWW);
        }
        else
        {
            $this->authorized = call_user_func(array($userClass, 'authorize'));
        }
	}
    

    

	function output(&$modules_output = null)
    {
		require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');
		//send headers
		if($this->authorized)
		{
            self::setUserLanguage($_SESSION[SESSION_NAME]['user']['language']);
			_core_add_js('js/site.js');
			_core_add_js(SHARED_WWW . 'js/xmlhttp.js');
			_core_add_js(SHARED_WWW . 'classes/processing/validation_assigner.js');
			if (!isset($_GET['module']))
			{
				$_GET['module'] = $_SESSION[SESSION_NAME]['user']['default_module'];
			}
			if ($module = _admin_load_module($_GET['module']))
			{
				$module_content = $module->output();
				//new style
				if(is_array($module_content))
				{
					$output = $module_content;
				}
				//old style
				else
				{
					$output['module'] = $module_content;
				}
			}
			if (!isset($_GET['single_module']))
			{
                $output['menu'] = $this->menu( $module );
                $output['profileModuleName'] = call_user_func(leafAuthorization::getUserClass() .'::getProfileModuleName');
			}
		}
		else
		{
            
            self::setUserLanguage();
			_core_add_css($this->module_www . 'login.css');
			$template = new leaf_smarty($this->module_path .  'templates/');
			if(empty($_POST) && !empty($_GET))
			{
				$template->assign('redirect_url', true);
			}
			$output['module'] = $template->fetch("login.tpl");
		}
		if(isset($_SESSION[SESSION_NAME]['user']))
		{
			$output['user_css'] = _admin_module_access('profile');
		}
		//store cache to session
		if(leaf_get('cache'))
		{
			$_SESSION['leafCMS']['cache'] = leaf_get('cache');
		}
		$_SESSION['leafCMS']['cache']['object_types'] = leaf_get('object_types');
		$_SESSION['leafCMS']['cache']['timestamp'] = time();
		if(!empty($_SESSION[SESSION_NAME]['user']['id']))
		{
			$_SESSION['leafCMS']['cache']['uid'] = $_SESSION[SESSION_NAME]['user']['id'];
		}
		// construct site title
		$siteTitle = $_SERVER['HTTP_HOST'];
		if( substr( $siteTitle, 0, 4 ) == 'www.' )
		{
			$siteTitle = substr( $siteTitle, 4 );
		}
		// assign
		$assign = array
		(
			'mainModule'  => $this,
			'siteTitle'  => $siteTitle,
			'css' 		 => leaf_get('css'),
			'css_ie' 	 => leaf_get('css_ie'),
			'js' 		 => leaf_get('js'),
			'js_ie' 	 => leaf_get('js_ie'),
			'properties' => leaf_get('properties')
        );
		
		$template = new leaf_smarty($this->module_path .  'templates/');
		$template->register_outputfilter(array('alias_cache', 'fillInAliases'));
		$template->Assign($assign);
		$template->assign($modules_output);
		$template->assign($output);
		$content = $template->fetch('content.tpl');

		require_once SHARED_PATH . '3rdpart/replacePngTags.php';
		$content = replacePngTags($content, '/images/',  'crop');

		return $content;
	}

	function menu( $currentModule = NULL )
    {
        $menuConfig = leaf_get( 'properties', 'leafMenuConfig');
        if(empty($menuConfig))
        {
            trigger_error('No enabled modules found in leafMenuConfig.', E_USER_ERROR );
        }

        $aliasCodes = array();
        $modules = array();
        foreach ($menuConfig as $moduleName)
		{
			if(_admin_module_access($moduleName))
			{
                $aliasCodes[] = $moduleName;
                $modules[] = array(
                    'module_name' => $moduleName
                );
			}
        }
        $aliases = alias_cache::getAliases($aliasCodes, 'admin:moduleNames');
        $prefixString = leaf_get('properties', 'language_id'). ':admin:moduleNames:';

        foreach($modules as &$module)
        {
            if(!empty($aliases[$module['module_name']]) &&  $prefixString . $module['module_name'] != $aliases[$module['module_name']])
            {
                $module['name'] = $aliases[$module['module_name']];
            }
        }
		
		// replace leafBaseModule in main menu with configured groups
		$menu = $modules;
		$activeGroupName = NULL;
		if( is_object( $currentModule ) && method_exists( $currentModule, 'getSubmenuGroupName' ) )
		{
			$activeGroupName = $currentModule->getSubmenuGroupName();
		}
		foreach( $menu as $key => $menuItem )
		{
			if( $menuItem['module_name'] == 'leafBaseModule' || $menuItem['module_name'] == 'emailsModule' )
			{
                $replacements = array();
                $baseModuleMenuItems = $menuItem['module_name']::getMenu();
                if(!empty($baseModuleMenuItems))
                {
                    foreach( $baseModuleMenuItems as $groupName => $group )
                    {
                        $item = array
                        (
                            'module_name' => $menuItem['module_name'],
							'icon' 		  => $menuItem['module_name']::getIcon(),
                            'isGroup' 	  => true,
                            'groupName'   => $groupName,
                        );

						if( leaf_get( 'properties', 'leafBaseModuleConfig', 'icons', $groupName ) )
						{
							$item['icon'] = leaf_get( 'properties', 'leafBaseModuleConfig', 'icons', $groupName );
						}
                        // TODO: check if module is not already in main menu
                        if( $activeGroupName == $groupName )
                        {
                            $item['isActive'] = true;
                        }
                        $replacements[] = $item;
                    }
                    array_splice( $modules, $key, 1, $replacements );
                }
			}
		}
		
		return $modules;
	}

    public static function setUserLanguage($userLanguage = null)
    {
		$q = '
		SELECT
		    l.id,
		    l.short
		FROM
		    `' . DB_PREFIX . 'languages` `l`
		';
		$availableLanguages = dbGetAll($q, 'short', 'id');
		if(!is_null($userLanguage) && in_array($userLanguage, $availableLanguages))
		{
		    $languageName = array_search($userLanguage, $availableLanguages);
		    leaf_set(array('properties', 'language_name') , $languageName);
		    leaf_set(array('properties', 'language_code') , $languageName);
		    leaf_set(array('properties', 'language_id'), $userLanguage);
		}
		// auto detect language
		elseif(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			$acceptedLanguages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
		    foreach($acceptedLanguages as $acceptedLanguage)
		    {
		        $acceptedLanguage = explode(';', $acceptedLanguage);
		        if(isset($availableLanguages[$acceptedLanguage[0]]))
		        {
		            leaf_set(array('properties', 'language_name') , $acceptedLanguage[0]);
		            leaf_set(array('properties', 'language_code') , $acceptedLanguage[0]);
		            leaf_set(array('properties', 'language_id'), $availableLanguages[$acceptedLanguage[0]]);
		            break;
		        }
		    }
		}
    }
}

	function _load_module_admin_settings(&$object){
		if(!empty($object->available_configs))
		{
			foreach($object->available_configs as $key)
			{
				$object->_config[$key] = _admin_module_config($object->_config['name'], $key);
			}
		}
	}

	function _admin_load_module($module_name, $check_access = true){
		$check_function = $check_access ? '_admin_module_access' : $check_access;
		return _core_load_module($module_name, $check_function);
	}

    function _admin_module_access($module, $processId = 1){
        $abilityClass = get(leaf_get( 'properties'), 'leafAdminAbilityClass', 'leafAdminAbility');
        return call_user_func($abilityClass .'::checkAccess', $module, $processId);
	}

    function _admin_module_config($module, $configName){
        $abilityClass = get(leaf_get( 'properties'), 'leafAdminAbilityClass', 'leafAdminAbility');
        $value = call_user_func($abilityClass .'::getConfig', $module, $configName);
		return $value;
	}
?>