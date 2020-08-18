<?php
class sitemap {

	protected $skip = array();
	protected $skipTemplates = array('siteServices/robots', 'siteServices/sitemap');
	protected $dontSkipTemplates = array();

	protected $loadAsObject = array();

	public function dynamic_output(& $module = null)
	{
		$this->setOutputMode('xml');
        
        
        if (
            (!defined('LEAF_ENV'))
            ||
            (LEAF_ENV == 'PRODUCTION')
        )
        {
            $allowBots = true;
        }
        else
        {
            $allowBots = false;
        }
        
        if (!$allowBots && !DEV_MODE)
        {
            $this->object_data['data']['list'] = array();
            return;
        }

        $skipTemplates = $module->getMenuSkipTemplates();

        $this->skipTemplates = array_merge( $skipTemplates, $this->skipTemplates);

        $this->skipTemplates = array_diff($this->skipTemplates, $this->dontSkipTemplates );


		// get all templates
		$q = '
		SELECT
			`template_path`,
			`lastModifiedMethod`
		FROM
			`xml_templates_list`
		WHERE
			`lastModifiedMethod` IS NOT NULL
		';
		$this->loadAsObject = dbGetAll($q, 'template_path', 'lastModifiedMethod');

		// set xml output mode
        if(!empty($this->object_data['data']['skip']))
		{
			foreach($this->object_data['data']['skip'] as $item)
			{
				$this->skip[] = $item['object'];
			}
		}
		$this->object_data['data']['list'] = $this->getTree(0);
	}

	protected function getTree($parentId)
	{
		$list = array();
		$qp = array
		(
            'select' => 'o.template, o.last_edit, o.id',
            'from'   => '`objects` AS `o`',
            'where'  => array
            (
                'o.parent_id = "' . $parentId . '"',
                'o.type = 22',
                'o.visible',
                'o.template NOT IN ("' . implode('","', $this->skipTemplates) . '")'
            ),
            'odderBy' => 'o.last_edit DESC'
		);

		$r = dbQuery($qp);
		while($item = $r->fetch())
		{
			if(!in_array($item['id'], $this->skip))
			{
				if(isset($this->loadAsObject[$item['template']]))
				{
					$obj = _core_load_object($item['id']);
					$methodName = $this->loadAsObject[$item['template']];
					$item['last_edit'] = $obj->$methodName();
				}
                $item['url'] = orp($item['id']);
                if (!isset($list[$item['url']]))
                {
                    $list[$item['url']] = $item;
                }

				if ($children = $this->getTree($item['id']))
				{
					$list = array_merge($list, $children);
				}
			}
		}
		return $list;
	}
}
?>
