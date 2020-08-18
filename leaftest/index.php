<?php
ini_set('display_errors', 1);
error_reporting(E_ALL | E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE);

// MySQL connection data. Please enter the data and uncomment.
/*
$config['dbconfig'] = array(
    'hostspec' => 'localhost',
    'database' => '',
    'username' => '',
    'password' => '',
    'mysql_set_utf8' => true
);
*/

///////////////////////////////////////////////////////////////
// images & specs pdf relative to this address
$base = 'http://www.cube.lv/leaftestres/';
// specs pdf name
$fileNameLV = 'cube_leaf_1-2_lv.pdf';
$fileNameEN = 'cube_leaf_1-2_en.pdf';
$fileCmsTestReadme = 'leaf_cms_test_izlasi_readme.pdf';
$fileCmsTestZip = 'leaftest.zip';

///////////////////////////////////////////////////////////////
// REPORT
$report = array();

///////////////////////////////////////////////////////////////
// ALLOWED EXCEPTIONS FOR SCRIPT TO PASS
$exceptions = array();

////////////////////////////////////////////////////////////
// PHP
////////////////////////////////////////////////////////////

$report['version']['desc'] = 'PHP versija';
$report['version']['data'] = PHP_VERSION;
if(version_compare(PHP_VERSION, '5.4.0', '<'))
{
	$report['version']['error'] = true;
}

////////////////////////////////////////////////////////////
// GD GD2
////////////////////////////////////////////////////////////

$report['gd']['desc'] = 'gd 2';
if(function_exists('gd_info'))
{
	$gd = gd_info();
	if(isset($gd['GD Version']))
	{
        $report['gd']['data'] = 'Ir';
		$report['gd']['data'] = $gd['GD Version'];
	}
}

////////////////////////////////////////////////////////////
// GD FREETYPE
////////////////////////////////////////////////////////////

$report['gd_freetype']['desc'] = 'gd - FreeType atbalsts';
if(isset($gd['FreeType Support']) && round($gd['FreeType Support']) == 1)
{
	$report['gd_freetype']['data'] = 'Ir';
    $report['gd_freetype']['testImg'] = true;

    if (isset($_GET['testImageText']))
    {
    
        $image      = imagecreatetruecolor( 200, 25 );
        $foreground = imagecolorallocate($image, 0, 0, 0);
        $background = imagecolorallocate($image, 127, 170, 3);
        imagefill($image, 0, 0, $background);
        
        imagefttext( $image, 11, 0, 6, 18, $foreground, './arial.ttf', 'Šaursliežu žagaru saišķis' );
        
        header('Content-type: image/png');    
        imagepng( $image );
        die();
    
    }

}

////////////////////////////////////////////////////////////
// GD JPEG
////////////////////////////////////////////////////////////

$report['gd_jpg']['desc'] = 'gd - JPG atbalsts';

if (
    (isset($gd['JPG Support']) && round($gd['JPG Support']) == 1)
    ||
    (isset($gd['JPEG Support']) && round($gd['JPEG Support']) == 1)
)
{
	$report['gd_jpg']['data'] = 'Ir';
}

////////////////////////////////////////////////////////////
// GD GIF
////////////////////////////////////////////////////////////

$report['gd_gif']['desc'] = 'gd - GIF atbalsts';
if(isset($gd['GIF Read Support']) && isset($gd['GIF Create Support']))
{
	$report['gd_gif']['data'] = 'Ir';
}

////////////////////////////////////////////////////////////
// ICONV
////////////////////////////////////////////////////////////

$report['iconv']['desc'] = 'iconv';
if(function_exists('iconv'))
{
	$report['iconv']['data'] = 'Ir';
}

////////////////////////////////////////////////////////////
// MULTIBYTE
////////////////////////////////////////////////////////////

$report['multibyte']['desc'] = 'multibyte';
if(function_exists('mb_get_info'))
{
	$report['multibyte']['data'] = 'Ir';
}

////////////////////////////////////////////////////////////
// CURL
////////////////////////////////////////////////////////////

$report['curl']['desc'] = 'curl';
if(function_exists('curl_init'))
{
	$report['curl']['data'] = 'Ir';
}

////////////////////////////////////////////////////////////
// TIDY
////////////////////////////////////////////////////////////

$report['tidy']['desc'] = 'tidy';
if(round(phpversion('tidy')) == 2)
{
	$report['tidy']['data'] = 'Ir';
}

////////////////////////////////////////////////////////////
// HTACCESS
////////////////////////////////////////////////////////////

$report['htaccess']['desc'] = 'mod_rewrite';
if(!empty($_GET['HTACCESSTEST']))
{
	$report['htaccess']['data'] = 'Ir';
}

////////////////////////////////////////////////////////////
// CTYPE
////////////////////////////////////////////////////////////

