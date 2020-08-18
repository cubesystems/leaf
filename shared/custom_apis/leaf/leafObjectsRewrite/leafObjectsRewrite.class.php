<?
/*
17:11 <gnc> hmm a tad tas vai saitam ir languages vai nee - to arii varees
            adminaa dabuut ?
*/
class leafObjectsRewrite extends leafComponent{
	const tableName = 'objects_rewrite_cache';

	protected static $objectsTableAutoIncrement = null;
		
	public static function deleteRewrite($objectId){
		dbDelete(self::tableName, array('object_id' => $objectId));
	}

	public static function update($parentIds, $update = false){
		if(!is_array($parentIds))
		{
			$parentIds = array($parentIds);
		}
		$parentsQ = getArgumentAsString($parentIds, '","', 'intval');
		$rewriteLanguageRoots = leaf_get('objects_config', 'rewriteLanguageRoots');
		$rewriteSel = 'IF(o.parent_id=0' . ($rewriteLanguageRoots === FALSE ? '' : ' AND o.rewrite_name="root"') . ', "", CONCAT(IF(o.rewrite_name != "", o.rewrite_name, o.id), "/")) `rewrite`';
		$q = '
		(
			SELECT
				' . $rewriteSel . ',
				f.file_name,
				r.file_name `file_name_stored`,
				o.id,
				o.parent_id,
				SUBSTRING_INDEX(SUBSTRING(r.url, 1, LENGTH(r.url)-1), "/", -1) AS `current`
			FROM
				`' . DB_PREFIX . 'objects` `o`
			LEFT JOIN
				`' . DB_PREFIX . 'files` `f` ON f.object_id = o.id
			LEFT JOIN
				`' . self::tableName . '` `r` ON r.object_id = o.id
			WHERE
				o.id IN ("' . $parentsQ . '")
		)
		UNION
		(
			SELECT
				' . $rewriteSel . ',
				f.file_name,
				r.file_name `file_name_stored`,
				o.id,
				o.parent_id,
				NULL `current`
			FROM
				`' . DB_PREFIX . 'object_ancestors` `anc`
			LEFT JOIN
				`' . DB_PREFIX . 'objects` `o` ON o.id = anc.object_id
			LEFT JOIN
				`' . DB_PREFIX . 'files` `f` ON f.object_id = o.id
			LEFT JOIN
				`' . self::tableName . '` `r` ON r.object_id = o.id
			WHERE
                anc.ancestor_id IN ("' . $parentsQ . '")
            HAVING 
                    o.id IS NOT NULL
			ORDER BY
				anc.level DESC, o.parent_id
		)
        ';
		$list = dbGetAll($q, 'id');
		if(!$update)
		{
			foreach($parentIds as $parentId)
			{
				if($list[$parentId]['file_name'] != $list[$parentId]['file_name_stored'] || $list[$parentId]['current'] != $list[$parentId]['rewrite'])
				{
					$update = true;
				}
			}
		}
		if($update)
		{
			$urls = array();
			foreach($list as &$item)
			{
				$urls[$item['id']] = array(
					'url' => self::buildUrl($item, $list, $urls),
					'object_id' => $item['id'],
					'file_name' => $item['file_name']
				);
			}
			if(!empty($urls))
			{
				dbReplace(self::tableName, $urls);
			}
		}
	}

    public static function getRewriteCacheKey( $objectId )
    {
        $callback = leaf_get('objects_config', 'getRewriteCacheKeyCallback');
        
		if (
            ($callback)
            &&
            (function_exists($callback))
        )
        {
            return call_user_func( $callback, $objectId );
        }
        
        return $objectId;
    }
    
