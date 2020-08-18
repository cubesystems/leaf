<?
class xml_template extends leaf_object_module
{

	var $module_path='modules/xml_template/';
	var $actions=array('save_object', 'get_array_node');
	var $output_actions=array();
	var $header_string='?module=xml_template';
	var $config = array();

	var $object_type = 22;

    var $fileFieldTypes = array('image', 'fileobject');
	var $available_configs = array();

	var $_template = null;

	/* for block functions */
	var $block = null;
	var $allowedBlocks = array();
    var $blockTemplates = array(); // optional template paths for blocks. defaults to blocks/{blockname}.stpl

	var $paramsGet = array();
	var $paramsPath = array();
    var $filterParams = array('objects_path', 'random', 'x', 'y'); // URL params that will not be forwarded with block urls

    var $localImportAvailable = null;
    var $localImportDir = null;
    var $localImportFileNames = null;

    public $actualTemplate = null;

	
    public function __toString()
    {
        if( isset($this->object_data[ 'name' ]) )
        {
            return $this->object_data[ 'name' ];
        }

        return null;
    }

    function _getProcessingRules()
    {
        return  array(
			array(
				'name' => 'template',
				'not_empty' => true
			),
		);
    }

	function _typeProcessing($params, $return_variables = true){
		if(!$this->new_object && !isset($params['template']))
		{
			return array();
		}
		$variables = $this->_getProcessingRules();

		$p = new processing;
		$p->request_type = 'v';
		$p->addPostCheck(array(&$this, '_checkTemplateAllowed'));

		if($return_variables)
		{
			return $p->check_values($variables, $params);
		}
		else
		{
			$p->error_cast = 'return';
			$p->check_values($variables, $params, $return_variables);
			$p->getXml();
		}
	}


	public function getLastModifiedDescendant()
	{
		$q = '
		SELECT
			last_edit
		FROM
			`object_ancestors` `oa`
		LEFT JOIN
			`objects` `o` ON oa.object_id = o.id
		WHERE
			`oa`.ancestor_id = "' . dbSE($this->object_data['id']) . '"
		ORDER BY
			o.last_edit DESC
		';
		$lastDescendantdEdit = dbGetOne($q);
		if($this->object_data['last_edit'] > $lastDescendantdEdit)
		{
			return $this->object_data['last_edit'];
		}
		else
		{
			return $lastDescendantdEdit;
		}
	}


	function _assignObjectData(){
		if(!$this->new_object)
		{

			if(!empty($this->table) || !empty($this->arrayTables))
			{
				$this->loadTemplateDataFromDb();
			}

			if(isset($this->on_load))
			{
				$f_name = $this->on_load;
				$this->$f_name();
			}
		}

        // unserialize google_map_point
        $emptyGoogleMapPointValue = array
        (
            'lat' => '',
            'lng' => ''
        );

        foreach ($this->_properties['fields'] as $fieldName => $fieldDef)
        {
            if ($fieldDef['type'] == 'google_map_point')
            {
                if (empty($this->object_data['data'][$fieldName]))
                {
                    $this->object_data['data'][$fieldName] = $emptyGoogleMapPointValue;
                }
                else
                {
                    $this->object_data['data'][$fieldName] = @unserialize($this->object_data['data'][$fieldName]);
                }

                if (empty($this->object_data['data'][$fieldName]))
                {
                    $this->object_data['data'][$fieldName] = $emptyGoogleMapPointValue;
                }
            }
            elseif ($fieldDef['type'] == 'array')
            {

                foreach ($fieldDef['fields'] as $subFieldName => $subFieldDef)
                {
                    if ($subFieldDef['type'] != 'google_map_point')
                    {
                        continue;
                    }
                    if (empty($this->object_data['data'][$fieldName]))
                    {
                        continue;
                    }

                    foreach ($this->object_data['data'][$fieldName] as & $arrayItem)
                    {
                        // $arrayItem
                        if (empty($arrayItem[$subFieldName]))
                        {
                            $arrayItem[$subFieldName] = $emptyGoogleMapPointValue;
                        }
                        else
                        {
                            $arrayItem[$subFieldName] = @unserialize($arrayItem[$subFieldName]);
                        }
                        if (empty($arrayItem[$subFieldName]))
                        {
                            $arrayItem[$subFieldName] = $emptyGoogleMapPointValue;
                        }
                    }

                }

            }
        }

		//debug ($this);
	}

	function _typeInit(){
		if(leaf_get_property('tinymce', false))
		{
			$this->_config = array_merge($this->_config, leaf_get_property('tinymce', false));
		}
		else if(leaf_get_property('xinha', false))
		{
			$this->_config = array_merge($this->_config, leaf_get_property('xinha', false));
		}
		$this->_init_properties();
		if(!empty($this->_config['allowed_templates']))
		{
			$this->_config['allowed_templates'] = explode(';', $this->_config['allowed_templates']);
		}

		if(!isset($this->_config['plugins']))
		{
			$this->_config['plugins'] =  array("UnFormat","LeafCleaner","ContextMenu","LeafLinks","LeafImages");
		}
		if(!isset($this->_config['toolbar']))
		{
			$this->_config['toolbar'] =  '
			(HTMLArea.is_gecko ? [] : ["cut","copy","paste"]),["separator"],
			["formatblock","separator","bold","italic","underline","separator"],
			["subscript","superscript","separator"],
			["linebreak","justifyleft","justifycenter","justifyright","justifyfull","separator"],
			["insertorderedlist","insertunorderedlist","separator"],
			["createlink","insertimage","inserthorizontalrule","separator"],
			["killword","removeformat","separator","htmlmode"]
		';
		}
	}

    function _checkTemplateAllowed($values){
        $parent_allowed_templates = $this->getParentAllowedChilds();
		if(
			//no object type, just templates
			is_numeric($values['template'])
			||
			//not in parent allowed templates
			!array_key_exists($values['template'], $parent_allowed_templates)
			||
			(
				//change not allowed, must be same template
				(
					!$this->_config['change_templates']
					&&
					!$this->new_object
				)
				&&
				$values['template'] != $this->object_data['template']
			)
			||
			//not in config allowed templates
			(
				!empty($this->_config['allowed_templates'])
				&&
				!in_array($values['template'], $this->_config['allowed_templates'])
			)
		)
		{
			$error['field'] = array(
				'name' => 'template'
			);
			$error['errorCode'] = 'unallowed template';
			return $error;
		}
		//xml_templates
		return true;
	}

	function _typeUpdateFields(){
		$replacable_fields = array('fileobject', 'objectlink', 'link');
		foreach($this->_properties['fields'] as $field)
		{
			//check for replacable field type
			if(in_array($field['type'], $replacable_fields))
			{
				//search for old object id key in childMap array
				if(!empty($this->object_data['data'][$field['name']]) && !empty($this->childMap[$this->object_data['data'][$field['name']]]))
				{
					//replace value with child new id
					$this->object_data['data'][$field['name']] = $this->childMap[$this->object_data['data'][$field['name']]];
				}
			}
			elseif($field['type'] == 'array')
			{
				//check for existing array fields and data
				if(!empty($field['fields']) && !empty($this->object_data['data'][$field['name']]))
				{
					//loop through all array data items
					foreach($this->object_data['data'][$field['name']] as $array_key => $array_value)
					{
						//loop through all item fields
						foreach($array_value as $array_field_name => $array_field_value)
						{
							//check for replacable field type and search for old object id key in childMap array
							if(in_array($field['fields'][$array_field_name]['type'], $replacable_fields) && !empty($this->childMap[$array_field_value]))
							{
								//replace value with child new id
								$this->object_data['data'][$field['name']][$array_key][$array_field_name] = $this->childMap[$array_field_value];
							}
						}
					}
				}
			}
		}
		$this->_typeSave($this->object_data['data'], array());
	}

