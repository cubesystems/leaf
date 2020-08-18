<?
// container class for holding all singleton objects
class singleton_container {
    var $objects = null;

    // constructor
    function singleton_container() {
        $this->objects = array();
    }

    // object method
    // returns a reference to the only instance of the passed class
    function & getOrCreateObject ($className) {
        $className = strtolower($className);
        if (!class_exists($className))
        {
            die ('class ' . htmlspecialchars($className) . ' does not exist');
        }

        // if instance does not exist, create it
        if (
            (!isset($this->objects[$className])) // not set
           )
        {
            $this->objects[$className] = null;
            $this->objects[$className] = new $className;
        }

        if  (!is_instance_of($this->objects[$className], $className))
        {
            die ('instance of ' . htmlspecialchars($className) . ' not found');
        }

        return $this->objects[$className];
    }

    function getKeyState ($className) {
        // returns array
        // $keyState['exists'] = bool
        // $keyState['isnull'] = bool
        $return = array('exists' => false, 'isnull' => false);
        if (array_key_exists($className, $this->objects))
        {
            $return['exists'] = true;
            if (is_null($this->objects[$className]))
            {
                $return['isnull'] = true;
            }
        }
        return $return;
    }

    // object method
    function destroyObject ($className) {
        $className = strtolower($className);
        if (isset($this->objects[$className]))
        {
            unset ($this->objects[$className]);
        }
        return true;
    }

}

?>