	public static function getUrl($objectOrId, $internalCall = false)
    {
        if (
            ($objectOrId instanceof leaf_object_module)
            &&
            (!empty($objectOrId->object_data['id']))
        )
        {
            $objectId = $objectOrId->object_data['id'];
        }
        else
        {
            $objectId = (int) $objectOrId;
        }

		// add cache support
        $cacheKey = self::getRewriteCacheKey( $objectOrId );
        
		if (
		    (!empty($GLOBALS['rewrite_cache']))
		    &&
            (array_key_exists($cacheKey, $GLOBALS['rewrite_cache']))
            &&
            (!$internalCall)
        )
		{
			return $GLOBALS['rewrite_cache'][$cacheKey];
		}


		$rewriteOn           = leaf_get('objects_config', 'rewrite');
		$rewriteBase         = leaf_get('objects_config', 'rewriteBase');
	    $rewriteBaseCallback = leaf_get('objects_config', 'rewriteBaseCallback');

		if (
            ($rewriteBaseCallback)
            &&
            (function_exists($rewriteBaseCallback))
        )
        {
            $rewriteBase = call_user_func( $rewriteBaseCallback, $rewriteBase, $objectId );
        }
        

		if (!$rewriteBase)
		{
			$rewriteBase = WWW;
		}
		if($rewriteOn === FALSE)
		{
			return ($GLOBALS['rewrite_cache'][$cacheKey] = $rewriteBase . '?object_id=' . $objectId);
		}
		// try to get from cached
		$q = '
		SELECT
			`url`,
			`file_name`
		FROM
			`' . self::tableName . '`
		WHERE
			`object_id` = "' . dbSE($objectId) . '"
		';

		// rewriteData is not found, try to make it
		if(!($rewriteData = dbGetRow($q)))
		{
		    // update rewrite cache, but only if object exists
		    $exists = (bool) dbgetone('select id from objects where id =' . (int) $objectId);
		    if (!$exists)
		    {
		        // store failed result in memory for this request
		        $GLOBALS['rewrite_cache'][$cacheKey] = null;
		        
		        // store failed result in cache table if possible
                $largestCurrentlyPossibleObjectId = self::getObjectsTableAutoIncrement();
		        if ($objectId < $largestCurrentlyPossibleObjectId)
		        {
                    // object not found and its ID is below auto_increment
                    // it means the object has been already deleted
                    // and will not ever exist again
                    // therefore it is ok to store empty return values in rewrite cache table

                    $cacheData = array
                    (
                        'url'       => null,
                        'object_id' => $objectId,
                        'file_name' => null
                    );
        			dbReplace(self::tableName, $cacheData);
        			
        			// also log this request to missing objects table
        			if (class_exists('missingContentObject', true))
        			{
                        missingContentObject::log( $objectId );
        			}
		        }
		        
		        return null;
		    }		    
			self::update($objectId, true);
			if(!($rewriteData = dbGetRow($q)))
			{
				return null;
			}
		}

		if ($internalCall)
		{
			return $rewriteData['url'];
		}


        if (
            (empty($rewriteData['file_name']))
            &&
            (empty($rewriteData['url']))
        )
        {
            $url = null;
        }
        elseif ($rewriteData['file_name'])
		{
			$url = leaf_get('objects_config', 21, 'files_www') . $rewriteData['file_name'];
		}
		else
		{

		    $callBack = leaf_get('objects_config', 'getUrlCallback');
    		if (
                ($callBack)
                &&
                (function_exists($callBack))
            )
            {
                $rewriteData['url'] = call_user_func( $callBack, $rewriteData['url'], $objectId);
            }

			$url = $rewriteBase . $rewriteData['url'];
		}


		$GLOBALS['rewrite_cache'][$cacheKey] = $url;
		return $url;
	}

	protected static function getObjectsTableAutoIncrement()
	{
	    if (!isset(self::$objectsTableAutoIncrement))
	    {
            $row = dbgetrow('SHOW TABLE STATUS WHERE name="objects"');
            if ($row && isset($row['Auto_increment']))
            {
                self::$objectsTableAutoIncrement = (int) $row['Auto_increment'];
            }
	    }
	    return self::$objectsTableAutoIncrement;
	}
	
	public static function buildUrl($item, &$list, &$urls){
		$url = $item['rewrite'];
		while($item['parent_id'] != 0)
		{
			// get parent from db
			if(!isset($list[$item['parent_id']]))
			{
				$parentUrl = self::getUrl($item['parent_id'], true);
				$url = $parentUrl . $url;
				break;
			}
			// get parent rewrite from already build urls
			else if(isset($urls['url']))
			{
				$url = $urls[$item['object_id']] . $url;
				break;
			}
			// get parent rewrite from list
			else
			{
				$item = $list[$item['parent_id']];
				if($item['rewrite'])
				{
					$url = $item['rewrite'] . $url;
				}
			}
		}
		return $url;
	}
	
	public static function registerPathInUrlHistory( $path, $objectId, $params = null)
	{
	    if (mb_substr($path, -1, 1) != '/')
	    {
	        $path .= '/';
	    }

	    if (mb_strlen($path) > 255)
	    {
            return false; // path is used as primary key in mysql and is a varchar 255
                          // storing longer paths in history would result in incorrect behaviour
	    }

	    $data = array
	    (
            'path'      => $path,
	        'object_id' => $objectId,
	        'params'    => null
	    );
        
	    if (!is_null($params))
	    {
	        if (is_array($params))
	        {
	            $params = serialize($params);
	        }
	        $data['params'] = $params;
	    }
        
        
	    return dbReplace('objects_url_history', $data);
	}

}
?>