$report['ctype']['desc'] = 'ctype';
if(function_exists('ctype_digit'))
{
	$report['ctype']['data'] = 'Ir';
}

////////////////////////////////////////////////////////////
// SHORT TAG
////////////////////////////////////////////////////////////

$report['short']['desc'] = 'short open tag';
if(ini_get('short_open_tag'))
{
	$report['short']['data'] = 'Ir';
}

////////////////////////////////////////////////////////////
// MAIL
////////////////////////////////////////////////////////////

date_default_timezone_set('UTC');
if(!empty($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL))
{
    $hostfull = strtolower( $_SERVER['HTTP_HOST'] );
    $host = str_replace('www.', '', $hostfull);
    $fromAddress = 'leaftest@' . $host;
    $to="leaftest@cube.lv";
    $subject="Leaf test";
    $header="from: " . $fromAddress;
    $message="Leaf test mail. Sent from " . $hostfull . " at " . date(DATE_RFC822);
    $sentmail = mail($_GET['email'],$subject,$message,$header);

    $report['mail']['desc'] = 'mail';
    if($sentmail){
        $report['mail']['data'] = 'Ir';
    }
}

////////////////////////////////////////////////////////////
// UID
////////////////////////////////////////////////////////////

$scriptOwner = getmyuid();
$directory = dirname(__FILE__) . '/';
$testFilePath = $directory . 'leaf_uid_testfile.txt';
$report['uid']['desc'] = 'process uid';
if(is_writable($directory))
{
	file_put_contents($testFilePath, '');
	$processOwner = fileowner($testFilePath);
	$report['uid']['data'] = 'File owner - ' . $scriptOwner . '. Script owner: ' . $processOwner;
	unlink($testFilePath);
	if($processOwner != $scriptOwner)
	{
		//$report['uid']['error'] = true;
        $report['uid']['data'] = 'Ir';
	}
}
else
{
    $report['uid']['data'] = 'File owner - ' . $scriptOwner . '. Script owner: unknow. Directory is not writable.';
	$report['uid']['error'] = true;
}

//array_push($exceptions, 'uid');

////////////////////////////////////////////////////////////
// MySQL
////////////////////////////////////////////////////////////

$report['mysql']['desc'] = 'MySQL DB';
$report['mysql']['data'] = '<i>- nav norādīti dati -</i>';

if ((!empty($config)) && (!empty($config['dbconfig'])))
{
    $db = $config['dbconfig'];

    // mysql_pconnect([string server], [string username], [string password], [int client_flags])

    // $report
    $conn = @mysql_pconnect($db['hostspec'], $db['username'], $db['password'] );
    if (!$conn)
    {
        $report['mysql']['data'] = mysql_error();
        $report['mysql']['error'] = true;
    }
    else
    {

        // $conn ok
        $selectOk = @mysql_select_db( $db['database'] );
        if (!$selectOk)
        {
            $report['mysql']['data'] = mysql_error();
            $report['mysql']['error'] = true;
        }
        else
        {
            $version = mysql_get_server_info();
            $report['mysql']['data'] = $version;

        }

    }
}

////////////////////////////////////////////////////////////
// GIT
////////////////////////////////////////////////////////////

$report['git'] = array (
	'desc'	=> 'GIT',
	'data'	=> '-'
);
exec("which git", $out, $result);
if($result == 0)
{
    $out = array_shift($out);
	$report['git']['data'] = $out;
}
else
{
	$report['git']['error'] = true;
}

////////////////////////////////////////////////////////////
// PDO
////////////////////////////////////////////////////////////
$report['pdo'] = array (
    'desc'  => 'PDO',
);
if (class_exists('PDO'))
{
    $report['pdo']['data'] = 'Ir';
}

////////////////////////////////////////////////////////////
$success = true;

// table with properties
$i = 1;
$len = count($report);
$output = '';

//foreach($report as $item)
foreach($report as $key => $item)
{
    if(empty($item['data']) || isset($item['error']))
    {
        $msg = '<img src="' . $base . 'images/icon-fail.png" alt="fail" />';
	    // ignore exceptions for overall result
		if (!in_array($key, $exceptions)) {
			$success = false;
		}
    }
    else
    {
        $msg = '<img src="' . $base . 'images/icon-ok.png" alt="ok" />';
    }

    
    $last = '';
    
    if ($i == $len)
    {
        $last = ' class="last"';
    }
    
    $output .= '<tr' . $last . '><th>' . $item['desc'] . '</th>';
    $output .= '<td class="icon">' . $msg . '</td>';
    
    $output .= '<td>';
    
    //$output .= (isset($item['data']) ? $item['data'] : '-'  );
    $output .= ((isset($item['data']) && $item['data'] != 'Ir') ? $item['data'] : ''  );
    
    if ((!empty($item['testImg' ])) && (!isset($item['error'])))
    {
        $output .= '<img src="?testImageText&amp;unique=' . uniqid() . '" alt="[[Šeit bija jābūt attēlam ar imagetext]]" />';
    }
    
    $output .= '</td>';
    $output .= '</tr>';
    
    $i++;
    
} 

