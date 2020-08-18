<?
class objectTree extends leafComponent{

    protected static $ancestorCache = array();
	
	/**
     * get first visible object from 1st level objects
     *
     * @param string $template
     * @param int $objectId
     * @return object
     */
    public static function getFirstObject( $template = null, $objectId = null, $visibleOnly = true )
    {
		$rootTemplateName = null;
		$rootId = null;

		$objectId = self::getObjectId( $objectId );

		if (!isPositiveInt($objectId))
		{
			$rootTemplateName = leaf_get('properties', 'language_name');
		}
		else
		{
			// get object root
			$queryParts = array(
				'select' => 'o.id',
				'from' => 'object_ancestors AS oa',
				'leftJoins' => array(
					'objects AS o ON oa.ancestor_id = o.id',
				),
				'where' => array(
					'oa.object_id = "' . $objectId . '"',
					'oa.level = 1'
				)
			);
			// try to get root from ancestors
			$rootId = dbGetOne($queryParts);
			// no ancestors, so object itself is root object
			if(!$rootId)
			{
				$rootId = $objectId;
			}
		}

        $queryParts = array(
            'select' => 'o.*',
            'from' => 'object_ancestors AS oa',
            'leftJoins' => array(
                'objects AS o ON oa.object_id = o.id',
                'objects as o2 ON oa.ancestor_id = o2.id'
            ),
            'where' => array(
                'oa.level = 1'
            )
        );
		if ($visibleOnly)
        {
        	$queryParts['where'][] = 'o.visible';
        }

        // set template condition if template not null
        if (
            (!is_null($template))
            &&
            (is_string($template))
        )
        {
            $queryParts['where'][] = 'o.template = "' . dbse($template) . '"';
        }

		if($rootTemplateName)
		{
			$queryParts['where'][] = 'o2.rewrite_name IN ("root", "' . $rootTemplateName . '")';
		}
		else
		{
			$queryParts['where'][] = 'o2.id = "' . $rootId . '"';
		}

        $objectId = dbGetOne($queryParts);
        if (!$objectId)
        {
            return null;
        }
        $object = _core_load_object( $objectId );
        if (!$object)
        {
            return null;
        }
        return $object;
    }

     /**
         * get current root object
         *
         * @param string $rootName
         * @return object
         */
    public static function getRootObject($rootName)
    {
        // get object root
        $queryParts = array(
            'select' => 'o.*',
            'from' => 'objects AS o',
            'where' => array(
                'o.rewrite_name = "' . dbSE($rootName) . '"',
                'o.parent_id = 0'
            )
        );
        $rootObjectData = dbGetOne($queryParts);
        if($rootObjectData)
        {
            $rootObject = _core_load_object($rootObjectData);
            return $rootObject;
        }
    }

    /**
     * get parent first child
     *
     * @param int $parentId
     * @param string $childTemplate
	 * @param bool $visible
     * @return object
     */
    public static function getFirstChild($parentId, $templateNames = array(), $visible = false)
	{
        $parentId = self::getObjectId( $parentId );

        $templatesCondition = self::getTemplatesCondition('o', $templateNames);

		$queryParts = array
		(
		   'select'  => 'o.id',
		   'from'    => 'objects AS o',
		   'where'   =>  array (
			   'o.parent_id = ' . intval($parentId)
		   ),
           'orderBy' => 'o.order_nr'
		);

		if ($templatesCondition)
		{
			$queryParts['where'][] = $templatesCondition;
		}
		if ($visible)
		{
			$queryParts['where'][] = 'o.visible = 1';
		}
        $id = dbGetOne ($queryParts);
        if (!$id)
        {
            return null;
        }
        $object = _core_load_object( $id );
        if (!$object)
        {
            return null;
        }
        return $object;
	}

	/**
     * get parent first visible child
     *
     * @param int $parentId
     * @param string $childTemplate
     * @return object
     */
	public static function getFirstVisibleChild($parentId, $childTemplate = null)
	{
	    return self::getFirstChild($parentId, $childTemplate, true);
	}

    /**
     * build visible children query
     *
     * @param int/array $parentIds
     * @param string/array $templateNames
     * @param boolean $templateNamesLike
     * @param string/array $orderBy
     * @return array
     */
	public static function getVisibleChildrenQueryParts($parentIds = array(), $templateNames = array(), $templateNamesLike = false, $orderBy = null)
	{
        $queryParts = self::getChildrenQueryParts($parentIds, $templateNames, $templateNamesLike, $orderBy);
        if (!is_array($queryParts))
        {
            return null;
        }
        $queryParts['where'][] = 'o.visible';
		return $queryParts;
	}

