<?
class leafAncestors{

	function loadObject($object_id){
		$q='
		SELECT
			`id`,
			`parent_id`
		FROM
			`' . DB_PREFIX .'objects`
		WHERE
			`id` = "'.dbSE($object_id).'"
		';
		if(($object = dbGetRow($q)) != NULL)
		{
			return $object;
		}
		else
		{
			return false;
		}
	}

	function updateAncestorData($objectId)
	{
        // 1) get all ancestor Ids
        // 2) get all descendant IDs
        // 3) calculate ancestors
        // 4) delete existing ancestor data
        // 5) generate sql inserts
        // 6) execute sql
		
        $object = $this->loadObject($objectId);
        if (!$object)
        {
            // this may happen when this method is called after an object has been deleted
            $this->deleteAncestorData( $objectId );
            return false;
        }

        // 1)
        $ancestorIds = $this->getAncestorIds($objectId);
        $commonAncestorCount = count ($ancestorIds);

        // 2)
        $descendants = $this->getDescendants($objectId);

        $objects = $descendants;

        // 3)
        // calculate ancestors foreach of the descendants
        foreach ($objects as $id => $value)
        {
            $objectRef = & $objects[$id];
            $ancestors = array();

            $currentParentId = $objectRef['parent_id'];

            while (isset($objects[$currentParentId]))
            {
                $ancestors[] = $currentParentId;
                $currentParentId = $objects[$currentParentId]['parent_id'];
            }
            $ancestors[] = $objectId; // top object

            $objectRef['ancestors'] = $ancestorIds;

            $nextLevel = $commonAncestorCount + 1;
            $ancestors = array_reverse( $ancestors );
            foreach ($ancestors as $ancestorId)
            {
                $objectRef['ancestors'][$nextLevel] = $ancestorId;
                $nextLevel++;
            }
            unset ($objectRef); // destroy reference
        }

        $objects[$objectId] = array(
            'id'        => $object['id'],
            'parent_id' => $object['parent_id'],
            'ancestors' => $ancestorIds
        );

        // 4)
        $objectIdsToCleanUp = array_keys($objects);
        $this->deleteAncestorData( $objectIdsToCleanUp );


        // 5)
        $sqlInserts = array();
        $sqlStart = '
            INSERT INTO object_ancestors
            (object_id, ancestor_id, level)
            VALUES
        ';
        foreach ($objects as $id => $value)
        {
            $objects[$id]['ancestorValueBlocks'] = array();
            foreach ($objects[$id]['ancestors'] as $level => $ancestor)
            {
                $objects[$id]['ancestorValueBlocks'][] = '(' . $id . ', ' . $ancestor . ', ' . $level . ')';
            }
            if (count($objects[$id]['ancestorValueBlocks']) < 1)
            {
                continue; // root objects have no ancestors
            }
            $sqlInserts[] = $objects[$id]['ancestorsSql'] = $sqlStart . implode (', ', $objects[$id]['ancestorValueBlocks']);
        }

        // 6)
        foreach ($sqlInserts as $sqlInsert)
        {
            dbQuery($sqlInsert);
        }

        return true;
	}

	function deleteAncestorData($objectIds = array())
	{

	    if (!is_array($objectIds))
	    {
	        $objectIds = array ($objectIds);

	    }

	    foreach ($objectIds as $key => $objectId)
	    {
	        $objectIds[$key] = (int) $objectId;
	    }

        $sql = '
            DELETE FROM object_ancestors
            WHERE object_id IN (' . implode(', ', $objectIds) . ')
        ';
        dbQuery($sql);
        return true;

	}
	function getAncestorIds($objectId)
	{
	    $ancestors = array();
        while ($parentId = $this->getParentId($objectId))
        {
            $ancestors[] = $parentId;
            $objectId = $parentId;
        }
        $reversed = array_reverse($ancestors);

        $realAncestors = array();
        $key = 1;
        foreach ($reversed as $ancestorId){
            $realAncestors[$key] = $ancestorId;
            $key++;
        }
        return $realAncestors;
	}

	function getParentId($objectId)
	{

	    $objectId = (int) $objectId;
        $sql = '
            SELECT
                parent_id
            FROM
                objects
            WHERE
                id = ' . $objectId . '
        ';
        $result = dbGetOne($sql);

        return $result;
	}

	function getDescendants($objectIds = array())
	{
	    $descendants = array();

        if (!is_array($objectIds))
        {
            $objectIds = array((int) $objectIds);
	    }
	    foreach ($objectIds as $key => $objectId)
	    {
	        $objectIds[$key] = (int) $objectId;
	    }

	    $objectIds = array_unique($objectIds);

	    if (count($objectIds) < 1)
	    {
	        return $descendants;
	    }

	    $sql = '
            SELECT
	           id,
	           parent_id
            FROM
               objects
            WHERE
               parent_id IN(' . implode(', ', $objectIds) . ')
	    ';
	    $children = dbGEtAll($sql, 'id');
	    if (!$children)
	    {
	        return $descendants;
	    }
	    $childrenIds = array_keys($children);
	    $deeperDescendants = $this->getDescendants($childrenIds);
	    $result = $children;
	    foreach ($deeperDescendants as $id => $value)
	    {
	        $result[$id] = $value;
	    }
	    //$result = array_merge($children, $deeperDescendants);
	    return $result;

	}
	
}
?>