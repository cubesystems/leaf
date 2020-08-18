<?
class content extends leaf_module{

	var $module_path='modules/content/';
	var $actions = array('copy_objects','delete_objects','move_objects','get_childs','close_childs', 'get_context_menu', 'save_object');
	var $output_actions = array
	(
		'move_dialog', 'copy_dialog', 'edit_object', 'object_manager', 'getObjectPreviewField',
		'linkDialog', 'getObjectData', 'getNode', 'richtextImageDialog', 'richtextEmbedDialog', 'getEmbedObject',
		'saveEmbedObject', 'getObjectHtml', 'relationsPanel', 'createRelationsGroup', 'createRelatedFromCopy',
		'getPossibleRelations', 'linkUpExisting', 'searchFiles',
		'canDeleteObjects', 'search',
	);

	var $objects = false;
	var $dialog = false;
	var $dialogs = array('move', 'copy');

	public $extraResponseFields = array( 'file' => 'file', 'src' => 'file' );

    protected static $_tableDefsStr = array
	(
        'objectAccess' => array
        (
            'fields' =>
            '
                objectId            int
                targetType          enum("General","Group","User")
                targetId            int
                value               tinyint
            '
            ,
            'indexes' => '
                primary objectId,targetType,targetId
                index objectId
            '
        )
    );


	protected $response = array();

	function checkObject(&$object, $methodName, $param3 = NULL)
	{
	    if ($object->object_data['id'] == 0) // new object
	    {
	        $objectId = $object->object_data['parent_id'];
	    }
	    else
	    {
            $objectId = $object->object_data['id'];
	    }

		if ($objectId && !self::userHasAccessToObject($objectId))
		{
			trigger_error('Access denied by object access rules.', E_USER_ERROR);
		}

	}

	function content(){
	    dbRegisterRawTableDefs( self::$_tableDefsStr );

		_core_add_css( WWW . 'styles/panelLayout.css' );
		_core_add_js( WWW . 'js/panelLayout.ie7.js', 'lte IE 7' );


		_core_add_js( SHARED_WWW . 'js/RequestUrl.class.js' );
		_core_add_js( SHARED_WWW . '3rdpart/jquery/plugins/cookie/jquery.cookie.min.js' );
        _core_add_js( WWW . 'modules/content/module.js' );


		$objects_environment = array(
			'method_check' => array(array($this, 'checkObject'))
		);
		leaf_set('objects_environment', $objects_environment);
		leaf_set('object_save_path', WWW . 'index.php?module=content&do=save_object');

		$this->module_www = $this->module_path;
		$this->module_path = PATH . $this->module_path;
		$this->header_string = WWW . '?module=content';

		_core_add_css( $this->module_www . 'style.ie7.css', 'lte IE 7' );

	    $this->parents=array('0');
        
        
        
        if (
            (isset($_GET['object_id']))
            &&
            (ispositiveint($_GET['object_id']))
            &&
            (($object = dbGetRow("SELECT * FROM objects WHERE id='" . dbSE( $_GET['object_id'] ) . "'")) !=  NULL)
        )
		{
            $this->active_object = $object;
            $this->loadOpenedObjects( $object['parent_id'] );
        }
		else if(isset($_GET['object_id']) && $_GET['object_id']==0)
		{
            if( isset( $_GET['parent_id'] ) )
            {
                $this->loadOpenedObjects( $_GET['parent_id'] );
            }
            
            $this->active_object = array( 'id' => 0 );
            
			if( isset( $_GET['type'] ) )
            {
				$this->active_object['type']=$_GET['type'];
            }
		}

        if (isset($_GET['group_id']))
		{
			$_GET['group_id'] = intval($_GET['group_id']);
			if(dbGetOne('SELECT COUNT(*) FROM objects WHERE id="' . intval($_GET['group_id']) . '"'))
			{
	           $this->group_id = $_GET['group_id'];
			}
			elseif($_GET['group_id'] == 0)
			{
				$this->group_id = 0;
			}
        }
		if(isset($this->group_id))
		{
			$root =  $this->group_id;
		}
		else
		{
			$root = 0;
		}
        
		leaf_set('object_parents', object_get_parents($root));
	}
    