    /**
     * build children query
     *
     * @param int/array $parentIds
     * @param string/array $templateNames
     * @param boolean $templateNamesLike
     * @param string/array $orderBy
     * @return array
     */
	public static function getChildrenQueryParts( $parentIds = array(), $templateNames = array(), $templateNamesLike = false, $orderBy = null)
	{
		if (is_null($orderBy))
		{
			 $orderBy = array('parent_id, order_nr');
		}
		elseif (is_string($orderBy))
		{
		    $orderBy = array(dbse($orderBy));
		}
		elseif (is_array($orderBy))
		{
		    foreach ($orderBy as & $value)
		    {
		        $value = dbse($value);
		    }
		    unset( $value ); // destroy  reference
		}
		else
		{
		    $orderBy = null;
		}

        $parentCondition = self::getParentsCondition( 'o', $parentIds );
        if (!$parentCondition)
        {
            return null;
        }

        $templatesCondition = self::getTemplatesCondition('o', $templateNames, $templateNamesLike);
		$queryParts = array
		(
		   'select'  => 'o.*',
		   'from'    => 'objects AS o',
		   'where'   =>  array (
			   $parentCondition
		   ),
		);

		if ($templatesCondition)
		{
			$queryParts['where'][] = $templatesCondition;
		}

		// auto-join xo table (only join if exactly one template is needed)
		if (
            (!empty($templateNames))
            &&
            (!$templateNamesLike)
            &&
            (
                (
                    (is_string($templateNames))
                    &&
                    ($templateName = $templateNames) // assign template name from string
                )
                ||
                (
                    (is_array($templateNames))
                    &&
                    (count($templateNames) == 1)
                    &&
                    ($templateName = current($templateNames))  // assign template name from array
                    &&
                    (is_string($templateName))
                )
            )
        )
        {
            // $templateName now contains the name of the only template passed

            $templateTableName = leafObject::getTemplateTable($templateName);
            if ($templateTableName)
            {
                $queryParts['leftJoins'] = array(
                    '`' . $templateTableName . '` AS `x` ON o.id = x.object_id'
                );
            }
        }

		if (!empty($orderBy))
		{
            $queryParts['orderBy'] = $orderBy;
		}

		return $queryParts;
	}

    /**
     * get visible children of one or more parents
     *
     * @param int/array $parentIds
     * @param string/array $templateNames
     * @param boolean $templateNamesLike
     * @param string/array $orderBy
     * @param int $itemsPerPage
     * @param int $pageNo
     * @return pagedObjectList
     */
    public static function getVisibleChildren($parentIds = array(), $templateNames = array(), $templateNamesLike = false, $orderBy = null, $itemsPerPage = null, $pageNo = null)
    {
		// get query
		$queryParts = self::getVisibleChildrenQueryParts($parentIds, $templateNames, $templateNamesLike, $orderBy);
		if(!$queryParts)
		{
			return null;
		}

		// get objects
		$result = new pagedObjectList($queryParts, $itemsPerPage, $pageNo);

		return $result;
    }

    /**
     * get all children of one or more parents
     *
     * @param int/array $parentIds
     * @param string/array $templateNames
     * @param boolean $templateNamesLike
     * @param string/array $orderBy
     * @param int $itemsPerPage
     * @param int $pageNo
     * @return pagedObjectList
     */
    public static function getChildren($parentIds = array(), $templateNames = array(), $templateNamesLike = false, $orderBy = null, $itemsPerPage = null, $pageNo = null)
    {
		// get query
		$queryParts = self::getChildrenQueryParts($parentIds, $templateNames, $templateNamesLike, $orderBy);
		if(!$queryParts)
		{
			return null;
		}

		// get objects
		$result = new pagedObjectList($queryParts, $itemsPerPage, $pageNo);

		return $result;
    }

    public static function getFirstAncestorQueryParts($objectId, $ancestorTemplate = null, $visibleOnly = false)
    {
        $objectId = self::getObjectId($objectId);
        $qp = array(
            'select' => 'o.*',
            'from'   => '`object_ancestors` AS oa',
            'leftJoins' => array(
                '`objects` AS o ON oa.ancestor_id = o.id'
            ),
            'where' => array(
                'oa.object_id = ' . $objectId
            ),
            'orderBy' => 'oa.level DESC'
        );

	    if ($ancestorTemplate)
	    {
	        $qp['where'][] = 'o.template = "' . dbse($ancestorTemplate) . '"';
	    }
	    if ($visibleOnly)
	    {
	        $qp['where'][] = 'o.visible';
	    }
	    return $qp;
    }

