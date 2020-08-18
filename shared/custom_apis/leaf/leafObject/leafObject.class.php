<?
class leafObject extends leafBaseObject{
    const tableName = 'objects';

    public static $types = array(
        '21' => array(
            'type' => 21,
            'module' => 'file',
            'name' => 'file',
        ),
        '22' => array(
            'type' => 22,
            'module' => 'xml_template',
            'name' => 'xml_template',
        ),
    );

    protected static $xmlTemplateList = null;

    public static function getTemplateList()
    {
        if(is_null(self::$xmlTemplateList))
        {
            self::$xmlTemplateList = dbGetAll('SELECT * FROM `xml_templates_list`', 'template_path' );
        }

        return self::$xmlTemplateList;
    }

    public static function getTemplateTable($template)
    {
        $list = self::getTemplateList();
        if(isset($list[$template]['table']))
        {
            return $list[$template]['table'];
        }
    }

	public static function copyTo($objects = array(), $groupId){
		$newObjects = array();
		foreach($objects as $objectId)
		{
			$object = _core_load_object(intval($objectId));
			$object->copyObject($groupId);
			$newObjects[] = $object->object_data['id'];
		}
		// update rewrite
		leafObjectsRewrite::update($newObjects, true);
		// store this update timestamp
		setValue('content_objects.last_update', time());
	}

	public static function moveTo($objects = array(), $groupId){
		foreach($objects as $objectId)
		{
			$object = _core_load_object(intval($objectId));
			$object->moveObject($groupId);
		}
		// update rewrite
		leafObjectsRewrite::update($objects, true);
		// store this update timestamp
		setValue('content_objects.last_update', time());
	}
}
?>
