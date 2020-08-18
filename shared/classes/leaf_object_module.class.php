<?
/**
 * leaf object module class
 */
class leaf_object_module{
    /**
     * Object type id
     * @access public
     * @var integer
     */
	var $object_type = NULL;
    /**
     * Object id
     * @access public
     * @var integer
     */
	var $object_id = 0;
    /**
     * Parent object id
     * @access public
     * @var integer
     */
	var $group_id = 0;
    /**
     * Object instance data array
     * @access public
     * @var array
     */
	var $object_data = array();
    /**
     * A new_object public variable, indicate is object new or already existing
     * @access public
     * @var boolean
     */
	var $new_object = false;
    /**
     * Stores object environment settings
     * @access public
     * @var array
     */
	var $objects_environment = array();

    /**
     * Stores output method
     *
     * @access public
     * @var string
     */
	var $_output_mode = 'page';

    public $loadedSnapshot = null;
    
	function leaf_object_module($object = NULL){
		//get object type configs
		$this->_config = leaf_get('objects_config', $this->object_type);

		//get xml template configs
		$this->_xml_config = leaf_get('objects_config', 22);
		//get class name
		$this->_self_name = get_class($this);
		//set object module path
		$this->module_path = SHARED_PATH . 'objects/' . $this->_config['name'] . '/';
		//set object module www location
		$this->module_www = SHARED_WWW . 'objects/' . $this->_config['name'] . '/';
		//load environment setting
		$this->objects_environment = leaf_get('objects_environment');
		//init environment
		if(!empty($this->objects_environment['object_init']))
		{
			foreach($this->objects_environment['object_init'] as $action)
			{
				$action($this);
			}
		}

		//init object type
		$this->_typeInit();
		//assign object data to instance
		$this->assignObjectData($object);
	}

    /**
     * Load object properties into $this->object_data['properties']
     */
	function _loadProperties(){
		$q='
		SELECT
			object_properties.property_id,
			object_properties.value,
			object_properties_desc.name
		FROM
			object_properties,
			object_properties_desc
		WHERE
			object_properties.object_id="' . $this->object_data['id'] . '" AND
			object_properties_desc.id=object_properties.property_id
		';
		$this->object_data['properties'] = dbGetAll($q, 'name', 'value');
	}

    /**
     * Assign object data from "objects" table and call _assignObjectData method if exist
     */
	function assignObjectData($object)
	{
        $this->_loadObjectData( $object );

        if(method_exists($this, '_assignObjectData'))
		{
			$this->_assignObjectData();
		}
	}

	function _loadObjectData( $object )
	{
		if(!is_array($object))
		{
			$q = '
			SELECT
				*
			FROM
				`' . DB_PREFIX . 'objects`
			WHERE
				`id` = "' . $object . '"
			';
			$object = dbGetRow($q);
		}
		$this->object_data = $object;


		if(!empty($this->object_data['data']))
		{
			$this->object_data['data'] = unserialize($this->object_data['data']);
		}
		//check new object
		if($this->object_data['id'] == 0)
		{
			$this->new_object = true;
		}
		if(!defined('VERSION'))
		{
			//load properties
			$this->_loadProperties();
		}

	}