    /**
     * get object first ancestor
     *
     * @param int $objectId
     * @param string $ancestorTemplate
     * @param boolean $visibleOnly
     * @return object
     */
	public static function getFirstAncestor($objectId, $ancestorTemplate = null, $visibleOnly = false)
	{
	    $qp = self::getFirstAncestorQueryParts( $objectId, $ancestorTemplate, $visibleOnly);

	    $result = dbGetRow($qp);
	    if (!$result)
	    {
	        return null;
	    }
	    $object = _core_load_object($result);
	    if (!$object)
	    {
	        return null;
	    }
        return $object;
	}

	public static function getFirstAncestorId( $objectId, $ancestorTemplate = null, $visibleOnly = false )
	{
        $ancestorQp = objectTree::getFirstAncestorqueryParts( $objectId, $ancestorTemplate, $visibleOnly);
        $ancestorQp['select'] = 'o.id';
        return dbgetone( $ancestorQp );
	}
    /**
     * build parent query condition
     *
     * @param string $tableName
     * @param int/array $parentIds
     * @return string
     */
    public static function getParentsCondition ( $tableName, $parentIds )
    {
        $tableName = dbse($tableName);
	    if (
	       (is_array($parentIds))
	       ||
	       ($parentIds instanceof ArrayIterator)
        )
	    {
	        $temp = $parentIds;
	        $parentIds = array();
	        foreach ($temp as $parent)
	        {
                $parentId = self::getObjectId( $parent );
	            $parentIds[] = $parentId;
	        }
	        $parentIds = array_unique( $parentIds  );
	    }
	    else
	    {
            $parentId  = self::getObjectId( $parentIds );
            $parentIds = array( $parentId );
	    }

	    if (empty($parentIds))
	    {
	        return '';
	    }

	    if (count($parentIds) == 1)
	    {
            $parentCondition = '`' . $tableName . '`.`parent_id` = ' . current($parentIds);
	    }
	    else
	    {
            $parentCondition = '`' . $tableName . '`.`parent_id` IN (' . implode(', ', $parentIds) . ')';
	    }
	    return $parentCondition;
    }

    /**
     * build template query condition
     *
     * @param string $tableName
     * @param string/array $templateNames
     * @param boolean $templateNamesLike
     * @param boolean $not
     * @return string
     */
	public static function getTemplatesCondition( $tableName, $templateNames, $templateNamesLike = false, $not = false)
	{
	    if (is_array($templateNames))
        {
            if (
                (!empty($templateNames['exclude']))
            )
            {
                $templateNames = $templateNames['exclude'];
                $not = !$not;
            }
            elseif (
                (!empty($templateNames['include']))
            )
            {
                $templateNames = $templateNames['include'];
            }
        }

	    $not = ($not) ? 'NOT' : '';
        $or = ($not) ? 'AND' : 'OR';
        $notOperator = ($not) ? '!' : '';

	    $tableName = dbse($tableName);
        $templatesCondition = '';
        if (!empty($templateNames))
        {
            if (is_array($templateNames))
            {
                foreach ($templateNames as $key => $val)
                {
                    $templateNames[$key] = '"' . dbse ($val)  . '"';
                }

                if ($templateNamesLike)
                {
                    foreach ($templateNames as $key => $val)
                    {
                        $templateNames[$key] = '`' . $tableName . '`.`template` ' . $not . ' LIKE ' . $val;
                    }
                    $templatesCondition = '(' . implode(' ' . $or . ' ', $templateNames) .')';
                }
                else
                {
                    $templatesCondition = '`' . $tableName . '`.`template` ' . $not . ' IN ('  . implode(',', $templateNames) . ')';
                }

            }
            elseif (is_string($templateNames))
            {
                $operator = ($templateNamesLike)  ? $not . ' LIKE' : $notOperator . '=';
                $templatesCondition = '`' . $tableName . '`.`template` ' . $operator . ' "' . dbse($templateNames) . '"';
            }
        }
        return $templatesCondition;
	}

