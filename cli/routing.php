<?php
$tmp = explode('?', $_SERVER['REQUEST_URI']);
if ( file_exists( $_SERVER['DOCUMENT_ROOT'] . '/' . $tmp[0] ) )
{
    return false; // serve the requested resource as-is.
}
else
{
    $_GET['objects_path'] = substr($tmp[0], 1);
    return false;
}
