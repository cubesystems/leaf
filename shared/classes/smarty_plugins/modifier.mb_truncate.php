<?php

function smarty_modifier_mb_truncate($string, $arg1 = null, $arg2 = null, $arg3 = null, $break_words = false, $middle = false)
{
    // two possible argument orders
    if (is_string($arg1))
    {
        // function smarty_modifier_mb_truncate($string, $charset='UTF8', $length = 80, $etc = '...', $break_words = false, $middle = false
        $charset = $arg1;
        $length  = $arg2;
        $etc     = $arg3;
    }
    elseif (is_int($arg1))
    {
        // preferred order
        // function smarty_modifier_mb_truncate($string, $length = 80, $etc = '...', $charset = 'UTF8', $break_words = false, $middle = false
        $length  = $arg1;
        $etc     = $arg2;
        $charset = $arg3;
    }

    if (is_null($length))
    {
        $length = 80;
    }

    if (is_null($charset))
    {
        $charset = 'UTF8';
    }

    if (is_null($etc))
    {
        $etc = '…';
    }

    if ($length == 0)
    {
        return '';
    }

    if (mb_strlen($string) > $length)
    {
        $length -= min($length, mb_strlen($etc));
        if (!$break_words && !$middle)
        {
            $string = preg_replace('/\s+?(\S+)?$/', '', mb_substr($string, 0, $length + 1, $charset));
        }
        if (!$middle)
        {
            return mb_substr($string, 0, $length, $charset) . $etc;
        }
        else
        {
            return mb_substr($string, 0, $length / 2, $charset) . $etc . mb_substr($string, -$length / 2, $charset);
        }
    }
    else
    {
        return $string;
    }
}


?>
