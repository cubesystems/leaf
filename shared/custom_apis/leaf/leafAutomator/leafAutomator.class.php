<?php
class leafAutomator extends leafBaseObject implements leafAutomatorActionInterface
{
    const tableName = 'leafAutomators';
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    // Basic data
    protected
        $id,
        $isActive,
        $title,
        $complete,
        $completeDate,
        $result;

    // field definitions
    protected $fieldsDefinition = array (
        'title' => array (
            'not_empty' => true, 
        ),
        'isActive' => array (
            'input_type' => 'checkbox', 
        ),
        'complete' => array (
            'input_type' => 'checkbox', 
            'optional' => true, 
        ),
        'result' => array (
            'input_type' => 'checkbox', 
            'optional' => true, 
        ),
        'completeDate' => array (
            'optional' => true, 
        ),
    );

    protected $modes = array(
        'admin' => array('title', 'isActive')
    );

	public static function _autoload( $className )
    {
		dbRegisterRawTableDefs(array(
            self::tableName => array (
                'fields' => '
                    id int auto_increment
                    title  varchar
                    isActive bool notnull default(1)
                    complete bool
                    completeDate datetime
                    result bool
                ',
                'indexes' => '
                    primary id
                    index complete
                ',
                'engine' => 'InnoDB',
            ),
       ));
    }


    public function __toString()
    {
        $title = $this->title;
        return $title;
    }

	public static function getQueryParts($params = array ())
    {
        if ( array_key_exists( 'search', $params ) )
        {
            $params['searchFields'] = array('title'); 
        }		
		
        $queryParts = parent::getQueryParts( $params );	
		
		if( !empty( $params ) && is_array( $params ) )
		{
			if( !empty( $params['active'] ) )
			{
				$queryParts['where'][] = 't.isActive = 1 AND (t.complete != 1 OR t.complete IS NULL)';
			}
        }
		
		return $queryParts;
    }

	public function variablesSave($variables, $fieldsDefinition = NULL, $mode = false)
    {
        $result = parent::variablesSave($variables, $fieldsDefinition, $mode);

        if($mode == 'admin')
        {
            leafAutomatorTrigger::massUpdate($variables, $this->id);
            leafAutomatorAction::massUpdate($variables, $this->id);
        }

        return $result;
    }

    public static function evaluateAll()
    {
        $list = self::getCollection(array(
            'active' => true
        ));
        foreach($list as $item)
        {
            $item->evaluate();
        }
    }

    public function evaluate()
    {
        if(!$this->complete)
        {
            if($this->evaluateTriggers())
            {
                $result = true;
                foreach($this->getActions() as $action)
                {
                    $actionResult = $action->evaluate();
                    if(!$actionResult && $result)
                    {
                        $result = $actionResult;
                    }
                }

                $this->completeDate = date(self::DATETIME_FORMAT);
                $this->complete = true;
                $this->result = $result;
                $this->save();
            }
        }
    }

    public function getTriggers()
    {
        if($this->id)
        {
            return leafAutomatorTrigger::getCollection(array('automatorId' => $this->id));
        }
    }

    public function getActions()
    {
        if($this->id)
        {
            return leafAutomatorAction::getCollection(array('automatorId' => $this->id));
        }
    }

    public function evaluateTriggers()
    {
        //TODO - implement in automator
        $orEnabled = false;

        // for orEnabled (in opposite to AND) default state for run is FALSE
        $run = !$orEnabled;
        foreach($this->getTriggers() as $item)
        {
            $result = $item->evaluate();
            if($orEnabled && $result)
            {
                $run = true;
                break;
            }
            elseif(!$orEnabled && !$result)
            {
                $run = false;
                break;
            }
        }
        return $run;
    }

    public function resetComplete()
    {
        $this->completeDate = null;
        $this->complete = null;
        $this->result = null;
        $this->save();
        foreach($this->getActions() as $action)
        {
            $action->variablesSave(array(
                    'completeDate' => null, 
                    'result' => null
                ),
                null,
                'complete'
            );
        }
    }

    /********************* automator related methods *********************/

    public static function getActionDefinitions()
    {
        $definitions['actions'] = array(
            'enable' => array(
            ),
            'disable' => array(
            ),
        );

        return $definitions;
    }

    public static function evaluateAction($item)
    {
        $result = false;
        if(!empty($item->action) && isPositiveInt($item->contextObjectId))
        {
            $sector = getObject(get_called_class(), $item->contextObjectId);
            if($sector)
            {
                if($item->action == 'enable')
                {
                    $sector->isActive = true;
                    $sector->save();
                    $result = true;
                }
                else if($item->action == 'disable')
                {
                    $sector->isActive = false;
                    $sector->save();
                    $result = true;
                }
            }
        }

        return $result;
    }
    

}