	function _typeSave($params, $save_values = null){
        // debug ($this->_properties['fields']);
		//initialize data array
		$save = array();
		//run class pre-save function
		if(isset($this->on_pre_save))
		{
			$f_name = $this->on_pre_save;
			$this->$f_name($params);
		}

		// process linked auto_source fields
        $this->processLinkedFields($this->_properties['fields'], $params);

		// process empty arrays
		foreach($this->_properties['fields'] as $field)
		{
			if($field['type'] == 'array' && !isset($params[$field['name']]))
			{
				$params[$field['name']] = array();
			}
		}

		// process all template fields
		foreach($this->_properties['fields'] as $field)
		{
			if(($f_val = $this->_saveFieldType($field, $params)) !== FALSE)
			{
				$save[$field['name']] = $f_val;
			}
		}
		//run class save function
		if(isset($this->on_save))
		{
			$f_name = $this->on_save;
			$save = $this->$f_name($save);
		}
		// save table based fields
		if(!empty($this->table) || !empty($this->arrayTables))
		{
			$save = $this->saveTemplateDataToDb($save);
		}
		//write template data
		$save_values['data'] = serialize($save);
		//start processing
		$p = new processing;
		$p->db_update_entry('objects', $save_values, array('id' => $this->object_data['id']), true, true);
		$this->object_data['data'] = $save;
		if(isset($this->on_post_save))
		{
			$f_name = $this->on_post_save;
			$save = $this->$f_name();
		}
		//re-load new data
		return true;
	}

	function getFieldTypes(){
		$types = array();
		foreach($this->_properties['fields'] as $field)
		{
			$types[$field['type']] = true;
			if($field['type'] == 'array')
			{
				foreach($field['fields'] as $arrayField)
				{
					$types[$arrayField['type']] = true;
				}
			}
		}
		return $types;
	}

	function _typeEdit($params){

		//return item node xml

		// set default order_nr
		if (
            ($params['object_id'] == 0)
            &&
            (isset($this->_properties['defaultPosition']))
        )
		{
		    $this->object_data['order_nr'] = $this->_properties['defaultPosition'];
		}

		if(!empty($params['get_array_node']))
		{
			$this->_getArrayNode($params);
        }

        $assign['templates'] = $this->getParentAllowedChilds();
		//build instance data structure
		foreach($this->_properties['fields'] as $field)
		{
			if($this->check_field_rules($field))
			{
				$this->_loadFieldType($field, $this->object_data['template']);
				$assign['fields'][$field['name']] = $field;
			}
		}

        $this->loadObjectFieldPreviews( $assign['fields'] );
        // debug ($assign['fields']);

		//run class field load method
		if(isset($this->on_field_load))
		{
			$f_name = $this->on_field_load;
			$this->$f_name($assign['fields']);
		}
		//run class on edit method
		if(isset($this->on_edit))
		{
			$f_name = $this->on_edit;
			$this->$f_name();
		}

		$template_check = $this->_checkTemplateAllowed(array('template' => $this->object_data['template']));
		if($template_check !== true && !empty($template_check['errorCode']))
		{
			$assign['error_code'] = $template_check['errorCode'];
			if ($assign['error_code'] == 'unallowed template')
			{
			    $assign['parent'] = _core_load_object( $this->object_data['parent_id'] );
			}
		}

		$fieldTypes = $this->getFieldTypes();

		if( empty( $params['useNewTemplate'] ) )
		{
			_core_add_css($this->module_www . 'style.css');
		}

		_core_add_js($this->module_www . 'functions.js');

		// ui dependencies
		_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.core.min.js');
		_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.widget.min.js');
		_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.mouse.min.js');

		$assign['fieldTypes'] = $fieldTypes;
		if(isset($fieldTypes['array']))
		{
			_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.draggable.min.js');
			// sortable
			_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.sortable.min.js');
			// custom code
			_core_add_js(SHARED_WWW . 'objects/xml_template/js/array.js');
		}
		if(isset($fieldTypes['date']) || isset($fieldTypes['datetime']))
		{
			_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/effects.core.min.js' );
			_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/effects.slide.min.js' );
			_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.datepicker.min.js' );
			// type-specific code
			_core_add_js( SHARED_WWW . 'classes/input/js/date.js' );
			// theme
			_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.core.css' );
			_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.theme.css' );
			_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.datepicker.css' );
			// type-specific style
			_core_add_css( SHARED_WWW . 'classes/input/css/date.css' );
		}
		if (isset($fieldTypes['google_map_point']))
		{
            $protocol = requestUrl::isHttpsOn() ? 'https' : 'http';
            
            _core_add_js( $protocol. '://maps.googleapis.com/maps/api/js?sensor=false');
            //_core_add_js(SHARED_WWW . '3rdpart/swfobject/swfobject.js');
		}
		if (isset($fieldTypes['richtext']))
		{
			// ui dependencies for dialog
			_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.draggable.min.js');
			_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.button.min.js');
			_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.position.min.js');
			_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.resizable.min.js');
			// dialog
			_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.dialog.min.js');
			// tabs
			_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.tabs.min.js');
			_core_add_css(SHARED_WWW . '3rdpart/jquery/themes/base/ui.tabs.css');
			//
			_core_add_js(  SHARED_WWW . 'js/RequestUrl.class.js' );
			_core_add_js(  SHARED_WWW . 'classes/input/js/richtextImageDialog.class.js' );
			_core_add_js(  SHARED_WWW . 'classes/input/js/richtextEmbedDialog.class.js' );
			_core_add_css( SHARED_WWW . 'styles/leafDialog.css' );
			_core_add_css( SHARED_WWW . 'classes/input/css/richtextImageDialog.css' );
			_core_add_css( SHARED_WWW . 'classes/input/css/richtextEmbedDialog.css' );
			// iivil shit - preload resources
			require_once( SHARED_PATH . 'classes/input/input.class.php' );
			input::load( 'objectlink' );
			// swf object for flash previews
			_core_add_js( SHARED_WWW . '3rdpart/swfobject2/swfobject.js' );
			// tinymce
			_core_add_js( SHARED_WWW . '3rdpart/tinymce/tiny_mce.js' );
		}
		if (isset($fieldTypes['link']) || isset($fieldTypes['objectlink']))
		{
			// ui dependencies for dialog
			_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.draggable.min.js');
			_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.resizable.min.js');
			_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.button.min.js');
			_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.position.min.js');
			// dialog
			_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.dialog.min.js');
			// theme
			_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.core.css');
			_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.resizable.css');
			_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.dialog.css');
			_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.theme.css');
			// custom code dialog dependecies
			_core_add_js(SHARED_WWW . 'js/Leaf.js');
		}

		$assign['query_string'] = clear_query_string(array('template'), false);
		$assign['editor'] = (leaf_get('properties', 'tinymce')  != false ? 'tinymce' : 'xinha');

		$assign['config'] = $this->_config;
		$assign['site_www'] = $this->_config['site_www'];
		if (!empty($this->_labelContext))
		{
		    $assign['label_context'] = $this->_labelContext;
    		if (!empty($this->_labelLanguage))
    		{
    		    $assign['label_language'] = $this->_labelLanguage;
    		}
		}
		// temporary - required to run both content modules simultaneously
		$templateName = NULL;
		if( !empty( $params['useNewTemplate'] ) )
		{
			$templateName = SHARED_PATH . 'objects/newEdit.tpl';
		}
		// --temporary - required to run both content modules simultaneously
		return $this->editForm( $assign, $templateName );
	}


