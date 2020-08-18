<?php
function smarty_modifier_lookup($value='', $from = array(), $key = null)
{
	if (array_key_exists($value, $from))
	{
		if(is_null($key))
		{
			return $from[$value];
		}
		else
		{
			return $from[$value][$key];
		}
	}
	else
	{
		return '';
	}
}
?>