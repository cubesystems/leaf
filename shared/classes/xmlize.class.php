<?
class xmlize{
	var $constants = array('false', 'true', 'null');
	var $mainXmlFile = NULL;
	
	const classPrefix = 'xmlTemplate_';
	const mainXMLName = '_main.xml';
	const dirSeparatorReplacement = '__';
	
	function xmlize(){
		$this->templates_path = leaf_get('objects_config', '22', 'templates_path');
		$this->main_xml_file = $this->templates_path . self::mainXMLName;

		// use old version then
		if(!file_exists($this->main_xml_file))
		{
			$this->main_xml_file = $this->templates_path . '.main.xml';
		}

		$this->main_xml_file_mtime = filemtime($this->main_xml_file);
		require_once(SHARED_PATH . 'classes/XMLParser.php');
	}

	public static function loadClass($className){
		$q = '
		SELECT
			`template_path`
		FROM
			`xml_templates_list`
		WHERE
			REPLACE(`template_path`,"/","' . self::dirSeparatorReplacement . '") = "' . substr($className, strlen(self::classPrefix)) . '"
		';
		$templateName = dbGetOne($q);
		$xmlize = new xmlize();
		$compiledClass = $xmlize->recompileTemplate($templateName);
		//get loaded template
		if(($template_cache = leaf_get('xml_templates', $templateName)) !== NULL)
		{

		}
		else
		{
			require_once($compiledClass);
		}
	}

	public static function getTemplateClassName( $template )
	{
		$templateName = self::classPrefix . str_replace( '/', self::dirSeparatorReplacement, $template );
		
		// remove restricted characters
		$restrictedCharacters = array('-');
		$templateName = str_replace($restrictedCharacters, '', $templateName);

		return $templateName;
	}

	function checkTemplateTable($template_data, $properties, $table_keys, $arrayName = null){
		//get table name
		$auto_table = array('yes', 'YES', 'true', 'TRUE', 1);
		if($arrayName)
		{
			$tableNameTag = $properties['fields'][$arrayName]['table'];
		}
		else
		{
			$tableNameTag = $template_data['table'];
		}

		if(in_array($tableNameTag, $auto_table))
		{
			$table['name'] = 'xo_' . $template_data['template_path'];
			if($arrayName)
			{
				$table['name'] .= '-' .  $arrayName;
			}
		}
		else
		{
			$table['name'] = $tableNameTag;
		}
		$table['name'] = str_replace(array('"', '.'), '', $table['name']);
		$table['name'] = str_replace('/', '-', $table['name']);
		//field type to db field mapping
		$type_default_fields = array(
			'text' => 'VARCHAR(255)',
			'textarea' => 'TEXT',
			'hidden' => 'TEXT',
			'image' => 'INT(11)',
			'fileobject' => 'INT(11)',
            'objectlink' => 'INT(11)',
			'link' => 'VARCHAR(255)',
			'array' => 'LONGTEXT',
			'richtext' => 'LONGTEXT',
			'checkbox' => 'TINYINT(1)',
			'date' => 'DATE',
			'datetime' => 'DATETIME',
			'google_map_point' => 'VARCHAR(255)',
			'default' => 'VARCHAR(255)',
		);
		//add object_id field
		$table['fields'][0] = array(
			'name' => 'object_id',
			'type' =>  'INT(11)',
			'default' => '0'
		);
		//make field definition
		if($arrayName)
		{
			//add object_id field
			$table['fields'][] = array(
				'name' => '_key',
				'type' =>  'INT(11)',
				'default' => '0'
			);
			$fields = $properties['fields'][$arrayName]['fields'];
		}
		else
		{
			$fields = $properties['fields'];
		}
		foreach($fields as $field)
		{
			if(empty($field['common']) && empty($field['table']))
			{
				if(!empty($field['properties']['column']))
				{
					$field['type'] = $field['properties']['column'];
				}
				elseif(!empty($type_default_fields[$field['type']]))
				{
					$field['type'] = $type_default_fields[$field['type']];
				}
				else
				{
					$field['type'] = $type_default_fields['default'];
				}
				$table['fields'][] = array(
					'name' => $field['name'],
					'type' => $field['type']
				);
			}
		}
		//make table keys array
		$table['keys'] = $table_keys;
		//add object_id key
		if($arrayName)
		{
			$table['keys']['object_id'] = array(
				'name' => 'object_id',
				'type' => 'index',
				'fields' => array(array('name' => 'object_id'))
			);
			$table['keys']['_key'] = array(
				'name' => '_key',
				'type' => 'index',
				'fields' => array(array('name' => '_key'))
			);
		}
		else
		{
			$table['keys']['PRIMARY'] = array(
				'name' => 'PRIMARY',
				'type' => 'primary',
				'fields' => array(array('name' => 'object_id'))
			);
		}
        
        $table['engine'] = 'InnoDB';
        
		//send table definition to processing
		dbTable($table);
		//return table name
		return $table['name'];
	}

