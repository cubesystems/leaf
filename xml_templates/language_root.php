<?php
class language_root
{

    function dynamic_output(& $module = null)
    {
		if(leaf_get('path_part'))
		{
			return null;
		}
        if (isset($this->object_data['data']['target']))
        {
            $targetUri = object_rewrite_path($this->object_data['data']['target']);
            if (!$targetUri)
            {
                return null;
            }
            header ('Location: ' . $targetUri);
            die();

        }
        return null;
    }
}
?>
