<?php
class emails extends leafBaseModule
{
	public $default_output_function = 'edit';

	public function __construct()
	{
		if( !empty( $_GET['email'] ) )
		{
			loadClassIfExists( $_GET['email'] );
			if( class_exists( $_GET['email'], false ) )
			{
				$instance = new $_GET['email'];
				if( is_a( $instance, 'leafEmail' ) )
				{
					$this->mainObjectClass = $_GET['email'];
				}
			}
		}
		if( empty( $this->mainObjectClass ) )
		{
			trigger_error( 'invalid e-mail class specified', E_USER_ERROR );
		}
		$this->loadResourcePack('richtext');
		
		$result = parent::__construct();
		
		$this->setActiveMenuItem( $this->getSubmenuEntryName() );
		return $result;
	}
	
	public function getSubmenuEntryName()
	{
		return lcfirst( substr( $this->mainObjectClass, 2 ) ); // TODO: proper clipping
	}
	
	public function all( $extra = array() )
	{
		return;
	}
	
	public function edit()
	{
		$assign = parent::edit();
		$_GET['returnOnSave'] = $this->getModuleUrl() . '&do=edit&email=' . $this->mainObjectClass;
		$assign['variables'] = array
		(
			'variables' => call_user_func( array( $this->mainObjectClass, 'getVarsString' ) ),
		);
		return $assign;
	}
}
?>