////////////////////////////////////////////////////////////

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Cube :: Leaf compatibility test</title>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js" type="text/javascript"></script>
<style type="text/css">
html, body
{
    height: 100%;
    
}

html
{
    overflow-x: hidden;
}

body
{
    font-family: Arial, Verdana, "Trebuchet MS", sans-serif;
    line-height:160%;
    font-size:1em;
    color: #fff;
    margin:0;
    background: url("<?php echo $base; ?>images/main-bg.gif") repeat #272727;
}

.main
{
    background: url("<?php echo $base; ?>images/head-bg.gif") repeat-x transparent;
    position: relative;
    min-height: 100%;
}

.wrapper
{
    width: 940px;
    margin: 0 auto;
    padding: 100px 0 0;
    background: url("<?php echo $base; ?>images/top.png") no-repeat transparent;
    position: relative;
}

.logo
{
    display: block;
    height: 40px;
    left: 30px;
    position: absolute;
    top: 10px;
    width: 115px;
}

.tableBlock
{
    width: 100%;
    padding-bottom:55px;
    background: url("<?php echo $base; ?>images/table-block-bg.png") no-repeat center bottom transparent;
}

.specs
{
    position: absolute;
    top:100px;
    right: 0;
    width: 120px;
    height: 30px;
}

.specs a
{
    display: block;
    text-decoration: none;
    width: inherit;
    height: inherit;
    margin-bottom: 10px;
}

.specs a.lv
{
    background: url("<?php echo $base; ?>images/pdf_sprite_lv.png") left top no-repeat transparent;
}

.specs a.en
{
    background: url("<?php echo $base; ?>images/pdf_sprite_en.png") left top no-repeat transparent;
}

.specs a.readme
{
    background: url("<?php echo $base; ?>images/leaf_cms_test.png") left top no-repeat transparent;
}

.specs a.zip
{
    background: url("<?php echo $base; ?>images/leaf_cms_test_script.png") left top no-repeat transparent;
}

.specs a:hover
{
    display: block;
    text-decoration: none;
    background-position: left bottom;
}

.specs h3
{
    color: #01b5db;
    /*color: #959494;*/
    font-size: 14px;
    line-height: 16px;
    width: 120px;
    text-align: left;
    padding: 0;
    margin: 0;
    margin-bottom: 10px;
}

table
{
	border: medium none;
    border-collapse: collapse;
    left: 288px;
    position: relative;
    width: 420px;
}
tr
{
    background: url("<?php echo $base; ?>images/tr-bg.png") no-repeat center bottom;
}
tr.last
{
    background: none;
}
td, th
{
	padding: 0px;
	border: none;
	vertical-align: middle;
    margin:0;
}
th
{
    width: 150px;
    padding: 0 15px 4px 0;
    text-align: right;
    font-weight: bold;
    font-size: 14px;
    line-height: 30px;
}

td
{
    padding: 0 0 3px 15px;
    vertical-align: middle;
    color: #7eaa03;
    font-size: 13px;
}
td img
{
    padding: 1px 0 3px 0;
}
td.icon
{
    padding: 0;
    width:20px;
    text-align: center;
}

.result
{
    position: relative;
    margin-top: -15px;
}

h2
{
    color: #888888;
    font-size: 27px;
    height: 60px;
    margin: 0;
    padding-top: 35px;
    text-align: center;
}

h2.warning
{
	color: #f31313;
}

h2.success
{
    color: #7EAA03;
}

.hand
{
    position: absolute;
    width: 1611px;
    height: 60px;
    top: 0px;
    left: 100%;
    margin-left: 0px;
}

.hand .wrap
{
    position: relative;
}

.hand .wrap img
{
    display:none;
    position: absolute;
    left: 0;
    top: 0;
}

#undefined
{
    margin-top: 33px;
}

#fail
{
    margin-top: 3px;
}

#fail
{
    margin-top: 30px;
}

.details
{
    background: url("<?php echo $base; ?>images/details-bg.png") no-repeat center top #272727;
    /*height:114px;*/
    height:370px;
    margin-bottom: 29px;
}

.details .wrap
{
    width: 940px;
    margin: 0 auto;
    padding: 30px 0 0;
    position: relative;
}

.details h3
{
    color: #01b5db;
    font-size: 14px;
    line-height: 16px;
    width: 120px;
    text-align: right;
    padding: 0;
    margin: 0;
}

