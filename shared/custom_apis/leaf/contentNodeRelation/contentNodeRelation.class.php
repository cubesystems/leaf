<?php
class contentNodeRelation extends leafBaseObject
{
	const tableName = 'contentNodeRelations';
	
	protected $groupId, $languageRootId, $nodeId, $add_date;
	
	// properties needed to trigger custom save method
	// ...
	
	protected $fieldsDefinition = array
	(
		'groupId'        => array( 'not_empty' => true ),
		'languageRootId' => array( 'not_empty' => true ),
		'nodeId'         => array( 'not_empty' => true ),
		
		// relations, this information is stored in other tables
		// ...
	);
	
	public static function _autoload( $className )
    {
		parent::_autoload( $className );
        
		$_tableDefsStr = array
		(
	        self::tableName => array
	        (
	            'fields' =>
	            '
	                id             int auto_increment
					groupId        int
					languageRootId int
					nodeId         int
					add_date       datetime
	            '
	            ,
	            'indexes' => '
	                primary id
					index groupId
					index languageRootId
					index nodeId
	            '
	        ),
	    );
	   
	   dbRegisterRawTableDefs( $_tableDefsStr );
    }
	
	// relations
	protected $group;
	protected $objectRelations = array
	(
		'group'  => array( 'key' => 'groupId',  'object' => 'contentNodeGroup' ),
	);

	public function getTemplate()
	{
		return $this->__get('group')->template;
	}
	
	public function getNode()
	{
		if( $this->hasInCache( 'node' ) == false )
		{
			$node = _core_load_object( $this->nodeId );
			$this->storeInCache( 'node', $node );
		}
		return $this->cache['node'];
	}
	
	public function getParentNode()
	{
		if( $this->hasInCache( 'parentNode' ) == false )
		{
			$node = _core_load_object( $this->getNode()->object_data['parent_id'] );
			$this->storeInCache( 'parentNode', $node );
		}
		return $this->cache['parentNode'];
	}
	
	public function getNodeDisplayString()
	{
		return $this->getNode()->object_data['name'];
	}
	
	public function getRelated()
	{
		if( $this->hasInCache( 'related' ) == false )
		{
			$params = array( 'groupId' => $this->groupId );
			$collection = self::getCollection( $params );
			
			//debug( $collection );
			//debug( $this->id );
			
			$this->storeInCache('related', $collection );
		}
		return $this->cache['related'];
	}
	
	public function getRelatedIn( $languageRootId )
	{
		foreach( $this->getRelated() as $relation )
		{
			if( $relation->languageRootId == $languageRootId )
			{
				return $relation;
			}
		}
		return NULL;
	}
	
	public function getPositions()
	{
		if( $this->hasInCache( 'positions' ) == false )
		{
			$positions = array();
			
			$languageRoots = objectTree::getChildren( 0, 'language_root' );
			foreach( $languageRoots as $languageRoot )
			{
				$position = array();
				$position['languageRoot'] = $languageRoot;
				$position['relation'] = $this->getRelatedIn( $languageRoot->object_data['id'] );
				
				$positions[] = $position;
			}
			//debug( $positions );
			
			$this->storeInCache('positions', $positions );
		}
		return $this->cache['positions'];
	}
	
	public function getParentNodeIdIn( $languageRootId )
	{
		$id = NULL;
		$parent = $this->getParentNode();
		
		if( $parent->object_data['parent_id'] == 0 )
		{
			$id = $languageRootId;
		}
		elseif( $parent->isInAnyRelation() )
		{
			$relation = self::getFor( $parent );
			
			//debug( $relation->id );
			
			$foreign = $relation->getRelatedIn( $languageRootId );
			if( $foreign !== NULL )
			{
				$id = $foreign->nodeId;
			}
		}
		
		return $id;
	}

	/********************* actions *********************/
	