	function check_field_rules(&$field){
		$allowedParents = array();
		$allowedLevels = array();
		$allow = true;
		if(!empty($field['rules']))
		{
			foreach($field['rules'] as $rule)
			{
				if($rule['type'] == 'parent')
				{
					$allowedParents[] = $rule['@']['template'];
				}
				else if($rule['type'] == 'level')
				{
					$allowedLevels = explode(',', $rule['@']['allowed']);
				}
			}
		}
		// check by allowed parent
		if(sizeof($allowedParents) > 0)
		{
			$q = '
			SELECT
				IF(obj.type = 22, obj.template, obj.type)
			FROM
				`objects` `obj`
			WHERE
				obj.id = "' . $this->object_data['parent_id'] . '"
			';
			$parentTemplate = dbGetOne($q);
			if(!in_array($parentTemplate, $allowedParents))
			{
				$allow = false;
			}
		}
		// check by allowed levels
		if(sizeof($allowedLevels) > 0)
		{
			if($this->object_data['parent_id'])
			{
				$q = '
				SELECT
					COUNT(*) + 2
				FROM
					`object_ancestors`
				WHERE
					`object_id` = "' . $this->object_data['parent_id'] . '"
				';
				$level = dbGetOne($q);
			}
			else
			{
				$level = 1;
			}
			if(!in_array($level, $allowedLevels))
			{
				$allow = false;
			}
		}
		return $allow;
	}

	function _typeDelete(){
		//try to delete template object data
		if(isset($this->on_delete))
		{
			$f_name = $this->on_delete;
			$save = $this->$f_name($this->object_data['id']);
		}
		// delete fields tables
		if(!empty($this->table) || !empty($this->arrayTables))
		{
			$this->deleteTemplateDataFromDb();
		}

		// clear relations
		contentNodeRelation::clearRelationsFor( $this->object_data['id'] );
	}

	function _typeCopy(){
		if(isset($this->on_save) || !empty($this->table) || !empty($this->arrayTables))
		{
			$save = $this->object_data['data'];
			if(isset($this->on_save))
			{
				$f_name = $this->on_save;
				$save = $this->$f_name($save);
			}
			// save table based fields
			if(!empty($this->table) || !empty($this->arrayTables))
			{
				$save = $this->saveTemplateDataToDb($save);
			}
			//clear object_id field
			if(!empty($save['object_id']))
			{
				unset($save['object_id']);
			}
			if(empty($save))
			{
				$save_values['data'] = 'NULL';
				$quote_fields = array('data');
			}
			else
			{
				$save_values['data'] = serialize($save);
				$quote_fields = array();
			}
			//start processing -> update date field
			$p = new processing;
			$p->db_update_entry('objects', $save_values, array('id' => $this->object_data['id']), true, true, $quote_fields);
		}
	}

	function _getArrayNode($params){
	    if(!isPositiveInt($_GET['nextNr']))
	    {
	       exit;
	    }

		//must escape here...
		$template_path = $params['template'];

		$fields = $this->_properties['fields'][$_GET['item_name']]['fields'];
		foreach($fields as &$field)
		{
			if($this->check_field_rules($field))
			{
				$this->_loadFieldType($field, $this->object_data['template'], true);
			}
		}

		$itemNr = $_GET['nextNr'];


		//run class field load method
		if (isset($this->on_array_field_load))
		{
			$f_name = $this->on_array_field_load;
			$this->$f_name($_GET['item_name'], $fields);
		}
		$assign['field']['value'][] = $fields;


    	foreach($assign['field']['value'][0] as $key => $item)
		{
			$assign['field']['value'][0][$key]['input_name'] = $_GET['item_name'] . '[' . $itemNr . '][' . $item['name'] . ']';
			$assign['field']['value'][0][$key]['input_id'] = $_GET['item_name'] . '_' . $itemNr . '_' . $item['name'];
		}

		$assign['editor'] = (leaf_get('properties', 'tinymce')  != false ? 'tinymce' : 'xinha');

		$template= new leaf_smarty($this->module_path .  'templates/');
		$template->Assign('node_get', true);
		$template->Assign('node_get_nr', $itemNr);
		$template->Assign('node_get_items', true);
		$template->Assign('_object', $this);
		$template->Assign('_config', $this->_config);
		$template->Assign($assign);
		require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');
		$template->register_outputfilter(array('alias_cache', 'fillInAliases'));

		header("Content-Type: text/xml");
		echo '<?xml version="1.0" encoding="UTF-8" standalone="no"?'.'>';
		echo $template->fetch('types/array.tpl');
		exit;
	}

	public function getArrayNamePostfix($name, $postfix)
	{
	   // detect if array
	   if(substr($name, -1, 1) == ']')
	   {
	       $value = substr($name, 0, -1) . $postfix . ']';
	   }
	   else
	   {
	       $value = $name . $postfix;
	   }
	   return $value;
	}

//type methods

	//main method
	function _loadFieldType(&$field, $template, $arrayNode = false){
		//get stored value
		if(!$arrayNode && isset($this->object_data['data'][$field['name']]))
		{
			$field['value'] = $this->object_data['data'][$field['name']];
		}
		//get default value
		else if(isset($field['properties']['default']))
		{
			$field['value'] = $field['properties']['default'];
		}
		else
		{
			$field['value'] = '';
		}
		$field['input_name'] = $field['name'];
		$field['input_id'] = $field['name'];
		$f_name = '_loadFieldType' .  ucfirst($field['type']);
		if(method_exists($this, $f_name))
		{
			$this->$f_name($field);
		}
	}

	function _loadFieldTypeCheckbox_group(&$field){
		if($field['value'])
		{
			$field['value'] = explode('/', $field['value']);
		}
	}

	function _loadFieldTypeFileobject(&$field){

	    if (
            (!empty($field['properties']['local_import']))
            &&
            (in_array($field['properties']['local_import'], array('1', 'true', 'yes')))
            &&
            ($this->isLocalImportAvailable())
	    )
	    {

	        $fileNames = $this->getLocalImportFileNames();
	        if ($fileNames)
	        {
                $field['properties']['local_import_options'] = $fileNames;
	        }
	    }

	    //debug ($field);

		if($field['value'])
		{
			if(!isset($this->file_www))
			{
				$file_config = leaf_get('objects_config', 21);
				$this->file_www = $file_config['files_www'];
			}
			//search for file object
			$q = '
			SELECT
				o.name,
				f.file_name,
				f.extension,
				f.extra_info
			FROM
				`files` `f`,
				`objects` `o`
			WHERE
				o.id = f.object_id AND
				o.id = "' . $field['value'] . '"
			';
			if(isPositiveInt($field['value']) && $field['file'] = dbGetRow($q))
			{
				$field['file']['extra_info'] = unserialize($field['file']['extra_info']);
				$field['file']['file_www'] = $this->file_www;
			}
			else
			{
				$field['value'] = 0;
			}
		}
	}