	function checkArrayTable($template_data, $properties, $table_keys){
		//get table name
		$auto_table = array('yes', 'YES', 'true', 'TRUE', 1);
		if(in_array($template_data['table'], $auto_table))
		{
			$table['name'] = 'xo_' . $template_data['template_path'];
		}
		else
		{
			$table['name'] = $template_data['table'];
		}
		$table['name'] = str_replace(array('"', '.'), '', $table['name']);
		$table['name'] = str_replace('/', '-', $table['name']);
		//field type to db field mapping
		$type_default_fields = array(
			'text' => 'VARCHAR(255)',
			'textarea' => 'TEXT',
			'hidden' => 'TEXT',
			'image' => 'INT(11)',
			'fileobject' => 'INT(11)',
			'objectlink' => 'INT(11)',
			'array' => 'LONGTEXT',
			'richtext' => 'LONGTEXT',
			'checkbox' => 'TINYINT(1)',
			'date' => 'DATE',
			'datetime' => 'DATETIME',
			'default' => 'VARCHAR(255)',
		);
		//add object_id field
		$table['fields'][0] = array(
			'name' => 'object_id',
			'type' =>  'INT(11)',
			'default' => '0'
		);
		//make field definition

		foreach($properties['fields'] as $field)
		{
			if(empty($field['common']))
			{
				if(!empty($field['column']))
				{
					$field['type'] = $field['column'];
				}
				elseif(!empty($type_default_fields[$field['type']]))
				{
					$field['type'] = $type_default_fields[$field['type']];
				}
				else
				{
					$field['type'] = $type_default_fields['default'];
				}
				$table['fields'][] = array(
					'name' => $field['name'],
					'type' => $field['type']
				);
			}
		}
		//make table keys array
		$table['keys'] = $table_keys;
		//add object_id key
		$table['keys']['PRIMARY'] = array(
			'name' => 'PRIMARY',
			'type' => 'primary',
			'fields' => array(array('name' => 'object_id'))
		);
		//send table definition to processing
		dbTable($table);
		//return table name
		return $table['name'];
	}