    /**
     * Try to delete object child and object
     */
	function deleteObject(){
		//run environment check functions
		if(!empty($this->objects_environment['method_check']))
		{
			foreach($this->objects_environment['method_check'] as $action)
			{
				// call_user_func($action, $this, 'deleteObject');
				call_user_func_array( $action, array( & $this, 'deleteObject'));
			}
		}
		$p = new processing;
		//delete all childs
		$q = '
		SELECT
			`id`
		FROM
			`objects`
		WHERE
			`parent_id` = ' . $this->object_data['id'] . '
		';
		$r = dbQuery($q);
		while($item  = $r->fetch())
		{
			_core_load_object($item['id'], 'deleteObject');
		}

        // always delete snapshots without checking if the feature is enabled
        // (to prevent orphaned snapshots)
        $this->deleteSnapshots();
        
		//delete from objects table
		$p->db_delete_entry('objects', array('id' => $this->object_data['id']));
		//delete from object_properties table
		if(!defined('VERSION'))
		{
			$p->db_delete_entry('object_properties', array('object_id' => $this->object_data['id']));
		}
		//delete from object_permissions table
		$p->db_delete_entry('objectAccess', array('objectId' => $this->object_data['id']));
		//run extended delete functions
		if(!empty($this->objects_environment['extended_delete']))
		{
			foreach($this->objects_environment['extended_delete'] as $action)
			{
				call_user_func($action, $this);
			}
		}
		//delete ancestors
		require_once(SHARED_PATH . 'classes/ancestors.class.php');
		$ancestors = new leafAncestors;
		$ancestors->deleteAncestorData( $this->object_data['id'] );
		// delete rewrite
		leafObjectsRewrite::deleteRewrite($this->object_data['id']);
		//type delete method
		$this->_typeDelete();
		// store this update timestamp
		setValue('content_objects.last_update', time());
		//check functions

		// clear relations
		contentNodeRelation::clearRelationsFor( $this->object_data['id'] );
        
	}

/**
 * Move object to new parent node
 * @param integer $new_parent_id
 * @return bolean
 */
	function moveObject($new_parent_id){
		//run environment check functions
		if(!empty($this->objects_environment['method_check']))
		{
			foreach($this->objects_environment['method_check'] as $action)
			{
				// call_user_func($action, $this, 'moveObject', $new_parent_id);
				call_user_func_array( $action, array( & $this, 'moveObject', $new_parent_id ));
			}
		}
		$new_parent_id = intval($new_parent_id);
		$q = '
		SELECT
			COUNT(*)
		FROM
			`' . DB_PREFIX . 'object_ancestors`
		WHERE
			`object_id` = "' . $new_parent_id . '" AND
			`ancestor_id` = "' . $this->object_data['id'] . '"
		';
		if(dbGetOne($q) || $new_parent_id == $this->object_data['id'] || $new_parent_id == $this->object_data['parent_id'])
		{
			return false;
		}

		$maxOrderNo = dbgetone('select max(order_nr) from objects where parent_id = "' . $new_parent_id . '"');
		$orderNo    = $maxOrderNo + 1;

		$rewriteName = $this->object_data['rewrite_name'];
		$ownId = $this->object_data['id'];
		if (!self::isRewriteNameUnique($rewriteName,$new_parent_id,$ownId))
		{
		    $rewriteName = self::makeRewriteNameUnique($rewriteName, $this->object_data['name'], $new_parent_id, $ownId);
		}

		$q = '
		UPDATE
			`' . DB_PREFIX . 'objects`
		SET
			`parent_id` = "' . $new_parent_id . '",
			`rewrite_name` = "' . dbse($rewriteName) . '",
			`order_nr` = ' . $orderNo . '
		WHERE
			`id` = "' . $this->object_data['id'] . '"
		';
		dbQuery($q);
		$this->object_data['parent_id'] = $new_parent_id;
		$this->object_data['rewrite_name'] = $rewriteName;
		// update ancestors
		require_once(SHARED_PATH . 'classes/ancestors.class.php');
		$ancestors = new leafAncestors;
		$ancestors->updateAncestorData($new_parent_id);

		// clear relations
		contentNodeRelation::clearRelationsFor( $this->object_data['id'] );

		return true;
	}

/**
 * Copy object to new parent node
 * @param integer $new_parent_id
 * @return bolean
 */
	function copyObject($new_parent_id){
		//check move logic
		$new_parent_id = intval($new_parent_id);
		$q = '
		SELECT
			COUNT(*)
		FROM
			`' . DB_PREFIX . 'object_ancestors`
		WHERE
			`object_id` = "' . $new_parent_id . '" AND
			`ancestor_id` = "' . $this->object_data['id'] . '"
		';
		if(dbGetOne($q) || $new_parent_id == $this->object_data['id'])
		{
			return false;
		}
		// verify order_nr (get largest existing order_nr for children of the new parent, add 1)
		//
		$sql = '
		  SELECT `order_nr`
		  FROM
		      `' . DB_PREFIX . 'objects`
		  WHERE
		      `parent_id` = ' . $new_parent_id . '
		  ORDER BY
		      `order_nr` DESC
        ';
		$orderNr = (int) dbGetOne($sql); // if no children exist, result is converted to int 0
		$orderNr++;

		//check for existing rewrite name
		$rewriteName = $this->object_data['rewrite_name'];

		if (!self::isRewriteNameUnique( $rewriteName, $new_parent_id ))
		{
            $rewriteName = self::makeRewriteNameUnique( $rewriteName, $this->object_data['name'], $new_parent_id );
		}
		// serialize array fields for xml_templates
		if (
            ($this->object_type == 22)
            &&
            (!empty($this->_properties['fields']))
        )
		{
			foreach ($this->_properties['fields'] as $fieldName => $fieldDef)
			{
                if (
                    ($fieldDef['type'] == 'google_map_point')
                    &&
                    (isset($this->object_data['data'][$fieldName]))
                    &&
                    (is_array($this->object_data['data'][$fieldName]))
                )
                {
                    $this->object_data['data'][$fieldName] = serialize($this->object_data['data'][$fieldName]);
                }
			}
		}

		// copy object table
		$values = array(
			'type' => $this->object_data['type'],
			'visible' => $this->object_data['visible'],
			'protected' => (isset($this->object_data['protected'])) ? $this->object_data['protected'] : 0,
			'order_nr' => $orderNr,
			'rewrite_name' => $rewriteName,
			'name' => $this->object_data['name'],
			'createdby' => $this->object_data['createdby'],
			'create_date' => 'NOW()',
			'last_edit' => 'NOW()',
			'parent_id' => $new_parent_id,
			'data' => $this->object_data['data'],
			'template' => $this->object_data['template']
		);


		if($user_id = leaf_get('_user', 'id'))
		{
			$values['createdby'] = $user_id;
		}

		// set old id
		$this->object_data['old_id'] = $this->object_data['id'];
		// start processing class
		$this->object_data['id'] = dbInsert(DB_PREFIX . 'objects', $values, NULL, array('create_date', 'last_edit'));
		// dublicate objects properties
		if(!defined('VERSION'))
		{
			$p = new processing;
			$q = '
			SELECT
				*
			FROM
				`' . DB_PREFIX . 'object_properties`
			WHERE
				`object_id` = ' . $this->object_data['old_id'] . '
			';
			$r = dbQuery($q);
			while ($property = $r->fetch())
			{
				$values = array(
					'object_id' => $this->object_data['id'],
					'property_id' => $property['property_id'],
					'value' => $property['value']
				);
				$p->db_create_entry('object_properties', $values, false, false, true, true);
			}
		}
		$this->_typeCopy();
		// copy all childs
		$this->childMap = array();
		$q = '
		SELECT
			`id`
		FROM
			`objects`
		WHERE
			`parent_id` = ' . $this->object_data['old_id'] . '
        ORDER BY order_nr
		';
		$r = dbQuery($q);
		while($item  = $r->fetch())
		{
			$child = _core_load_object($item['id']);
			$child->copyObject($this->object_data['id']);
			$this->childMap[$item['id']] = $child->object_data['id'];
			if (is_array($child->childMap) && sizeof($child->childMap))
			{
				$this->childMap += $child->childMap;
			}
		}
		// update ancestors
		require_once(SHARED_PATH . 'classes/ancestors.class.php');
		$ancestors = new leafAncestors;
		$ancestors->updateAncestorData($this->object_data['id']);
		// child map
		if(sizeof($this->childMap) && method_exists($this, '_typeUpdateFields'))
		{
			$this->_typeUpdateFields($this->childMap);
		}
        return true;
	}