    public function loadOpenedObjects( $objectId )
    {
        if( is_null( $objectId ) )
		{
            return;
        }
        
        //put parents in opened objects array
        $q = '
        SELECT
            id,
            parent_id
        FROM
            ' . DB_PREFIX . 'objects
        WHERE
            id = "'  . $objectId . '"
        ';
        
        while( $parent = dbGetRow( $q ) )
        {
            if(
                isset($_SESSION[SESSION_NAME]['modules'])
                &&
                !in_array( $parent['id'], $_SESSION[SESSION_NAME]['modules']['content']['opened_objects'] ) )
            {
                $_SESSION[SESSION_NAME]['modules']['content']['opened_objects'][] =$parent['id'];
            }
            
            $q = '
            SELECT
                id,
                parent_id
            FROM
                ' . DB_PREFIX . 'objects
            WHERE
                id = "'  . $parent['parent_id'] . '"
            ';
        }
    }

	public function edit_object()
	{
		if (!isset($_GET['object_id']))
	    {
            die('open error');
        }
		$object_id = intval($_GET['object_id']);
		//load new object module
		if($object_id == 0 && !empty($_GET['_leaf_object_type']))
		{
			$object_param['type'] = intval($_GET['_leaf_object_type']);
			$object_param['parent_id'] = intval($_GET['parent_id']);
			$object_param['id'] = 0;
			$object_param['protected'] = (leaf_get('objects_config', $object_param['type'], 'new_objects_protected_by_default')) ? 1 : 0;

		}
		//load existing object module
		elseif($object_id)
		{
			$object_param = $object_id;
		}
		else
		{
			die('open error');
		}

		require_once( SHARED_PATH . 'classes/input/input.class.php' );
		input::load( 'select' );

		$params = array();
		if(!empty($_GET['template']))
		{
			$params['template'] = $_GET['template'];
		}
		$object = _core_load_object($object_param, NULL, $params);
		//prepare params
		$params['objectModules'] = $this->getObjectTools();
        
		//call edit method
        if ($object)
        {
            $object->save_url = '?module=content&do=save_object&object_id=' . $object_id;
            
            $editResult = $object->editObject( $_GET );
            if ($object_id && $object)
            {
                $this->active_object = $object->object_data;
            }

            return $this->viewObjects($editResult, $params);
        }
        else
        {
            $errorContent = $this->moduleTemplate('error');
            return $this->viewObjects($errorContent);
        }
	}

	function object_manager(){
		$_GET['single_module'] = true;
		$objectManager = getObject('objectManager');
		return $objectManager->buildDialog($_GET['type']);
	}

	// dialogs

	function linkDialog()
	{
		$assign = array
		(
			'objects' => objectTree::getChildren(0)
        );
		die( $this->moduleTemplate( 'tree', $assign ) );
	}

	function richtextImageDialog()
	{
		$assign = array();
		if( !empty( $_GET['objectId'] ) )
		{
			$assign['item'] = _core_load_object( $_GET['objectId'] );
		}
		die( $this->moduleTemplate( 'richtextImageDialog', $assign ) );
	}

	function richtextEmbedDialog()
	{
		$assign = array();
		die( $this->moduleTemplate( 'richtextEmbedDialog', $assign ) );
	}

	function getEmbedObject()
	{
		$id = 0;
		if( !empty( $_GET['id'] ) )
		{
			$id = $_GET['id'];
		}
		//$item = getObject( 'embedObject', $id );
		$item = new embedObject( $id );
		if( is_object( $item ) )
		{
			$response = array
			(
				'ok' => true,
				'html' => $item->getHtml(),
				'objectHtml' => $item->getObjectHtml(),
				'embedCode' => $item->embedCode,
				'objectId' => $item->objectId,
				'source' => $item->source,
			);
		}
		else
		{
			$response = array
			(
				'ok' => false,
			);
		}
		die( json_encode( $response ) );
	}

