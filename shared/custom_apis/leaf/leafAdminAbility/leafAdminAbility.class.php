<?
class leafAdminAbility
{
    protected static $cachedModuleNames = null;

    public static function getModuleConfigurations( $groupId )
    {
        $list = array();

        if ($handle = opendir(ADMIN_PATH . 'modules'))
        {
            while (false !== ($module = readdir($handle)))
            {
                if ( is_dir(ADMIN_PATH . 'modules/' . $module) && $module != '.' && $module != '..' )
                {
                    $moduleConfiguration = array(
                        'name' => $module
                    );

                    ## add process with id=1(module access)
                    $process = array(
                        '1' => 'Access'
                    );
                    $configs = array();
                    $configFile = ADMIN_PATH . 'modules/' . $module . '/config.php';
                    ## Load module config file (if exist) with additionaly access and configs
                    if (is_file($configFile))
                    {
                        include($configFile);
                        if(isset($configs['skip_me']) && $configs['skip_me'] == true)
                        {
                            continue;
                        }
                    }

                    $configurationValues = leafAdminModuleConfig::getConfigurationsForGroup($module, $groupId);
                    $processValues = leafAdminModuleAccess::getConfigurationsForGroup($module, $groupId);

                    foreach($process as $processId => $processName)
                    {
                        $moduleConfiguration['processes'][] = array(
                            'id' => $processId,
                            'name' => $processName,
                            'value' => get($processValues, $processId, 0)
                        );
                    }

                    foreach($configs as $configurationName => $configuration)
                    {
                        $configuration['value'] = get($configurationValues, $configurationName, 0);
                        $configuration['name'] = $configurationName;
                        $moduleConfiguration['configurations'][] = $configuration;
                    }

                    $list[$module] = $moduleConfiguration;
                }
             }
            closedir($handle);
        }
        
        ksort($list);
        
        return $list;
    }

    public static function getModuleNames()
    {
        if(is_null(self::$cachedModuleNames))
        {
            self::$cachedModuleNames = array();
            if ($handle = opendir(ADMIN_PATH . 'modules'))
            {
                while (false !== ($module = readdir($handle)))
                {
                    if ( is_dir(ADMIN_PATH . 'modules/' . $module) && $module != '.' && $module != '..' )
                    {
                        self::$cachedModuleNames[$module] = $module;
                    }
                    sort( self::$cachedModuleNames );
                }
                closedir($handle);
            }
        }

        return self::$cachedModuleNames;
    }

    public static function getConfig($moduleName, $configName)
    {
        $groupId = call_user_func(leafAuthorization::getUserClass() . '::getCurrentUserGroupId');
        $value = leafAdminModuleConfig::getConfig($groupId, $moduleName, $configName);
		return $value;
    }

    public static function checkAccess($moduleName, $processId = 1)
    {
        $groupId = call_user_func(leafAuthorization::getUserClass() . '::getCurrentUserGroupId');
        $value = leafAdminModuleAccess::checkAccess($groupId, $moduleName, $processId);
		return $value;
    }
}
