<?php
class leafAutomatorTrigger extends leafBaseObject
{
    const tableName = 'leafAutomatorTriggers';

    public static $types;

    const AFTER_OPERATOR = 'after';
    public static $dateOperators = array(
        self::AFTER_OPERATOR => array(
            'valueType' => 'datetime',
        )
    );

    // Basic data
    public
        $id,
        $automatorId,
        $type,
        $contextObjectId,
        $value,
        $operator;

    // field definitions
    protected $fieldsDefinition = array (
        'automatorId' => array (
            'type' => 'int', 
        ),
        'type' => array (
            'no_empty' => 'true', 
        ),
        'contextObjectId' => array (
            'empty_to_null' => 'true', 
        ),
        'operator' => array (
            'no_empty' => 'true', 
        ),
        'value' => array (
            'empty_to_null' => 'true', 
        ),
    );

    protected $modes = array(
        'admin' => array('title')
    );

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
        // normalize triggers definition
        $normalizedList = array();
        $list = leaf_get('properties', 'automator', 'triggers');
        if(!empty($list))
        {
            foreach($list as $type)
            {
                if(isset($type['title']))
                {
                    $key = strtolower($type['title']);
                    $methodName = 'self::get' . $type['title'] . 'TriggerDefinition';
                }
                else
                {
                    $key = strtolower($type['class']);
                    $methodName = $type['class'] . '::getTriggerDefinition';
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
                    operator varchar
                    value varchar
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
        $this->operator = key(self::$types[$this->type]['operators']);
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
        $valueType = get(self::$types[$this->type]['operators'][$this->operator], 'valueType');
        return $valueType;
    }

    public function getFormatedValue()
    {
        $formatedValue = $this->value;
        $format = $this->getValueType();
        if($format == 'datetime')
        {
            if(!isPositiveInt($this->value))
            {
                $this->value = strtotime('tomorrow');
            }
            $formatedValue = date("Y-m-d H:i:s", $this->value);
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
        if(!empty($data['triggers']))
        {
            foreach($data['triggers'] as $item)
            {
                $typeDef = self::$types[$item['type']];
                $operator = $typeDef['operators'][$item['operator']];
                $value = null;

                if(!empty($operator['valueType']))
                {
                    if($operator['valueType'] == 'datetime')
                    {
                        $value = strtotime($item['value']);
                    }
                    elseif($operator['valueType'] == 'int')
                    {
                        $value = intval($item['value']);
                    }
                }

                getObject(get_called_class(), $item['id'], true)->variablesSave(array(
                    'automatorId' => $automatorId,
                    'type' => $item['type'],
                    'operator' => $item['operator'],
                    'contextObjectId' => (isset($typeDef['class']) ? intval($item['contextObjectId']) : null),
                    'value' => $value,
                ));

            }
        }

        if(!empty($data['triggersDeleted']))
        {
            foreach($data['triggersDeleted'] as  $id)
            {
                if(isPositiveInt($id))
                {
                    getObject(get_called_class(), $id)->delete();
                }
            }
        }
    }

    public function evaluate()
    {
        if(isset(self::$types[$this->type]['class']))
        {
            $methodName = self::$types[$this->type]['class'] . '::evaluateTrigger';
        }
        else
        {
            $methodName = 'self::evaluate' . self::$types[$this->type]['title'] . 'Trigger';
        }

        $result = call_user_func($methodName, $this);
        return $result;
    }

    public static function getDateTriggerDefinition()
    {
        $definition = array(
            'operators' => self::$dateOperators,
        );
        return $definition;
    }

    public static function evaluateDateTrigger($trigger)
    {
        $success = false;
        if(!empty($trigger->value))
        {
            if($trigger->operator == self::AFTER_OPERATOR && isPositiveInt($trigger->value) && (int)$trigger->value <= time())
            {
                $success = true;
            }
        }

        return $success;
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

}


