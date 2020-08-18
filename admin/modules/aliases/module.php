<?
class aliases extends leaf_module{

	public $actions = array('save', 'delete', 'install', 'import');
	public $output_actions = array('edit', 'export', 'updateAliases','searchAliases');

    const LEAF_URL = 'http://leaf.cube.lv/aliases-export';

	function aliases()
	{
		parent::leaf_module();

        require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');

		_core_add_js( SHARED_WWW . 'js/RequestUrl.class.js' );

		$this->contextDelimiter = chr(0);
		if(empty($_GET['do']) || $_GET['do'] != 'install')
		{
            $qp = leafLanguage::getQueryParts();
            $qp['select'] = 't.id, t.code';

			$this->options['languages'] = dbGetAll($qp, 'id', 'code');

			$q = '
			SELECT
				g.id,
				g.name,
				IF(INSTR(t.name, CHAR(0)),SUBSTRING_INDEX(t.name, CHAR(0), 1),"") `context`
			FROM
				`translations_groups` AS g
				LEFT JOIN
				`translations` as t ON g.id = t.group_id
			WHERE
			    ' . $this->getGroupExclusionClause() . '
            GROUP BY
                g.id
			ORDER BY
				g.name
			';

            $groups = dbGetAll($q, 'id');
            foreach ($groups as & $group)
            {
                $group['category'] = $this->getContextCategory($group['context']);
            }

			$this->options['groups'] = $groups;

			_core_add_css($this->module_www . 'style.css');

			$this->options['filter']     = get($_GET, 'filter');

			// if "categories" is set in GET (even if empty), use value from GET
			// if "categories" not set in GET, use cookies
			// if cookie is not set, assume all

            $categories = $this->getAvailableCategories();
			$visibleCategories = null;
			if (isset($_GET['categories']))
			{
			    $visibleCategories = $this->getCategoriesFromString($_GET['categories']);
			}
			elseif (isset($_COOKIE['visibleAliasGroupCategories']))
            {
                // not set in GET, use cookie
                $visibleCategories = $this->getCategoriesFromString($_COOKIE['visibleAliasGroupCategories']);
			}
			if (!is_array($visibleCategories))
			{
			    // not set in GET nor COOKIE (or bad value)
			    // show all
                $visibleCategories = $this->getAvailableCategories();
			}

			$this->options['categories'] = $categories;
			$this->options['visibleCategories'] = $visibleCategories;

			// visibleAliasGroupCategories


			if (isset($_GET['incomplete']))
			{
                $incomplete = $_GET['incomplete'];
			}
			elseif (isset($_COOKIE['incompleteAliases']))
			{
			    $incomplete = $_COOKIE['incompleteAliases'];
			}
			else
			{
			    $incomplete = false;
			}



			$this->options['incomplete'] = $incomplete;



			$incompleteLanguages = array();
			if (!$incomplete || $incomplete == 'none')
			{
			    // filter disabled
			    // use cookies for filling out checkboxes

                $incompleteLanguages = $this->options['languages'];

                if (!empty($_COOKIE))
    			{
    			    foreach ($_COOKIE as $name => $value)
    			    {
                        if (!preg_match('/^ignoreIncomleteLanguageId-(.+)$/', $name))
                        {
                            continue;
                        }
                        $parts = explode('-', $name);
                        unset($parts[0]);
                        $languageCode = implode('-', $parts);

                        $languageId = array_search($languageCode, $incompleteLanguages);

                        unset($incompleteLanguages[$languageId]);
    			    }
                }

			}
			elseif ($incomplete == 'any')
            {
                $incompleteLanguages = $this->options['languages'];
            }
            else // incomplete contains language codes
            {
                $incompleteLanguages = explode('|', $incomplete);
            }

            // debug ($_COOKIE, 0);
            // debug ($incompleteLanguages, 0);
            $this->options['incompleteLanguages'] = $incompleteLanguages;


            //$this->options['googleTranslate'] = leaf_get('properties', 'googleTranslate');
            $this->options['googleTranslate'] = false;

			/*
			$visibleLanguages = $this->options['languages'];

			if (!empty($_COOKIE))
			{
			    foreach ($_COOKIE as $name => $value)
			    {
                    if (!preg_match('/^hideLanguageId-\d+$/', $name))
                    {
                        continue;
                    }
                    $parts = explode('-', $name);
                    $languageId = $parts[1];
                    unset($visibleLanguages[$languageId]);
			    }
            }
            $this->options['visibleLanguages'] = $visibleLanguages;

            */
            $languageIds = array_keys($this->options['languages']);
            if (empty($languageIds))
            {
                $languageIds = array(-1);
            }

			$incompleteQ =
			'
                SELECT
                    d.language_id, t.group_id
                FROM
                    translations_data AS d
                    LEFT JOIN
                    translations AS t
                    ON
                        d.translation_id = t.id
                WHERE
                    d.language_id IN (' . implode(', ', $languageIds ) . ')
			        AND
                    d.translation = ""
                GROUP BY
                    group_id, language_id
			';

			$incompleteGroupRows = dbgetall($incompleteQ);
			$incompleteGroups = array();
			foreach ($incompleteGroupRows as $row)
			{
                $languageCode = get($this->options['languages'], $row['language_id'] );
                if (!$languageCode)
                {
                    continue;
                }

			    $incompleteGroups[$row['group_id']][] = $languageCode;
			}
			$this->options['incompleteGroups'] = $incompleteGroups;



		}

        _core_add_js( SHARED_WWW . '3rdpart/jquery/jquery-core.js' );
        _core_add_js(SHARED_WWW . '3rdpart/jquery/plugins/cookie/jquery.cookie.min.js');
        _core_add_js( $this->module_www . 'module.js' );

        _core_add_css( WWW . 'styles/panelLayout.css' );
        _core_add_css( WWW . 'styles/leafTable.css' );
        _core_add_js( WWW . 'js/panelLayout.ie7.js', 'lte IE 7' );
        _core_add_css( $this->module_www . 'style.css' );
	}