	function parseField($field_data){
		$mainFields = array('type', 'name', 'table');
		foreach($field_data['@'] as $name => $value)
		{
			if(in_array($name, $mainFields))
			{
				$field[$name] = $value;
			}
			else
			{
				$field['properties'][$name] = $value;
			}
		}

        
		//construct properties
		if(isset($field_data['*']['property']))
		{
			$max = sizeof($field_data['*']['property']);
			for($i = 0; $i < $max; $i++)
			{
				$property = $field_data['#'][$field_data['*']['property'][$i]];

                if (!$this->elementAppliesToCurrentSite($property))
                {
                    continue; // ignore property
                }   
                
                
				//name - options
				if($property['@']['name'] == 'options')
				{
					if(isset($property['#']))
					{
						foreach($property['#'] as $option)
						{
                            if (!$this->elementAppliesToCurrentSite($option))
                            {
                                continue; // ignore option
                            }   
                            
							$field['properties'][$property['@']['name']][$option['@']['value']] = $option['@']['name'];
						}
					}
				}
				else
				{
					//parse true/false
					if($property['@']['value'] == 'true')
					{
						$property['@']['value'] = true;
					}
					if($property['@']['value'] == 'false')
					{
						$property['@']['value'] = false;
					}
					if ($property['@']['name'] == 'visible')
					{
						$property['@']['value'] = $property['@']['value'] ? 1 : 0;
					}

					$field['properties'][$property['@']['name']] = $property['@']['value'];
				}
			}
		}

		//construct rules
		if(isset($field_data['*']['rules']))
		{
			$rules = $field_data['#'][$field_data['*']['rules'][0]];
            
			foreach($rules['#'] as $rule)
			{
                if (!$this->elementAppliesToCurrentSite($rule))
                {
                    continue; // ignore common field rule
                }                   
                
				$field['rules'][] = array(
					'type' => $rule['tag'],
					'@' => $rule['@'],
				);
			}
		}
		//parse array type fields
		if($field['type'] == 'array')
		{
			if(isset($field_data['*']['common_field']))
			{
				$array_name = 'common_field';
			}
			elseif(isset($field_data['*']['field']))
			{
				$array_name = 'field';
			}
			else
			{
				continue;
			}
			$max = sizeof($field_data['*'][$array_name]);
			for($i = 0; $i < $max; $i++)
			{
				$array_field = $this->parseField($field_data['#'][$field_data['*'][$array_name][$i]]);
				$field['fields'][$array_field['name']] = $array_field;
			}
		}
		return $field;
	}

	function getCommonFields($template){

		$common_fields = array();
		if($this->loadMainXmlFile())
		{
			$tree = $this->mainXmlFile;
			//parse common fields
			if(isset($tree['#'][0]['*']['common_field']))
			{

				foreach($tree['#'][0]['*']['common_field'] as $common_field_id)
				{
					$add_common_field = true;
					$field = $tree['#'][0]['#'][$common_field_id];

                    if (!$this->elementAppliesToCurrentSite( $field ))
                    {
                        continue; // ignore common field
                    }                    

					//get all affected_templates rules
					if(isset($field['*']['affected_templates']))
					{
						$affected = $field['#'][$field['*']['affected_templates'][0]];

                        
						foreach($affected['#'] as $affected_item)
						{
                            if (!$this->elementAppliesToCurrentSite( $affected_item ))
                            {
                                continue; // ignore common field affected rule
                            } 
                            
							if(
								$affected_item['@']['name'] == '*'
								||
								$affected_item['@']['name'] == $template
							)
							{
								$add_common_field = $affected_item['tag'] == 'include' ? true : false;
							}
						}
					}
					if($add_common_field)
					{
						$common_fields[$field['@']['name']] = $this->parseField($field);
						$common_fields[$field['@']['name']]['common'] = true;
					}
				}
			}
		}
		//return common fields
		return $common_fields;
	}

	function loadMainXmlFile(){
		if($this->mainXmlFile === NULL && is_file($this->main_xml_file))
		{
			$parser = new XMLParser($this->main_xml_file, 'file', 0, 1);
			$this->mainXmlFile = $parser->getTree();
			return true;
		}
		elseif($this->mainXmlFile)
		{
			return true;
		}
		else
		{
			return NULL;
		}
	}

