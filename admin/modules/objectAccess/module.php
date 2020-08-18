<?
class objectAccess extends leaf_module{

	var $module_path       = 'modules/objectAccess/';
	var $actions           = array('save_access_rules');
	var $output_actions    = array('edit_object');
	var $header_string     = '?module=objectAccess';

	public $assigns = array('js');
	
	protected static $descendantCache = array();

	function __construct()
	{
		parent::leaf_module();
		_core_add_css($this->module_www . 'style.css');
		_core_add_css(WWW . 'styles/leafTable.css');
	}

	function output()
	{
		return parent::output();
	}

	function view()
	{
		//properties
		$output = array();

		return $this->edit_object();
	}


	function edit_object()
	{
		$objectId = get($_GET, 'object_id', null);
		if (
            (!ispositiveint($objectId))
            ||
            (!($object = _core_load_object( $objectId )))
        )
		{
		    return false;
		}

		if (!$this->userHasAccessToObject($objectId))
		{
            trigger_error('Access denied by object access rules.', E_USER_ERROR);
		}

	    $assign = array
	    (
            '_module'     => $this,
            'objectId'    => $objectId,
            'targetTypes' => self::getTargetTypes()
        );

        // load users and groups
		$assign['subjects'] = array
		(
            'Group' => $this->getSubjectList('Group'),
            'User'  => $this->getSubjectList('User')
		);

        // load access data
		$accessRules = array();
		$qp = array
		(
            'select'  => '*',
            'from'    => '`objectAccess`',
            'where'   => '`objectId` = ' . $objectId,
            'orderBy' => '`targetType`, `targetId`'
		);

		$result = dbgetall($qp);
		foreach ($result as $row)
		{
		    $targetType  = $row['targetType'];
		    $targetId    = $row['targetId'];

		    $accessRules[$targetType][$targetId] = $row['value'];
		}
		$assign['accessRules'] = $accessRules;

// 		debug ($assign);


		$template= new leaf_smarty($this->module_path .  'templates/');
		$template->Assign($assign);

		return $template->fetch('edit_access.tpl');
	}


	function save_access_rules()
	{
		$objectId = get($_POST, 'objectId',null);
		if (
            (!ispositiveint($objectId))
            ||
            (!($object = _core_load_object( $objectId )))
        )
		{
		    return false;
		}

		if (!$this->userHasAccessToObject($objectId))
		{
            trigger_error('Access denied by object access rules.', E_USER_ERROR);
		}


		$rules = array();
		$targetTypes = self::getTargetTypes();
		if (empty($targetTypes))
		{
		    return;
		}

		$targetTypePattern = implode( '|', $targetTypes );
		$pattern = '/^(allow)(?P<targetType>(' . $targetTypePattern . '))(?P<targetId>\d+)$/';

		foreach ($_POST as $key => $value)
		{
		    $matches = array();
		    if (!preg_match($pattern, $key, $matches))
		    {
		        continue;
		    }
		    $rule = array
		    (
                'objectId'   => $objectId,
                'targetType' => $matches['targetType'],
                'targetId'   => $matches['targetId'],
                'value'      => $value
		    );
		    $rules[] = $rule;
		}

		dbquery('DELETE FROM `objectAccess` WHERE `objectId` = ' . $objectId);
		if (!empty($rules))
		{
            dbinsert('objectAccess', $rules);
		}

		$this->header_string = '?module=content&object_module=objectAccess&object_id=' . $objectId;
		return true;
	}


	protected function getSubjectList( $type )
    {
        $list = array();

        if( $type == 'User' )
        {
            foreach(leafAuthorization::listUsers() as $item)
            {
                $list[$item->id] = $item;
            }
        }
        elseif( $type == 'Group' )
        {
            foreach(leafAuthorization::listGroups() as $item)
            {
                $list[$item->id] = $item;
            }
        }

		return $list;
	}

	public static function getTargetTypes()
	{
	    if (!class_exists('content', false))
	    {
            // tabledef stored in content module
	        $content = _core_load_module('content');
	    }
        return dbGetEnumValuesFromTableDef('objectAccess', 'targetType');
	}

