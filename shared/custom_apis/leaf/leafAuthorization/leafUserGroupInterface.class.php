<?php
interface leafUserGroupInterface
{
    public static function getById( $id );
    public static function getCollection( $params = array() );
    public function getDisplayString();    
}
