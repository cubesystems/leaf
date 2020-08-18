<?php
class leafEmailHeader extends aliasSingleton
{
	protected 
		$name, $email;
	
	protected $fieldsDefinition = array
	(
		'name' 	=> array( 'not_empty' => true ),
		'email' => array( 'not_empty' => true ),
	);
}
?>