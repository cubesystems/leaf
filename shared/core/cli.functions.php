<?php
/**
 * Shows a timestamp and sends the arguments as a concatenated string to 
 * current output stream.
 * 
 * @param string $message,...
 */
function message()
{
    $options = getopt('v::');
    if (isset($options['v']))
    {
        $argumentList = func_get_args();
        printf('@[%s]: %s' . "\r\n", date('Y-m-d H:i:s'), implode('', $argumentList));
    }
}

/**
 * Returns difference between two timestamps in human readable format.
 * 
 * @param int $start Start timestamp
 * @param int $end End timestamp
 */
function getDuration($start, $end)
{
	$difference = abs($end - $start);
	$timeIncrements = array (
		3600,
		60,
		1,
	);
			
	$return = array ();
	$period = $difference;
	while (list(, $length) = each($timeIncrements))
	{
		$value = (int) ($period / $length);
		if ($value > 0)
		{
			$period = ($period % $value);
		}
				
		$return[] = sprintf('%02s', $value);
	}
	return implode(':', $return);
}