	function _loadFieldTypeRichtext(&$field){
		if(empty($field['value']))
		{
			return;
		}
		$field['value'] = str_replace('alt=""', 'alt="___"', $field['value']);
	}

	function _loadFieldTypeArray(&$field){
		if (isset($this->on_array_field_load))
		{
			$f_name = $this->on_array_field_load;
			$this->$f_name($field['name'], $field['fields']);
		}

		if(!is_array($field['value']))
		{
			return;
		}
		if (
            (isset($field['properties']['count']))
            &&
            ($field['properties']['count'] == 'count')
        )
		{
		    $field['properties']['count'] = sizeof($field['value']);
		}
		//reload existing
		foreach($field['value'] as $key => $array_item)
		{
			$nr = $key + 1;
			$array_item_data = array();
			foreach($field['fields'] as $array_field)
			{
				//get stored value
				if(isset($array_item[$array_field['name']]))
				{
					$array_field['value'] = $array_item[$array_field['name']];
				}
				//get default value
				else if(isset($array_field['properties']['default']))
				{
					$array_field['value'] = $array_field['properties']['default'];
				}
				else
				{
					$array_field['value']='';
				}

				$f_name = '_loadFieldType' .  $array_field['type'];
				if(method_exists($this, $f_name))
				{
					$this->$f_name($array_field);
				}
				$array_field['input_name'] = $field['name'] . '[' . $nr . '][' . $array_field['name'] . ']';
				$array_field['input_id'] = $field['name'] . '_' . $nr . '_' . $array_field['name'];

				$array_item_data[] = $array_field;
			}
			$field['value'][$key] = $array_item_data;
		}
	}

	//type save methods
	function _saveFieldType($field, &$params){
		$f_name = '_saveFieldType' .  $field['type'];
		if(
			isset($params[$field['name']])
			||
			(
                !empty($field['array_name'])
                &&
                isset($params[$field['array_name']][$field['array_key']][$field['name']])
            )
            ||
            (
                $field['type'] == 'checkbox'
            )
		)
		{
			if(method_exists($this, $f_name))
			{
				$val = $this->$f_name($field, $params);
			}
			else
			{
				if(!empty($field['array_name']))
				{
					$val = $params[$field['array_name']][$field['array_key']][$field['name']];
				}
				else
				{
					$val = $params[$field['name']];
				}
			}
			return $val;
		}
		else
		{
			return FALSE;
		}
	}

	function _saveFieldTypeDatetime($field, &$params){
		if(!empty($field['array_name']))
		{
			if(!empty($params[$field['array_name']][$field['array_key']][$field['name']]))
			{
     		    $time = empty($params[$field['array_name']][$field['array_key']][$field['name'] . 'Timefield']) ? '00:00' :  $params[$field['array_name']][$field['array_key']][$field['name'] . 'Timefield'];
    		    $value =  $params[$field['array_name']][$field['array_key']][$field['name']] .' ' . $time . ':00';
    			return $value;
			}
			else
			{
				return null;
			}
		}
		else if(!empty($params[$field['name']]))
		{
     		$time = empty($params[$field['name']. 'Timefield']) ? '00:00' :  $params[$field['name']. 'Timefield'];
 		    $value =  $params[$field['name']] .' ' . $time . ':00';
			return $value;
		}
		else
		{
			return null;
		}
	}

	function _saveFieldTypeDate($field, &$params){
		if(!empty($field['array_name']))
		{
			if(!empty($params[$field['array_name']][$field['array_key']][$field['name']]))
			{
				return $params[$field['array_name']][$field['array_key']][$field['name']];
			}
			else
			{
				return null;
			}
		}
		else if(!empty($params[$field['name']]))
		{
			return $params[$field['name']];
		}
		else
		{
			return null;
		}
	}





    function _saveFieldTypeLink($field, &$params){
        if (!empty($field['array_name']))
        {
            $value = $params[$field['array_name']][$field['array_key']][$field['name']];
        }
        else
        {
            $value = $params[$field['name']];
        }
        // check for non integer (url)
        if (!isPositiveInt($value) && !empty($value))
        {
            // add http:// prefix if no scheme part found in url
            if (strpos($value, '://') === FALSE)
            {
                $value  = 'http://' . $value;
            }
        }
        return $value;
    }

	function _saveFieldTypeCheckbox_group($field, &$params){
		
		$fieldName = false;
		if(!empty($field['array_name']))
		{
			if(isset($params[$field['array_name']][$field['array_key']][$field['name']]))
			{
				$fieldName = $params[$field['array_name']][$field['array_key']][$field['name']];
			}
		}
		else if(!empty($params[$field['name']]))
		{
			$fieldName = $params[$field['name']];
		}
		
		return '/' . implode('/', $fieldName) . '/';
	}


	function _saveFieldTypeCheckbox($field, &$params)
    {
        $value = 0;
		if(!empty($field['array_name']))
		{
			if(isset($params[$field['array_name']][$field['array_key']][$field['name']]))
			{
				$value = 1;
			}
		}
		else if(isset($params[$field['name']]))
		{
			$value = 1;
		}
        return $value;
	}

	function _saveFieldTypeGoogle_map_point( $field, & $params)
	{
		if(!empty($field['array_name']))
		{
			if(!empty($params[$field['array_name']][$field['array_key']][$field['name']]))
			{
				$value = $params[$field['array_name']][$field['array_key']][$field['name']];
			}
			else
			{
				return null;
			}
		}
		else if(!empty($params[$field['name']]))
		{
			$value = $params[$field['name']];
		}
		else
		{
			return null;
		}

	    $coords = array(
	       'lat' => '',
	       'lng' => ''
	    );

		if(!is_array($value))
		{
		    $value = explode(';', $value);
		    if (count($value) == 2)
		    {
			$coords['lat'] = $value[0];
			$coords['lng'] = $value[1];
		    }
		}

	    return serialize($coords);

	}



	function _saveFieldTypeRichtext($field, &$params){
		if(!empty($field['array_name']))
		{
			$html = $params[$field['array_name']][$field['array_key']][$field['name']];
		}
		else
		{
			$html = $params[$field['name']];
		}
		$html = leafHtmlCleaner::clean($html);
		// strip img alt tags
		$html = str_replace('alt="___"', 'alt=""', $html);
		// correct IE richtext relative href fuckup
		$html = str_replace('http:///?object_id', '?object_id', $html);
		return $html;
	}

