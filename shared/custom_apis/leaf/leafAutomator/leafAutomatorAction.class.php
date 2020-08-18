<?php
class leafAutomatorAction extends leafBaseObject
{
    const tableName = 'leafAutomatorActions';
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    public static $types = null;

    // Basic data
    public
        $id,
        $automatorId,
        $type,
        $contextObjectId,
        $action,
        $completeDate,
        $result,
        $value;

    // field definitions
    protected $fieldsDefinition = array (
        'automatorId' => array (
            'type' => 'int', 
        ),
        'type' => array (
            'no_empty' => 'true', 
        ),
        'contextObjectId' => array (
            'empty_to_null' => true
        ),
        'action' => array (
            'no_empty' => 'true', 
        ),
        'value' => array (
            'empty_to_null' => true
        ),
        'completeDate' => array (
            'empty_to_null' => true
        ),
        'result' => array (
            'empty_to_null' => true
        ),
    );

    protected $modes = array(
        'default' => array('automatorId', 'type', 'contextObjectId', 'action', 'value'),
        'complete' => array('completeDate', 'result')
    );
    protected $currentMode = 'default';

	public function __construct($initData = NULL)
    {
        parent::__construct($initData);

        // set default type
        if(!$this->id)
        {
            $this->setType(key(self::$types));
        }
    }

	public static function _autoload( $className )
    {
        // normalize actions definition
        $normalizedList = array();
        $list = leaf_get('properties', 'automator', 'actions');
        if(!empty($list))
        {
            foreach($list as $type)
            {
                if(isset($type['title']))
                {
                    $key = strtolower($type['title']);
                    $methodName = 'self::get' . $type['title'] . 'ActionDefinitions';
                }
                else
                {
                    $key = strtolower($type['class']);
                    $methodName = $type['class'] . '::getActionDefinitions';
                }
                $normalizedList[$key] = array_merge($type, call_user_func($methodName));
            }
            self::$types = $normalizedList;
        }

		dbRegisterRawTableDefs(array(
            self::tableName => array (
                'fields' => '
                    id int auto_increment
                    automatorId int
                    type enum(' . '"' . implode('", "', array_keys($normalizedList)) . '"' . ')
                    contextObjectId int
                    action varchar
                    value varchar
                    completeDate datetime
                    result bool
                ',
                'indexes' => '
                    primary id
                    index contextObjectId
                ',
                'foreignKeys' => '
                    automatorId ' . leafAutomator::tableName . '.id CASCADE CASCADE
                ',
                'engine' => 'InnoDB',
           ),
       ));
    }

	public static function getQueryParts($params = array ())
    {
        $queryParts = parent::getQueryParts( $params );	
		
		if( !empty( $params ) && is_array( $params ) )
		{
			if( !empty( $params['automatorId'] ) )
			{
				$queryParts['where'][] = 't.automatorId = "' . dbSE($params['automatorId']) . '"';
			}
		}
		
		return $queryParts;
    }

    public function setType($type)
    {
        $this->type = $type;
        $this->action = key(self::$types[$this->type]['actions']);
    }

    public function getContextObject()
    {
        $class = get(self::$types[$this->type], 'class');
        if(!empty($class) && isPositiveInt($this->contextObjectId))
        {
            return getObject($class, $this->contextObjectId);
        }
    }

    public function getValueType()
    {
        $valueType = get(self::$types[$this->type]['actions'][$this->action], 'valueType');
        return $valueType;
    }

    public function getFormatedValue()
    {
        $formatedValue = $this->value;
        $format = $this->getValueType();

        if($format == 'money')
        {
            require_once( SHARED_PATH . 'classes/smarty_plugins/modifier.moneyFormat.php' );
            $formatedValue = smarty_modifier_moneyFormat($this->value);
        }
        elseif($format == 'int')
        {
            $formatedValue = intval($this->value);
        }

        return $formatedValue;
    }

    public function getDefinition()
    {
        return self::$types[$this->type];
    }

    public static function massUpdate($data, $automatorId)
    {
        if(!empty($data['actions']))
        {
            foreach($data['actions'] as $item)
            {
                $typeDef = self::$types[$item['type']];
                $action = $typeDef['actions'][$item['action']];
                $value = null;

                if(!empty($action['valueType']))
                {
                    if($action['valueType'] == 'int')
                    {
                        $value = intval($item['value']);
                    }
                    elseif($action['valueType'] == 'money' && $item['value'] !=  '')
                    {
                        // fix money format
                        $value = preg_replace('/\s/', '', $item['value']);
                        $value = preg_replace('/,/',  '.', $value);
                        $value = (int) round($value * 100);
                    }
                }

                getObject(get_called_class(), $item['id'], true)->variablesSave(array(
                    'automatorId' => $automatorId,
                    'type' => $item['type'],
                    'action' => $item['action'],
                    'contextObjectId' => (isset($typeDef['class']) ? intval($item['contextObjectId']) : null),
                    'value' => $value,
                ));
            }
        }

        if(!empty($data['actionsDeleted']))
        {
            foreach($data['actionsDeleted'] as  $id)
            {
                if(isPositiveInt($id))
                {
                    getObject(get_called_class(), $id)->delete();
                }
            }
        }
    }

    public static function getTypes($onlyNames = false)
    {
        if($onlyNames)
        {
            return array_keys(self::$types);
        }
        else
        {
            return self::$types;
        }
    }

    public function evaluate()
    {
        $methodName = self::$types[$this->type]['class'] . '::evaluateAction';
        $result = call_user_func($methodName, $this);
        $this->variablesSave(array(
                'completeDate' => date(self::DATETIME_FORMAT), 
                'result' => $result
            ),
            null,
            'complete'
        );
        return $result;
    }

}


