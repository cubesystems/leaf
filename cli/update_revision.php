<?php
require_once(dirname(__FILE__) . '/prepend.cli.php');
$revisionFile = PATH . 'REVISION';
if(file_exists($revisionFile))
{
    $revision = trim(file_get_contents($revisionFile));
    setValue('REVISION', $revision);
}
