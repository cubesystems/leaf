<?php
class robots {

	function dynamic_output(& $module = null)
	{
		$this->setOutputMode('text');
        
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
        
        $this->object_data['data']['allowBots'] = $allowBots;
        
        if (!$allowBots && !DEV_MODE)
        {
            return;
        }
        
		$q = '
		SELECT
			o.id
		FROM
			`objects` `o`
		WHERE
			o.template = "siteServices/sitemap"
		';
		$this->object_data['data']['sitemap'] = dbGetOne($q);

		$disallow = array();

		if (!empty($this->object_data['data']['disallow']))
		{
            foreach ($this->object_data['data']['disallow'] as $item)
            {
                if (!ispositiveint($item['link']))
                {
                    continue;
                }

                $url = strtolower( orp($item['link']) );
                $url = substr( $url, strlen(WWW) - 1 );
                $disallow[] = $url;
            }
        }

        $this->object_data['data']['disallowRelative'] = $disallow;
	}

}
?>