	public function userHasAccessToObject( $objectId )
	{
	    if (!ispositiveint($objectId))
	    {
	        return false;
	    }

        $ancestorIds = objectTree::getAncestorIds( $objectId, $useCache = true );
        $objectIds = array_merge( array( $objectId ), array_reverse( $ancestorIds ) );

        if (empty($objectIds))
        {
            return true;
        }

        $userId  = $_SESSION[SESSION_NAME]['user']['id'];
        $groupId = call_user_func(leafAuthorization::getUserClass() . '::getCurrentUserGroupId');
        
        $q = '
             SELECT
                a.value
             FROM
                `objectAccess` AS a
                LEFT JOIN
                `object_ancestors` AS oa
                    ON
                        a.objectId = oa.ancestor_id
                        AND
                        oa.object_id = ' . $objectId . '
            WHERE
                (a.objectId IN (' . implode(', ', $objectIds) . '))
                AND
                (
                    (a.targetType = "General")
                    OR
                    (   (a.targetType = "Group") AND (a.targetId   = "' . $groupId . '") )
                    OR
                    (   (a.targetType = "User")  AND (a.targetId   = "' . $userId . '") )
                )
            ORDER BY
                IFNULL(oa.level, ~0 >> 32) DESC,
                targetType DESC

        ';
        // debug ($q);
        $result = dbgetone($q);

        if ($result === false) // no rows returned, no rules defined, allow access
        {
            return true;
        }

        return (bool) $result;
	}

	public function userHasAccessToAllDescendants( $ancestorId )
	{
	    // TODO: Remove this function
		return true;
		
		
		if( empty( self::$descendantCache ) )
		{
			// this method ignores any inherited ancestor rules
			$descQp = objectTree::getDescendantsQueryParts( 0 );
			$descQp['select'] = '`o`.`id`, `oa`.`ancestor_id`';
			$descQp['where'] = null;
			
			self::$descendantCache = dbGetAll( $descQp, $key = 'id', $value = 'ancestor_id', $dbLinkName = null, $eachCallback = null, $keyArrays = true );
		}
		
		$descendantIds = get( self::$descendantCache, $ancestorId, array() );
        $descendantIds = array_filter( $descendantIds );
		
        if( empty( $descendantIds ) )
        {
            return true;
        }
		
        $userId  = $_SESSION[SESSION_NAME]['user']['id'];
        $groupId = call_user_func(leafAuthorization::getUserClass() . '::getCurrentUserGroupId');

        if ($userId != 10)
        {
            return true;
        }


        $q = '
            SELECT
                a.objectId,
                LEFT(CONCAT(IFNULL(CAST(us.value as CHAR),""), IFNULL(CAST(gr.value as CHAR),""), IFNULL(CAST(ge.value as CHAR),"")), 1) AS ownComputedValue
            FROM
                `objectAccess` AS a
                LEFT JOIN
                    `objectAccess` AS ge
                    ON
                    (
                        a.objectId = ge.objectId
                        AND
                        ge.targetType = "General"
                        AND
                        ge.targetId = 0
                    )
                LEFT JOIN
                    `objectAccess` AS gr
                    ON
                    (
                        a.objectId = gr.objectId
                        AND
                        gr.targetType = "Group"
                        AND
                        gr.targetId = "' . $groupId . '"
                    )
                LEFT JOIN
                    `objectAccess` AS us
                    ON
                    (
                        a.objectId = us.objectId
                        AND
                        us.targetType = "User"
                        AND
                        us.targetId = "' . $userId . '"
                    )
            WHERE
                (a.objectId IN (' . implode(', ', $descendantIds ) . '))
            GROUP BY
                a.objectId
            HAVING ownComputedValue = 0
            ORDER BY null
        ';

        $result = dbgetrow( $q );
        if ($result === false)
        {
            // no objects with ownComputedValue = 0
            return true;
        }

        return false;


	}



}
?>