	function saveEmbedObject()
	{
		$id = 0;
		if( !empty( $_GET['id'] ) )
		{
			$id = $_GET['id'];
		}
		//$item = getObject( 'embedObject', $id );
		$item = new embedObject( $id );
		$item->variablesSave( $_POST );
		$response = array
		(
			'ok' => true,
			'id' => $item->id,
		);
		die( json_encode( $response ) );
	}

	function getObjectHtml()
	{
		if( !empty( $_GET['id'] ) )
		{
			$fakeSmarty = '';
			require_once(SHARED_PATH . 'classes/smarty_plugins/function.banner.php');
			$response = smarty_function_banner( array( 'objectId' => $_GET['id'] ), $fakeSmarty );
			die( $response );
		}
	}

	function getObjectData()
	{
		$object = _core_load_object( $_GET['id'] );
		if( $object )
		{
			$response = array();
			// file
			if( !empty( $object->object_data['data']['file_www'] ) )
			{
                $contentBaseUrl = getValue('components.objectManager.contentBaseUrl');
                if(!$contentBaseUrl)
                {
                    $contentBaseUrl = '../';
                }
				$response = array
				(
					'name' => $object->object_data['data']['original_name'],
					'file' => $object->object_data['data']['file_www'],
					'src'  => $contentBaseUrl . '?object_id=' . $object->object_data['id'],
				);
			}
			die( json_encode( $response ) );
		}
	}

	function getNode()
	{
		$assign = array
		(
			'objects' => objectTree::getChildren($_GET['id'])
		);
		die( $this->moduleTemplate( '_treeLevel', $assign ) );
	}

	public function relationsPanel()
	{
		$assign = array();

		$assign['item'] = $item = _core_load_object( $_GET['object_id'] );

		$idInBranch = NULL;
		if( !empty( $_GET['parent_id'] ) )
		{
			$idInBranch = $_GET['parent_id'];
		}
		elseif( !empty( $_GET['object_id'] ) )
		{
			$idInBranch = $_GET['object_id'];
		}

		//debug( $idInBranch );

		if( $idInBranch !== NULL )
		{
			$activeLanguageRoot = objectTree::getFirstAncestor( $idInBranch, 'language_root' );
			if( !is_object( $activeLanguageRoot ) )
			{
				$assign['activeLanguageRootId'] = $idInBranch;
			}
			else
			{
				$assign['activeLanguageRootId'] = $activeLanguageRoot->object_data['id'];
			}
			//debug( $assign['activeLanguageRootId'] );
		}
		//debug( contentNodeRelation::getFor( $item->object_data['id'] ) );

		if( !empty( $_GET['seedRelationId'] ) )
		{
			$assign['relation'] = getObject( 'contentNodeRelation', $_GET['seedRelationId'] );
		}
		elseif( is_object( $item ) && $item->isInAnyRelation() )
		{
			$assign['relation'] = contentNodeRelation::getFor( $item->object_data['id'] );
		}

		return $assign;
	}

	public function getPossibleRelations()
	{
		$assign = array();

		$item = _core_load_object( $_GET['object_id'] );
		$relation = contentNodeRelation::getFor( $item->object_data['id'] );
		$foregnParentId = $relation->getParentNodeIdIn( $_GET['languageRootId'] );
		$assign['collection'] = objectTree::getChildren( $foregnParentId, $item->object_data['template'] );

		// TODO: filter-out objects that already are in a relation

		return $assign;
	}

	public function linkUpExisting()
	{
		$item = _core_load_object( $_GET['object_id'] );
		$relation = contentNodeRelation::getFor( $item->object_data['id'] );

		$result = contentNodeRelation::linkUp( $item->object_data['id'], $_POST['nuggetNodeId'] );
		if( $result )
		{
			die( 'ok' );
		}
		die( 'error' );
	}

