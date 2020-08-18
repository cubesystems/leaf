<?

    function object_get_node_tree($parent_id)
	{
        if (!in_array($parent_id, leaf_get('object_parents')))
		{
            return false;
		}

        if (@in_array($parent_id, leaf_get('in_object_tree')))
		{
            return false;
		}
		
		leaf_set(array('in_object_tree', NULL), $parent_id);
        $output = array();
		
		$q = '
		SELECT
			obj.id,
			obj.name,
			obj.type,
			obj.parent_id,
			obj.visible,
			obj.createdby,
			xtl.icon_path `template_icon`,
			obj.template
		FROM
			`' . DB_PREFIX . 'objects` `obj`
		LEFT JOIN
			`xml_templates_list` `xtl` ON xtl.template_path = obj.template
		WHERE
			`parent_id` =  "' . dbSE( $parent_id ) . '"
		ORDER BY
			`order_nr`
		';
		
        $r = dbQuery($q);
		
		$objectParents = dbGetAll(
			'SELECT `id`, `parent_id` FROM `' . DB_PREFIX . 'objects` WHERE `parent_id` IN ( SELECT id FROM ' . DB_PREFIX . 'objects WHERE parent_id = "' . dbSE( $parent_id ) . '" )',
			$key = 'parent_id',
			$value = 'id',
			$dbLinkName = null,
			$eachCallback = null,
			$keyArrays = true
		);
		
		
        while( $entry = $r->fetchRow() )
		{
			if( get( $objectParents, $entry['id'] ) )
			{
				$entry['group_image'] = ( in_array( $entry['id'], leaf_get('object_parents') ) ) ? 'close' : 'open';
			}
			if(!empty($entry['template_icon']))
			{
				$entry['icon_path'] = leaf_get('objects_config', 22, 'templates_www') . $entry['template_icon'];
			}
			else
			{
				if($entry['type'] == 21)
				{
					$entry['type_icon'] = 'file';
				}
				elseif($entry['type'] == 22)
				{
					$entry['type_icon'] = 'xml_template';
				}
				$entry['icon_path'] = SHARED_WWW . 'objects/' . $entry['type_icon'] . '/icon.png';
			}
			
			$entry['module'] = leaf_get('object_types', $entry['type'], 'module');
			$entry['childs'] = object_get_node_tree($entry['id']);
			$entry['allowed'] = $entry['allDescendantsAllowed'] = true;
			
			// read access settings if content module is loaded
			if (
                (class_exists('content', false))
                &&
                (is_subclass_of('content', 'leaf_module'))
            )
            {
                $entry['allowed'] = content::userHasAccessToObject( $entry['id'] );
				
                if (!$entry['allowed'])
                {
                    $entry['allDescendantsAllowed'] = false;
                }
                else
                {
                    $entry['allDescendantsAllowed'] = content::userHasAccessToAllDescendants( $entry['id'] );
                }
            }
			
			$output[] = $entry;
        }
		
        return $output;
    }

	function object_get_parents($object_id){
		$output[] = $object_id;
            if(($object = dbGetRow('SELECT parent_id FROM ' . DB_PREFIX . 'objects WHERE id="' . $object_id . '"')) != NULL)
			{
                while($object['parent_id']!=0)
				{
                    $output[]=$object['parent_id'];
                    $object = dbGetRow('SELECT parent_id FROM ' . DB_PREFIX . 'objects WHERE id = "' . $object['parent_id'] . '"');
                }
                $output[]=$object['parent_id'];
            }
		return $output;
	}

?>