    /**
     * search for object first common field value starting from object itself
     *
     * @param object $object
     * @param string $fieldName
     * @return array
     */
	public static function getFirstCommonFieldValue ( $object, $fieldName )
	{
	    // finds the first ancestor of given object (or the object itself) that has a value set
	    // fot the given common field
	    //
	    // returns an array consisting of:
	    //   * value of the field
	    //   * id of the object in which the value was found
	    //   * level of the object in the tree if the value was found in an ancestor and not the object itself
	    //     if the value is found directly in the given object, level will be null
	    //
	    //   return array (
        //       'value'      => $fieldValue,
        //       'objectId'   => $objectId,
        //       'level'      => $level
        //   );
	    //
	    // returns null on error
	    //

        $fieldValue = $objectId = $level = null;

        if (
            (!preg_match('/^[a-zA-z_0-9]+$/', $fieldName))
            ||
            (!$object)
        )
        {
            return null;
        }

	    // 1)
        // check self

        if (!empty($object->object_data['data'][$fieldName]))
        {
            // check opened object
            $fieldValue = $object->object_data['data'][$fieldName];
            $objectId   = $object->object_data['id'];
        }
        else
        {
            // check ancestors
            $givenObjectId = $object->object_data['id'];

            if (!$givenObjectId)
            {
                return null;
            }

            $sql = '
                SELECT
                    *
                FROM
                    `object_ancestors` AS oa
                    LEFT JOIN
                    `objects` AS o
                    ON
                    oa.ancestor_id = o.id
                WHERE
                    oa.object_id = ' . $givenObjectId . '
                    AND
                    o.data LIKE "%s:' . strlen($fieldName) . ':\"' . $fieldName . '\";%"
                ORDER BY
                    level DESC
            ';

            $result = dbGetAll($sql, 'id', null, null, array('objectTree', 'unserializeData'));


            if (!$result)
            {
                return null;
            }

            $firstFound = false;
            foreach ($result as $id => $row)
            {
                if ($firstFound)
                {
                    continue;
                }
                if (!empty($row['data'][$fieldName]))
                {
                    $fieldValue = $row['data'][$fieldName];
                    $firstFound = true;
                    $objectId = $id;
                    $level = $row['level'];
                }
            }
        }

        // debug ($fieldValue);

        if (!$fieldValue)
        {
            return null;
        }

        $result = array(
            'value'     => $fieldValue,
            'objectId'  => $objectId,
            'level'     => $level
        );


        return $result;

	}

	public static function unserializeData($value)
	{
	    if (!empty($value['data']))
	    {
	        $value['data'] = unserialize($value['data']);
	    }
	    return $value;
	}

    /**
     * get object language id
     *
     * @param int $objectId
     * @return int
     */
	public static function getObjectLanguage( $objectId )
	{
	    $objectId = self::getObjectId( $objectId );

	    $sql = '
            SELECT
                l.id
            FROM
                object_ancestors AS oa
                LEFT JOIN
                objects AS o
                ON
                oa.ancestor_id = o.id
                LEFT JOIN
                languages AS l
                ON
                o.rewrite_name = l.short
            WHERE
                oa.object_id = ' . $objectId . '
                AND
                oa.level = 1
	    ';
	    $result = dbgetone($sql);
	    if (!$result)
	    {
			// try to get as language root
			$sql = '
				SELECT
					l.id
				FROM
					objects AS o
					LEFT JOIN
					languages AS l
					ON
					o.rewrite_name = l.short
				WHERE
					o.id = ' . $objectId . ' AND
					o.parent_id = 0
			';
			$result = dbgetone($sql);
			if (!$result)
			{
				return null;
			}
	    }
	    return $result;

	}