	function _saveFieldTypeFileobject($field, &$params){
		if(!empty($field['array_name']))
		{
		    $arrayMode = true;
			$val = $params[$field['array_name']][$field['array_key']][$field['name']];
		}
		else
		{
		    $arrayMode = false;
			$val = $params[$field['name']];
		}


		$f_name = (isset($field['input_id']) ? $field['input_id'] : $field['name'])  . '_file';
		$importFieldName = (isset($field['input_id']) ? $field['input_id'] : $field['name']) . '_local_import';


		$localImportFileName = null;
		if (
            (
                (!empty($params[$importFieldName]))
                &&
                ($localImportFileName = $this->getLocalImportFileName($params[$importFieldName]))
            )
            ||
            (
                (isset($params[$f_name]) && is_array($params[$f_name]) && !empty($params[$f_name]['name']) && file_exists($params[$f_name]['tmp_name']) && $params[$f_name]['error'] == 0)
            )
        )
		{
		    // debug ($localImportFileName);

			if(!empty($field['properties']['allowedExtensions']))
			{
				$tmp = pathinfo($params[$f_name]['name']);
				$extension = strtolower($tmp['extension']);
				$allowed = explode(',' ,$field['properties']['allowedExtensions']);
				if(!in_array($extension, $allowed))
				{
					return intval($val);
				}
			}
			if(empty($val))
			{
				$object_param['type'] = 21;
				$object_param['parent_id'] = $this->object_data['id'];
				$object_param['id'] = 0;
			}
			else
			{
				$object_param = $val;
			}
			$object = _core_load_object($object_param);

			if ($localImportFileName)
			{
			    $save_params = array(
                    'name' => basename($localImportFileName),
                    'source_file' => $localImportFileName
			    );
			    $object->setLocalImportMode('move');
			}
			elseif (
                (!empty($params[$f_name]['name']['http_upload_copy']))
                &&
                ($params[$f_name]['name']['http_upload_copy'])
            )
			{
			    $save_params = $params[$f_name];
			    $object->setLocalImportMode('move');
			}
			else
			{
			     $save_params = $params[$f_name];
			}
			if(isset($field['properties']['visible']))
			{
				$save_params['visible'] = $field['properties']['visible'];
			}
			else
			{
				$save_params['visible'] = 0;
			}
			if(isset($field['properties']['auto_source']))
			{
				$save_params['auto_source'] = $field['properties']['auto_source'];
			}
			if(isset($field['properties']['image_resize']))
			{
				$save_params['resize'] = $field['properties']['image_resize'];
			}
			if(isset($field['properties']['image_crop']))
			{
				$save_params['crop'] = $field['properties']['image_crop'];
			}
            if(isset($field['properties']['image_crop_mode']))
			{
				$save_params['crop_mode'] = $field['properties']['image_crop_mode'];
			}
            if(isset($field['properties']['image_resize_and_crop']))
			{
				$save_params['resize_and_crop'] = $field['properties']['image_resize_and_crop'];
			}
			if(isset($field['properties']['image_thumbnail']))
			{
				$save_params['thumbnail'] = $field['properties']['image_thumbnail'];
			}
			if(isset($field['properties']['thumbnail_mode']))
			{
				$save_params['thumbnail_mode'] = $field['properties']['thumbnail_mode'];
			}
			if(isset($field['properties']['quality']))
			{
				$save_params['quality'] = $field['properties']['quality'];
			}
			if(isset($field['properties']['plugins']))
			{
				$save_params['fileobjectPlugins'] = explode(',', $field['properties']['plugins']);
			}
			if(isset($field['properties']['watermark']))
			{
				$object->options['watermark'] = $field['properties']['watermark'];
			}
			if (isset($field['properties']['skip_plugins']))
			{
			    $skipPlugins = array();
			    $tmp = explode(',', $field['properties']['skip_plugins']);
			    foreach ($tmp as $tmp2)
			    {
			        $tmp2 = trim($tmp2);
			        if (empty($tmp2))
			        {
			            continue;
			        }
			        $skipPlugins[] = $tmp2;
			    }
			    if (!empty($skipPlugins))
			    {
                    $save_params['skipPlugins'] = $skipPlugins;
			    }
			}
			if(isset($params['postCompleted']))
			{
				$save_params['postCompleted'] = $params['postCompleted'];
			}
			$object->saveObject($save_params);
			return $object->object_data['id'];
		}
		else
		{
			return intval($val);
		}
	}

	function getLinkedFieldKeysToCopy( $fields, $params)
	{
	    // collect source/target param key pairs that need to be copied

	    $keysToCopy = array();
        foreach ($fields as $fieldName => $field)
        {

            $fieldNamePrefix = $fieldName; // different for arrays

            if ($field['type'] == 'fileobject')
            {
                // check if file upload exists for this field, skip if not
                $fileParamKey = $fieldNamePrefix  . '_file';
                if (empty($params[$fileParamKey]['tmp_name']))
                {
                    continue;
                }

                // check if this field has linked fields and if the update checkbox is set, skip if not
                $updateLinkedKey = $fieldNamePrefix . '_update_linked';
                // debug ($updateLinkedKey, 0);
                if (
                    (empty($field['linked_fields']))
                    ||
                    (empty($params[$updateLinkedKey]))
                )
                {
                    continue;
                }

                // debug ($field, 0);
                foreach ($field['linked_fields'] as $linkedField )
                {
                    $linkedFieldNamePrefix = $linkedField;

                    $sourceFileField = $fileParamKey;
                    $targetFileField = $linkedFieldNamePrefix . '_file';

                    // check if target field does not have its own file upload
                    if (!empty($params[$targetFileField]['tmp_name']))
                    {
                        continue;
                    }

                    $keysToCopy[ $targetFileField ] = $sourceFileField;
                    //debug ($sourceFileField . ' -> ' . $targetFileField , 0);
                }
            }
            elseif ($field['type'] == 'array')
            {
                // if no fields in array
                if (empty($field['fields']))
                {
                    continue;
                }

                foreach ($field['fields'] as $arrayFieldName => $arrayField)
                {
                    if (
                        ($arrayField['type'] != 'fileobject')
                        ||
                        (empty($params[$fieldName]))
                    )
                    {
                        continue;
                    }

                    foreach ($params[$fieldName] as $itemKey => $arrayItem)
                    {
                        $fieldNamePrefix = $fieldName . '_' . $itemKey . '_' . $arrayFieldName;

                        // check if file upload exists for this field, skip if not
                        $fileParamKey = $fieldNamePrefix  . '_file';
                        if (empty($params[$fileParamKey]['tmp_name']))
                        {
                            continue;
                        }

                        // check if this field has linked fields and if the update checkbox is set, skip if not
                        $updateLinkedKey = $fieldNamePrefix . '_update_linked';
                        if (
                            (empty($arrayField['linked_fields']))
                            ||
                            (empty($params[$updateLinkedKey]))
                        )
                        {
                            continue;
                        }

                        foreach ($arrayField['linked_fields'] as $linkedField )
                        {

                            $linkedFieldNamePrefix =  $fieldName . '_' . $itemKey . '_' . $linkedField;

                            $sourceFileField = $fileParamKey;
                            $targetFileField = $linkedFieldNamePrefix . '_file';

                            // check if target field does not have its own file upload
                            if (!empty($params[$targetFileField]['tmp_name']))
                            {
                                continue;
                            }

                            $keysToCopy[ $targetFileField ] = $sourceFileField;
                        }

                    }

                }
            }
        }

        // debug ($keysToCopy, 0);

        return $keysToCopy;

	}

	function processLinkedFields($fields, & $params, $arrayName = null, $arrayKey = null)
	{
	    $keysToCopy = $this->getLinkedFieldKeysToCopy( $fields, $params);
	    foreach ($keysToCopy as $targetKey => $sourceKey)
	    {
            if (empty($params[$sourceKey]['tmp_name']))
            {
                continue; // wtf?
            }

            // file upload exists in source, copy to current field
            $sourceFileName = $params[$sourceKey]['tmp_name'];
            $newTempFileName = $this->getUniqueFileName( $sourceFileName );

            $copyOk = copy($sourceFileName, $newTempFileName);
            if ($copyOk)
            {
                $newArray = array();
                foreach ($params[$sourceKey] as $key => $val)
                {
                    $newArray[$key] = $val;
                }
                $newArray['tmp_name'] = $newTempFileName;
                $newArray['http_upload_copy'] = true;
                $params[$targetKey] = $newArray;
            }

	    }

	    // debug ($params);
	    return true;
	}