    public function updateAliases()
    {
        $assign = array
        (
            'newGroups' => 0,
            'newTranslations' => 0,
            'updatedTranslations' => 0
        );

        $postData = array
        (
            'export' => 1 // _POST must not be empty
        );

        /*
        $contexts = array('pages_admin', 'admin:contentObjects', 'admin:moduleNames', 'admin');

        $dir = PATH . 'modules/';
		if ($handle = opendir($dir))
		{
			while (false !== ($dir_name = readdir($handle)))
			{
				if (is_dir($dir . $dir_name) && substr($dir_name, 0, 1) != '.')
				{
					$contexts[] = 'admin:' . $dir_name;
				}
			}
		}
		$postData['contexts'] = $contexts;

		*/

		$bot = new leafBot();
		$response = $bot->post( self::LEAF_URL, $postData );

		if (!$response->isJsonOk())
		{
		    return $assign;
		}

		$data = $response->body;


        // get all languages
        $q = '
            SELECT
                `id`,
                `short`
            FROM
                `languages`
        ';
        $languages = dbGetAll($q, 'short', 'id');

        $existingGroups = alias_cache::getExistingContexts();

        //        debug ($existingGroups);

        /*
        // get current contexts
        $q =
        '
        SELECT
            IF(INSTR(t.name, CHAR(0)),SUBSTRING_INDEX(t.name, CHAR(0), 1),"") `context`,
            g.id
        FROM
            `translations` `t`
        LEFT JOIN
            `translations_groups` `g` ON g.id = t.group_id
        GROUP BY
            t.group_id
        ';
        $existingGroups = dbGetAll($q, 'context', 'id');
        */


        $fieldsCollection = array();
        foreach ($data as $group)
        {
            // grupa
            $context = $group['context'];

            if (isset($existingGroups[$context]))
            {
                $groupId = $existingGroups[$context];
            }
            else
            {
                $fields = array
                (
                    'name' => $group['name'],
                );
                $groupId = dbInsert('translations_groups', $fields);
                ++$assign['newGroups'];
            }

            // grupas tulkojumi
            foreach ($group['translations'] as $translation)
            {
                if (empty($context))
                {
                    $translationName = $translation['name'];
                }
                else
                {
                    $translationName = $context  . chr(0)  . $translation['name'];
                }

                $q = '
                SELECT
                    `id`
                FROM
                    `translations`
                WHERE
                    `name` = "' . dbse($translationName) . '"
                ';
                $translationId = dbGetOne($q);
                // jauns tulkojums
                if (!$translationId)
                {
                    $fields = array
                    (
                        'group_id' => $groupId,
                        'name'     => $translationName,
                        'type'     => $translation['type']
                    );
                    $translationId = dbInsert('translations', $fields);
                    ++$assign['newTranslations'];
                }
                else
                {
                    ++$assign['updatedTranslations'];
                }

                foreach ($languages as $languageName => $languageId)
                {
                    //aiztiekam tikai ja shaada valoda ir centaalajaa sisteemaa
                    if (
                        (isset($translation['languages'][$languageName]))
                        &&
                        (mb_strlen($translation['languages'][$languageName]) > 0)
                    )
                    {

                        $fields = array
                        (
                            'translation_id' => $translationId,
                            'language_id'    => $languageId,
                        );

                        // dzeesham esksisteejoshu
                        dbDelete('translations_data', '`translation_id` = "' . $translationId . '" AND language_id = "' . $languageId . '"');
                        $fields['translation'] = $translation['languages'][$languageName];
                        $fieldsCollection[] = $fields;
                    }
                }
            }
        }

        // liekam iekshaa valodu tulkojumus
        if (!empty($fieldsCollection))
        {
            dbInsert('translations_data', $fieldsCollection);
        }

        alias_cache::registerDbChanges();
        
        return $assign;
    }

