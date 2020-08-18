<?php
class errors extends leaf_module
{
	public $actions = array ('save', 'deleteAll', 'loadMessages', 'deleteError', 'deleteBefore', );
	public $output_actions = array ('all', 'view', 'latest', );
	public $default_output_function = 'all';

	protected $mainObjectClass = 'leafError';

	protected $itemsPerPage = 40;
	protected $maxPagesInNavigation = 15;

	public function output()
	{
		_core_add_css( WWW . 'styles/leafTable.css' );
		
		_core_add_css($this->module_www . 'module.css');
		
		// add resources
		_core_add_js(SHARED_WWW . 'js/RequestUrl.class.js');
		//asdf;
		_core_add_js( SHARED_WWW . '3rdpart/jquery/jquery-core.js' );
		_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.core.min.js' );
		_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.widget.min.js' );
		_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.mouse.min.js' );
		_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.sortable.min.js' );
		
		_core_add_js($this->module_www . 'module.js');
		
        if(!empty($_GET['search']))
        {
            $this->addUrlPart('search', $_GET['search']);
        }
        if(isPositiveInt($_GET['page']))
        {
            $this->addUrlPart('page', $_GET['page']);
        }
		return parent::output();
	}

	public function all()
	{
		_core_add_css( WWW . 'styles/expandTool.css' );
		_core_add_css( WWW . 'styles/page-navigation.css' );
		
		// page
		$page = 1;
		if( !empty($_GET['page']) && is_numeric($_GET['page']) )
		{
			$page = $_GET['page'];
		}
		// collect object
		$params = $_GET;
		$params['group'] = true;
		$assign['collection'] = call_user_func(array($this->mainObjectClass, 'getCollection'), $params, $this->itemsPerPage, $page);
		// construct page navigation
		$assign['pageNavigation'] = pagedNavigation::getFromList( $assign['collection'], $this->maxPagesInNavigation );
		return $assign;
	}

	public function latest()
	{
	    $after = (ispositiveint($_GET['after'])) ? $_GET['after'] : 0;

	    $params = array
	    (
            'after' => $after,
            'returnRows' => true
        );

        $errors = call_user_func(array($this->mainObjectClass, 'getCollection'), $params);

        if (leafBot::isCurrentUserAgent())
        {
            $keys = array_keys($errors);
            $largestId = (empty($keys)) ? null : max( $keys );

            $leafBotResponseData = array
            (
                'after'          => $after,
                'numberOfErrors' => count($errors),
                'largestId'      => $largestId,
                'datetime'       => date('Y-m-d H:i:s'),
                'errors'         => $errors
            );

            leafBotRequestResponse::sendJson( $leafBotResponseData );
        }

	}

    public function loadMessages()
    {
        $params = array
        (
            'hash' => $_GET['hash']
        );
        $collection = call_user_func(array($this->mainObjectClass, 'getCollection'), $params);

        $list = array();
        $firstSkiped = false;
        foreach($collection as $obj)
        {
            // skip first entry
            if($firstSkiped)
            {
                $list[] = array
                (
                    'id' => $obj->id,
                    'date' => $obj->add_date,
                    'ip' => !empty($obj->log->user_ip) ? $obj->log->user_ip : '',
                );
            }
            else
            {
                $firstSkiped = true;
            }
        }

        echo json_encode($list);
        exit;
    }

	public function view()
	{
		$item = getObject($this->mainObjectClass, $_GET['id']);
		if($item)
		{
			$assign['item'] = $item;
			return $assign;
		}
		else
		{
			leafHttp::redirect($this->getModuleUrl());
		}
	}

	public function save()
	{
		$obj = getObject($this->mainObjectClass, $_GET['id']);
		$obj->variablesSave($_POST);
	}
    
    public function deleteError()
    {
    	if (!empty($_POST['id']))
    	{
	    	leafError :: deleteByHash($_POST['id']);
    	}
    }
	
	public function deleteAll()
	{
        call_user_func(array($this->mainObjectClass, 'deleteAll'));
	}
	
	public function deleteBefore()
	{
	    call_user_func(array($this->mainObjectClass, 'deleteBefore'), get($_GET, 'timeStamp', strtotime('-1 day')));
		if (leafBot::isCurrentUserAgent())
        {
            leafBotRequestResponse::sendJson(array ('status' => 'ok', ));
        }
	}
	
}
