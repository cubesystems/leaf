<?
require_once '../shared/config.php';
## enable error display for admin side
ini_set('display_errors', 1);
## site define variables
$def_config['PATH'] = $def_config['ADMIN_PATH'];
$def_config['WWW'] = $def_config['ADMIN_WWW'];
$def_config['SESSION_NAME'] = md5($_SERVER['HTTP_HOST']);
## db info
$config['modules']=array(
	'leaf'=>array(
		'log'=>true,
		'main'=>true,
		'load'=>true,
		'return'=>true,
	),
);

$config['flash'] = array(
	'defaultFlashVersion' => 9,
	'defaultSwfobjectVersion' => 2
);

$config['leafAuthorization'] = array
(
	'userClass'  => 'leafUser',
    'groupClass' => 'leafUserGroup'
);

$config['objectModules'] = array( 'objectAccess' );


$config['leafAdminAbilityClass'] = 'leafAdminAbility';

// main menu config
$config['leafMenuConfig'] = array(
    'content',
    'leafBaseModule',
    'errors',
    'aliases',
);

// example config
$config['leafBaseModuleConfig'] = array
(
	'menu' => array
	(
		// main menu
		'settings' => array
		(
			// submenu sections
			'users'   => array( 'users', 'userGroups' ),
        ),
        
        /*
		// main menu
		'projectSpecific' => array
		(
			// submenu sections
			'emails' => array
			(
				'emailHeader',
				'exampleEmail' => array
				(
					'url' 		 => '?module=emails&email=leafExampleEmail',
					'moduleName' => 'emails',
				),
                'errorReports' 	  => array
                (
                    'url' 		 => '?module=errorReports',
                    'moduleName' => 'errorReports',
                    'badgeCallback'      => function()
                    {
                        $unresolvedErrors = errorReport::getUnresolvedForCurrentUser();
                        if($unresolvedErrors)
                        {
                            return '<span class="unresolvedErrorsBadge">' . $unresolvedErrors . '</span>';
                        }
                    }
                ),
			),	
        ),
     */
	),
);
