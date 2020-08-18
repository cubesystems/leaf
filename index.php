<?
##includes
require_once 'config/config.php';
##start core
_core_init();
echo _core_output();
if (leaf_get('properties', 'db', 'trackQueries'))
{
    dump(get($GLOBALS, 'QUERY_LOG'), 0);
}