    function searchAliases()
    {
        $assign = array
		(
			'aliases' 	   => array(),
			'searchString' => '',
			'limit' 	   => false,
		);

		$aliases = array();
        $searchString = NULL;

        if(!empty($_GET['filter']))
		{
            $searchString = self::getSearchPattern( $_GET['filter'] );

            $q = '
                (
                    SELECT
                        t.id, t.group_id, t.name, g.`name` as groupName, d.`translation`
                    FROM
                        `translations_groups` `g`, `translations` `t`
                    LEFT JOIN
                        `translations_data` `d` ON t.`id` = d.`translation_id`
                    WHERE
                        IF(LOCATE("' . $this->contextDelimiter . '", t.`name`), SUBSTR(t.`name`, LOCATE("' . $this->contextDelimiter . '", t.`name`)), t.name) LIKE "' . dbSE($searchString) . '"
                        AND
                        ' . $this->getGroupExclusionClause( true ) . '
                    GROUP BY
                        t.id
                )
                UNION
                (
                    SELECT
                        t.id, t.group_id, t.name, g.`name` as groupName, d.`translation`
                    FROM
                        `translations_data` `d` 
                    LEFT JOIN
                        `translations` `t` ON t.`id` = d.`translation_id`
                    LEFT JOIN 
                        `translations_groups` `g` ON g.id = t.group_id
                    WHERE
                        d.`translation` LIKE "' . dbSE($searchString) . '"
                        AND
                        ' . $this->getGroupExclusionClause( true ) . '
                    GROUP BY
                        t.id
                )
                ';

			$result = dbQuery( $q );

			$assign['count'] = $result->rowCount();

			$limit = false;
			if( !empty( $_GET['limit'] ) && isPositiveInt( $_GET['limit'] ) )
			{
				$limit = $assign['limit'] = $_GET['limit'];
			}

			$i = 0;
			while( $row = $result->fetch() )
			{
                if( $limit !== false && $i >= $limit )
				{
					break;
				}

				$tmp = explode($this->contextDelimiter, $row['name']);

                if (count($tmp) == 1)
                {
                    $aliases[$row['id']]['name'] = $row['name'];
                    $aliases[$row['id']]['context'] = '';
                }
                elseif (count($tmp) == 2)
                {
                    $aliases[$row['id']]['name'] = $tmp[1];
                    $aliases[$row['id']]['context'] = $tmp[0];
                }
                $aliases[$row['id']]['nameNormal'] = $aliases[$row['id']]['name'];
                $this->highlightString($aliases[$row['id']]['name'], $searchString);

                $aliases[$row['id']]['group_id'] = $row['group_id'];
                $aliases[$row['id']]['groupName'] = $row['groupName'];
                $aliases[$row['id']]['translation'] = $row['translation'];

				$aliases[$row['id']]['translationNormal'] = $aliases[$row['id']]['translation'];
                $this->highlightString($aliases[$row['id']]['translation'], $searchString);

				$i++;
			}
        }
		if( !empty( $_GET['ajax'] ) )
		{
			$template = 'aliasSearchResults';
		}
		else
		{
			$template = 'searchResults';
		}

		$assign['aliases']      = $aliases;
		$assign['searchString'] = $searchString;

        $content = $this->moduleTemplate( $template, $assign );
		if( !empty( $_GET['ajax'] ) )
		{
			die( $content );
		}
		return $content;
    }

    private function highlightString(&$haystack, $needle)
    {
        $searchPos = stripos($haystack, $needle);
        if($searchPos !== FALSE)
        {
            $stringParts = explode(strtolower($needle), strtolower($haystack));

            $returnstring = (empty($stringParts[0])?'':$stringParts[0]).
                            '<span class="highlight">' . $needle . '</span>' .
                            (empty($stringParts[1])?'':$stringParts[1]);

            $haystack = $returnstring;
        }

    }

    /*
  function doPostRequest($url, $data)
  {
     $params = array('http' => array(
                  'method' => 'POST',
                  'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                  'content' => $data,
               ));
     $ctx = stream_context_create($params);
     // ja kljuudas, raadam notices
     $fp = fopen($url, 'rb', false, $ctx);
     // ja kljuuas, spraagstam nost
     if (!$fp) {
        exit;
        throw new Exception("Problem with $url, $php_errormsg");
     }
     // ja kljuudas, raadam notices
     $response = stream_get_contents($fp);
     // ja kljuudas, spraagstam nost
     if ($response === false)
     {
        exit;
        throw new Exception("Problem reading data from $url, $php_errormsg");
     }
     return $response;
  }
  */


