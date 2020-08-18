<?
$pathToShared = realpath ( dirname( __FILE__ ) . '/../shared' );
require_once $pathToShared . '/config.php';
## core settings
$modules = array(
    'leaf_rewrite' => array (
        'load' => true,
        'return' => false,
        'config'=> array(
            'path_parts' => true
        )
    ),
	'site'=>array(
		'load'=>true,
		'return'=>true,
		'main'=>true,
	),
);
if(isset($config['modules']))
{
    $modules = array_merge($modules, $config['modules']);
}
$config['modules'] = $modules;
//global flash version for {banner}
$config['flash'] = array(
	'defaultFlashVersion' => 9,
	'defaultSwfobjectVersion' => 2
);
?>