	function parseTemplateRules(){
		//get all templates
		$q_templates = '
		SELECT
			`template_path` `value`
		FROM
			`' . DB_PREFIX . 'xml_templates_list` `tl`
		';
		$rules_objects = dbGetAll($q_templates, false, 'value');
		$rules_objects[] = 'file';
		$max = sizeof($rules_objects);
		//make template access array
		for($i = 0; $i < $max; $i++)
		{
			for($a = 0; $a < $max; $a++)
			{
				$obj_registry[$rules_objects[$i]][$rules_objects[$a]] = true;
			}
		}
		//parse template rules
		if(
			$this->loadMainXmlFile() &&
			isset($this->mainXmlFile['#'][0]['*']['rules']) &&
			!empty($this->mainXmlFile['#'][0]['#'][$this->mainXmlFile['#'][0]['*']['rules'][0]]['#'])
		)
		{
			$rules = $this->mainXmlFile['#'][0]['#'][$this->mainXmlFile['#'][0]['*']['rules'][0]];

			//parse each object
			foreach($rules['#'] as $rules_obj)
			{
                if (!$this->elementAppliesToCurrentSite( $rules_obj ))
                {
                    continue; // ignore rules object
                }
                
				if($rules_obj['@']['name'] == 'file')
				{
					$rules_obj['@']['name'] = 'file';
				}
				if
				(
					($rules_obj['@']['name'] != '*' && !isset($obj_registry[$rules_obj['@']['name']]))
					||
					!isset($rules_obj['#'])
				)
				{
					continue;
				}
			//parse each rule
				foreach($rules_obj['#'] as $rules_entry)
				{
                    if (!$this->elementAppliesToCurrentSite( $rules_entry ))
                    {
                        continue; // ignore rule
                    }                    

                    
					if($rules_entry['@']['name'] == 'file')
					{
						$rules_entry['@']['name'] = 'file';
					}
					if($rules_entry['@']['name'] != '*' && !isset($obj_registry[$rules_entry['@']['name']]))
					{
						continue;
					}
				//parse access
					if($rules_entry['tag'] == 'child_allow' || $rules_entry['tag'] == 'parent_allow')
					{
						$access = 1;
					}
					else
					{
						$access = 0;
					}
				//parse type
					if($rules_entry['tag'] == 'child_allow' || $rules_entry['tag'] == 'child_deny')
					{
						//parse child rules
						$type = 1;
					}
					else
					{
						//parse parent rules
						$type = 0;
					}
				//put data in registry
					for($i = 0; $i < $max; $i++)
					{
						if($rules_obj['@']['name'] == '*' || $rules_obj['@']['name'] == $rules_objects[$i])
						{
							for($a = 0; $a < $max; $a++)
							{
								if($rules_entry['@']['name'] == '*' || $rules_entry['@']['name'] == $rules_objects[$a])
								{
									$obj_registry[$type ? $rules_objects[$i] : $rules_objects[$a]][!$type ? $rules_objects[$i] : $rules_objects[$a]] = array(
									'access' => $access,
									'max' => (isset($rules_entry['@']['max']) ? $rules_entry['@']['max'] : NULL)
									);
								}
								if($rules_entry['@']['name'] == $rules_objects[$a])
								{
									break;
								}
							}
							if($rules_obj['@']['name'] == $rules_objects[$i])
							{
								break;
							}
						}
					}
				}
			}
		}
		//update rules registry database
		//delete old registry
		$q = '
		DELETE
		FROM
			`' . DB_PREFIX . 'object_rules`
		';
		dbQuery($q);
		//insert new registry
		$rules_insert = array();
		for($i = 0; $i < $max; $i++)
		{
			for($a = 0; $a < $max; $a++)
			{
				if($obj_registry[$rules_objects[$i]][$rules_objects[$a]]['access'])
				{
					$rules_insert[] = array(
						'object' => $rules_objects[$i],
						'child' => $rules_objects[$a],
						'max' => $obj_registry[$rules_objects[$i]][$rules_objects[$a]]['max'],
					);
				}
			}
		}
		if(!empty($rules_insert))
		{
			dbInsert(DB_PREFIX . 'object_rules', $rules_insert);
		}
	}

