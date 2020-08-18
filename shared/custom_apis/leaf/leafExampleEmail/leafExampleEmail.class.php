<?php
class leafExampleEmail extends leafEmail
{
	public static function getVars()
	{
		$vars = array( 'firstName', 'lastName', 'personCode' );
		return $vars;
	}
}
?>