<?
ini_set('display_errors', 1);
ini_set('html_errors', 0);
error_reporting(E_ALL | E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_STRICT);
mb_internal_encoding("UTF-8");
ini_set("session.cookie_httponly", 1);

$timeZone = ini_get('date.timezone');
if (empty($timeZone))
{
    date_default_timezone_set('Europe/Riga');
}

// detect production version
$def_config['PRODUCTION_SERVER'] = getenv('LEAF_PRODUCTION') ? true : false;
// detect leaf environment
if (getenv('LEAF_ENV'))
{
	$def_config['LEAF_ENV'] = getenv('LEAF_ENV');
}
else
{
	$def_config['LEAF_ENV'] = $def_config['PRODUCTION_SERVER'] ? 'PRODUCTION' : 'DEVELOPMENT';
}

// detect http/https
$httpPrefix = !empty($_SERVER['HTTPS']) ? 'https' : 'http';

//defined variables
$docRoot = (isset($_SERVER['DOCUMENT_ROOT'])) ? $_SERVER['DOCUMENT_ROOT'] : false;

if ($docRoot === false)
{
    trigger_error('Error reading path to document root.', E_USER_ERROR);
}
$def_config['VERSION'] = 1.2;
$def_config['PATH'] =  realpath($docRoot) . '/';
$def_config['BASE_PATH'] =  $def_config['PATH'];
$def_config['WWW'] = $httpPrefix . '://' . $_SERVER['HTTP_HOST'] . '/';
$def_config['SHARED_PATH'] = $def_config['PATH'] . 'shared/';
$def_config['SHARED_WWW'] = $def_config['WWW'] . 'shared/';
$def_config['CACHE_PATH'] = $def_config['SHARED_PATH'] . 'cache/';
$def_config['CACHE_WWW'] = $def_config['SHARED_WWW'] . 'cache/';
$def_config['DB_PREFIX'] = '';


$def_config['LEAF_FILE_ROOT'] = $docRoot . '/files/';
$def_config['LEAF_FILE_ROOT_WWW'] = $httpPrefix . '://' . $_SERVER['HTTP_HOST'] . '/files/';

$def_config['HOME_DIR'] = dirname($docRoot) . '/';
$def_config['APP_PATH']   = $def_config['HOME_DIR'];

$def_config['ADMIN_PATH']   = $def_config['PATH'] . 'admin/';
$def_config['ADMIN_WWW']   = $def_config['WWW'] . 'admin/';
$def_config['PUBLIC_PATH']   = $def_config['PATH'];
$def_config['PUBLIC_WWW']   = $def_config['WWW'];

if($def_config['PRODUCTION_SERVER'])
{
    $pattern = '/(.*\/)(.*\/)releases\/(.*)/i';
    preg_match($pattern, realpath($_SERVER['DOCUMENT_ROOT']), $matches);
    if(sizeof($matches) > 1)
    {
        $def_config['HOME_DIR'] = $matches[1];
        $def_config['APP_PATH']   = $def_config['HOME_DIR'] . $matches[2];
    }
}

if (
    (
        (in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '188.112.174.251')))
        ||
        (substr($_SERVER['REMOTE_ADDR'], 0, 11) == '192.168.42.')
    )
    &&
    (!isset($_GET['devModeOff']))
)
{
    $def_config['DEV_MODE'] = true;
}
else
{
    $def_config['DEV_MODE'] = false;
}

if (!$def_config['DEV_MODE'])
{
    ini_set('display_errors', 0);
}

// possible values: cube | cubesystems
$def_config['DEVELOPED_BY'] = 'cube';

//db configuration
$config['db'] = array
(
    'trackQueries' => false, // $def_config['DEV_MODE']
);
$config['dbconfig'] = array(
    'hostspec' => 'localhost',
    'database' => '{$dbName}',
    'username' => 'root',
    'password' => '{$dbPassword}',
    'mysql_set_utf8' => true,
);
//core settings
$config['language_id'] = 1;
$config['language_code'] = $config['language_name'] = 'lv';
$config['loadXmlTemplateList'] = true;
$config['smarty'] = array(
    'plugins_dir' => array(
        $def_config['SHARED_PATH'] . 'classes/smarty_plugins',
        'plugins', // the default under SMARTY_DIR
    )
);
$config['useSession'] = false; // dont start session by default