    /**
     * Return object type view
     * @param array $params
     * @return array
     */
	function viewObject($params = array(), & $module = null){
		//run environment check functions
		if(!empty($this->objects_environment['method_check']))
		{
			foreach($this->objects_environment['method_check'] as $action)
			{
				// call_user_func($action, $this, 'viewObject');
				call_user_func_array($action, array( & $this, 'viewObject' ) );
			}
		}
		//check functions
		return $this->_typeView($params, $module);
	}

	function editObject($params = false){
		_core_add_js(SHARED_WWW . 'js/xmlhttp.js');
		_core_add_js(SHARED_WWW . 'objects/edit.js');
		//run environment check functions
		if(!empty($this->objects_environment['method_check']))
		{
			foreach($this->objects_environment['method_check'] as $action)
			{
				call_user_func_array($action, array(& $this, 'editObject'));
			}
		}
		if (isset($params['is_rewrite_name_unique']))
		{
			$isOk = self::isRewriteNameUnique($params['is_rewrite_name_unique'], $this->object_data['parent_id'], $this->object_data['id']);
			die( ($isOk) ? '1' : '0' );

		}
		//return guesed rewrite name
		if (isset($params['suggest_rewrite_name']))
		{
			$rewriteName = $this->generateRewriteName($params['suggest_rewrite_name'], $this->object_data['parent_id'], $this->object_data['id']);
			die($rewriteName);
		}
        
        
        if ($this->areSnapshotsEnabled())
        {
            $this->loadSnapshot( get( $params, 'snapshot' ) );
        }
        
        
		//run type edit method
		return $this->_typeEdit($params);
	}

	/*

	   veido objektu, suugest -> return unique
	   manuaali redigjee rewrite un ieraksta dubultu - onchange highlight
	   onsave - validaacijas errors ka nav unikaals

	*/

	protected static function isRewriteNameUnique( $rewriteName, $parentId, $ownId = null)
	{
        if (!ispositiveint($parentId))
        {
            return true; // parent not given, cannot verify uniqueness, return
        }
        if ($rewriteName === '')
        {
            return true;
        }

        if (!ispositiveint($ownId))
        {
            $ownId = null;
        }

        $qp = array
        (
            'select' => 'id',
            'from'   => 'objects',
            'where'  => array
            (
                'parent'  => 'parent_id = ' . $parentId,
                'rewrite' => 'rewrite_name = "' . dbse($rewriteName) . '"'
            )
        );

        if ($ownId)
        {
            $qp['where'][] = 'id != ' . $ownId;
        }

        return !dbgetone($qp);
	}


