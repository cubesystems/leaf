<?php
/**
* Smarty plugin
* @package Smarty
* @subpackage plugins
*/

// returns path to image file
// adds width and height html attributes if the image is PNG  and the client is IE
//
// usage:
// <img src="{image_src file=$objectId}" alt="foo" />
//
//
//
//
function smarty_function_image_src($params, &$smarty)
{
    if (
        (!isset($params['file']))
    )
    {
        return null;
    }

    $fileId = (int) $params['file'];

    $imageSrc = object_rewrite_path($fileId);

    // if NOT IE, return src

    /* taken from replacePngTags.php */
    $msie = '/msie\s(5\.[5-9]|[6]\.[0-9]*).*(win)/i';
    if (
        (!isset($_SERVER['HTTP_USER_AGENT']))
        ||
        (!preg_match($msie, $_SERVER['HTTP_USER_AGENT']))
        ||
        (preg_match('/opera/i', $_SERVER['HTTP_USER_AGENT']))
    )
    {
        return $imageSrc;
    }
    /* ----- */

    // if NOT png, return src

    if (strtolower(substr($imageSrc,-4)) != '.png')
    {

        return $imageSrc;
    }


    // try to locate image data


    if (isset($smarty->_tpl_vars['_object']))
    {
        if (!isset($smarty->_tpl_vars['_object']->fileData))
        {
            return $imageSrc;
        }
        $fileDataArray = $smarty->_tpl_vars['_object']->fileData;
    }
    elseif (isset($smarty->_tpl_vars['openedObject']))
    {
        if (!isset($smarty->_tpl_vars['openedObject']->object_data['fileData']))
        {
            return $imageSrc;
        }
        $fileDataArray = $smarty->_tpl_vars['openedObject']->object_data['fileData'];
    }


    if (
        (!is_array($fileDataArray))
        ||
        (!isset($fileDataArray[$fileId]))
        ||
        (!is_array($fileDataArray[$fileId]))
        ||
        (!isset($fileDataArray[$fileId]['extra_info']))
        ||
        (!is_array($fileDataArray[$fileId]['extra_info']))
        ||
        (!isset($fileDataArray[$fileId]['extra_info']['image_width']))
        ||
        (!isset($fileDataArray[$fileId]['extra_info']['image_height']))
    )
    {
        return $imageSrc;
    }


    $width = (int) $fileDataArray[$fileId]['extra_info']['image_width'];
    $height = (int) $fileDataArray[$fileId]['extra_info']['image_height'];

    $imageSrc .= '" width="' . $width . '" height="' . $height;

    return $imageSrc;

}



?>
