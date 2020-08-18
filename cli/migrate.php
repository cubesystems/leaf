<?php
require_once(dirname(__FILE__) . '/prepend.cli.php');
$options = getopt('d::');
$dryRun = isset($options['d']);

// re read custom apis dir
readCustomApisDir( SHARED_PATH . 'custom_apis/' );
// autoload all custom apis classes (to load classes table definitions)
$q = '
SELECT
    `value`,
    `name`
FROM
    `system_values`
WHERE
    `name` LIKE "custom_apis.%"
';
$r = dbQuery($q);
while($row = $r->fetch())
{
    $path = $row['value'];
    if(file_exists($path) && strpos($path, ".class.php"))
    {
        $className = substr($row['name'], 12);
        leafAutoloader($className);
    }
}
// maintain all known tables
$table_defs = getTableDefinitions();
foreach($table_defs as $tableName => $table)
{
    maintainTable($tableName, null, $table_defs, $dryRun);
}


// Recompile xml_templates with XO tables

$xmlize = new xmlize();
$xmlize->main_xml_file_mtime = time(); // Force recompile proccess

$q = "SELECT template_path FROM `xml_templates_list` WHERE `table` IS NOT NULL";
$xo_templates = dbGetAll( $q, $key = null, $value = 'template_path' );

if( sizeof( $xo_templates ) > 0 )
{
    foreach( $xo_templates as $template )
    {
        if( file_exists( $xmlize->templates_path . $template  . '.xml' ) )
        {
            $xmlize->recompileTemplate( $template );
        }
    }
}