	function save()
	{
		$params = $_POST;

		//get update values
		$variables = array(
			array(
				'name' => 'name',
				'not_empty' => true
			),
			array(
				'name' => 'id',
				'type' => 'int'
			),
		);
		//start processing class
		$p = new processing;
		//update object information
		$p->setVariables($params);
		if (isset($_POST['getValidationXml']))
		{
			$p->getValidationXml($variables);
		}
		$values = $p->check_values($variables);
		if($values['id'])
		{
			$p->db_update_entry('translations_groups', $values, array('id' => $values['id']));
		}
		else
		{
			unset($values['id']);
			$values['id'] = $p->db_create_entry('translations_groups', $values, false, false, true, true);
		}
		$q = '
		SELECT
			`id`
		FROM
			`translations`
		WHERE
			`group_id` = ' . $values['id'] . '
		';
		$existing = dbGetAll($q);
		$saved = array();
		$contextPrefix = '';
		$params['context'] = trim ($params['context'] );
		if (strlen($params['context']) > 0)
		{
            $contextPrefix = $params['context'] . $this->contextDelimiter;
		}
		if(!empty($params['translations_name']))
		{
			foreach($params['translations_name'] as $index=>$value)
			{
				if($params['translations_id'][$index])
				{
					$q='
					REPLACE
					INTO
						translations(group_id,id,name,type)
					VALUES
					(
					"'.$values['id'].'",
					"'.dbSE($params['translations_id'][$index]).'",
					"'.dbSE($contextPrefix . $params['translations_name'][$index]).'",
					"'.dbSE($params['translations_type'][$index]).'"
					)
					';
					dbQuery($q);
					$id = $params['translations_id'][$index];
				}
				else
				{
					if(!is_numeric($params['translations_type'][$index]) || $params['translations_type'][$index] == '')
					{
						$params['translations_type'][$index] = 0;
					}
					$q='
					INSERT
					INTO
						translations(group_id,name,type)
					VALUES
					(
					"'.$values['id'].'",
					"'.dbSE($contextPrefix . $params['translations_name'][$index]).'",
					"'.dbSE($params['translations_type'][$index]).'"
					)
					';
					dbQuery($q);
					$id = dbGetOne('SELECT LAST_INSERT_ID()');
				}
				//delete old
				$q = '
				DELETE
				FROM
					translations_data
				WHERE
					translation_id="' . $id . '"
				';
				dbQuery($q);

				foreach($this->options['languages'] as $language_id=>$language_name)
				{
				    $machineValue = (empty($params['translations_lang_' . $language_id . '_machine'][$index])) ? 'NULL' : '"1"';
					$q='
					INSERT
					INTO
						translations_data
					(
					translation_id,
					language_id,
					translation,
					machineTranslated
					)
					VALUES
					(
					"'.$id.'",
					"'.$language_id.'",
					"'.dbSE($params['translations_lang_'.$language_id][$index]).'",' .
					$machineValue . '
					)
					';
					dbQuery($q);
				}
				$saved[] = $params['translations_id'][$index];
			}
		}

		foreach($existing as $tmp_object){
			if($tmp_object['id']!='' && !in_array($tmp_object['id'],$saved)){
				$q="DELETE FROM translations WHERE id='".dbSE($tmp_object['id'])."'";
				dbQuery($q);
				$q="DELETE FROM translations_data WHERE translation_id='".dbSE($tmp_object['id'])."'";
				dbQuery($q);
			}
		}
		$this->header_string .= '&do=edit&id=' . $values['id'];
        alias_cache::registerDbChanges();
	}

	function view(){
		return $this->moduleTemplate('view');
	}

	function edit()
	{
		if (
	       (!isset($_GET['id']))
        )
        {
	       return null;
	    }


		$id = intval($_GET['id']);
        _core_add_js(SHARED_WWW . 'classes/processing/validation_assigner.js');

        $translations = $this->getTranslations( $id );


        $expandedLangs = NULL;
        if(!empty($_COOKIE['aliasesOpenedLangs']))
        {
            if(is_array($_COOKIE['aliasesOpenedLangs']))
            {
                $expandedLangs = $_COOKIE['aliasesOpenedLangs'];
            }
            else
            {
                $expandedLangs = explode(',', $_COOKIE['aliasesOpenedLangs']);
            }

            //debug($_COOKIE['aliasesOpenedLangs']);
        }
        $assign = array
        (
            'id'            => $id,
            'translations'  => $translations,
            'expandedLangs' => $expandedLangs,
        );

        //debug($assign);

		return $this->moduleTemplate('edit', $assign);
	}

	function delete()
	{
	    if (
	       (empty($_POST['id']))
	       ||
	       (!$object_id = intval($_POST['id']))
        )
        {
	       return null;
	    }
		$q = 'DELETE FROM translations_groups WHERE id="'.$object_id.'"';
		dbQuery($q);
		$q = 'SELECT * FROM translations WHERE group_id="'.$object_id.'"';
		$result = dbQuery($q);
		while ($entry = $result->fetch())
		{
			$q = 'DELETE FROM translations WHERE id="'.$entry['id'].'"';
			dbQuery($q);
			$q = 'DELETE FROM translations_data WHERE translation_id="'.$entry['id'].'"';
			dbQuery($q);
		}
        alias_cache::registerDbChanges();
	}