	public function createRelationsGroup()
	{
		$item = _core_load_object( $_POST['id'] );
		$nodeId = $item->object_data['id'];

		if( $item->canBeInRelation() )
		{
			contentNodeRelation::createGroup( $nodeId );
		}

		$assign = $this->relationsPanel();

		$assign['_template'] = 'relationsPanel';
		return $assign;
	}

	public function createRelatedFromCopy()
	{
		if( !empty( $_POST['sourceNodeId'] ) && !empty( $_POST['targetNodeId'] ) )
		{
			$sourceNodeId = $_POST['sourceNodeId'];
			$source = _core_load_object( $sourceNodeId );
			if( is_object( $source ) )
			{
				$source->copyObject( $_POST['targetNodeId'] );
				$this->response['newNodeId'] = $source->object_data['id'];
				$this->response['result'] = 'ok';

				contentNodeRelation::linkUp( $sourceNodeId, $this->response['newNodeId'] );
			}
		}
		else
		{
			$this->response['result'] = 'error';
		}
		return array( '_template' => '_empty' );
	}

	public function searchFiles()
	{
		$collection = array();

		$item = array
		(
			'id' => 5,
			'displayString' => 'bla bla bla',
			'totalResults' => 25,
		);
		$search = '';
		if( !empty( $_GET['search'] ) )
		{
			$search = dbSE( $_GET['search'] );
		}

		$queryTemplate = '
		SELECT
			{what}
		FROM
			objects
		WHERE
			type=21
			AND
			name LIKE "%' . $search . '%"
		ORDER BY
			name ASC
		';

		$countQuery = str_replace( '{what}', 'COUNT(id)', $queryTemplate );
		$selectQuery = str_replace( '{what}', '*', $queryTemplate . ' LIMIT 0,20' );

		$noOfItems = dbGetOne( $countQuery );
		$items = dbGetAll( $selectQuery );

		$contentBaseUrl = getValue('components.objectManager.contentBaseUrl');
		if(!$contentBaseUrl)
		{
			$contentBaseUrl = '../';
		}

		foreach( $items as $row )
		{
			$node = _core_load_object( $row['id'] ); // baad, redo this to a more faster
			$item = array
			(
				'id' 			=> $row['id'],
				'displayString' => $row['name'],
				'totalResults'  => $noOfItems,
				'file' 			=> $node->object_data['data']['file_www'],
				'src' 			=> $contentBaseUrl . '?object_id=' . $row['id'],
			);
			$collection[] = $item;
		}

		$response = array
		(
			'collection' => $collection,
		);

		die( json_encode( $response ) );
	}

	// --dialogs

	function getObjectPreviewField()
	{
	    if (
            (empty($_GET['object_id']))
	        ||
	        (!isPositiveInt($_GET['object_id']))
        )
	    {
	         die();
	    }
	    $object = _core_load_object($_GET['object_id']);
	    if (!$object)
	    {
	        die();
	    }

        $previewData = $object->getObjectFieldPreviewData();
	    $smarty = new leaf_smarty(SHARED_PATH . 'objects/xml_template/templates/');

	    $field = array('preview' => $previewData );
        $smarty->assign('field', $field);

	    $previewHtml = $smarty->fetch('objectFieldPreview.tpl');


	    die($previewHtml);

	}

	function save_object(){
		$object_id = intval($_GET['object_id']);
		//load new object module
		if($object_id == 0 && !empty($_POST['_leaf_object_type']))
		{
			$object_param['type'] = intval($_POST['_leaf_object_type']);
			$object_param['parent_id'] = intval($_POST['parent_id']);
			$object_param['id'] = 0;
		}
		//load existing object module
		elseif($object_id)
		{
			$object_param = $object_id;
		}
		else
		{
			die('open error');
		}
		$params = array();
		if(!empty($_POST['template']))
		{
			$params['template'] = $_POST['template'];
		}
		//load object module
		$object = _core_load_object($object_param, NULL, $params);
		//prepare params
		$params = array_merge($_POST, $_FILES);
		//call save method
		$object->saveObject($params);
		//redirect to edit_object
		$this->header_string .= '&do=edit_object&object_id=' . $object->object_data['id'];

		// save content node relation
		if( !empty( $_POST['seedRelationId'] ) )
		{
			$seed = getObject( 'contentNodeRelation', $_POST['seedRelationId'] );
			$nuggetNodeId = $object->object_data['id'];

			contentNodeRelation::linkUp( $seed->nodeId, $nuggetNodeId );
		}
	}