	protected static function nameToRewriteName( $name )
	{
	    $rewriteSuggestMode = leaf_get('objects_config', 'rewriteSuggestMode');
		$rewriteName = '';
        
		switch($rewriteSuggestMode)
		{
			case 'unicode':
				$rewriteName = mb_strtolower( mb_ereg_replace(" ", "-", trim($name)));
				break;
			case 'latin':
            default:
				$rewriteName = strtolower( stringToLatin( trim($name), true, true ) );
				break;
		}
        
		return $rewriteName;
	}

	function generateRewriteName($name, $parentId, $ownId)
	{
	    $rewriteName = self::nameToRewriteName( $name );

	    if (!self::isRewriteNameUnique($rewriteName, $parentId, $ownId))
	    {
            $rewriteName = $this->makeRewriteNameUnique( $rewriteName, $name, $parentId, $ownId );
	    }
	    return $rewriteName;
	}


	public static function makeRewriteNameUnique( $rewriteName, $name, $parentId, $ownId = null)
	{
	    // extract base rewrite name (in case numbering has been already added)
	    $baseRewriteName = $indexNumber = null;

	    preg_match('/^(.*)((\-)(\d+))$/', $rewriteName, $matches);
	    if (!empty($matches[2]))
	    {

            // given rewrite name ends with a number
            // check if name ends with the same number
            $defaultRewrite = self::nameToRewriteName( $name );
            if (substr($defaultRewrite, strlen($matches[2]) * -1) == $matches[2])
            {
                // name also ends with the same number
                // it means the number is an intentional part of the title
                // and should not be incremented
                $baseRewriteName = $rewriteName;
                $indexNumber     = 0;
            }
            else
            {
                // name does not end with this number
                // it means the number is probably
                // a previously added incremental part of the rewrite name
                $baseRewriteName = substr($rewriteName, 0, strlen($matches[2]) * -1);
                $indexNumber = $matches[4];
            }
	    }
	    else
	    {
            $baseRewriteName = $rewriteName;
            $indexNumber     = 0;
	    }

	    $safety = 100000;
	    do
	    {
	        $indexNumber++;
	        $rewriteName = $baseRewriteName . '-' . $indexNumber;
	        $safety--;
	    }
	    while (
	       ($safety)
	       &&
	       (!self::isRewriteNameUnique( $rewriteName, $parentId, $ownId))
        );

        if (!$safety)
        {
            $rewriteName .= '-' . uniqid();
        }

        return $rewriteName;
	}

    public function validateRewriteName( $values )
    {
        $rewriteName = (isset($values['rewrite_name'])) ? $values['rewrite_name'] :  '';

        $ownId = null;
        if (!empty($this->object_data['id']))
        {
            $ownId = $this->object_data['id'];
        }
        $parentId = $this->object_data['parent_id'];

        if (self::isRewriteNameUnique( $rewriteName, $parentId, $ownId ))
        {
            return true;
        }

        $error['field'] = array
        (
    		'name' => 'rewrite_name'
		);
        $error['errorCode'] = 'duplicateRewriteName';
        return $error;
    }


	function getOrderList()
    {

		$output[1] = array('alias' => 'order_start');
		$q = '
		SELECT
			`name`
		FROM
			`objects`
		WHERE
			`parent_id` = ' . $this->object_data['parent_id'] . ' AND
			`id` != ' . $this->object_data['id'] . '
		ORDER BY
			`order_nr`
		';
		$result = dbQuery($q);
		for ($i = 2; $order_entry = $result->fetch(); ++$i)
		{
			$output[$i] = array('alias' => 'order_after', 'name' => $order_entry['name']);
		}
		return $output;
	}

	function editForm($assigns = array()){
		_core_add_js(SHARED_WWW . 'classes/processing/validation_assigner.js');
		$template = new leaf_smarty($this->module_path .  'templates/');
		//load order
		$assigns['order_select'] = $this->getOrderList();
		//set default order nr after all existing objects
		if($this->new_object && empty($this->object_data['order_nr']))
		{
			$this->object_data['order_nr'] = sizeof($assigns['order_select']);
		}

        $parentUrl = orp($this->object_data['parent_id']);


		$template->Assign('parentUrl', $parentUrl);
		$template->Assign($assigns);
		$template->Assign($this->object_data);
		$template->Assign('_object', $this);
		$template->Assign('_config', $this->_config);
		return $template->fetch(SHARED_PATH . 'objects/edit.tpl');
	}

