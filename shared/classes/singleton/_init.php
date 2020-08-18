<?

/*
singleton pattern implementation
v1.1 - 2006-04-30
    imported into LEAF,
    slightly refactored
v1.0 - 2005-05-18
    first version
*/
/*
    instead of
        $x = new someClass();
    use
        $x = & singleton::get('someClass');

    real instances of singleton classes are stored in a global variable,
    inside an array in an instance of singleton_container
*/

/*
usage example:

    // derived class
    class blah extends singleton_object {

        // constructor
        function blah () {
            $createOk = parent::singleton_object();
            // .. continue constructor .. /
        }
    }

    $foo = & singleton::get('blah'); // not $foo = new blah();
    $foo->doSomethign();

*/
$singletonInit_dirName = dirname(__FILE__);

require_once $singletonInit_dirName . '/singleton.class.php';
require_once $singletonInit_dirName . '/singleton_container.class.php';
require_once $singletonInit_dirName . '/singleton_object.class.php';

//require_once

if (!defined('SINGLETON_VARIABLE_NAME'))
{
    define ('SINGLETON_VARIABLE_NAME', 'SINGLETON_CONTAINER');
}

?>