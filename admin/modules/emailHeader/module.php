<?php
class emailHeader extends leafBaseModule
{
	/* public $actions = array( 'delete', 'save' );
	public $output_actions = array( 'all', 'view', 'confirmDelete', 'edit', 'saveAndRespond'); */
	public $default_output_function = 'edit';

	protected $mainObjectClass = 'leafEmailHeader';
	
	public function all( $extra = array() )
	{
		return;
	}
	
	public function edit()
	{
		$assign = parent::edit();
		$_GET['returnOnSave'] = $this->getModuleUrl() . '&do=edit';
		return $assign;
	}
}
?>