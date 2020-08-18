<?php
interface leafUserInterface
{
    public static function authorize();
    public static function deauthorize();
    public static function getProfileModuleName();
    public static function getCurrentUserGroupId();
    
    public static function getById( $id );
    public static function getCollection( $params = array() );
    public function getDisplayString();       
}
