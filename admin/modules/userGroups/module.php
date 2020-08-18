<?php
class userGroups extends leafBaseModule
{
    protected $mainObjectClass = null;
    public $tableMode = 'css';

    public function __construct()
	{
        $this->mainObjectClass = leafAuthorization::getGroupClass();
        return parent::__construct();
    }

    public function view()
    {
        $assign = parent::view();
        $assign['modules'] = leafAdminAbility::getModuleConfigurations($assign['item']->id);
        $assign['moduleNames'] = leafAdminAbility::getModuleNames();
        foreach($assign['modules'] as $moduleName => $foo)
        {
            $assign['moduleNames'][$moduleName] = $moduleName;
        }

        return $assign;
    }

    public function edit()
    {
        $assign = parent::edit();
        $assign['modules'] = leafAdminAbility::getModuleConfigurations($assign['item']->id);
        $assign['moduleNames'] = leafAdminAbility::getModuleNames();
        foreach($assign['modules'] as $moduleName => $foo)
        {
            $assign['moduleNames'][$moduleName] = $moduleName;
        }

        return $assign;
    }

    public function isItemDeletable( $item )
    {
        if ($item && $item->getUsersCount() < 1)
        {
            return true;
        }
        return false;
    }

}
