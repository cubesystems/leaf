<?php
class group
{

    function dynamic_output(& $module = null)
    {
		if(leaf_get('path_part'))
		{
			$module=_core_load_module('leaf_rewrite');
			$module->error_404();		
		}

        $targetId = null;
        if (!empty($this->object_data['data']['target']))
        {
            $targetId = $this->object_data['data']['target'];
        }
        else
        {
            $firstVisibleChild = objectTree::getFirstVisibleChild( $this->object_data['id'] );
            if ($firstVisibleChild)
            {
                $targetId = $firstVisibleChild->object_data['id'];
            }
        }

        if ($targetId)
        {
            $targetUri = orp($targetId);
            if (!$targetUri)
            {
                $targetUri = WWW; // redirect to root
            }
            leafHttp::redirect($targetUri);
        }
    }

}
?>