	function getTranslations( $groupId )
	{
		$result = array
		(
            'id'            => $groupId,
            'translations'  => array(),
            'context'       => null,
            'languagesWithMachineTranslations' => array()
		);

		if (!isPositiveInt($groupId))
		{
		    return $result;
		}

		// load translation definitions
		$q = '
    		SELECT
    			*
    		FROM
    			`translations`
    		WHERE
    			`group_id` = ' . $groupId . '
            ORDER BY
                `name`
		';
		$translations = dbGetAll( $q, 'id');

		// set default context
		$context = null;

		// parse translation definitions, set context
		foreach ($translations as $key => $val)
		{
			$tmp = explode($this->contextDelimiter, $val['name']);
			if (count($tmp) == 1)
			{
				$translations[$key]['name'] = $val['name'];
			}
			elseif (count($tmp) == 2)
			{
				$translations[$key]['name'] = $tmp[1];
				$context = $tmp[0];
			}
		}

		// load translation texts
		$q = '
    		SELECT
    			 translations_data.*
    		FROM
    			`translations_data`,
    			`translations`
    		WHERE
    			translations_data.translation_id = translations.id AND
    			translations.group_id = ' . $groupId . '
    		ORDER BY
    			translations_data.language_id
		';

		$r = dbQuery($q);
		$languagesWithMachineTranslations = array();
		while($item = $r->fetch())
		{
		    $translationId = $item['translation_id'];
		    $languageId = $item['language_id'];
			$translations[$translationId]['languages'][$languageId] = $item['translation'];
			$translations[$translationId]['machine'][$languageId] = empty($item['machineTranslated']) ? 0 : 1;
			if (!empty($item['machineTranslated']))
			{
			    $languagesWithMachineTranslations[$languageId] = true;
			}
		}
		$languagesWithMachineTranslations = array_keys($languagesWithMachineTranslations);

		$result['translations'] = $translations;
		$result['context'] = $context;
		$result['languagesWithMachineTranslations'] = $languagesWithMachineTranslations;

        return $result;
	}

	function export()
	{
      $id = get( $_GET, 'id', null );
      
      if( !$id || !isPositiveInt( $id ) )
      {
          return null;
      }
      
      $translations = $this->getTranslations( $id );
      
      if( !$translations )
      {
          return null;
      }
      
      $phpExcelPath = SHARED_PATH . '3rdpart/PHPExcel/PHPExcel.php';
      
      if( file_exists( $phpExcelPath ) )
      {
        require_once( $phpExcelPath );
        
        
    		$context          = stringtolatin( $translations['context'], true, true );
        $fileName         = 'translations-' . $context . '-' . date('Y-m-d-H-i-s');
        $activeLanguages  = leafLanguage::getLanguages();


    		$excel = new PHPExcel();
        $excel->setActiveSheetIndex(0);

        $column     = 'A';
        $row        = 1;
        $columns    = array();
        $columns[ $column ] = array( 
            'type'  => 'name',
            'title' => 'name',
            'value' => 'name',
        );
      
        $column++;
      
        foreach( $activeLanguages as $language )
        {
            $columns[ $column ] = array( 
                'type'  => 'language',
                'title' => $language->code,
                'value' => $language->id,
            );
            $column++;
        }
            

        // Set column titles
        foreach( $columns as $key => $value )
        {
            $excel->getActiveSheet()->setCellValue( $key . $row, $value['title'] );
            $excel->getActiveSheet()->getColumnDimension( $key )->setAutoSize( true );
        }
        $row++;

        // Write data
        foreach( $translations['translations'] as $translation )
        {
            foreach( $columns as $key => $val )
            {
                $value = null;

                switch( $val['type'] )
                {
                    case "name":
                      $value = $translation['name'];
                      break;
                    case "language":
                      $value = isset( $translation['languages'][ $val['value'] ] ) ? $translation['languages'][ $val['value'] ] : null;
                      break;
                }
                $excel->getActiveSheet()->setCellValue( $key . $row, $value );
            }
            $row++;
        }

    		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
    		header( 'Content-Disposition: attachment;filename="' . $fileName . '.xls"' );
    		header( 'Cache-Control: max-age=0' );

    		$writer = PHPExcel_IOFactory::createWriter( $excel, 'Excel5' );
    		$writer->save( 'php://output' );
      }
      else
      {
        $context = stringtolatin( $translations['context'], true, true );
        $fileName = 'translations-' . $context . '-' . date('Y-m-d-H-i-s') . '.txt';
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-type: text/plain;charset=utf-8');
        $text = serialize($translations);
        echo $text;
      }
      die();
	}

