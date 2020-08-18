<?
class leafAdminModuleAccess extends leafBaseObject
{
    const tableName = 'module_access';

	// db fields
    protected $moduleName, $groupId, $processId, $value;

    protected static $acessableModules = [];

	// dynamic properties
	protected $fieldsDefinition = array
	(
		'moduleName' => array(),
		'groupId' => array(),
		'processId' => array(),
		'value' => array(),
    );

    protected static $_tableDefsStr = array
    (
        'module_access' => array(
            'fields' =>
            '
                moduleName varchar(32)
                groupId tinyint(1)
                processId tinyint(1)
                value tinyint(1)
            ',
            'indexes' => '
                unique process moduleName,groupId,processId
            '
        )
    );

    public static function updateModuleGroupConfiguration($groupId, $moduleName, $values)
    {
        foreach($values as $processId => $value)
        {
            dbReplace(self::tableName, array(
                'moduleName' => $moduleName,
                'groupId' => $groupId,
                'processId' => $processId,
                'value' => $value,

            ));
        }
    }

    public static function checkAccess($groupId, $moduleName, $processId)
    {
        if(!self::$acessableModules)
        {
            $qp = self::getQueryParts();
            $qp['select'] = 'CONCAT_WS(\':\', t.groupId, t.moduleName, t.processId) as module_access_key, t.value';

            self::$acessableModules = dbGetAll($qp, 'module_access_key', 'value');
        }

        $keyPath = implode(':', compact('groupId', 'moduleName', 'processId'));

        return get(self::$acessableModules, $keyPath);
    }

    public static function getConfigurationsForGroup($moduleName, $groupId)
    {
        $qp = self::getQueryParts(array('groupId' => $groupId, 'moduleName' => $moduleName));
        $result = dbGetAll($qp, 'processId', 'value');
        return $result;
    }
	
	public static function getQueryParts ( $params = array() )
    {
        $qp = parent::getQueryParts($params);

		if(isset($params['processId']))
		{
            $qp['where'][] = 't.processId = "' . dbSE( $params['processId'] ) . '"';
        }

		if(isset($params['groupId']))
		{
            $qp['where'][] = 't.groupId = "' . dbSE( $params['groupId'] ) . '"';
        }

		if(isset($params['moduleName']))
		{
			$qp['where'][] = 't.moduleName = "' . dbSE( $params['moduleName'] ) . '"';
        }

        return $qp;
	}
}
