<?
// class for singleton pattern operations
class singleton {

    // public class method
    // returns a reference to the only instance of the passed class
    static function & get ($className) {
        $singletonContainer = & singleton::getOrCreateSingletonContainer();
        return $singletonContainer->getOrCreateObject($className);
    }

    // public class method
    // destroys the only instance of the passed class
    function destroy($className) {
        $singletonContainer = singleton::getOrCreateSingletonContainer();
        return $singletonContainer->destroyObject($className);
    }

    // class method
    // return (locate or create) singleton container variable
    static function & getOrCreateSingletonContainer () {
        $varName = SINGLETON_VARIABLE_NAME;
        if (
            (!isset($GLOBALS[$varName]))  // not set
        )
        {   // must create
            $GLOBALS[$varName] = new singleton_container();
        }
        if (!is_instance_of($GLOBALS[$varName], 'singleton_container'))  // wrong class
        {
            die ('singleton container not found');

        }
        return $GLOBALS[$varName];
    }

    // class method
    static function getKeyState($className) {
        $singletonContainer = & singleton::getOrCreateSingletonContainer();
        return $singletonContainer->getKeyState($className);
    }
}

?>