if (isset($def_config['googleMapsKey']))
{
    $config['googleMaps'] = array(
        'key' => $def_config['googleMapsKey'],
        
        'version'     => 'v3',
        'defaultZoom' => 13,
        'centerLat' => '56.94725473000847', // riga
        'centerLng' => '24.099142639160167'
    );
}
$config['googleAnalyticsId'] = '';

if (!$def_config['PRODUCTION_SERVER'] && (bool) preg_match('/\.cube(systems)?\.lv$/i', $_SERVER['HTTP_HOST']))
{
    $config['googleTranslate'] = array
    (
        'APIKey' => 'AIzaSyCR6c48a6DnkR02O1GdEe2gwOYIoVi2Xjc',
        'disableBatchRequests' => false
    );
}

$config['leafHtmlCleaner'] = array(
    'allowInlineStyleColor' => false
);

$config['imageBase'] = array
(
    'quality' => 90
);

$config['textBanner'] = array
(
//    // increase text banners pixel ratio
//    'pixelRatio' => 2.0
);

//tinymce config
$config['tinymce']['properties'] = array(
    'mode' => "specific_textareas",
    'editor_selector' => "leafRichtext",
    'theme' => "advanced",
    'entities' => "160,nbsp,38,amp,60,lt,62,gt",
    'content_css' => "/styles/textFormat.css",
    'body_class' => "content",
    'class_filter_names' => 'contentImageLeft|contentImageRight|block|clear|clearLeft|clearRight|embedObject|hiddenText',
    'plugins' => "inlinepopups,leafImage,leaflink,iespell,insertdatetime,preview,searchreplace,contextmenu,safari,leafEmbed,fullscreen",
    //,bramus_cssextras
    'theme_advanced_buttons1' => "bold,italic,formatselect,justifyleft,justifycenter,justifyright," .
                              "justifyfull,|,sub,sup,|,bullist,numlist,|,link,unlink,image,embed,|,code,cleanup," .
                              "removeformat,|,fullscreen",
    //'theme_advanced_buttons1_add' => "separator,bramus_cssextras_classes,bramus_cssextras_ids",
    'theme_advanced_blockformats' => 'p,address,pre,h2,h3,h4,h5,h6',
    'theme_advanced_buttons2' => "",
    'theme_advanced_buttons3' => "",
    'theme_advanced_toolbar_location' => "top",
    'theme_advanced_toolbar_align' => "left",
    'theme_advanced_statusbar_location' => "bottom",
    'plugin_insertdate_dateFormat' => "%Y-%m-%d",
    'plugin_insertdate_timeFormat' => "%H:%M:%S",
    'extended_valid_elements' => "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|style],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]",
    'relative_urls' => true,
    'theme_advanced_resizing' => true,
    'object_resizing' => false,
);
//object type configuration
$objects_config['rewrite'] = true;
$objects_config['rewriteBase'] = $def_config['WWW'];
// possible values: latin, unicode
$objects_config['rewriteSuggestMode'] = 'latin';
//$objects_config['rewriteLanguageRoots'] = false;
// $objects_config['getUrlCallback']             = null;
// $objects_config['getRewriteCacheKeyCallback'] = null;

$objects_config['cache'] = true;
$objects_config['snapshotsEnabled'] = false;
$objects_config[22] = array(
	'name' => 'xml_template',
	'templates_path' =>  $def_config['PATH'] . 'xml_templates/',
	'templates_www' => $def_config['WWW'] . 'xml_templates/',
	'site_www' => $def_config['WWW'],
	'rescan_templates' =>  true,
	'change_templates' => true,
	'allowed_templates' => NULL,
    'new_objects_protected_by_default' => false,
    // possibility to choose files from server
	// 'files_import_path' => $def_config['HOME_DIR'] . 'upload/'
);
$objects_config[21] = array(
	'name' => 'file',
    'plugins' => array(

		'pngGammaRemover'  => array(
			'applyOn' => array('*.png')
		),
/*
		'imageCorners'  => array(
			'cornerFile' => $_SERVER['DOCUMENT_ROOT'] . '/images/corner.png',
			'applyOn' => array('*.jpg')
		),
		'imageWatermark' => array(
			'watermarkFile' => $_SERVER['DOCUMENT_ROOT'] . '/images/watermark.png',
			'applyOn' => array('*.jpg')
		)
*/

    ),
	'files_path' => $def_config['PATH'] . 'files/',
	'files_www' => $def_config['WWW'] . 'files/',
	'secure_files_path' => NULL,
	'new_objects_protected_by_default' => false
);



