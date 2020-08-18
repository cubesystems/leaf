<?

// base class for classes that need to use the singleton pattern

class singleton_object {

    function singleton_object() {
        // allow creation of instance only if singleton key exists but contains null
        $actualClassName = strtolower(get_class($this));
        $keyState = singleton::getKeyState($actualClassName);
        // $keyState['exists'] = bool
        // $keyState['isnull'] = bool
        if (
            (!$keyState['exists'])
            ||
            (!$keyState['isnull'])
           )
        {
            die ('attempt to create an unauthorized instance of singleton class ' . htmlspecialchars($actualClassName) );
            //return false;
        }
        return true;
    }


}


?>