	function import()
	{     
        $params = $_POST;
        $data = array();
      
        if ( empty( $params['id'] ) )
        {
           $params['id'] = $_GET['id'];
        }

        $groupId            = (int) $params['id'];
        $translationsByCode = $this->getExistingTranslations( $groupId );

        // Languages array is used to convert language code to ID
        $languages        = array(); 
        $activeLanguages  = leafLanguage::getLanguages();
        
        foreach( $activeLanguages as $language )
        {
            $languages[ $language->code ] = $language->id;
        }

        if( isset( $_POST['translations_name'] ) )
        {
          $formTranslations = array();
          
          foreach( $_POST['translations_name'] as $key => $name )
          {
            $formTranslations[ $name ] = array(
              'id'        => isset( $_POST['translations_id'][$key] ) ? $_POST['translations_id'][$key] : null,
              'name'      => $name,
              'group_id'  => $groupId,
              'type'      => isset( $_POST['translations_type'][$key] ) ? $_POST['translations_type'][$key] : null,
              'languages' => array(),
              'machine'   => array(),
            );
            
            foreach( $languages as $languageId )
            {
              if
              ( 
                  isset( $_POST['translations_lang_' . $languageId ] ) 
                  && 
                  array_key_exists( $key, $_POST['translations_lang_' . $languageId ] ) 
              )
              {
                  $formTranslations[ $name ]['languages'][ $languageId ] = $_POST['translations_lang_' . $languageId ][ $key ];
              }
              if
              ( 
                  isset( $_POST['translations_lang_' . $languageId . '_machine' ] ) 
                  && 
                  array_key_exists( $key, $_POST['translations_lang_' . $languageId . '_machine' ] ) 
              )
              {
                  $formTranslations[ $name ]['machine'][ $languageId ] = $_POST['translations_lang_' . $languageId . '_machine' ][ $key ];
              }
            }
          }
          
          $translationsByCode = self::mergeTranslationArrays( $groupId, $formTranslations, $translationsByCode );
        }


        if 
        (
          ( empty( $_FILES['translations_file'] ) )
          ||
          ( empty( $_FILES['translations_file']['tmp_name'] ) )
        )
        {
          return null;
        }
        $pathinfo = pathinfo($_FILES['translations_file']['name']);

        if( $pathinfo['extension'] == 'xls' )
        {
            require_once( SHARED_PATH . '3rdpart/PHPExcel/PHPExcel.php' );
            
            $excelData  = array();
            $columns    = array();
            $excel      = PHPExcel_IOFactory::load( $_FILES['translations_file']['tmp_name'] );
            $sheet      = $excel->getActiveSheet();
            $thisRow    = 1;
            
            foreach( $sheet->getRowIterator() as $row )
            {
              $cells = $row->getCellIterator();
              $cells->setIterateOnlyExistingCells( false );
              
              $thisColumn = 'A';
              
              $excelData[ $thisRow ] = array(
                'id'        => null,
                'name'      => null,
                'group_id'  => $groupId,
                'type'      => 0,
                'languages' => array(),
                'machine'   => array(),
              );
              
              foreach( $cells as $cell )
              {
                
                // First row is used as column titles
                if( $thisRow == 1 )
                {
                  $columns[ $thisColumn ] = $cell->getValue();
                  unset( $excelData[ $thisRow ] ); // Don't create empty array for first row
                }
                else
                {
                  $value = $cell->getValue();
                  
                  if( $value )
                  {
                    $columnTitle = $columns[ $thisColumn ];
                    
                    if( $columnTitle == 'name' )
                    {
                      $excelData[ $thisRow ]['name'] = $value;
                      
                      if( array_key_exists( $value, $translationsByCode ) && isset( $translationsByCode[ $value ]['id'] ) )
                      {
                          $excelData[ $thisRow ]['id'] = $translationsByCode[ $value ]['id'];
                      }
                    }
                    else if( in_array( $columnTitle, array_keys( $languages ) ) )
                    {
                      $languageId = isset( $languages[ $columnTitle ] ) ? $languages[ $columnTitle ] : null;
                      
                      if( $languageId )
                      {
                        $excelData[ $thisRow ][ 'languages' ][ $languageId ] = $value;
                      }
                    }
                  }
                }
                $thisColumn++;
              }
              $thisRow++;
            }

            $data['id']           = $groupId;
            $data['translations'] = $excelData;
        }
        else
        {
            $data = file_get_contents( $_FILES['translations_file']['tmp_name'] );
            $data = unserialize( $data );
        }

        if( !is_array( $data ) || empty( $data['translations'] ) )
        {
          return null;
        }

        if( isset( $data['context'] ) )
        {
          $data['context'] = trim( $data['context'] );
        }


        // iterate through the new translations (from imported file)
        // and for each combine the new data with existing (if exists)
        $importTranslations = $data['translations'];

        $translations = self::mergeTranslationArrays( $groupId, $importTranslations, $translationsByCode );

        $expandedLangs = NULL;
      
        if( !empty( $_COOKIE['aliasesOpenedLangs'] ) )
        {
            if( is_array( $_COOKIE['aliasesOpenedLangs'] ) )
            {
                $expandedLangs = $_COOKIE['aliasesOpenedLangs'];
            }
            else
            {
                $expandedLangs = explode( ',', $_COOKIE['aliasesOpenedLangs'] );
            }
        }
        $assign = array(
            'translations'  => $translations,
            'expandedLangs' => $expandedLangs,
        );

        print $this->moduleTemplate( 'translationsTable', $assign );
    
        alias_cache::registerDbChanges();
        
        die();
	}