	function getUniqueFileName($currentFileName)
	{
	    $found = false;
	    while (!$found)
	    {
            $newFileName = $currentFileName . '_' . substr(md5(rand()), 0, 5);
            if (!file_exists($newFileName))
            {
                $found = true;
            }
	    }
	    return $newFileName;
	}

	function _saveFieldTypeArray($field, &$params){
		$field['value'] = null;
		foreach($params[$field['name']] as  $nr => $array_item)
		{
			$array_item_data = array();
			foreach($field['fields'] as $array_field)
			{
				if(isset($array_item[$array_field['name']]) || $array_field['type'] == 'checkbox')
				{
					$array_field['input_id'] = $field['name'] . '_' . $nr . '_' . $array_field['name'];
					$array_field['array_name'] = $field['name'];
					$array_field['array_key'] = $nr;
					$array_item_data[$array_field['name']] = $this->_saveFieldType($array_field, $params);
				}
			}
			$field['value'][] = $array_item_data;
		}
		return $field['value'];
	}

	function _assignObject($object){
		if(is_object($object))
		{
			$this->object_data = get_object_vars($object);
		}
		else
		{
			$this->object_data = $object;
		}
		//unserialize
		if(!empty($this->object_data['data']) && !is_array($this->object_data['data']))
		{
			$this->object_data['data'] = unserialize($this->object_data['data']);
		}
	}

	function loadAssigns()
	{
		//add assigns
		if(isset($this->_properties['assigns']))
		{
			$dirname = $this->_config['templates_www'];
			foreach($this->_properties['assigns'] as $assign)
			{
				$prefix = (!isset($assign['absolute_path']) || $assign['absolute_path'] == 'no') ? $dirname : '';
				if($assign['type'] == 'js')
				{
					_core_add_js($prefix . $assign['path']);
				}
				elseif($assign['type'] == 'css')
				{
					_core_add_css($prefix . $assign['path']);
				}
			}
		}
	}

