<?php
class leafUserGroup extends leafBaseObject implements leafUserGroupInterface
{
	const tableName = 'user_groups';
	
	protected
		$name, $default_module;
	
	protected $fieldsDefinition = array
	(
		'name' => array( 'not_empty' => true ),
		'default_module'  => array( 'not_empty' => true ),
	);
	
	/********************* constructors *********************/
	
	public static function _autoload( $className )
    {
		parent::_autoload( $className );
        
		$_tableDefsStr = array
		(
	        self::tableName => array
	        (
	            'fields' =>
	            '
	                id    int auto_increment
					name varchar(255)
					default_module  varchar(255)
	            '
	            ,
	            'indexes' => '
	                primary id
                ',
                'engine' => 'InnoDB',
	        ),
        );

	    dbRegisterRawTableDefs( $_tableDefsStr );
    }

    public function variablesSave($variables, $fieldsDefinition = NULL, $mode = false)
    {
        $result = parent::variablesSave($variables, $fieldsDefinition, $mode);

        // save group permissions
        if($result === true)
        {
            foreach($variables['configurations'] as $moduleName => $values)
            {
                leafAdminModuleAccess::updateModuleGroupConfiguration($this->id, $moduleName, $values['process']);
                leafAdminModuleConfig::updateModuleGroupConfiguration($this->id, $moduleName, get($values, 'config', array()));
            }
        }

        return $result;
    }
	
	/********************* get methods *********************/

    public function getUsersCount()
    {
        $query = leafUser::getQueryParts(array('group_id' => $this->id));
        $query['select'] = array('COUNT(t.id)');
        $count = dbGetOne($query);
        return $count;
    }
    
    public function __toString()
    {
        return $this->getDisplayString();
    }
    
    public static function getById( $id )
    {
        return getObject(__CLASS__, $id);
    }    

}
