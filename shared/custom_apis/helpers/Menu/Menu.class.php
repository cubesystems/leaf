<?

/*
Usage:

	// Create menu object
	$menu = Menu::create( $this->object );
	
	// Set templates witch will bee skipped in menu
	$menu->setSkipTemplates( $templates );
	
	// Get Menu by type
	$menu->getSide();
	$menu->getFooter();
	
	// Get language menu
	$menu->getLanguages();
	
	// Get title for opened object
	$menu->getTitle()
	
*/



class Menu
{
    const TYPE_MAIN     = 'main';
    const TYPE_FOOTER   = 'footer';
    
    // $skipTemplates may contain template names to exclude from the menu
    public $skipTemplates = array();
    
    //public $authorizedTemplates = array(
    public $sideMenuTemplates = array();
    
    // set $includeFileObjects to false to include only xml_templates
    protected $includeFileObjects = false;
    
    // set $expandAll to true to expand all branches ( not only the active/open path )
    protected $expandAll = false;
    
    // set $expandAllUpToLevel to int to limit the level up to which all branches are expanded. null to expand all levels
    protected $expandAllUpToLevel = null;
    
    // set $maxLevel to int to limit menu depth. null to disable (load all)
    protected $maxLevel = null;
    
    protected $object = null;
    
    protected $menu = array();
    
    protected $pathParts = null;
    
    protected $objectsTreeIndex = array();
    protected $objectsTree = array();
    
    
    public static function listMenuTypes()
    {
        return array(
            self::TYPE_MAIN,
            self::TYPE_FOOTER,
        );
    }
    
    
    public static function isValidMenuType( $type )
    {
        return in_array( $type, self::listMenuTypes() );
    }
    
    
    public function __construct( $object )
    {
        $this->setObject( $object );
        $this->pathParts = leaf_get( 'path_part' );
    }
	
	
    public function __call( $method, $arguments )
    {
        $type = $method;
        
        $ucTypes = array_map( 'ucfirst', self::listMenuTypes() );
        
        if( preg_match( '/get(?P<type>' . implode( '|', $ucTypes ) . ')Menu/ui', $method, $match ) )
        {
            return $this->getMenuByType( lcfirst( $match['type'] ) );
        }
        
        return false;
    }
    
	
    public function setObject( $object )
    {
        $this->object = $object;
        return $this->loadObjectsTree();
    }

	
    public function setSkipTemplates( $templates = array() )
    {
        $this->skipTemplates = $templates;
    }

    public function getSkipTemplates()
    {
        return $this->skipTemplates;
    }

	public function setProperties( $props = array() )
    {
        if(
            (empty($props))
            ||
            (!is_array($props))
        )
        {
            return null;
        }
        foreach($props as $key => $value)
        {
            if(property_exists(__CLASS__, $key))
            {
                $this->{$key} = $value;
            }
        }
    }
    