	function close_childs(){
		if(!isset($_GET['group_id']))
		{
			exit;
		}
		$key=array_search($_GET['group_id'],$_SESSION[SESSION_NAME]['modules']['content']['opened_objects']);
		unset($_SESSION[SESSION_NAME]['modules']['content']['opened_objects'][$key]);
		exit;
	}

	function get_childs(){
		if(!isset($_GET['group_id']))
		{
			exit;
		}
		if(isset($_GET['dialog']) && in_array($_GET['dialog'],$this->dialogs))
		{
			$this->dialog=$_GET['dialog'];
		}
		if(!empty($_SESSION[SESSION_NAME]['modules']['content']['opened_objects']) && !in_array($_GET['group_id'],$_SESSION[SESSION_NAME]['modules']['content']['opened_objects']))
		{
			$_SESSION[SESSION_NAME]['modules']['content']['opened_objects'][]=$_GET['group_id'];
		}
		echo $this->get_tree($_GET['group_id']);
		exit;
	}


	function output(){
		//actions
		if (isset($_GET['do']) && in_array($_GET['do'],$this->actions))
		{
			$this->$_GET['do']();
			header("Location: ".$this->header_string);
			exit;
		}

		// if this is an xml request call
		if( !empty($_GET['ajax']) && $_GET['ajax'] == true )
		{
			$html = parent::output();
			// strip
			$patterns = array( "/\r\n/i", "/\r/i", "/\n/i", "/\t/i", '/  /i' );
			$html = preg_replace( $patterns, ' ', $html );
			if( !empty( $_GET['json'] ) )
			{
				if( isset( $_GET['html'] ) == false || $_GET['html'] == 1 )
				{
					$this->response['html'] = $html;
				}
				die( json_encode( $this->response ) );
			}
			else
			{
				die( $html );
			}
		}

		//output
		//set CSS
		_core_add_css($this->module_www . 'style.css');
		_core_add_js($this->module_www . 'functions.js');
		//add object_properties assets
		if(_admin_module_access('object_properties'))
		{
			_core_add_css('modules/object_properties/style.css');
			_core_add_js('modules/object_properties/functions.js');
		}

		if( isset( $this->customLayout ) && $this->customLayout === true )
		{

			die( parent::output() );
		}

		if (isset($_GET['do']) && in_array($_GET['do'],$this->output_actions) && $output=$this->$_GET['do']())
		{
			return $output;
		}
		else
		{
			return $this->viewObjects();
		}
	}

	function move_objects(){
		if (!empty($_POST['objects']) && is_array($_POST['objects']))
		{
		    if (
                ($this->group_id && !self::userHasAccessToObject($this->group_id))
                ||
                (!self::userHasAccessToObjectsAndDescendants( $_POST['objects'] ))
            )
		    {
                trigger_error('Operation not permitted.', E_USER_ERROR);
            }

			leafObject::moveTo($_POST['objects'], $this->group_id);
		}
		$this->header_string .= '&group_id=' . $this->group_id;
		return true;
	}

	function copy_objects(){
		if (!empty($_POST['objects']) && is_array($_POST['objects']))
		{

		    if (
                ($this->group_id && !self::userHasAccessToObject($this->group_id))
                ||
                (!self::userHasAccessToObjectsAndDescendants( $_POST['objects'] ))
            )
		    {
                trigger_error('Operation not permitted.', E_USER_ERROR);
            }


			leafObject::copyTo($_POST['objects'], $this->group_id);
		}
		$this->header_string.='&group_id='.$this->group_id;
		return true;
	}