	public function delete()
	{
		// delete group once it has no relations in it
		$group = NULL;
		if( count( $this->getRelated() ) <= 1 )
		{
			$group = $this->__get( 'group' );
		}
		
		// delete all groups of descendant nodes
		$parts = objectTree::getDescendantsQueryParts( $this->nodeId );
		$parts['select'] = 'g.*';
		$parts['rightJoins'][] = self::tableName . ' as rel ON o.id=rel.nodeId';
		$parts['rightJoins'][] = contentNodeGroup::tableName . ' as g ON rel.groupId=g.id';
		
		$groups = new pagedObjectCollection( 'contentNodeGroup', $parts );
		foreach( $groups as $descendantGroup )
		{
			$descendantGroup->delete();
		}
		
		$result = parent::delete();
		
		if( is_object( $group ) )
		{
			$group->delete();
		}
		return $result;
	}

	
	public static function isInAnyRelation( $nodeId )
	{
		$params = array( 'nodeId' => $nodeId );
		$collection = self::getCollection( $params );
		if( count( $collection ) > 0 )
		{
			return true;
		}
		return false;
	}
	
	// get //
	
	public static function getLanguageRootIdFor( $nodeOrId )
	{
		$nodeId = $nodeOrId;
		if( is_object( $nodeOrId ) )
		{
			$nodeId = $nodeOrId->object_data['id'];
		}
		$languageRoot = objectTree::getFirstAncestor( $nodeId, 'language_root' );
		$languageRootId = $languageRoot->object_data['id'];
		return $languageRootId;
	}
	
	// actions //
	
	public static function createGroup( $nodeOrId )
	{
		$node = $nodeOrId;
		if( !is_object( $node ) )
		{
			$node = _core_load_object( $node );
		}
		
		if( !is_object( $node ) )
		{
			return false;
		}
		
		$languageRootId = self::getLanguageRootIdFor( $node );
		
		// create group
		$group = getObject( 'contentNodeGroup', 0 );
		$variables = array
		(
			'template' => $node->object_data['template'],
		);
		$group->variablesSave( $variables );
		
		// create relation
		$relation = getObject( __CLASS__, 0 );
		$variables = array
		(
			'groupId' 		 => $group->id,
			'languageRootId' => $languageRootId,
			'nodeId' 		 => $node->object_data['id'],
		);
		$relation->variablesSave( $variables );
		
		return true;
	}
	
	public static function linkUp( $veteranNodeId, $nuggetNodeId )
	{
		//debug( $veteranNodeId, 0 );
		//debug( $nuggetNodeId, 0 );
		
		$oldRelation = self::getFor( $veteranNodeId );
		if( is_object( $oldRelation ) )
		{
			$newRelation = getObject( __CLASS__, 0 );
			$variables = array
			(
				'groupId' 		 => $oldRelation->groupId,
				'languageRootId' => self::getLanguageRootIdFor( $nuggetNodeId ),
				'nodeId' 		 => $nuggetNodeId,
			);
			$newRelation->variablesSave( $variables );
			
			
			
			return true;
		}
		return false;
	}
	
	public static function clearRelationsFor( $nodeOrId )
	{
		$relation = self::getFor( $nodeOrId );
		if( is_object( $relation ) )
		{
			$relation->delete();
		}
		return true;
	}
	
	/********************* collection related methods *********************/
	
	public static function getFor( $nodeOrId )
	{
		$nodeId = $nodeOrId;
		if( is_object( $nodeId ) )
		{
			$nodeId = $nodeId->object_data['id'];
		}
		
		$params = array( 'nodeId' => $nodeId );
		$collection = self::getCollection( $params );
		if( count( $collection ) > 0 )
		{
			$collection = (array)$collection;
			$item = array_shift( $collection );
			return $item;
		}
		return NULL;
	}
	
	public static function getCollection( $params = array(), $itemsPerPage = NULL, $pageNo = NULL )
	{
		$queryParts['select'][]   = 't.*';
		$queryParts['from'][]     =  '`' . self::getClassTable( __CLASS__ ) . '` AS `t`';
		$queryParts['orderBy'][]  = 't.id ASC';

	    if( is_array( $params ) )
	    {
			if( !empty( $params['groupId'] ) )
			{
				$queryParts['where'][] = 't.groupId = "' . dbSE( $params['groupId'] ) . '"';
			}
			if( !empty( $params['languageRootId'] ) )
			{
				$queryParts['where'][] = 't.languageRootId = "' . dbSE( $params['languageRootId'] ) . '"';
			}
			if( !empty( $params['nodeId'] ) )
			{
				$queryParts['where'][] = 't.nodeId = "' . dbSE( $params['nodeId'] ) . '"';
			}
			
	    }

		return new pagedObjectCollection( __CLASS__, $queryParts, $itemsPerPage, $pageNo );
	}
}
?>