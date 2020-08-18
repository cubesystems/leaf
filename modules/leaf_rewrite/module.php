<?
class leaf_rewrite{

	function leaf_rewrite(){
		leaf_set('mod_rewrite', true);

		$this->config = array(
			'languages' => true,
			'root_name' => 'root',
			'path_parts' => true,
			'skip_on_module' => true,
			'variable_start' => false,
			'host_name_correcting' => true,
			'correct_host_name' => NULL
		);
	}

	function checkHost()
	{
	    $host = (!empty($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '';
	    
		if (!is_null($this->config['correct_host_name']))
		{
		    // correct host specified in config
		    $correctHost = $this->config['correct_host_name'];
		}
		else
		{
		    // no correct host specified. by default add www if missing
            $hostParts = explode('.', strtolower($host));
			if (sizeof($hostParts) != 2)
			{
				return;
			}
			
			$correctPrefix = ($hostParts[0] == 'www') ? '' : 'www.';
			$correctHost   = $correctPrefix . $host;
		}
		
		if ($host != $correctHost)
		{
            $httpPrefix = isset($_SERVER['HTTPS']) ? 'https' : 'http';
			$redirectUrl = $httpPrefix . '://' . $correctHost . $_SERVER['REQUEST_URI'];
            leafHttp::redirect($redirectUrl, true);
		}
	}

	function output(){
		// check host name and redirect to correct one
		if($this->config['host_name_correcting'] == true)
		{
			$this->checkHost();
		}
		//try to open correct object_id
		if(isset($_GET['object_id']) && is_numeric($_GET['object_id']) && (intval($_GET['object_id']) == floatval($_GET['object_id'])))
		{
			leaf_set('object_id', $_GET['object_id']);
			return;
		}
		//try to open existing module
		if(isset($this->config['skip_on_module']) && $this->config['skip_on_module'] == true && isset($_GET['module']) && array_key_exists($_GET['module'], leaf_get('properties', 'modules')))
		{
			return;
		}
		if(!$this->config['languages'])
		{
			$q = '
			SELECT
				`id`
			FROM
				`objects`
			WHERE
				`parent_id` = "0" AND
				`rewrite_name` = "' . dbSE($this->config['root_name']) . '"
			';
			if(($id = dbGetOne($q)))
			{
				leaf_set(array('properties', 'root'), $id);
			}
		}
		//set properties root
		if(($object['id'] = leaf_get('properties', 'root')) === NULL)
		{
			$object['id'] = 0;
			leaf_set(array('properties', 'root'), $object['id']);
		}
		// set language_root as first known object
		if($object['id'] != 0)
		{
			leaf_set('object_id', $object['id']);
		}
		//set global root
		leaf_set('root', $object['id']);
		//no path? > redirect to (language) start page
		if(!isset($_GET['objects_path']))
		{
			// auto detect language
			if (
                ($this->config['languages'])
                &&
                (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
                &&
                (
                    (!isset($this->config['language_auto_detect']))
                    ||
                    ($this->config['language_auto_detect'])
                )
            )
			{
				$q = '
				SELECT
					l.id,
					l.short
				FROM
					`' . DB_PREFIX . 'languages` `l`
				LEFT JOIN
					`objects` `o` ON o.rewrite_name = l.short
				WHERE
					o.parent_id = "0" AND
					o.visible = 1
				ORDER BY
					`id`
				';
				$availableLanguages = dbGetAll($q, 'short', 'id');
				$currentLanguage = leaf_get('properties', 'language_id');

				// default language is not available, choose first from available list
				if(!in_array($currentLanguage, $availableLanguages))
				{
					if(sizeof($availableLanguages) > 0)
					{
						leaf_set(array('properties', 'language_name') , key($availableLanguages));
						leaf_set(array('properties', 'language_id'), current($availableLanguages));
					}
					else
					{
						require_once(SHARED_PATH . 'classes/leaf_error/leaf_error.class.php');
						$leaf_error = new leaf_error;
						$leaf_error->addMessage(array('header' => 'No languages available'));
						$leaf_error->display();
					}
				}
				$acceptedLanguages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
				foreach($acceptedLanguages as $acceptedLanguage)
				{
					$acceptedLanguage = explode(';', $acceptedLanguage);
					if(strpos($acceptedLanguage[0], 'en') === false && isset($availableLanguages[$acceptedLanguage[0]]))
					{
						leaf_set(array('properties', 'language_name') , $acceptedLanguage[0]);
						leaf_set(array('properties', 'language_id'), $availableLanguages[$acceptedLanguage[0]]);
						break;
					}
				}
			}
			if(!$this->config['languages'])
			{
				$start_id = leaf_get('properties', 'start');
			}
			else
			{

				$start_id = leaf_get('properties', leaf_get('properties', 'language_name') . '_start');
			}
			//try to get root from object tree
			if($this->config['languages'] && (!$start_id || ($start_url = object_rewrite_path($start_id)) == false))
			{
			    $rootId = (int) leaf_get('root');
				$q = '
				SELECT
					`id`
				FROM
					`objects`
				WHERE
					`parent_id` = ' . $rootId . '
					AND
					`rewrite_name` = "' . dbse(leaf_get('properties', 'language_name')) . '"
					AND
					`visible`
				';
				$start_id = dbGetOne($q);
				if (!$start_id)
				{
				    // get first visible root level object
    				$q = '
        				SELECT
        					`id`
        				FROM
        					`objects`
        				WHERE
        					`parent_id` = ' . $rootId . '
        					 AND
        					`visible`
                        ORDER BY order_nr ASC
    				';
				    $start_id = dbGetOne($q);
				    if (!$start_id)
				    {
				        trigger_error('No visible root object found.', E_USER_ERROR);
				        die();
				    }
				}

				leaf_set('object_id', $start_id);
				unset($start_url);
			}
			if(!$start_id || $start_id == leaf_get('root'))
			{
				if(leaf_get('properties', 'start'))
				{
					leaf_set('object_id', leaf_get('properties', 'start'));
				}
				else
				{
					leaf_set('object_id', leaf_get('root'));
				}
				return;
			}
			else
			{
				header('Location: ' . (isset($start_url) ? $start_url : object_rewrite_path($start_id)));
				exit;
			}
		}
		//split path part
		$parts = explode('/', $_GET['objects_path']);
		//cut web directory
		$www_parts = explode('/', WWW);
		$www_parts = array_splice($www_parts, 3, -1);
		$split_start = 0;
		if($max = sizeof($www_parts))
		{
			for($i = 0; $i < $max; $i++)
			{
				if($www_parts[$i] == $parts[$i])
				{
					$split_start++;
				}
			}
			$parts = array_splice($parts, $split_start);
			$_GET['objects_path'] = implode('/', $parts);
		}
		leaf_set('objects_path', $_GET['objects_path']);
		//trim empty part
		if($parts[sizeof($parts)-1] == '')
		{
			unset($parts[sizeof($parts)-1]);
		}
		$rewrite_path = '';
		$max = sizeof($parts);
		for($i = 0; $i < $max; $i++)
		{
		    if (preg_match('/^\d+$/', $parts[$i]))
		    {
                $intId = (int) $parts[$i];
		    }
		    else
		    {
		        $intId = 0;
		    }


		    $parentId = $object['id'];
            $object = null;

		    $queryParts = array(
                'select'  => array('`id`', '`parent_id`'),
                'from'    => '`' . DB_PREFIX . 'objects`',
                'where'   => array(
                    '`parent_id` = "' . $parentId . '"'
                ),
                'orderBy' => '`visible` DESC, `order_nr` ASC',
                'limit'   => 1
	 	    );

		    if (isPositiveInt($parts[$i]))
		    {
    	        // look for numeric rewrite name first
		        $numericRewriteQP = $queryParts;
		        $numericRewriteQP['select'][] = '`rewrite_name` AS `name`';
		        $numericRewriteQP['where'][] = '`rewrite_name` = "' . dbSE($parts[$i]) . '"';

		        $object = dbGetRow($numericRewriteQP);
            }

            if (!$object)
            {
                $rewriteOrIdQp = $queryParts;
                $rewriteOrIdQp['select'][] = 'IF(LENGTH(`rewrite_name`) AND "' . dbSE($parts[$i]) . '" = `rewrite_name`, `rewrite_name`, `id`) AS `name`';
                $rewriteOrIdQp['where'][]  =
                '
                    (
    					`rewrite_name` = "' . dbSE($parts[$i]) . '"
    					OR
    					`id` = ' . $intId . '
                    )
                ';
               $object = dbGetRow($rewriteOrIdQp);
            }

			if(
				$object
				&&
				(
					($i == 0 && leaf_get('properties', $parts[$i] . '_root') == $object['id'])
					||
					($object['parent_id'] == leaf_get('properties', 'root'))
				)
				&&
				($this->config['languages'] && $language_id = dbGetOne('SELECT `id` FROM `' . DB_PREFIX . 'languages` WHERE `short` = "' . dbSE($parts[$i]) . '"')) != NULL)
			{
				leaf_set(array('properties', 'language_name') , $parts[$i]);
				leaf_set(array('properties', 'language_id'), $language_id);
				leaf_set('root', $object['id']);
				leaf_set('lang_root', $object['id']);
				leaf_set('start', leaf_get('properties', $parts[$i] . '_start'));
				leaf_set('object_id', $object['id']);
				$rewrite_path = $parts[$i] . '/';
			}
			else if($object)
			{
				$rewrite_path .= $object['name'] . '/';
				leaf_set('object_id', $object['id']);
			}
			else
			{
				break;
			}
		}

		// get language code
		if($this->config['languages'])
		{
			$q = '
			SELECT
				`code`
			FROM
				`languages`
			WHERE
				`id` = "' . leaf_get('properties', 'language_id') . '"
			';
			$langCode = dbGetOne($q);
			leaf_set(array('properties', 'language_code') , $langCode);
		}

		// store in table
		if($oid = leaf_get('object_id'))
		{
			self::registerPathInUrlHistory( $rewrite_path, $oid );
		}
		if($this->config['path_parts'])
		{
			leaf_set('path_part', substr($_GET['objects_path'], strlen($rewrite_path)));
		}
		//no page > error 404
		if($rewrite_path != $_GET['objects_path'] && !$this->config['path_parts'])
		{
			$this->error_404();
		}
	}

	public static function registerPathInUrlHistory( $path, $objectId, $params = null)
	{
	    return leafObjectsRewrite::registerPathInUrlHistory( $path, $objectId, $params );
	}

	function error_404($force404 = false, $outputHtml = true)
	{
        $returnType = 404;
        if(!$force404 && !empty($_GET['objects_path']))
        {
    		// try to get oid for requested url
    		if(substr($_GET['objects_path'], -1, 1) != '/')
    		{
    			$_GET['objects_path'] .= '/';
    		}

    		$q = '
    		SELECT
    			*
    		FROM
    			`objects_url_history`
    		WHERE
    			`path` = "' . dbSE($_GET['objects_path']) . '"
    		';
    		if(sizeof($_POST) == 0 && $historyRow = dbGetRow($q))
    		{
    		    $oid = $historyRow['object_id'];

    		    $url = null;

    		    if (
                    (isset($historyRow['params']))
                    &&
                    (!empty($historyRow['params']))
                )
    		    {
    		        $objectParams = @unserialize($historyRow['params']);
    		        if ($objectParams !== false)
    		        {
        		        // custom path registered
        		        // attempt to load object and call custom function

        		        $object = _core_load_object( $oid );
            		        if (
                            ($object)
                            &&
                            (method_exists( $object, 'getUrlFromHistoryParams' ))
                        )
        		        {
                            $url = $object->getUrlFromHistoryParams( $objectParams );
        		        }
    		        }
    		    }

    		    // custom url not found, redirect to object
                if (empty($url))
                {
                    $url = orp($oid);
                }

    			if ($url && $url != $oid)
    			{
    				$returnType = 301;
    				unset($_GET['objects_path']);
    				if (!empty($_GET))
    				{
    					$url .= '?' . http_build_query($_GET);
     				}
    			}
    		}
        }
		// 301 Moved Permanently
		if ($returnType == 301)
		{
            leafHttp::redirect( $url, true );
		}
		// 404 Not Found
		else
		{
            header("HTTP/1.0 404 Not Found");
            if($outputHtml)
            {
                $message = array(
                    'header' => '404 File Not Found',
                    'html' => '<p> The requested URL  <strong>' . WWW . htmlspecialchars(substr($_SERVER['REQUEST_URI'],1)) . '</strong> was not found on this server. It may have been removed, had its name changed, is
                temporarily
                unavailable,
                or maybe it was never here at all.</p>
                    <p>Either go <a href="javascript:history.go(-1)" title="history go -1">back</a> and try again or goto the <a
                href="/" title="">main index</a>.</p>'
                );

                require_once(SHARED_PATH . 'classes/leaf_error/leaf_error.class.php');
                $leaf_error = new leaf_error;

                $leaf_error->addHeader('<meta http-equiv="imagetoolbar" content="no" />
        <meta http-equiv="cache-control" content="no-cache" />
        <meta http-equiv="pragmas" content="no-cache" />
        <meta name="robots" content="noindex,nofollow" />

        <meta name="googlebot" content="noindex,nofollow" />
        <meta name="googlebot" content="noarchive" />
        <meta name="robots" content="noimageindex" />
        <meta name="robots" content="nocache,noarchive" />');
                //'no page here: ' .
                $leaf_error->addMessage($message);
                $leaf_error->display();
            }
		}
	}

}