  public static function mergeTranslationArrays( $groupId, $importTranslations, $existingTranslations = array() )
  {
      if( !isPositiveInt( $groupId ) )
      {
         return;
      }
      if( !is_array( $importTranslations ) || empty( $importTranslations ) )
      {
          return $existingTranslations;
      }
      if( !is_array( $existingTranslations ) || empty( $existingTranslations ) )
      {
          $existingTranslations = getExistingTranslations( $groupId );
      }
      
      $translations = $existingTranslations;
    
      foreach( $importTranslations as $importData )
      {
          $name = $importData['name'];

          if( !array_key_exists( $name, $translations ) )
          {
              // translation does not exist, create empty
              $translation = array(
                  'id'        => null,
                  'group_id'  => $groupId,
                  'name'      => $name,
                  'type'      => 0,
                  'languages' => array(),
                  'machine'   => array()
              );
          }
          else
          {
              // translation exists, use existing as base
              $translation = $existingTranslations[ $name ];
          }

          // set data from the import
          if( isset( $importData['type'] ) )
          {
               $translation['type'] = (int) $importData['type'];
          }
          
          if 
          (
              ( ! empty( $importData['languages'] ) )
              &&
              ( is_array( $importData['languages'] ) )
          )
          {
              $machineArray = get( $importData, 'machine', array() );
            
              foreach ($importData['languages'] as $languageId => $text)
              {
                  $translation['languages'][$languageId] = $text;
                  $translation['machine'][$languageId] = get( $machineArray, $languageId, 0 );
              }
          }
          
          $translations[$name] = $translation;
      }
      
      return $translations;
  }

  // create array with existing translations with codes as keys (not ids as by default)
  function getExistingTranslations( $groupId )
  {
        $existingTranslations = $this->getTranslations( $groupId );

        $translationsByCode = array();
    
        if( !empty( $existingTranslations ) )
        {
            foreach( $existingTranslations['translations'] as $translation )
            {
                $code = $translation['name'];
                $translationsByCode[$code] = $translation;
            }
        }
    
        return $translationsByCode;
  }