	public function loadObjectsTree( )
	{
	    if( !$this->object )
        {
            return false;
        }
        
        $objectData = $this->object->object_data;
		$this->objectsTreeIndex[] = $objectData['id'];
		$this->objectsTree[ $objectData['id'] ] = $objectData;
		
        $q = '
    		SELECT
    			obj.*
    		FROM
    			`' . DB_PREFIX . 'object_ancestors` `anc`
    		LEFT JOIN
    			`' . DB_PREFIX . 'objects` `obj` ON obj.id = anc.ancestor_id
    		WHERE
    			anc.object_id = "' . dbSE( $objectData['id'] ) . '"
    		ORDER BY
    			anc.level DESC
		';
        
		$r = dbQuery( $q );
        
		while( $item = $r->fetch() )
		{
			if( !empty( $item['data'] ) )
			{
				$item['data'] = unserialize( $item['data'] );
			}
            
			$this->objectsTreeIndex[] = $item['id'];
			$this->objectsTree[$item['id']] = $item;
		}
        
		return true;
	}
    
    
    public function generateMenu()
    {
        if (
            ( empty( $this->objectsTreeIndex ) )
            ||
            ( !is_array( $this->objectsTreeIndex ) )
        )
        {
            return;
        }
        
        // active / open object IDs
        $objectIds = array_reverse( $this->objectsTreeIndex );
        
        // $objectMap array will store references to all loaded objects with IDs as keys
        $objectMap = array();

        $templatesCondition = objectTree::getTemplatesCondition( 'o', $this->skipTemplates, false, true );
        $typeCondition      = ( $this->includeFileObjects ) ? '' : '`o`.`type` = 22';

        // init loop variables
        $safety = 100;
        $level  = 1; // level 1 is directly under root

        $rootLevelId = $objectIds[0];
        $parentIds = array($rootLevelId); // first parent set contains only the active object of 0th level

        $menu = array();

        do
        {
            $queryParts = objectTree::getVisibleChildrenQueryParts( $parentIds );
            if (!empty($typeCondition))
            {
                $queryParts['where'][] = $typeCondition;
            }
            if (!empty($templatesCondition))
            {
                $queryParts['where'][] = $templatesCondition;
            }

            $activeObjectId = (isset($objectIds[$level])) ? $objectIds[$level] : null;

            // fetch all children for all parents
            $children = new pagedObjectList( $queryParts, null, null );

            $nextParentIds = array();
            // collect next level parent ids and mark active object
            foreach ($children as $child)
            {
                $objectId = $child->object_data['id'];

                // mark active
                $objectIsActive = ($objectId == $activeObjectId);
                if ($objectIsActive)
                {
                    $child->object_data['active'] = true;
                }

                // locate parent
                $parent = null;
                $parentId = $child->object_data['parent_id'];
                if ($parentId == $rootLevelId)
                {
                    // for the first level copy all children to $menu
                    $targetArray = & $menu;
                }
                else
                {
                    // for deeper levels locate parent via object map
                    if (!isset($objectMap[$parentId])) // wtf?
                    {
                        continue;
                    }
                    $parent = & $objectMap[$parentId];
                    if (empty($parent->object_data['children']))
                    {
                        $parent->object_data['children'] = array();
                    }
                    $targetArray = & $parent->object_data['children'];
                }

                $targetArray[] = & $child;

                // store id reference
                $objectMap[$objectId] = & $child;

                // add to next level parent ids
                if ($level != $this->maxLevel) // not the last level
                {
                    if (
                        ($objectIsActive)                // always add active object
                        ||
                        (
                            ($this->expandAll)                 // add other objects if expanding all branches
                            &&
                            (
                                (is_null($this->expandAllUpToLevel))   // and the limit is not reached
                                ||
                                ($this->expandAllUpToLevel >= $level)
                            )
                        )
                    )
                    {
                        $nextParentIds[] = $objectId;
                    }
                }
                unset( $child, $parent, $targetArray ); // destroy references
            }

            $parentIds = $nextParentIds;
            $level++;
            $safety--;
        }
        while (
            // continue loop while
            // max level is not reached
            (
                (is_null($this->maxLevel))
                ||
                ($this->maxLevel >= $level) // here $level is the level of the _next_ iteration
            )
            &&
            // and objects exist in next level
            (!empty($parentIds))
            &&
            // and something has not gone wrong leading to an infinite loop
            ($safety)
        );
        
        
        foreach( $menu as $object )
        {
            $menuKey = self::TYPE_MAIN;
            
            if( get( $object->object_data['data'], 'showInFooter' ) )
            {
                $menuKey = self::TYPE_FOOTER;
            }
            
            $this->menu[$menuKey][] = $object;
        }
    }
    
    
    public function getMenu()
    {
        if( !$this->menu )
        {
            $this->generateMenu();
        }
        
        return $this->menu;
    }
    

    public function getMenuByType( $type )
    {
        if( !self::isValidMenuType( $type ) )
        {
            return false;
        }
        
        $menu = $this->getMenu();
        
        return get( $menu, $type );
    }
    
    
    public function getObjectsTree()
    {
        return $this->objectsTree;
    }
    
    
    // get languages from language table that have matching objects in site root level
	public function getLanguages()
	{
	    $queryParts = objectTree::getVisibleChildrenQueryParts( 0 );

	    $queryParts['select']      = 'l.*, o.id as object_id, o.name';
	    $queryParts['leftJoins'][] = '`languages` AS l ON o.rewrite_name = l.short';
	    $queryParts['where'][]     = 'l.id IS NOT NULL';
        
        $getData = $_GET;
        unset( $getData['objects_path'] );
        $getQuery = http_build_query($getData);

        $result = dbGetAll( $queryParts, 'id' );

        foreach( $result as $key => $language )
        {
            $objectRelations = contentNodeRelation::getFor( $this->object );
            
            if( $objectRelations )
            {
                $relatedObject = $objectRelations->getRelatedIn( get( $language, 'object_id' ) );
                
                if( $relatedObject )
                {
                    $node = $relatedObject->getNode();
                    $result[$key]['object_id'] = $node->object_data['id'];
                    $result[$key]['get_query'] = $getQuery;
                    $result[$key]['path_part'] = $this->pathParts;
                }
            }
        }

        $languageId = leaf_get('properties', 'language_id');
        
        if (
            ( $languageId )
            &&
            ( isset( $result[$languageId] ) )
        )
        {
            $result[$languageId]['active'] = true;
        }
        
        return $result;
	}
    
    
    public function getTitle( )
    {
        $path = $this->getPathMenu( $this->objectsTree );
        
        if (
            ( !is_array( $path ) )
            ||
            ( empty( $path ) )
        )
        {
            return null;
        }
        
        $title = array_reverse( $path, true );
        
        return $title;
    }
    
    
    public function getPathMenu( )
    {
        $skipTemplates = array( 'language_root' );
        
        if( !$this->objectsTree )
        {
            return null;
        }
        
        $keys = array_keys( $this->objectsTree );
        $keys = array_reverse( $keys );
        
        $pathMenu = array();
        
        foreach( $keys as $key )
        {
            $objectData = $this->objectsTree[ $key ];
            
            if (
                ( !$objectData['visible'] )
                ||
                ( in_array( $objectData['template'], $skipTemplates ) )
            )
            {
                continue;
            }
            
            $pathMenu[ $key ] = array(
                'id'    => $objectData['id'],
                'name'  => $objectData['name'],
            );
        }
        
        return $pathMenu;
    }
}
