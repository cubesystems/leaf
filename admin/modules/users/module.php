<?php
class users extends leafBaseModule
{
    protected $mainObjectClass = null;
    public $tableMode = 'css';
    protected $saveMode = 'admin';

    public function __construct()
	{
        $this->mainObjectClass = leafAuthorization::getUserClass();
        return parent::__construct();
    }
    
    public function edit()
    {
        $assign = parent::edit();
 
        $assign['groups']    = leafAuthorization::listGroups();
        $assign['languages'] = leafLanguage::getCollection();

        return $assign;
    }
 
    public function isItemDeletable( $item )
    {
        return ($item->id != $_SESSION[SESSION_NAME]['user']['id']);
    }
    
}