	function saveObject($params = false, $type_save = true){


        
		//run environment check functions
		if(!empty($this->objects_environment['method_check']))
		{
			foreach($this->objects_environment['method_check'] as $action)
			{
				call_user_func_array($action, array(& $this, 'saveObject'));
			}
		}
		$variables = array();
		$variables[] = array(
			'name' => 'postCompleted',
			'error_alias' => 'incompletePostData',
			'not_empty' => true
		);
		if ($this->new_object && $this->object_type == 22)
		{
			$variables[] = array(
				'name' => 'template',
				'not_empty' => true
			);
		}
		//get update values
		if($this->new_object || isset($params['name']))
		{
			$variables[] = array(
				'name' => 'name',
				'not_empty' => true
			);
		}
		if(isset($params['visible']))
		{
			$variables[] = array(
				'name' => 'visible',
				'type' => 'int'
			);
		}
		if (isset($params['protected']))
		{
			$variables[] = array(
				'name' => 'protected',
				'type' => 'int'
			);
		}
		if(isset($params['rewrite_name']))
		{
			$variables[] = array(
				'name' => 'rewrite_name'
			);
		}

		//start processing class
		$p = new processing;
		$p->addPostCheck( array($this, 'validateRewriteName') );
		//update object information
		$p->request_type = 'v';
		//object check
		if (isset($params['getValidationXml']))
		{
			$p->error_cast = 'return';
			//return on error or without type method
			if($p->check_values($variables, $params, false) === FALSE || !($call_type_method = method_exists($this, '_typeProcessing')))
			{
				$p->getXml();
			}
			//try to validate type
			$this->_typeProcessing($params, false);
		}
		else
		{
			$values = $p->check_values($variables, $params);
			if($call_type_method = method_exists($this, '_typeProcessing'))
			{
				$type_save_values = $this->_typeProcessing($params, true);
			}
			else
			{
				$type_save_values = NULL;
			}
		}
		unset($values['postCompleted']);
		//run type pre save method
		if(method_exists($this, '_typePreSave'))
		{
			$params = $this->_typePreSave($params);
		}
        
        if ($this->areSnapshotsEnabled())
        {
            // load selected snapshot values to find out if any changes have been made before save
            $this->loadSnapshot( get($params, 'snapshot'));
        }
        
        
		//add new object
		if($this->object_data['id'] == 0)
		{
			$add_variables = array(
				'parent_id' => $this->object_data['parent_id'],
				'type' => $this->object_data['type'],
				'create_date' => 'NOW()'
			);
			if($user_id = leaf_get('_user', 'id'))
			{
				$add_variables['createdby'] = $user_id;
			}
			$this->new_object = true;
			$this->object_data['id'] = $p->db_create_entry('objects', $add_variables);
		}
        
        
		//update object order
		$values['order_nr'] = $this->updateOrder($params);
		if(!empty($params))
		{
			//update rewrite name
			$values['rewrite_name'] = $this->makeRewriteName($params);
		}
		//add values
		$values['last_edit'] = 'NOW()';
		$p->db_update_entry('objects', $values, array('id' => $this->object_data['id']), true, true, array('last_edit'));

        $preservedData = false;
        if (isset($this->object_data['data']))
        {
            $preservedData = $this->object_data['data'];
        }
        $this->_loadObjectData( $this->object_data['id'] ) ;
        if ($preservedData !== false)
        {
            $this->object_data['data'] = $preservedData;
        }

		// update ancestors
		require_once(SHARED_PATH . 'classes/ancestors.class.php');
		$ancestors = new leafAncestors;
		$ancestors->updateAncestorData($this->object_data['id']);
		//run type save method
		if($type_save)
		{
			$this->_typeSave($params, $type_save_values);
		}
		//reload new object data
		$this->assignObjectData( $this->object_data['id'] );

		// update rewrite
		leafObjectsRewrite::update($this->object_data['id']);
		// store this update timestamp

        if ($this->areSnapshotsEnabled())
        {
            $this->createSnapshot();
        }
        
		setValue('content_objects.last_update', time());
	}

	function makeRewriteName(&$params){
		//strip all whitespaces
		if(empty($params['rewrite_name']))
		{
			$params['rewrite_name'] = '';
		}
		//
		return $params['rewrite_name'];
	}

	function updateOrder(&$params){
		if(!isset($params['order_nr']) || !is_numeric($params['order_nr']))
		{
			if(isset($this->object_data['order_nr']))
			{
				$order_nr = $this->object_data['order_nr'];
			}
			else
			{
				$q = '
				SELECT
					COUNT(*)
				FROM
					`objects`
				WHERE
					`parent_id` = ' . $this->object_data['parent_id'] . '
				';
				$order_nr = dbGetOne($q);
			}
		}
		else
		{
			$order_nr = $params['order_nr'];
		}
		##re-order
		$q = '
		SELECT
			`id`
		FROM
			`objects`
		WHERE
			`parent_id` = ' . $this->object_data['parent_id'] . ' AND
			`id` != ' .  $this->object_data['id'] . '
		ORDER BY
			`order_nr`
		';
		$result = dbQuery($q);
		for ($i = 1; $item = $result->fetch(); ++$i)
		{
			if ($i == $order_nr)
			{
				++$i;
			}
			$q = '
			UPDATE
				`objects`
			SET
				`order_nr` = ' . $i . '
			WHERE
				`id` = ' . $item['id'] . '
			';
			dbQuery($q);
		}
		return $order_nr;
	}

