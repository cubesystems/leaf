<?php
function smarty_function_input($params, & $smarty)
{
    require_once(SHARED_PATH . 'classes/input/input.class.php');
	$input = new input( $params );
	return $input->output();
}
?>