	function getObject($object){
		$q = '
		SELECT
			`alias`
		FROM
			`xml_templates_list`
		WHERE
			`template_path` = "' . dbSE($object['template']) . '"
		';
		$actualTemplate = null;
		if($aliasName = dbGetOne($q))
		{
			$templateName = $actualTemplate = $aliasName;
		}
		else
		{
			$templateName = $object['template'];
		}

		//try to recompile template
		$compiled_class = $this->recompileTemplate($templateName);
		$class_name = self::getTemplateClassName( $templateName );
		//get loaded template
		if(($template_cache = leaf_get('xml_templates', $object['template'])) !== NULL)
		{
			$obj = new $class_name($object);
		}
		else
		{
			require_once($compiled_class);
			leaf_set(array('xml_templates', $object['template']), true);
			$obj = new $class_name($object);
		}

		$obj->actualTemplate = $actualTemplate;
		return $obj;
	}
    
    public function getTemplateKeys( $template_data )
    {
        $keys = array();
        
		if(isset($template_data['*']['keys']))
		{
			foreach($template_data['*']['keys'] as $keys_id)
			{
				if(isset($template_data['#'][$keys_id]['*']['key']))
				{
					foreach($template_data['#'][$keys_id]['*']['key'] as $key_id)
					{
						$key = $template_data['#'][$keys_id]['#'][$key_id]['@'];
						if(!empty($template_data['#'][$keys_id]['#'][$key_id]['*']['column']))
						{
							foreach($template_data['#'][$keys_id]['#'][$key_id]['*']['column'] as $col_id)
							{
								$key['fields'][] = $template_data['#'][$keys_id]['#'][$key_id]['#'][$col_id]['@'];
							}
						}
						//add default name
						if(empty($key['type']))
						{
							$key['type'] = 'index';
						}
						if(empty($key['name']) && !empty($key['column']))
						{
							$key['name'] = $key['column'];
							$key_col['name'] = $key['column'];
							if(!empty($key['size']))
							{
								$key_col['size'] = $key['size'];
							}
							$key['fields'][] = $key_col;
							unset($key['column']);
						}
						$keys[$key['name']] = $key;
					}
				}
			}
		}
        
        return $keys;
    }
    