    /**
     * build descendants query
     *
     * @param int/array $parentIds
     * @param string/array $templateNames
     * @param boolean $templateNamesLike
     * @param boolean $visibleOnly
     * @return array
     */
    public static function getDescendantsQueryParts($ancestorIds = array(), $templateNames = array(), $templateNamesLike = false, $visibleOnly = false, $orderBy = null)
    {
		if(is_null($orderBy))
		{
			 $orderBy = '
	           oa.level DESC,
	           p.order_nr,
	           o.order_nr
		   ';
		}

		$ids = array();
		if (
            (is_array($ancestorIds))
            ||
            ($ancestorIds instanceof ArrayIterator)
        )
        {
            foreach ($ancestorIds as $ancestor)
            {
                $ids[] = self::getObjectId( $ancestor );
            }
        }
        else
        {
            $ids[] = self::getObjectId( $ancestorIds );
        }

        $numberOfIds = count($ids);

        if ($numberOfIds > 1)
        {
            $ancestorCondition = '`oa`.`ancestor_id` IN ( '  . dbse(implode(', ', $ids)) . ')';
        }
        elseif ($numberOfIds == 1)
        {
            $id = array_pop($ids);
            $ancestorCondition = '`oa`.`ancestor_id` = "' . (int) $id . '"';
        }
        else
        {
            // no ancestors given
            return null;
        }


		$queryParts = array
		(
		   'select'  => 'o.*',
		   'from'    => '`object_ancestors` AS `oa`',
		   'where'   =>  array (
			   $ancestorCondition
		   ),
		   'leftJoins'   =>  array (
			   '`objects` AS `o` ON oa.object_id = o.id',
			   '`objects` AS `p` ON o.parent_id = p.id',
		   ),
		   'orderBy' => $orderBy
		);

        $templatesCondition = self::getTemplatesCondition('o', $templateNames, $templateNamesLike);
	    if ($templatesCondition)
	    {
	       $queryParts['where'][] = $templatesCondition;
	    }


		// auto-join xo table (only join if exactly one template is needed)
		if (
            (!empty($templateNames))
            &&
            (!$templateNamesLike)
            &&
            (
                (
                    (is_string($templateNames))
                    &&
                    ($templateName = $templateNames) // assign template name from string
                )
                ||
                (
                    (is_array($templateNames))
                    &&
                    (count($templateNames) == 1)
                    &&
                    ($templateName = current($templateNames))  // assign template name from array
                    &&
                    (is_string($templateName))
                )
            )
        )
        {
            // $templateName now contains the name of the only template passed
            $templateTableName = leafObject::getTemplateTable($templateName);
            if ($templateTableName)
            {
                $queryParts['leftJoins'][] = '`' . $templateTableName . '` AS `x` ON o.id = x.object_id';
            }
        }


	    if ($visibleOnly)
	    {
	       $queryParts['where'][] .= 'o.visible';
	    }

		return $queryParts;
    }

    /**
     * get descendants from one or more parents
     *
     * @param int/array $parentIds
     * @param string/array $templateNames
     * @param boolean $templateNamesLike
     * @param boolean $visibleOnly
     * @param string/array $orderBy
     * @param int $itemsPerPage
     * @param int $pageNo
     * @return pagedObjectList
     */
	public static function getDescendants ($ancestorIds = array(), $templateNames = array(), $templateNamesLike = false, $visibleOnly = false, $orderBy = null, $itemsPerPage = null, $pageNo = null)
	{
		// get query
		$queryParts = self::getDescendantsQueryParts($ancestorIds, $templateNames, $templateNamesLike, $visibleOnly, $orderBy);
		if(!$queryParts)
		{
			return null;
		}
		// get objects
		$result = new pagedObjectList($queryParts, $itemsPerPage, $pageNo);
		return $result;
	}

    /**
     * get visible descendants from one or more parents
     *
     * @param int/array $parentIds
     * @param string/array $templateNames
     * @param boolean $templateNamesLike
     * @param string/array $orderBy
     * @param int $itemsPerPage
     * @param int $pageNo
     * @return pagedObjectList
     */
	public static function getVisibleDescendants ($ancestorIds = array(), $templateNames = array(), $templateNamesLike = false, $orderBy = null, $itemsPerPage = null, $pageNo = null)
	{
        return self::getDescendants ($ancestorIds, $templateNames, $templateNamesLike, true, $orderBy, $itemsPerPage, $pageNo);
	}
	
	/**
     * get first visible descendant from one or more parents
     *
     * @param int/array $parentIds
     * @param string/array $templateNames
     * @param boolean $templateNamesLike
     * @param string/array $orderBy
     * @return xml_template|NULL
     */
	public static function getFirstVisibleDescendant( $ancestorIds = array(), $templateNames = array(), $templateNamesLike = false, $orderBy = null )
	{
        $list = self::getDescendants ($ancestorIds, $templateNames, $templateNamesLike, true, $orderBy );
		if( is_object( $list ) )
		{
			return $list->first();
		}
		return NULL;
	}

