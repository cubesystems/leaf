<?php
class LeafException extends Exception
{
    private $context = array();

    public function __construct($message, $code = 0, $context = array()) {
        $this->context = $context;
        parent::__construct($message, $code);
     }

    public function getContext()
    {
        return $this->context;
    }

}