	function recompileTemplate($template){
		$xml_file = $this->templates_path . $template  . '.xml';
		$php_file = $this->templates_path . $template . '.php';
		$class_name = self::getTemplateClassName( $template );
		$compiled_class = CACHE_PATH . $class_name . '.php';
		$table_keys = array();
		$cache_mtime = @filemtime($compiled_class);
		$latest = max(array(@filemtime($xml_file), @filemtime($php_file), $this->main_xml_file_mtime));
		//load class
		if($cache_mtime > $latest)
		{
			return $compiled_class;
		}
		//re-compile class
		$properties['fields'] = $this->getCommonFields($template);

		$parser = new XMLParser($xml_file, 'file', 0, 1);
		$template_data = $parser->getTree();
		$template_data  = $template_data['#'][0];
        
		//get all table definition keys
        $table_keys = $this->getTemplateKeys( $template_data );
		
		//get all fields
		if(isset($template_data['*']['field']))
		{
			foreach($template_data['*']['field'] as $field_id)
			{
				$properties['fields'][$template_data['#'][$field_id]['@']['name']] = $this->parseField($template_data['#'][$field_id]);
                
                if(
                    isset( $template_data['#'][$field_id]['@']['type'] )
                    &&
                    $template_data['#'][$field_id]['@']['type'] == 'array'
                )
                {
                    $array_keys = $this->getTemplateKeys( $template_data['#'][$field_id] );
                    $properties['fields'][$template_data['#'][$field_id]['@']['name']]['keys'] = $array_keys;
                }
			}
		}
        $this->markLinkedFields($properties['fields']);

		//get all assigns
		if(isset($template_data['*']['assign']))
		{
			foreach($template_data['*']['assign'] as $field_id)
			{
				$dirname = dirname($template . '.xml');
				if($dirname != '.' && empty($template_data['#'][$field_id]['@']['absolute_path']))
				{
					$template_data['#'][$field_id]['@']['path'] = $dirname . '/' . $template_data['#'][$field_id]['@']['path'];
				}
				$properties['assigns'][] = $template_data['#'][$field_id]['@'];
			}
		}

		// get default_position
		if (isset($template_data['@']['defaultPosition']))
		{
            $properties['defaultPosition'] = (int) $template_data['@']['defaultPosition'];
		}

		//check for template icon
		if (is_file($this->templates_path . $template  . '.png'))
		{
			$template_values['icon_path'] = $template  . '.png';
		}
		else if(is_file($this->templates_path . $template  . '.gif'))
		{
			$template_values['icon_path'] =  $template  . '.gif';
		}
		else
		{
			$template_values['icon_path'] = null;
		}
		//add template default css & js
		if (is_file($this->templates_path . $template  . '.css'))
		{
			$properties['assigns'][] = array(
				'path' => $template  . '.css',
				'type' => 'css',
			);
		}
		if (is_file($this->templates_path . $template  . '.js'))
		{
			$properties['assigns'][] = array(
				'path' => $template  . '.js',
				'type' => 'js',
			);
		}
		if(!isset($template_data['@']['name']))
		{
			$template_data['@']['name'] = $template;
		}

		//template fields
		$template_values['template_path'] = $template;
		$template_values['name'] = $template_data['@']['name'];
		if(!empty($properties['fields']) && is_array($properties['fields']))
		{
			$template_values['fields_index'] =  serialize($properties['fields']);
		}
		//make class data add string
		$class_data_add = '';
		//re-check template table
		if(isset($template_data['@']['table']))
		{
			$template_values['table'] = $template_data['@']['table'];
			$generated_table_name = $this->checkTemplateTable($template_values, $properties, $table_keys);
			$class_data_add .= 'var $table = \'' . $generated_table_name  . '\';';
		}
        
		//re-check template array tables
		$arrayTables = array();
		if(!empty($properties['fields']) && is_array($properties['fields']))
		{
			foreach($properties['fields'] as $field)
			{
                if($field['type'] == 'array' && isset($field['table']))
				{
                    $array_keys = get( $field, 'keys' );
                    $arrayTables[$field['name']] = $this->checkTemplateTable($template_values, $properties, $array_keys, $field['name']);
				}
			}
			if(!empty($arrayTables))
			{
				$class_data_add .= 'var $arrayTables = ' . $this->arraytostring($arrayTables) . ';';
			}
		}
        
		// custom template support via xml attribute
		if (isset($template_data['@']['template']))
		{
		    // :WARNING: possible security hole
		    $saferTemplate = preg_replace('/[^A-Za-z0-9_\-\.\/]/', '', $template_data['@']['template']);
		    if ($saferTemplate)
		    {
                $class_data_add .= 'var $_template = "' . $saferTemplate . '";';
		    }
	    }

		// automatic field labels as aliases via xml label_context attribute
		if (isset($template_data['@']['label_context']))
		{
		    // :WARNING: possible security hole
		    $saferContext = preg_replace('/[^A-Za-z0-9_\-\.\/]/', '', $template_data['@']['label_context']);
		    if ($saferContext)
		    {
                $class_data_add .= 'var $_labelContext = "' . $saferContext . '";';
		    }

            if (isset($template_data['@']['label_language']))
            {
                $saferLanguage = (int) $template_data['@']['label_language'];
                $class_data_add .=  'var $_labelLanguage = "' . $saferLanguage . '";';
            }
	    }
		
			
		if (!empty($template_data['@']['lastModifiedMethod']))
		{
			$lastModifiedMethods = array
			(
				'descendants' => 'getLastModifiedDescendant'
			);
			$methodName = $template_data['@']['lastModifiedMethod'];
			if(isset($lastModifiedMethods[$methodName]))
			{
				$methodName = $lastModifiedMethods[$methodName];
			}
			$template_values['lastModifiedMethod'] = $methodName;
		}
		
		//update xml_templates_list table
		if(!empty($generated_table_name))
		{
			$template_values['table'] =  $generated_table_name;
		}
		dbReplace(DB_PREFIX . 'xml_templates_list', $template_values);
		// update aliases
		if(isset($template_data['*']['aliases']))
		{
			//remove existing aliases
			dbDelete(DB_PREFIX . 'xml_templates_list', array('alias' => $template_values['template_path']));
			// add new aliases
			$template_values['alias'] = $template_values['template_path'];
			foreach($template_data['*']['aliases'] as $keys_id)
			{
				$aliases = $template_data['#'][$keys_id];
				if(isset($aliases['*']['template']))
				{
					foreach($aliases['*']['template'] as $template_key)
					{
						$template_values['template_path'] = $aliases['#'][$template_key]['@']['code'];
						$template_values['name'] = $aliases['#'][$template_key]['@']['name'];
						dbReplace(DB_PREFIX . 'xml_templates_list', $template_values);
					}
				}
			}
		}
		//add init method
		$class_data_add .= 'function _init_properties(){$this->_properties = ' . $this->arraytostring($properties) . ';}';
		// define class name
		if (!empty($template_data['@']['extends']))
		{
		    $parentClass = self::getTemplateClassName( $template_data['@']['extends'] );
		}
		else
		{
            $parentClass = 'xml_template';
		}

		$class_data = '<? class ' . $class_name . ' extends ' . $parentClass . ' { ' . $class_data_add;
		//check class
		if(is_file($php_file))
		{
			// read class
			$existinClassData = file_get_contents($php_file);
			// trim data
			$existinClassData = trim($existinClassData);
			// get start position
			$existinClassData = substr(strstr($existinClassData, '{'), 1);
			// merge with other class data
			$class_data  .=  $existinClassData;
		}
		else
		{
			// add php end tag
			$class_data = $class_data . '} ?>';
		}
		//write class file
		file_put_contents($compiled_class, $class_data);
		//refresh template rules
		$this->parseTemplateRules();
		//return template class
		return $compiled_class;
	}