	//empty default methods
	function _typeInit(){}
	function _typeView(){}
	function _typeSave($params, $save_values = null){}
	function _typeCopy(){}
	function _typeDelete(){}
	function _typeEdit($params){return $this->editForm();}

	function check_template_dir($path){
		$list = array();
		if (is_dir($path))
		{
		   if ($dh = opendir($path))
		   {
			   while (($file = readdir($dh)) !== false)
			   {
					$node_path = $path . $file;
					// ignore dot files and _main.xml
					if(substr($file, 0, 1) != '.' && $node_path != $this->_xml_config['templates_path'] . xmlize::mainXMLName)
					{
						if(is_file($node_path) && strtolower(substr($node_path, -4, 4)) == '.xml')
						{
							$template = substr(str_replace($this->_xml_config['templates_path'], '', $node_path), 0, -4);
							$xmlize = new xmlize();
							$xmlize->recompileTemplate($template);
							$list[] = $template;
						}
						elseif(is_dir($node_path))
						{
							$list = array_merge($list, $this->check_template_dir($node_path . '/'));
						}
					}
			   }
			   closedir($dh);
		   }
		}
		return $list;
    }

    public function getAllowedChilds($contextObject = null)
    {
		require_once(SHARED_PATH . 'classes/xmlize.class.php');
		//development mode option -> rescan templates each time
		if($this->_xml_config['rescan_templates'])
		{
			//add new & modify existing templates
			$existing_templates = $this->check_template_dir($this->_xml_config['templates_path']);
			//get list with unexisting templates
			$q = '
			SELECT
				xtl.template_path
			FROM
				`' . DB_PREFIX . 'xml_templates_list` `xtl`
			WHERE
				(
					xtl.alias IS NULL AND
					xtl.template_path NOT IN ("' . implode('", "', $existing_templates) . '")
				)
				OR
				(
					xtl.alias IS NOT NULL AND
					xtl.alias NOT IN ("' . implode('", "', $existing_templates) . '")
				)
			';
			if(sizeof($unexisting_templates = dbGetAll($q, false, 'template_path')))
			{
				$q = '
				DELETE
				FROM
					`' . DB_PREFIX . 'object_rules`
				WHERE
					`object` IN ("' . implode('", "', $unexisting_templates) . '") OR
					`child` IN ("' . implode('", "', $unexisting_templates) . '")
				';
				dbQuery($q);
				$q = '
				DELETE
				FROM
					`' . DB_PREFIX . 'xml_templates_list`
				WHERE
					`template_path` IN ("' . implode('", "', $unexisting_templates) . '")
				';
				dbQuery($q);
			}
		}
		//get all templates - parent is root
        $q = '
        SELECT
            rules.max,
            IF(!STRCMP(rules.child, 21), "file", rules.child ) `id`,
            IF(!STRCMP(rules.child, 21), "file", IF(!STRCMP(rules.child, "file"), "file", IF(xtl.alias IS NOT NULL, xtl.alias, xtl.template_path))) `type`,
            xtl.name,
            xtl.icon_path
        FROM
            `object_rules` `rules`
        LEFT JOIN
            `xml_templates_list` `xtl` ON xtl.template_path = rules.child
        WHERE
            (xtl.name IS NOT NULL OR rules.child = 21 OR rules.child = "file") AND
            rules.object = "' . dbSE($this->object_data['template']) . '"
        GROUP BY
            rules.child
        ORDER BY
            `name`
            ';
        $r = dbQuery($q);
		$list = array();
		while($item = $r->fetch())
		{
			if(isPositiveInt($item['max']))
			{
				$q = '
				SELECT
					COUNT(*)
				FROM
					`objects` `o`
				WHERE
					o.parent_id = "' . $this->object_data['id'] . '" AND
					o.template = "' . $item['id'] . '"
                    ';
				if(!is_null($contextObject) && $contextObject->object_data['template'] == $item['id'])
				{
                    $q .= ' AND o.id != ' . $contextObject->object_data['id'];
				}
				$currentCount = dbGetOne($q);
				if($currentCount >= $item['max'])
				{
					continue;
				}
            }

			if($item['type'] == "file" || $this->checkTemplate($item['type']))
			{
				if(!empty($item['icon_path']))
				{
					$item['icon_path']  = $this->_xml_config['templates_www'] . $item['icon_path'];
				}
				$list[] =  $item;
            }
		}
		return $list;
    }