    function import_old()
	{
        $params = $_POST;

        //debug($params);
        if (empty($params['id']))
        {
            return null;
        }
        $groupId = (int) $params['id'];

        // read data in post
        if (
            (empty($_FILES['translations_file']))
            ||
            (empty($_FILES['translations_file']['tmp_name']))
        )
        {
            return null;
        }

        $data = file_get_contents( $_FILES['translations_file']['tmp_name'] );
        if (!$data)
        {
            return null;
        }

        $data = unserialize($data);
        if (
            (!is_array($data))
            ||
            (empty($data['translations']))
        )
        {
            return null;
        }

        //
        if (isset($data['context']))
        {
            $data['context'] = trim($data['context']);
        }
        $context = empty( $data['context'] ) ? '' : $data['context'];
        $contextPrefix = (empty($context)) ? '' : $context . $this->contextDelimiter;


        // load existing, foreach
        $existingTranslations = $this->getTranslations( $groupId );
        // debug ($existingTranslations, 0);

        $translationsByCode = array();
        // create array with existing translations with codes as keys (not ids as by default)\
        foreach ($existingTranslations['translations'] as $translation)
        {
            $code = $translation['name'];
            $translationsByCode[$code] = $translation;
        }

        // \ ($translationsByCode);
        // iterate through the new translations (from imported file)
        // and for each combine the new data with existing (if exists)

        $importTranslations = $data['translations'];
        // debug ($importTranslations, 0);

        $translations = array();
        foreach ($importTranslations as $importData)
        {
            $name = $importData['name'];
            if (empty($translationsByCode[$name]))
            {
                // translation does not exist, create empty
                $translation = array(
                    'id' => null,
                    'group_id' => $groupId,
                    'name' => $name,
                    'type' => 0,
                    'languages' => array()
                );
            }
            else
            {
                // translation exists, use existing as base
                $translation = $translationsByCode[$name];
            }

            // set data from the import
            if (isset($importData['type']))
            {
                 $translation['type'] = (int) $importData['type'];
            }
            if (
                (!empty($importData['languages']))
                &&
                (is_array($importData['languages']))
            )
            {
                foreach ($importData['languages'] as $languageId => $text)
                {
                    $translation['languages'][$languageId] = $text;
                }

            }
            $translations[] = $translation;
        }

        // $translations now contains all translations that need to be created / updated
        // debug ($translations, 0);

        // at first create all new ones, get their ids
        $updatableTranslationIds = array();
        foreach ($translations as $key => $translation)
        {
            if ($translation['id'])
            {
                $updatableTranslationIds[] = $translation['id'];
                continue;
            }
            $dbCode = $contextPrefix . $translation['name'];

            $sql = '
                INSERT INTO
                    `translations`
                    (`group_id`, `name`, `type`)
                    VALUES
                    (' . $groupId . ', "' . dbse($dbCode) . '", ' . $translation['type'] . ')
            ';

            // let's dangerously assume that nothing goes wrong here ;)
            dbQuery( $sql );
            $newId = dbInsertId();
            $translations[$key]['id'] = $newId;

            $updatableTranslationIds[] = $newId;
        }

        // now that all updatable translations have their ids, update the texts (DELETE + INSERT)
        if (!empty($updatableTranslationIds))
        {
            $idsString = implode(', ', $updatableTranslationIds);
            $deleteSql = 'DELETE FROM `translations_data` WHERE translation_id IN (' . $idsString . ')';
            // debug ($deleteSql, 0);
            dbQuery( $deleteSql );
        }
        foreach ($translations as $translation)
        {
            $translationId = $translation['id'];
            $rows = array();
            foreach ($translation['languages'] as $languageId => $text)
            {
                $languageId = (int) $languageId;
                $values = $translationId . ', ' . $languageId . ', "' . dbse($text) . '"';
                $rows[] = $values;
            }

            $valueList = '(' . implode('), (', $rows) . ')';
            $sql = '
                INSERT INTO `translations_data`
                (`translation_id`, `language_id`, `translation`)
                VALUES
                ' . $valueList . '
            ';
            dbQuery($sql);

        }


        $this->header_string .= '&do=edit&id=' . $groupId;

        alias_cache::registerDbChanges();
        return null;


	}

	public function isLanguageVisible( $languageId )
	{
		if( empty( $_COOKIE[ 'hideLanguageId-' . $languageId ] ) )
		{
			return true;
		}
		return false;
	}

	protected function getGroupExclusionClause( $useCategoryFilter = false )
	{

	    $rules = array
	    (
            '(t.name NOT LIKE "singleton:%")',
	    );

	    if ($useCategoryFilter)
	    {
	        $categories = $this->options['categories'];
	        $visibleCategories = $this->options['visibleCategories'];

	        if (empty($visibleCategories))
	        {
	            // no categories visible
	            $rules[] = '(false)';
	        }
	        else
	        {
	            $excludedCategories = array_diff($categories, $visibleCategories);
	            if (!empty($excludedCategories))
	            {
                    // not all categories visible
                    if (in_array('admin', $excludedCategories))
                    {
                        $rules[] = '(t.name NOT LIKE "admin:%")';
                        $rules[] = '(t.name NOT LIKE CONCAT("admin",CHAR(0),"%"))';
                        $rules[] = '(t.name NOT LIKE CONCAT("pages_admin",CHAR(0),"%"))';
                    }

                    if (in_array('other', $excludedCategories))
                    {
                        // since there are currently only two categories,
                        // excluding "other" means only including "admin"

                        $rules[] = '(
                            (t.name LIKE "admin:%")
                            OR
                            (t.name LIKE CONCAT("admin",CHAR(0),"%"))
                            OR
                            (t.name LIKE CONCAT("pages_admin",CHAR(0),"%"))
                        )';
                    }


	            }

	        }



	    }

        $clause = '(' . implode(' AND ' , $rules) . ')';
        return $clause;
	}

	public function getAvailableCategories()
	{
	    $categories = array('admin', 'other');
        return $categories;
	}

	public function getContextCategory( $context )
	{
        if (
            (preg_match('/^admin\:/', $context))
            ||
            (in_array($context, array('admin', 'pages_admin')))
        )
        {
            return 'admin';
        }

        // do not call this category "public", because it also contains shared groups like validation
        return 'other';

	}

	public function getCategoriesFromString( $string )
	{
	    $string = (string) $string;
        $categories = explode('|', $string);

        $allCategories = $this->getAvailableCategories();
        $categories = array_intersect( array_unique( $categories ) , $allCategories); // filter out bad values
        sort( $categories );
        return $categories;
	}

	protected static function getSearchPattern( $userInputStr )
	{
        $search = trim($userInputStr);
		$search = str_replace('%', '\%', $search);
		$search = preg_replace('/\s+/u', ' ', $search);
		$search = explode(' ', $search);
		$search = dbse('%' . implode('%', $search) . '%');
		return $search;
	}

}
?>
