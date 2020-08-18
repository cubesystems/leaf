<?php
class leafFlash
{
    const SESSION_NAME = 'flashes';

	public static function gc()
    {
		if (!session_id())
		{
			session_start();
		}
		
		if (!empty($_SESSION[self::SESSION_NAME]))
		{
			foreach ($_SESSION[self::SESSION_NAME] as $name => $meta)
			{
				if ($meta['expired'])
				{
					unset($_SESSION[self::SESSION_NAME][$name]);
				}
				else
				{
					$_SESSION[self::SESSION_NAME][$name]['expired'] = true;
				}
			}
		}
	}
	
	public static function get($name)
    {
        if(isset($_SESSION[self::SESSION_NAME][$name]))
        {
            $value = $_SESSION[self::SESSION_NAME][$name]['value'];
            return $value;
        }
	}
	
	public static function set($name, $value)
    {
		$_SESSION[self::SESSION_NAME][$name] = array (
			'value' => $value,
			'expired' => false, 
		);
	}

	public static function remove($name)
    {
		unset($_SESSION[self::SESSION_NAME][$name]);
	}    
}