    public function getParentAllowedChilds()
    {
        if(isPositiveInt($this->object_data['parent_id']))
        {
            $list =  _core_load_object($this->object_data['parent_id'])->getAllowedChilds($this);
        }
        else
        {
            $list = self::getAllTypes();
        }

        $returnList = array();
        foreach($list as $item)
        {
            $returnList[$item['id']] = $item['name'];
        }
        unset($returnList['file']);
        return $returnList;
    }

    public static function getAllTypes()
    {
        $q = '
        SELECT
            NULL `max`,
            xtl.template_path `id`,
            IF(xtl.alias IS NOT NULL, xtl.alias, xtl.template_path) `type`,
            xtl.name
        FROM
            `xml_templates_list` `xtl`
        ORDER BY
            `name`
            ';
        $list = dbGetAll($q);
        return $list;
    }

	function checkTemplate($template){
		$file = $this->_xml_config['templates_path'] . $template . '.xml';
		//file exists?
		if(!file_exists($file))
		{
		//delete everything from db
			//from template list
			$q = '
			DELETE
			FROM
				`' . DB_PREFIX . 'xml_templates_list`
			WHERE
				`template_path` = "' . $template . '"
			';
			dbQuery($q);
			//from rules
			$q = '
			DELETE
			FROM
				`' . DB_PREFIX . 'object_rules`
			WHERE
				`object` = "' . $template . '"
			';
			dbQuery($q);
			return false;
		}
		elseif(!isset($this->config['allowed_templates']) || in_array($template, $this->config['allowed_templates']))
		{
			//everything is ok
			return true;
		}
		else
		{
			//no permissions
			return false;
		}
	}

	function setOutputMode($mode)
	{
	    $this->_output_mode = $mode;
	}

	function getOutputMode()
	{
	    return $this->_output_mode;
	}

	function getObjectFieldPreviewData( $objectIds = null )
	{
	    // if $objectId is null, it is set to the id of the current object

	    // if $objectId contains a single object id
	    // the return will be in the form array('id' => (int) <object_id>, 'name' => (string) <object_name>);
	    // or null if object is not found

	    // if (is_array($objectIds))
	    // the return will be an array with objectIds as keys
	    // and result arrays (or nulls) as values

	    if (is_null($objectIds))
	    {
	        $objectIds = $this->object_data['id'];
	    }

        if (is_array( $objectIds ))
        {
            $returnMultiple = true;
        }
        else
        {
            $returnMultiple = false;
            $objectId = (int) $objectIds;
            $objectIds = array( $objectId);
        }

	    $queryParts = array(
                'select' => 'o.id, o.name',
                'from' => 'objects AS o',
                'where' => 'o.id IN(' . implode(', ', $objectIds ) . ')'
	    );


	    $query = dbBuildQuery($queryParts);

	    $rows = dbgetall($query, 'id');

	    // shorten names
	    $maxLen = 40;
	    $maxStart = $maxEnd = round($maxLen / 2) - 2;
	    foreach ($rows as & $row)
	    {
	        $name = $row['name'];
	        $len = mb_strlen( $name );
	        if ($len > $maxLen)
	        {
	            $name = mb_substr($name, 0, $maxStart) . '...' . mb_substr($name, $maxEnd * -1);
	        }
            $row['displayName']	= $name;

	    }

	    if ($returnMultiple)
	    {
	        $result = array();
	        foreach ($objectIds as $id)
	        {
	            $value = (empty($rows[$id])) ? null : $rows[$id];
	            $result[$id] = $value;
	        }
	    }
	    else
	    {
	        $result = (empty($rows[$objectId])) ? null : $rows[$objectId];
	    }

        return $result;

	}

    public function redirectToSelf($add = null)
    {
        if (is_array($add))
        {
            $add = '?' . leafUrl::buildQueryString( $add );
        }

        $url = orp($this->object_data['id']);
        if($add)
        {
            $url .= $add;
        }
        leafHttp::redirect($url);
    }

    public function haveChildren()
    {
        $qp = array(
            'select' => 'COUNT(id)',
            'from'   => 'objects',
            'where' => array(
                'parent_id = ' . $this->object_data['id']
            ),
        );
        $haveChildren = dbGetOne($qp);

        return $haveChildren;
    }

    public function getChildren()
    {
        $list = objectTree::getChildren($this->object_data['id']);
        return $list;
    }

	public function getIconUrl()
    {
        if($this->object_data['type'] == 21)
        {
			$iconWww = SHARED_WWW . 'objects/file/icon.gif';
        }
        else
        {
            $iconPart = $this->object_data['template'] . '.png';
            $iconWww = leaf_get('objects_config', 22, 'templates_www') . $iconPart;
            if( !file_exists( leaf_get('objects_config', 22, 'templates_path') . $iconPart ) )
            {
                $iconWww = SHARED_WWW . 'objects/xml_template/icon.png';
            }
        }
		return $iconWww;
	}
    
