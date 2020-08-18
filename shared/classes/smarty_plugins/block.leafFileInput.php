<?php


function smarty_block_leafFileInput (array $params, $content, &$smarty, &$repeat)
{
    if (is_null($content)) // opening tag
    {
        return;
    }

    $params['type']    = 'leafFile';
    $params['content'] = $content;

    require_once(SHARED_PATH . 'classes/input/input.class.php');

	$input = new input( $params );
	return $input->output();

}

?>