<?
class leafAdminModuleConfig extends leafBaseObject
{
    const tableName = 'module_config';

	// db fields
    protected $moduleName, $groupId, $name, $value;

	// dynamic properties
	protected $fieldsDefinition = array
	(
		'moduleName' => array(),
		'groupId' => array(),
		'name' => array(),
		'value' => array(),
    );

    protected static $_tableDefsStr = array
    (
        'module_config' => array(
            'fields' =>
            '
                moduleName varchar(32)
                groupId tinyint(1)
                name varchar
                value varchar
            ',
            'indexes' => '
                unique config moduleName,groupId,name
            '
        )
    );

    public static function updateModuleGroupConfiguration($groupId, $moduleName, $values)
    {
        foreach($values as $configName => $value)
        {
            dbReplace(self::tableName, array(
                'moduleName' => $moduleName,
                'groupId' => $groupId,
                'name' => $configName,
                'value' => $value,

            ));
        }
    }

    public static function getConfig($groupId, $moduleName, $name)
    {
        $qp = self::getQueryParts(array(
            'groupId' => $groupId,
            'name' => $name,
            'processId' => $name
        ));
        $qp['select'] = 't.value';
        $result = dbGetOne($qp);
        return $result;
    }

    public static function getConfigurationsForGroup($moduleName, $groupId)
    {
        $qp = self::getQueryParts(array('groupId' => $groupId, 'moduleName' => $moduleName));
        $result = dbGetAll($qp, 'name', 'value');
        return $result;
    }
	
	public static function getQueryParts ( $params = array() )
    {
        $qp = parent::getQueryParts($params);

		if(isset($params['name']))
		{
            $qp['where'][] = 't.name = "' . dbSE( $params['name'] ) . '"';
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