    public function createSnapshot()
    {
        if (empty($this->object_data['id']))
        {
            return null;
        }
        
        // do not create new snapshot if data has not changed
        // compare data of an existing snapshot to the current state

        // if a snapshot is loaded, use it for comparison
        // otherwise use current snapshot (or latest existing if no snapshot is marked as current)
        
        $snapshot = $this->loadedSnapshot;
        
        if (!$snapshot)
        {
            $snapshot = $this->getCurrentSnapshot();
        }
        
        $ignoredFields = array('last_edit', 'parent_id', 'order_nr');

        if ($snapshot)
        {
            // a previous snapshot exists, compare data
            
            $snapshotData = $snapshot->getObjectData();
            if (!$snapshotData)
            {
                $snapshot = null; // data no found, cannot use this snapshot
            }
            
            $currentData = $this->object_data;
            
            // modification dates will always be different 
            // for a freshly saved object and its earlier snapshot
            // so discard them
            foreach ($ignoredFields as $ignoredField)
            {
                unset ($currentData[$ignoredField]);               
                unset ($snapshotData[$ignoredField]);
            }

            
            // sort all data of both sides alphabetically by key 
            // to allow reuse of snapshots in case the field order has changed
            
            self::recursiveKeySort( $currentData );
            self::recursiveKeySort( $snapshotData );
            
            
            if (serialize($currentData) != serialize($snapshotData))
            {
                $snapshot = null; // different data. cannot use this snapshot
            }
        }
        
        
        if (!$snapshot)
        {
            $snapshotData = $this->object_data;
            foreach ($ignoredFields as $ignoredField)
            {
                unset ($snapshotData[$ignoredField]);
            }
            $values = array
            (
                'objectId'  => $this->object_data['id'],
                'createdAt' => date('Y-m-d H:i:s'),
                'data'      => serialize($snapshotData)
            );

            $snapshot = getObject('contentNodeSnapshot', 0);
            $snapshot->variablesSave($values, null, 'factory');
        }

        if (!$snapshot->isCurrent())
        {
            $snapshot->markAsCurrent();
        }
        
        return $snapshot;
    }
    
    public function deleteSnapshots()
    {
        $snapshots = $this->getSnapshots();
        foreach ($snapshots as $snapshot)
        {
            $snapshot->delete();
        }
        return true;
    }
    
    public function getSnapshots()
    {
        if (empty($this->object_data['id']))
        {
            return null;
        }
        
        $params = array
        (
            'objectId' => $this->object_data['id'],
            'latestFirst' => true
        );         
        
        return contentNodeSnapshot::getCollection( $params );
    }
    
    public function getLatestSnapshot()
    {
        if (empty($this->object_data['id']))
        {
            return null;
        }        
        
        $params = array
        (
            'objectId'    => $this->object_data['id'],
            'latestFirst' => true
        );    

        return contentNodeSnapshot::getObject( $params );
    }
    
    public function getCurrentSnapshot()
    {
        if (empty($this->object_data['id']))
        {
            return null;
        }        
        
        $params = array
        (
            'objectId'    => $this->object_data['id'],
            'current'     => true,
            'latestFirst' => true
        );    

        $snapshot = contentNodeSnapshot::getObject( $params );
        if (!$snapshot)
        {
            // fallback to latest if no snapshot is marked as current
            $snapshot = $this->getLatestSnapshot();
        }
        return $snapshot;
    }
    
    public function loadSnapshot( $snapshotId )
    {

        if (empty($this->object_data['id']))
        {
            return false;
        }
        
        $snapshot = null;
        
        if (ispositiveint( $snapshotId ))
        {
            // snapshot id given, attempt to load
            $params = array
            (
                'objectId'    => $this->object_data['id'],
                'id'          => $snapshotId
            );           

            $snapshot = contentNodeSnapshot::getObject( $params );            
        }
        
        
        if ($snapshot)
        {
            // overwrite object data with loaded snapshot
            $this->object_data = array_merge( $this->object_data, $snapshot->getObjectData() );
        }
        else
        {
            // snapshot not found or id not given. load current snapshot
            $snapshot = $this->getCurrentSnapshot();
        }
        
        $this->loadedSnapshot = $snapshot;
        
        return true;
        
        
    }
    
    
    public function areSnapshotsEnabled()
    {
        $setting = leaf_get('objects_config', 'snapshotsEnabled');
        
        // undefined means disabled
        if ($setting !== true)
        {
            return false;
        }
        
        // snapshots enabled 
        // but they are only available for xml templates
        
        if ($this->object_type != 22)
        {
            return false;
        }

        return true;
    }
    
    
    public static function recursiveKeySort( & $array )
    {
        ksort($array);
        foreach ($array as & $item)
        {
            if (is_array($item))
            {
                self::recursiveKeySort( $item );
            }
        }
        return;
    }
    
    
}
?>
