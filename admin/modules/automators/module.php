<?php
class automators extends leafBaseModule
{
    protected $mainObjectClass = 'leafAutomator';
    public $tableMode = 'css';
    protected $saveMode = 'admin';
    protected static $partials = array(
        'triggers' => 'trigger',
        'actions' => 'action',
    );

    public function __construct()
    {
        parent::__construct();
        $this->actions[] = 'reset';
    }

    public function reset()
    {
        getObject($this->mainObjectClass, $_GET['id'])->resetComplete();
        leafHttp::redirect($_GET['listUrl']);
    }

	public function edit()
    {
        require_once( SHARED_PATH . 'classes/input/input.class.php' );
		input::load( 'select' );
        $assign['triggerTypes'] = leafAutomatorTrigger::getTypes();
        $assign['actionTypes'] = leafAutomatorAction::getTypes();

        if(isset($_GET['getPartial']) && isset(self::$partials[$_GET['getPartial']]))
        {
            $kind = self::$partials[$_GET['getPartial']];
            $class = 'leafAutomator' . $kind;
            $item = getObject($class, get($_GET, 'itemId'));
            $item->contextObjectId = get($_GET, 'contextObjectId');

            if(!empty($_GET['type']))
            {
                $item->setType($_GET['type']);
            }

            if(!empty($_GET['operator']))
            {
                $item->operator = $_GET['operator'];
            }
            elseif(!empty($_GET['action']))
            {
                $item->action = $_GET['action'];
            }

            $assign['item'] = $item;
            $assign['_template'] = '_' . $kind;
        }
        else
        {
            $assign = array_merge(parent::edit(), $assign);
        }

		return $assign;
    }

    public function getUUID()
    {
        return uniqid('key');
    }

}