$config['dateFormats'] = array
(
    'short' => array
    (
        'lv' => '%d.%m.%Y.',
        'en' => '%d.%m.%Y',
        'ru' => '%d.%m.%Y'
    )
);

$config['tableArchiver'] = array
(
    'archiveDir'  => $def_config['PATH'] . 'tableArchives',
    'minRecords'  => 5000,
    'hoursToKeep' => 24
);

$config['automator'] = array
(
    'triggers' => array(
        array(
            'title' => 'date'
        ),
        // example trigger class
        /*
        array(
            'class' => 'classNameHere',
            'controller' => 'adminControllerNameHere',
        ),
        */
    ),
    'actions' => array(
        array(
            'class' => 'leafAutomator',
            'controller' => 'automators',
        ),
    )
);

/* payment configurations

	$config['payment'] = array (
		'leafBankPaymentProviderSwedbank' => array (
			'action' => 'https://ib.swedbank.lv/banklink/',
			'VK_SND_ID' => '__MERCHANT-ID__',
		),
		'leafBankPaymentProviderNordea' => array (
			'MAC' => '__MAC-ID__',
            'ID' => '__ID__',
		),
        'leafPaymentProviderECOMM' => array (),
        'leafBankPaymentProviderDnb' => array (
          'action'    => 'http://ib-t.dnb.lv/login/index.php',
          'cert_url'  => '__PATH-TO-CERT__',
          'VK_SND_ID' => __MERCHANT-ID__,
          'VK_ACC'    => '__IBAN-ACCOUNT-ID__',
          //'VK_ACC'    => 'LV94RIKO0002023032547', // Test acount
          'VK_NAME'   => '__MERCHANT-NAME__', // ex. SIA Cube
          'VK_REG_ID' => '__MERCHANT-REG-NR__', //ex. 40103145086
          'VK_SWIFT'  => 'RIKOLV2X',
        ),
    );


    $config['ecomm'] = array (
      'domain' => 'https://secureshop.firstdata.lv',
      'cert_url' => $def_config['CERTS_PATH'] . '__KEYSTORE-ID___keystore.pem',
      'cert_pass' => '__CERTIFICATE-PASSWORD__'
      );

    // processes configuration
    $config['process'] = array
    (
        'reportingLevel' => 'REPORT_ERRORS',
        'emails' => array
        (
            // admin/developer email
            'user@example.com'
        ),
        'processes' => array
        (
            'contentSync' => array
            (
                'command' => $def_config['PHP_EXECUTABLE'] . ' ' . $docRoot . '/cli/__SCRIPT_NAME__.php -v',
                'log' => $def_config['APP_PATH'] . 'log/__LOG_FILE__.{modifier}.log',
            ),
        ),
    );

    // crontab configuration
    $config['crontab'] = array (
        'name' => '__SITE_NAME_HERE__',
        'jobs' => array(
            array (
            'description' => 'monitor processes',
            'schedule' => '* * * * *',
            'command' => $def_config['PHP_EXECUTABLE'] . ' ' . $docRoot . '/cli/monitorProcesses.cli.php',
            //'log' => $def_config['APP_PATH'] . 'log/name_here.log',
            )
        ),
    );


*/

// read site custom config
$siteConfig = $def_config['APP_PATH'] . ($def_config['PRODUCTION_SERVER'] ? 'shared/' : '') . 'config.php';
if(file_exists($siteConfig))
{
    include($siteConfig);
}


//shared includes
require_once $def_config['SHARED_PATH'] . 'core/core.functions.php';
require_once $def_config['SHARED_PATH'] . 'core/common.functions.php';
require_once $def_config['SHARED_PATH'] . 'classes/leaf_object_module.class.php';
require_once $def_config['SHARED_PATH'] . 'classes/processing/processing.class.php';
set_error_handler("leafError");
set_exception_handler("leafException");