	function _typeView($params = null, & $module = null){
		$this->_init_properties();

		$deferredAssigns = ((isset($this->deferAssigns)) && ($this->deferAssigns));
		if (!$deferredAssigns)
		{
            $this->loadAssigns();
		}

		//run template internal dynamic output
		if(method_exists($this, 'dynamic_output'))
		{
		    if (!empty($this->allowedBlocks))
		    {
                $this->setBlock();
                if ((isset($_GET)) && (is_array($_GET)))
                {
                    $this->paramsGet = $_GET;
                }
		    }

			$dynamic_return = $this->dynamic_output($module);

			if(!is_null($dynamic_return))
			{
				return $dynamic_return;
			}

            if ($this->block)
            {
                // block mode. set appropriate template
                $this->customLayout = true;
                $template = $this->getBlockTemplate( $this->block );
                $this->_setTemplate($template);
            }


		}

		if ($deferredAssigns)
		{
            $this->loadAssigns();
		}
		//get xml template directory
		$templateName = ($this->actualTemplate) ? $this->actualTemplate : $this->object_data['template'];

		$path_parts = pathinfo($this->_config['templates_path'] . $templateName);
		//set instance template
		$templateFile = $this->_template ? $this->_template : $path_parts['basename'] . '.tpl';


		//check for these template
		if(file_exists($path_parts['dirname'] . '/' . $templateFile))
		{
			$template = new leaf_smarty($path_parts['dirname']);
			require_once (SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');
			//set alias context
			alias_cache::setContext($template, $this->object_data['template']);
			if(isset($smarty_plugin_directories))
			{
				 // the default under SMARTY_DIR
				$smarty_plugin_directories[] = 'plugins';
				$template->plugins_dir = $smarty_plugin_directories;
			}
			$template->assign('properties', leaf_get('properties'));
			$template->assign_by_ref('_object', $this);
			$template->assign($this->object_data['data']);
			$output = $template->fetch($templateFile);
			return $output;
		}
	}

	function _setTemplate($template)
	{
	    $this->_template = $template;
	}

/*
	function dynamic_output(){

	}

	function output(){

	}
*/
	function saveTemplateDataToDb($data){
		$replace_values = array();
		$return_values = array();
		$arrayTablesFields = array();
		if(!empty($this->arrayTables))
		{
			foreach($this->arrayTables as $tableName)
			{
				dbDelete($tableName, array('object_id' => $this->object_data['id']));
			}
		}
		foreach($data as $key => $val)
		{
			//skip common fields
			if(empty($this->_properties['fields'][$key]['common']) && !empty($this->_properties['fields'][$key]))
			{
				// array have table
				if($this->_properties['fields'][$key]['type'] == 'array' && !empty($this->arrayTables[$key]))
				{
                    if(!empty($val))
                    {
                        foreach($val as $arrayItemKey => &$arrayItem)
                        {
                            $arrayItem['_key'] = $arrayItemKey;
                            $arrayItem['object_id'] = $this->object_data['id'];
                        }
                        // insert new
                        dbInsert($this->arrayTables[$key], $val);
                    }
				}
				elseif(!empty($this->table))
				{
					if($this->_properties['fields'][$key]['type'] == 'array')
					{
						$val = serialize($val);
					}
					$replace_values[$key] = $val;
				}
			}
			else
			{
				$return_values[$key] = $val;
			}
		}
		if(!empty($replace_values) && !empty($this->table))
		{
			$replace_values['object_id'] = $this->object_data['id'];
			dbReplace($this->table, $replace_values);
		}
		return $return_values;
	}

	function prepareSQL(){
		$q = '
		SELECT
			*
		FROM
			`' . $this->table . '`
		WHERE
			`object_id` = "' . $this->object_data['id'] . '"
		';
		return $q;
	}

	function loadTemplateDataFromDb(){
		// load from template table
		if(!empty($this->table))
		{
			$q = $this->prepareSQL();
			if($entry = dbGetRow($q))
			{
				foreach($entry as $f_key => $f_val)
				{
					if($f_key != 'object_id' && isset($this->_properties['fields'][$f_key]) && $this->_properties['fields'][$f_key]['type'] == 'array' && $f_val)
					{
						$this->object_data['data'][$f_key] = unserialize($f_val);
					}
					else
					{
						$this->object_data['data'][$f_key] = $f_val;
					}
				}
			}
		}
		if(!empty($this->arrayTables))
		{
			foreach($this->arrayTables as $fieldName => $tableName)
			{
				$q = '
				SELECT
					*
				FROM
					`' . $tableName . '`
				WHERE
					`object_id` = "' . $this->object_data['id'] . '"
				ORDER BY
					`_key`
				';
				$this->object_data['data'][$fieldName] = dbGetAll($q, '_key');
			}
		}


	}

	function deleteTemplateDataFromDb(){
		if(!empty($this->table))
		{
			dbDelete($this->table, array('object_id' => $this->object_data['id']));
		}
		if(!empty($this->arrayTables))
		{
			foreach($this->arrayTables as $tableName)
			{
				dbDelete($tableName, array('object_id' => $this->object_data['id']));
			}
		}
	}

	function loadFileData()
	{
	    $this->fileData = $this->getFileData();

	}

	function getFileData()
	{
	    // scans template data for image / fileobject types
	    // and loads extra_info
	    // debug ($this->object_data);

	    // 1) get file field names
	    $fieldDefs = $this->_properties['fields'];
	    $fileFieldNames = array();
	    foreach ($fieldDefs as $fieldName => $fieldDef)
	    {
	        if (
	           (!isset($fieldDef['type']))
	           ||
	           (!in_array($fieldDef['type'],$this->fileFieldTypes))
	        )
	        {
	            continue;
	        }
	        $fileFieldNames[] = $fieldName;
	    }

	    // 2) get non-zero file field values, prepare result array
        $valueData = $this->object_data['data'];
	    $fileFieldValues = array();
        foreach ($fileFieldNames as $fieldName)
        {
            if (
                (isset($valueData[$fieldName]))
                &&
                ($valueData[$fieldName])
            )
            {
                $fileId = (int) $valueData[$fieldName];
                $fileFieldValues[$fileId] = null;
            }
        }

        if (empty($fileFieldValues))
        {
            // no file data
            return null;
        }
        $fileObjectIds = array_keys($fileFieldValues);

        // 3) get data from DB
        $sql = '
            SELECT
                *
            FROM
                files
            WHERE
                object_id
                IN ('  . implode(', ', $fileObjectIds) . ')
        ';

        $fileData = dbGetAll($sql, 'object_id');
        foreach ($fileData as $objectId => $fileDataRow)
        {
            $fileDataRow['extra_info'] = unserialize($fileDataRow['extra_info']);
            $fileFieldValues[$objectId] = $fileDataRow;
        }

	    return $fileFieldValues;
	}

	function _addEditTemplate($template){
		$this->_editIncludes[] = $template;
	}


	/* ajax block methods */

    function setBlock($blockName = null)
    {
        if ($blockName === false)
        {
            $this->block = null;
        }
        elseif (
            (!is_null($blockName))
            &&
            (in_array($blockName, $this->allowedBlocks))
        )
        {
            $this->block = $blockName;
        }
        elseif
        (
            (isset($_GET['block']))
            &&
            (in_array($_GET['block'], $this->allowedBlocks))
        )
        {
            $this->block = $_GET['block'];
            unset ($_GET['block']);
        }
        elseif
        (
            (isset($_POST['block']))
            &&
            (in_array($_POST['block'], $this->allowedBlocks))
        )
        {
            $this->block = $_POST['block'];
            unset ($_POST['block']);
        }
    }

    function getBlockTemplate($blockName)
    {
        if (!in_array($blockName, $this->allowedBlocks))
        {
            return null;
        }
        if (!empty($this->blockTemplates[$blockName]))
        {
            return $this->blockTemplates[$blockName];
        }
        // by now $blockName must contain only valid chars to avoid ../../ tricks etc
        // this is ensured by checking against allowedblocks in the beginning of this function
        return 'blocks/' . $blockName . '.stpl';
    }

    function addParamPath($pathPart)
    {
        $this->paramsPath[] = $pathPart;
    }
    function getForwardParams($additionalFilterParams = array())
    {
        // returns key:value array with GET params of current page that need to be passed on in ajax/block and other requests
        $params = $this->paramsGet;
        $filterParams = $this->filterParams;

        foreach ($filterParams as $varName)
        {
            unset ($params[$varName]);
        }

        if (!empty($additionalFilterParams))
        {
            if (!is_array($additionalFilterParams))
            {
                $additionalFilterParams = array($additionalFilterParams);
            }
            foreach ($additionalFilterParams as $varName)
            {
                unset ($params[$varName]);
            }
        }

        return $params ;
    }

    function getUrlWithParams($paramData = null, $argRemoveArrayItem = false)
    {
        // returns current page url with added/removed query arguments

        // ->getUrlWithParams('foo=123'); - adds 'foo=123' to query string in url. overvrites any existing foo=value in url
        // ->getUrlWithParams('foo=123&bar=456'); - adds 'foo=123&bar=456'  to query string in url. also overwrites existing values
        // ->getUrlWithParams('foo[]=123'); - adds array param foo[]=123 to query string. avoids duplicate array values
        // ->getUrlWithParams('foo');  - if exists, removes 'foo' variable and its current value from the query string.
        //                               if exists as an array variable (foo[]=12&foo[]=14), all foo[] values are removed
        // ->getUrlWithParams('foo[]=123', true); - if foo[]=123 exists in url, removes it. (does not affect other foo[]=xxx values)

        // multiple operations are also supported when the $paramData is passed as an array.
        // in that case the main $argRemoveArrayItem argument is ignored:
        // ->getUrlWithParams(array (
        //        'foo=123&baz=777',
        //        array( 'bar[]=256', true),
        //        array( 'bar[]=456')
        //  ))
        //  the above will 1) add foo=123&baz=777 to params,
        //                 2) if exists,  remove bar[]=256
        //                 3) add bar[]=456, if it does not exist already

        // special cases:
        // ->getUrlWithParams();       - adds/removes nothing, returns the full url as is.
        // ->getUrlWithParams(false);  - adds/removes nothing, returns the url without GET params at all (only uses path params)

        $skipGetParams = false;
        $queryString = null;

        // decide what to do depending on given args nad prepare the args
        if (is_null($paramData))
        {
            // $argRemoveArrayItem is ignored
            $paramData = array();
        }
        elseif ($paramData === false)
        {
            // $argRemoveArrayItem is ignored
            $skipGetParams = true;
        }
        elseif (!is_array($paramData))
        {
            // not a special case, not an array, convert to array, as if receiving multiple params
            $paramData = array(
                array($paramData, $argRemoveArrayItem)
            );
        }


        if (!$skipGetParams)
        {
            // get current params in url

            $params = $this->getForwardParams();
            if (!is_array($params))
            {
                $params = array();
            }

            foreach ($paramData as $paramPair)
            {
                if (!$paramPair)
                {
                    continue;
                }
                elseif (!is_array($paramPair))
                {
                    $paramPair = array($paramPair);
                }
                $paramPairArgCount = count($paramPair);
                if ($paramPairArgCount == 1)
                {
                    $paramPair[] = false; // set default value of $argRemoveArrayItem
                    $paramPairArgCount++;
                }
                if ($paramPairArgCount != 2)
                {
                    continue; // wrong param count in a pair. skip this
                }

                list($paramString, $removeArrayItem) = $paramPair;
                if (!$paramString)
                {
                    continue; // bad item in first arg, skip this pair
                }


                // for each argument pair, split argument string into variable pairs on '&'
                $argParams = explode('&', $paramString);
                foreach ($argParams as $argParam)
                {
                    // split each variable pair into variable name and value
                    $argParam = explode('=', $argParam);
                    $partCount = count($argParam);

                    if ($partCount == 1)
                    {
                        // if only variable name given, unset variable
                        $varName = $argParam[0];
                        unset ($params[$varName]);

                    }
                    elseif ($partCount == 2)
                    {
                        // if both variable name and value given,
                        // actions depend on the variable type

                        $varName = $argParam[0];
                        $varValue = $argParam[1];

                        $argParamIsArray = (substr($varName,-2) == '[]');

                        if ($argParamIsArray)
                        {
                            // if variable is an array, attempt to locate current key for given value
                            // and check the additional $removeArrayItem argument for action (whether to add or remove)

                            $varName = substr($varName,0,-2);

                            // make sure the array exists.
                            // convert from scalar value if needed
                            if (!isset($params[$varName]))
                            {
                                $params[$varName] = array();
                            }
                            elseif (!is_array($params[$varName]))
                            {
                                $params[$varName] = array($params[$varName]);
                            }

                            $currentKey = array_search( $varValue, $params[$varName] );

                            if ($removeArrayItem)
                            {
                                //  remove if found
                                if ($currentKey !== false)
                                {
                                    unset ($params[$varName][$currentKey]);
                                }
                            }
                            elseif ($currentKey === false)
                            {
                                // should add, but only if value not already
                                $params[$varName][] = $varValue;
                            }
                        }
                        else
                        {
                            // if not an array, set value
                            $params[$varName] = $varValue;
                        }

                    }
                }
            }
            // done adding/removing $params

            // build query string
            if (!empty($params))
            {
                foreach ($params as $key => $value)
                {
                    // if param is an array, overwrite its value with a combined string
                    if (is_array($params[$key]))
                    {
                        if (empty($params[$key]))  // remove empty arrays
                        {
                            unset ( $params[$key] );
                            continue;
                        }
                        $arrayValues = array();
                        foreach ($params[$key] as $value)
                        {
                            $arrayValues[] = $key . '[]=' . $value;
                        }
                        $arrayValues = implode('&', $arrayValues);
                        $params[$key] = $arrayValues;
                    }
                    else
                    {
                        // for scalar values just join key & value
                        $params[$key] = $key . '=' . $value;
                    }
                }
                // join params with &
                $queryString = implode('&', $params);
            }

        }

        $url = orp($this->object_data['id']);

        if (!empty($this->paramsPath))
        {
            $url .= implode('/', $this->paramsPath) . '/';
        }
        if ($queryString)
        {
            $url .= '?' . $queryString;
        }

        return $url;
    }


    /* file import stuff */

	function isLocalImportAvailable()
	{
        if (is_null($this->localImportAvailable))
        {
            $this->loadLocalImportState();
        }
        return $this->localImportAvailable;
	}

	function loadLocalImportState()
	{
	    $importOn = false;
	    if (
	       (!empty($this->_config['files_import_path']))
	       &&
	       (is_dir($this->_config['files_import_path']))
	       &&
	       (is_writable($this->_config['files_import_path']))
        )
	    {

	        $importOn = true;
	        $this->localImportDir = realpath($this->_config['files_import_path']);
	    }
	    $this->localImportAvailable = $importOn;

        return true;
	}

	function getLocalImportDir()
	{
	    if (!$this->isLocalImportAvailable())
	    {
	        return null;
	    }

	    return $this->localImportDir;
	}

	function getLocalImportFileNames()
	{
	    if (!$this->isLocalImportAvailable())
	    {
	        return null;
	    }
	    if (is_null($this->localImportFileNames))
	    {
            $this->loadLocalImportFileNames();
	    }

	    return $this->localImportFileNames;
	}



	function loadLocalImportFileNames()
	{
        $importDir = $this->getLocalImportDir();
        if (!$importDir)
        {
            $this->localImportFileNames = null;
            return false;
        }

        $fileList = $this->getFileList( $importDir );
        $this->localImportFileNames = $fileList;
        return true;
	}

	function getFileList($dirName)
	{
	    $dir = opendir($dirName);
	    if (!$dir)
	    {
	        return null;
        }

        $files = array();
        while (false !== ($file = readdir($dir))) {

            if (
                (!$file)
                ||
                (!is_file($dirName . '/' . $file))
                ||
                (substr($file, 0 ,1) == '.')
            )
            {
                continue;
            }
            $files[] = $file;
        }

        closedir( $dir );
        natcasesort( $files );

        return $files;
	}

	function getLocalImportFileName( $fileName )
	{
	    $dir = $this->getLocalImportDir();
	    if (!$dir)
	    {
	        return null;
	    }

        $fullFileName = realpath($dir . '/'  . $fileName);

        if (substr($fullFileName, 0, strlen($dir)) != $dir)
        {
            return null;
        }

	    if (!file_exists($fullFileName) || (!is_readable($fullFileName)))
	    {
	        return null;
	    }

	    return $fullFileName;
	}

	function getFieldTemplate( $field )
	{
        $templatePath = $this->getTemplatePath('path');
	    $typeCustomTemplate = $templatePath . 'types/' . $field['type'] . '.tpl';
	    if (
            (file_exists($typeCustomTemplate))
            &&
            (is_readable($typeCustomTemplate))
            &&
            (is_file($typeCustomTemplate))
        )
	    {

            return $typeCustomTemplate;
	    }

	    // default
	    return 'types/' . $field['type'] . '.tpl';
	}

	function getTemplatePath($property = 'path')
	{
	    $templatesPath = leaf_get('objects_config', '22', 'templates_' . $property);

	    $templateName = $this->object_data['template'];
	    $templateDir = explode('/', $templateName); // split in path parts
	    array_pop($templateDir); // remove last part
	    $templateDir = implode('/', $templateDir); // rejoin

	    return $templatesPath . $templateDir . '/';
	}


	function loadObjectFieldPreviews( & $fields )
	{
		if(empty($fields))
		{
			return;
		}
	    $objectFieldTypes = array('objectlink', 'link');

	    $objectFields = array();
	    foreach ($fields as & $field)
	    {
	        if (in_array($field['type'], $objectFieldTypes))
	        {
                if (!isPositiveInt($field['value']))
                {
                    continue;
                }
                $objectFields[] = & $field;
	        }
	        elseif (
                    ($field['type'] == 'array')
                    &&
                    (!empty($field['value']))
            )
	        {
	            foreach ($field['value'] as & $arrayItem)
	            {
                    foreach ($arrayItem as & $arrayField)
                    {
                        if (
                            (!in_array($arrayField['type'], $objectFieldTypes))
                            ||
                            (!isPositiveInt($arrayField['value']))
                        )
                        {
                            continue;
                        }
                        $objectFields[] = & $arrayField;
                    }
                    unset($arrayField); // destroy reference
	            }
	            unset ($arrayItem); // destroy reference
                // debug ($field);
	        }
	    }

	    $objectFieldIds = array();
	    foreach ($objectFields as & $field)
	    {
	        if (!isPositiveInt($field['value']))
	        {
	            continue;
	        }
            $objectFieldIds[] = $field['value'];
	    }

	    $objectFieldIds = array_unique( $objectFieldIds );
	    if (empty($objectFieldIds))
	    {
	        return;
	    }


	    $previewData = $this->getObjectFieldPreviewData( $objectFieldIds );

	    foreach ($objectFields as & $field)
	    {
            $id = $field['value'];
            if (!empty($previewData[$id]))
            {
                $field['preview'] = $previewData[$id];
            }
	    }
	    //debug ($previewData);
	    return true;

	}

	public function isInAnyRelation()
	{
		return contentNodeRelation::isInAnyRelation( $this->object_data['id'] );
	}

	public function canBeInRelation()
	{
		$parent = _core_load_object( $this->object_data['parent_id'] );
		if( is_object( $parent ) && ($parent->isInAnyRelation() || $parent->object_data['template'] === 'language_root'))
		{
			return true;
		}
		return false;
	}

	public function set( $key, $value )
	{
		$this->object_data['data'][$key] = $value;
	}
	
	public function get( $key )
	{
		$value = null;
		
		if( isset( $this->object_data['data'][$key] ) )
		{
			$value = $this->object_data['data'][$key];
		}
		
		return $value;
	}
	
	public function has( $key )
	{
		$value = $this->get( $key );
		
		if( empty( $value ) )
		{
			return false;
		}
		
		if(
			is_array( $value )
			||
			strlen( trim( strip_tags( $value, '<img>' ) ) ) > 0
		)
		{
			return true;
		}
		
		return false;
	}
}
