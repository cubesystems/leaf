<?
class profile extends leaf_module{

	var $actions = array('save');
	var $output_actions = array('view','edit');
	var $assigns = array('css');
    public $aliasContext = 'admin:users';

	function save(){
		$user = getObject('leafUser', $_SESSION[SESSION_NAME]['user']['id']);
		$user->setMode('user');
		$user->variablesSave($_POST);
		// backward compatibility
		$_SESSION[SESSION_NAME]['user'] = dbGetRow('SELECT * FROM users WHERE id = ' . $user->id);
	}

	function view(){
		$assign['obj'] = getObject('leafUser', $_SESSION[SESSION_NAME]['user']['id']);
		return $assign;
	}

	function edit(){
		$assign['obj'] = getObject('leafUser', $_SESSION[SESSION_NAME]['user']['id']);
		return $assign;
	}
}
?>