	function delete_objects(){
		if (!empty($_POST['objects']) && is_array($_POST['objects']))
		{
		    if (!self::userHasAccessToObjectsAndDescendants( $_POST['objects'] ))
		    {
                trigger_error('Operation not permitted.', E_USER_ERROR);
            }

			foreach($_POST['objects'] as $object_id)
			{
				if( !empty( $_SESSION[SESSION_NAME]['modules']['content']['opened_objects'] ) )
                {
                    $openedObjectKey = array_search( $object_id, $_SESSION[SESSION_NAME]['modules']['content']['opened_objects'] );
                    
                    if( $openedObjectKey !== FALSE && !is_null( $openedObjectKey ) )
                    {
                        unset( $_SESSION[SESSION_NAME]['modules']['content']['opened_objects'][$openedObjectKey] );
                    }
                }
                
                _core_load_object(intval($object_id), 'deleteObject');
			}
            
            $this->header_string .= '&preserveTreeState=1';
		}
		return true;
	}
###############################
#####  OUTPUT FUNCTIONS  ######
###############################
	function move_dialog(){
		$this->dialog='move';
		//root objects
		$template= new leaf_smarty($this->module_path .  'templates/');
		$template->Assign('objects_tree', $this->get_tree(0));
		return $template->fetch("move_dialog.tpl");
	}

	function copy_dialog(){
		$this->dialog='copy';
		//root objects
		$template= new leaf_smarty($this->module_path .  'templates/');
		$template->Assign('objects_tree', $this->get_tree(0));
		return $template->fetch("copy_dialog.tpl");
	}

	function menu_content(){
		$output = array();
		$types = leaf_get('object_types');
		foreach($types as $entry){
			//check for access
			if(_admin_module_access($entry['module']))
			{
				$output[] = $entry;
			}
		}
		return $output;
	}

	function get_tree($root_id){
		$template= new leaf_smarty($this->module_path .  'templates/');
		$template->Assign('dialog', $this->dialog);
		$template->Assign('root_id', $root_id);
		$template->Assign('objects', object_get_node_tree($root_id));

		require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');
		$template->register_outputfilter(array('alias_cache', 'fillInAliases'));

		return $template->fetch("object_tree.tpl");
	}

	function viewObjects($content = NULL, $params = array()){
		$template= new leaf_smarty($this->module_path .  'templates/');
		//open object module
        if(isset($_GET['object_module']) && $module=_admin_load_module($_GET['object_module']))
		{
            $template->Assign('content', $module->edit_object());
		}
		else
		{
			//open object related module
			$template->Assign('content', $content);
		}
		//

		//reset opened objects
		if(
            !isset($_SESSION[SESSION_NAME]['modules']['content']['opened_objects'])
            ||
            ( sizeof($_GET)==1 && isset( $_GET['module'] ) && !isset( $_GET['preserveTreeState'] ) )
        )
		{
			$_SESSION[SESSION_NAME]['modules']['content']['opened_objects']=array();
		}
		leaf_set('object_parents', array_merge(leaf_get('object_parents'), $_SESSION[SESSION_NAME]['modules']['content']['opened_objects']));
		$template->Assign($params);

		$template->Assign('menu_content', $this->menu_content());
		$template->Assign('objects_tree', $this->get_tree(0));

		$template->Assign( '_module', $this );

		return $template->fetch("view.tpl");
	}

    function getObjectTools(){
		$object_menu = array();
		foreach(leaf_get( 'properties', 'objectModules') as $moduleName)
		{
			if(_admin_module_access($moduleName))
			{
                $object_menu[] = array(
                    'module_name' => $moduleName
                );
			}
		}
		return $object_menu;
	}

    public function get_context_menu(){
        $openedObject = _core_load_object(intval($_GET['group_id']));
        $assign['list'] = $openedObject->getAllowedChilds();

		$assign['object_id'] = intval($_GET['group_id']);
		$template= new leaf_smarty($this->module_path .  'templates/');
		$template->Assign($assign);

		header("Content-Type: text/xml");
		echo '<?xml version="1.0" encoding="UTF-8" standalone="no"?'.'><div>';
		echo $template->fetch('context_menu.tpl');
		die('</div>');

		exit;
	}