	function arraytostring($array){
		$text = "array(";
		$count = count($array);
		$x = 0;
		foreach ($array as $key=>$value)
		{
			$x++;
			if (is_array($value))
			{
				$text .= "'" . $key . "'" . "=>" . $this->arraytostring($value);
			}
			else
			{
				$text .=  "'" . $key . "'=>'" . $value . "'";
			}
			if ($count != $x)
			{
				$text .= ",";
			}
		}
		$text .= ")";
		return $text;
	}

	function markLinkedFields(& $fields)
	{
	    foreach ($fields as $fieldName => $field)
	    {
            if ($field['type'] == 'fileobject')
            {
                if (
                    // if auto source not set
                    (empty($field['properties']['auto_source']))
                    ||
                    // or auto source does not exist
                    (empty($fields[$field['properties']['auto_source']]))
                )
                {
                    // skip this
                    continue;
                }
        	    $autoSourceKey = $field['properties']['auto_source'];

                $sourceField = & $fields[$autoSourceKey];
                if (empty($sourceField['linked_fields']))
                {
                    $sourceField['linked_fields'] = array();
                }
                $sourceField['linked_fields'][] = $fieldName;
                unset ($sourceField); // destroy reference
            }
            elseif (($field['type'] == 'array') && (!empty($field['fields'])))
            {
                $this->markLinkedFields( $fields[$fieldName]['fields'] );
            }
	    }
        return true;
	}
    
    protected function elementAppliesToCurrentSite( $element )
    {
        // validates site attribute for xml nodes
        if (empty($element['@']['site']))
        {
            return true;
        }
        
        if (!defined('SITE'))
        {
            trigger_error('SITE constant not defined. (Required by site attribute in xml definition).', E_USER_WARNING);
            return false;
        }
        
        $sites = explode(',', $element['@']['site']);
        
        if (
            (!in_array(SITE, $sites))
            ||
            (in_array('!' . SITE, $sites))
        )
        {
            return false;
        }

        return true;
    }
                    
    
}
?>
