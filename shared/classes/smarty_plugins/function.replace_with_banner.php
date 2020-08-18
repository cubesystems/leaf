<?php
/**
* Smarty plugin
* @package Smarty
* @subpackage plugins
*/


/**
 * Smarty replace_with_banner function plugin
 *
 * Type:     modifier<br>
 * Name:     replace_with_banner<br>
 * Purpose:  convert string to banner callback.
 * @author  Sascha Krot <sascha at krot dot lv>
 * @param string
 * @param string
 * @return string
*/
function smarty_function_replace_with_banner($params, & $smarty)
{
	require_once('function.banner.php');
	
    $output = $params['text'];

    $data = array_filter(explode(';', $params['tagClassTuples']));
    foreach ($data as $tuple)
    {
        list($tagName, $textClass) = explode(':', $tuple);
        $matches = array ();
        preg_match_all('/<' . $tagName . '.*?>\s*(.+)\s*<\/' . $tagName . '>/m', $output, $matches);

        list($searchList, $replaceList) = $matches;
        foreach ($replaceList as &$replace)
        {
            $replace = smarty_function_banner(array ('text' => $replace, 'textClass' => $textClass, 'containerTag' => $tagName, ), $smarty);
        }
        $output = str_replace($searchList, $replaceList, $output);
    }
    return $output;
}

/* vim: set expandtab: */

