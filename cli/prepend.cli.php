<?php
// check cli
function isCli() {
	if(php_sapi_name() == 'cli')
	{
		return true;
	}
	return false;
}

// if not cli, simulate not found (instead of forbidden)
if(!isCli())
{
	header('HTTP/1.1 404 Not Found');
	header("Status: 404 Not Found");
	$_SERVER['REDIRECT_STATUS'] = 404;
	echo "<h1>404 Not Found</h1>";
    echo "The page that you have requested could not be found.";
	exit();
}

ini_set('display_errors', 1);
error_reporting(8191);
define("CLI_MODE", true);

set_time_limit(0);

if(function_exists('pcntl_signal'))
{
    // Needed to trigger shutdown functions in CLI
    declare(ticks = 1);

    function sigint()
    {
        exit;
    }
    pcntl_signal(SIGINT, 'sigint');
    pcntl_signal(SIGTERM, 'sigint'); 
}

// dynamically set document root
if(isset($_SERVER['LEAF_PRODUCTION']))
{
    $pos = strpos(__FILE__, '/releases/');
    $appPath = substr(__FILE__, 0, $pos);
    $_SERVER['DOCUMENT_ROOT'] = $appPath . '/current';
    $customConfigPath =  $appPath . '/shared/config.php';
}
else
{
    $_SERVER['DOCUMENT_ROOT'] = realpath( dirname(__FILE__) . '/../');
    $root = dirname( $_SERVER['DOCUMENT_ROOT'] );
    $customConfigPath =  $root . '/config.php';
}
// must be override in custom config
if(empty($_SERVER['HTTP_HOST']))
{
    $_SERVER['HTTP_HOST'] = '';
}
// no remote addr
$_SERVER['REMOTE_ADDR'] = '';

// preload custom config (it will be loaded again at the end of shared config)
include( $customConfigPath );
require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/shared/core/cli.functions.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

_core_init();