	function cmpOrder($a, $b)
	{
	   return $b["sort"] > $a["sort"];
	}

	public static function userHasAccessToObject( $objectId )
	{
	    $module = _admin_load_module('objectAccess');
	    if (!$module)
	    {
	        return false;
	    }

	    return $module->userHasAccessToObject( $objectId );

	}

	public static function userHasAccessToAllDescendants( $ancestorId )
	{
	    $module = _admin_load_module('objectAccess');
	    if (!$module)
	    {
	        return false;
	    }
	    return $module->userHasAccessToAllDescendants( $ancestorId );

	}

	public static function userHasAccessToObjectsAndDescendants( $objectIds )
	{
	    if (
	       (!empty($objectIds))
	       &&
	       (is_array($objectIds))
        )
        {
            foreach ($objectIds as $objectId)
            {
                if (
                    (!self::userHasAccessToObject( $objectId ))
                    ||
                    (!self::userHasAccessToAllDescendants( $objectId ))
                )
                {
                    return false;
                }
            }
        }
        return true;

	}

	public function canDeleteObjects( )
	{
	    $validIds = array();

	    if (!empty($_GET['ids']))
	    {
	        $ids = explode('|', $_GET['ids']);
	        foreach ($ids as $id)
	        {
	            if (ispositiveint($id))
	            {
	                $validIds[] = $id;
	            }
	        }
	    }

	    if (empty($validIds))
	    {
	        die('1');
	    }

	    // debug ($validIds);

        $qp = objectTree::getDescendantsQueryParts( $validIds );
        $qp['select'] = 'o.id';
        $descendantIds = dbgetall($qp, null, 'id');

        $objectIds = array_unique( array_merge($validIds, $descendantIds) );

        if (empty($objectIds))
        {
            die('1');
        }

        $sql = 'SELECT id FROM `objects` WHERE id IN (' . implode(', ', $objectIds) . ') AND protected';
        if (dbgetone($sql))
        {
            die('0');
        }

        die('1');

	}

    
    public function search()
    {
        _core_add_js( WWW . 'modules/content/module.js' );
        
        $params             = array();
        
        $searchString       = get( $_GET, 'searchString' );
        $searchString       = trim( $searchString );
        $searchRequest      = isset( $_GET['searchString'] ) ? true : false;
        
        $mysqlSearchString  = self::getStringForSearch( $searchString );
        
        $list               = array();
        $serialized_fields  = array();
        $table_fields       = array();

        if( $searchRequest && $searchString )
        {
            //get all searchable fields
            $q = '
            SELECT
                `template_path`,
                `table`,
                `fields_index`
            FROM
                `xml_templates_list`
            WHERE
                `fields_index` IS NOT NULL';
                
            $r = dbQuery( $q );
    
            while( $template = $r->fetch() )
            {
                $template['fields_index'] = unserialize( $template['fields_index'] );
                foreach( $template['fields_index'] as $field )
                {
                    if( !empty( $field['properties']['searchable'] ) )
                    {
                        if( $template['table'] && empty( $field['common'] ) )
                        {
                            $table_fields[ $template['table'] ][] = 'x.' . $field['name'];
                        }
                        else
                        {
                            $serialized_fields[ $template['template_path'] ][] = $field['name'];
                        }
                    }
                }
            }
    
            //search in all serialized data
            foreach( $serialized_fields as $template_name => $template_fields )
            {
                $q = '
                SELECT
                    o.id,
                    o.name,
                    o.data
                FROM
                    `objects` `o`
                WHERE
                    o.visible AND
                    o.template = "' . $template_name . '" AND
                    (' . implode(' OR ', $this->makeWhere( array('o.data'), $mysqlSearchString ) ) . ')
                ';
                $r = dbQuery( $q );
                while( $item = $r->fetch() )
                {
                    $item['data'] = unserialize( $item['data'] );
                    if( $item['text'] = $this->search_in_array( $item['data'], $searchString ) )
                    {
                        $list[ $item['id'] ] = $item;
                    }
                }
            }
    
            //search in all tables
            foreach( $table_fields as $table_name => $table_fields )
            {
                $q = '
                SELECT
                    o.id,
                    o.name,
                    ' . implode(', ', $table_fields) . '
                FROM
                    `' . dbSE( $table_name ) . '` `x`
                LEFT JOIN
                    `objects` `o` ON o.id = x.object_id
                WHERE
                    o.visible AND
                    (' . implode(' OR ', $this->makeWhere( $table_fields, $mysqlSearchString ) ) . ')
                ';
    
                $r = dbQuery( $q );
                while( $item = $r->fetch() )
                {
                    foreach( $item as $key => $value )
                    {
                        if( $item['text'] = $this->search_entry( $value, $searchString ) )
                        {
                            $list[ $item['id'] ] = $item;
                            break;
                        }
                    }
                }
            }
    
            //search by object name (groups and xml templates only)
            $q = '
            SELECT
                o.id,
                o.name
            FROM
                `objects` `o`
            WHERE
                (' . implode(' OR ', $this->makeWhere( array('o.name'), $mysqlSearchString ) ) . ') AND
                o.type IN (22) AND
                o.visible';
                
            $r = dbQuery( $q );
            while( $item = $r->fetch() )
            {
                if( empty( $list[ $item['id'] ] ) )
                {
                    $list[ $item['id'] ] = $item;
                }
            }
        }
        else if( $searchRequest && !$searchString )
        {
            $params['errorAlias']   = 'searchStringNotValid';
        }
        
        $params['searchProcessed']  = $searchRequest;
        $params['searchString']     = $searchString;
        $params['searchResults']    = $list;
        $params['resultsCount']     = sizeof( $list );
        
        $this->active_object = true;
        
		$template= new leaf_smarty($this->module_path .  'templates/');
		$template->Assign( $params );
		$content = $template->fetch( 'search.tpl' );
        
        return $this->viewObjects( $content );
    }
    