    /**
     * build objects query
     *
     * @param string/array $templateNames
     * @param boolean $templateNamesLike
     * @param boolean $visibleOnly
     * @param string/array $orderBy
     * @return array
     */
    public static function getObjectsQueryParts($templateNames = array(), $templateNamesLike = false, $visibleOnly = false, $orderBy = null)
    {
		if(is_null($orderBy))
		{
			 $orderBy = '
	           o.order_nr
		   ';
		}


		$queryParts = array
		(
		   'select'  => 'o.*',
		   'from'    => '`objects` AS `o`',
		   'orderBy' => $orderBy
		);

        $templatesCondition = self::getTemplatesCondition('o', $templateNames, $templateNamesLike);
	    if ($templatesCondition)
	    {
	       $queryParts['where'][] = $templatesCondition;
	    }


		// auto-join xo table (only join if exactly one template is needed)
		if (
            (!empty($templateNames))
            &&
            (!$templateNamesLike)
            &&
            (
                (
                    (is_string($templateNames))
                    &&
                    ($templateName = $templateNames) // assign template name from string
                )
                ||
                (
                    (is_array($templateNames))
                    &&
                    (count($templateNames) == 1)
                    &&
                    ($templateName = current($templateNames))  // assign template name from array
                    &&
                    (is_string($templateName))
                )
            )
        )
        {
            // $templateName now contains the name of the only template passed

            $templateTableName = leafObject::getTemplateTable($templateName);
            if ($templateTableName)
            {
                $queryParts['leftJoins'][] = '`' . $templateTableName . '` AS `x` ON o.id = x.object_id';
            }
        }


	    if ($visibleOnly)
	    {
	       $queryParts['where'][] .= 'o.visible';
	    }

		return $queryParts;
    }

    /**
     * get objects
     *
     * @param string/array $templateNames
     * @param boolean $templateNamesLike
     * @param boolean $visibleOnly
     * @param string/array $orderBy
     * @param int $itemsPerPage
     * @param int $pageNo
     * @return pagedObjectList
     */
	public static function getObjects ($templateNames = array(), $templateNamesLike = false, $visibleOnly = false, $orderBy = null, $itemsPerPage = null, $pageNo = null)
	{
		// get query
		$queryParts = self::getObjectsQueryParts($templateNames, $templateNamesLike, $visibleOnly, $orderBy);
		if(!$queryParts)
		{
			return null;
		}
		// get objects
		$result = new pagedObjectList($queryParts, $itemsPerPage, $pageNo);
		return $result;
	}


    /**
     * get object id from object or id
     *
     * @param int/leaf_object_module $objectOrId
     * @return int
     */
    public static function getObjectId( $objectOrId )
    {
        if ($objectOrId instanceof leaf_object_module)
        {
            if (!empty($objectOrId->object_data['id']))
            {
                return $objectOrId->object_data['id'];
            }
        }
        return (int) $objectOrId;
    }

    public static function getAncestorIds( $objectOrId, $useCache = false )
    {
        $objectId = self::getObjectId( $objectOrId );
		
		if( $useCache )
		{
			if( empty( self::$ancestorCache ) )
			{
				$qp = array(
					'select' => array(
						'oa.object_id',
						'oa.ancestor_id',
					),
					'from'   => '`object_ancestors` AS oa',
					'orderBy' => 'oa.level ASC'
				);
				
				$r = dbQuery( $qp );
				
				while( $item = $r->fetch() )
				{
					self::$ancestorCache[ $item['object_id'] ][] = $item['ancestor_id'];
				}
			}
			
			$ancestors = get( self::$ancestorCache, $objectId, array() );
		}
		else
		{
			$qp = array(
				'select' => array(
					'oa.ancestor_id',
				),
				'from'   => '`object_ancestors` AS oa',
				'where' => array(
					'oa.object_id = ' . $objectId
				),
				'orderBy' => 'oa.level ASC'
			);
			
			$ancestors = dbgetall($qp, null, 'ancestor_id');
		}
		
        return $ancestors;
    }


    public static function getChildByRewriteName( $parentOrId, $rewriteName, $template = null, $params = array())
    {
        $objectId = self::getObjectId($parentOrId);
        if (!ispositiveint($objectId))
        {
            return null;
        }
        $rewriteName = trim($rewriteName);
        if (strlen($rewriteName) < 1)
        {
            return null;
        }

        $qp = self::getChildrenQueryParts( $objectId, $template );
        $qp['where'][] = '`rewrite_name` = "' . dbse($rewriteName) . '"';
        $qp['limit'] = 1;
        $objectRow = dbGetRow ($qp);

        if (!$objectRow)
        {
            return null;
        }

        if (!empty($params['returnId']))
        {
            return $objectRow['id'];
        }

        $object = _core_load_object( $objectRow );
        if (!$object)
        {
            return null;
        }
        return $object;
    }

}
?>