.details .text
{
    width: 420px;
    position: absolute;
    left: 140px;
    top: 30px;
    color: #959494;
    font-size: 12px;
    line-height: 18px;
}

.details .contactBlock
{
    width: 250px;
    height: 65px;
    position: absolute;
    top: 30px;
    right: 0;
}

.contactBlock img
{
    padding-right: 20px;
    float: left;
}

.contactBlock p,
.contactBlock a
{
    font-size: 13px;
    color: #999999;
    margin: 0;
    padding: 0;
    line-height: 18px;
    text-decoration: none;
}

.contactBlock p.name
{
    font-size: 14px;
    font-weight: bold;
    margin: 2px 0 0 0;
}

.footer
{
    height: 29px;
    width: 100%;
    background: #171717;
    margin: -29px 0 0 0;
    padding: 0;   
    /*position: relative;*/
}

.footer .copy
{
    width: 860px;
    margin: 0 auto;
    font-size: 10px;
    line-height: 12px;
    font-weight: bold;
    color: #747474;
    line-height: 28px;
}

.contentClear
{
    clear: both;
    height: 1px;
    margin-top: -1px;
    overflow: hidden;
    visibility: hidden;
}

</style>


<!--[if IE]>
    <style type="text/css">
    </style>
<![endif]-->

<script type="text/javascript">

(function($) {
    $(document).ready(function() {
    
        $("#undefined").css({'display': 'block'});
    
        
        $("#hand").delay(1000).animate({"left": "50%", "margin-left": "150px"}, "slow", function() {
            
            $("#result").addClass("<?php echo ($success) ? 'success' : 'warning'; ?>");
            $("#result").html("<?php echo ($success) ? 'Atbilst prasībām' : 'Neatbilst prasībām'; ?>");
            
            $("#undefined").css({'display': 'none'});
            $("#<?php echo ($success) ? 'ok' : 'fail'; ?>").css({'display': 'block'});
        });
        
    }) // document
})(jQuery)

</script>


</head>

<body>
    <div class="main">
        <div class="wrapper">
        
            <a class="logo" href="http://www.cube.lv/" title="Cube" target="_blank"><!-- ~ --></a>
        
            <div class="tableBlock">
                <table>
                    <?php echo $output; ?>
                </table>
            </div>

            <div class="specs">
            	<h3>Dokumenti</h3>
                <a class="lv" href="<?php echo $base . $fileNameLV; ?>" title="Sistēmas prasības"><!-- --></a>
                <a class="en" href="<?php echo $base . $fileNameEN; ?>" title="System requirements"><!-- --></a>
                <h3>Tests</h3>
                <a class="readme" href="<?php echo $base . $fileCmsTestReadme; ?>" title="Izlasi / Readme"><!-- --></a>
                <a class="zip" href="<?php echo $base . $fileCmsTestZip; ?>" title="Lejupielādēt / Download"><!-- --></a>
            </div>
        </div>
            
        <div class="result">
        
            <h2 id="result">..................</h2>
            
            <div id="hand" class="hand">
                <div class="wrap">
                    <img src="<?php echo $base; ?>images/roka.png" alt="hand undefined" id="undefined"/>
                    <img src="<?php echo $base; ?>images/roka_ok.png" alt="hand ok" id="ok" />
                    <img src="<?php echo $base; ?>images/roka_not_ok.png" alt="hand fail" id="fail" />
                </div>
            </div>
        
        </div>

        

        <div class="details">
            <div class="wrap">
                <h3>Vispārējās prasības</h3>
                <div class="text">
                	<p>32/64-bit BSD/*nix</p>
					<p>Apache 1.3+</p>
					<ul>
						<li>mod_rewrite atbalsts</li>
						<li>iespēja mainīt uzstādījumus ar .htaccess faila vai VirtualHost palīdzību</li>
						<li>PHP skripti tiek darbināti ar faila īpašnieka tiesībām nevis ar webservera (apache)</li>
					</ul>
					<p>PHP 5.4+</p>
					<ul>
						<li>iconv</li>
						<li>gd2</li>
						<li>multibyte support (mbstring)</li>
						<li>tidy</li>
						<li>curl</li>
						<li>GIT</li>
					</ul>
					<p>MySQL 4.1+</p>
                </div>
                <div class="contactBlock">
                    <img src="<?php echo $base; ?>images/martins.png" alt="Mārtiņš Bergmanis" />
                    <p class="name">Mārtiņš Bergmanis</p>
                    <p><a href="mailto:martins.bergmanis@cube.lv">martins.bergmanis@cube.lv</a></p>
                    <p>+371 29343155</p>
                </div>
                
            </div>
        </div>
        
        <div class="contentClear"><!-- ~ --></div>

    </div>

    <div class="footer">
        <div class="copy">© 2011 Cube All Rights Reserved</div>
    </div>

</body>
</html>