	public function makeWhere( $fields, $mysqlSearchString )
	{
		foreach( $fields as $field )
		{
			$parts = array();
            
            $sFields[] = $field . ' LIKE "%' . dbSE( $mysqlSearchString ) . '%"';
            
            $searchWords = explode( " ", $mysqlSearchString );
            
            if( sizeof( $searchWords ) > 1 )
            {
                foreach( $searchWords as $word )
                {
                    if( strlen( $word ) > 3 ) // min search length
                    {
                       $sFields[] = $field . ' LIKE "%' . dbSE( $word ) . '%"';
                    }
                }
            }
		}

		return $sFields;
	}

	public function search_in_array( $array, $searchString )
    {
		foreach( $array as $value )
		{
			if( is_array( $value ) )
			{
				if( $text = $this->search_in_array( $value, $searchString ) )
                {
                    return $text;
				}
			}
			else
			{
                if ( $text = $this->search_entry( $value, $searchString ) )
				{
                    return $text;
				}
			}
		}
		return false;
	}

	public function search_entry( $text, $searchString )
    {
		$text = strip_tags( $text );
		$text = explode( "\n", $text );
        $text = array_map( 'trim', $text );
        $text = implode( " ", $text );

        $searchWord = preg_quote( $searchString );
        $pattern = '/([^\.,;]*?' . preg_quote($searchWord, '/') . '.*?)(;|,|\.|$)/miu';

        preg_match($pattern, $text, $out );

		if( !sizeof( $out ) )
		{
			return false;
		}
		$text = trim( $out[1] );

        return $text;
	}
    

	public function getStringForSearch( $str )
    {
        $str = preg_replace( '/\%/u', '\%', $str );
        $str = preg_replace( '/_/u', '\\_', $str );
        return $str;
    }
    

    
}
?>
