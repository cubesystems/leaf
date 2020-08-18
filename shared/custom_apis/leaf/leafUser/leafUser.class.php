<?
class leafUser extends leafBaseObject implements leafUserInterface
{
    const failMaxCounts = 5;
    const failExpireTime = '30 minutes';

    
	const tableName = 'users';
	protected $strictMode = false;
    protected $login, $name, $surname, $password, $group_id, $add_date, $last_login, $language, $email, $fontsize;
    // group relation object
    protected $group;
	// relations
	protected $objectRelations = array(
		'group' => array('key' => 'group_id', 'object' => 'leafUserGroup'),
    );
    
    protected $languageCode;

	protected $propertiesDesc;
	protected $modes = array(
		'user' => array(
			'email'
		),
		'user_w_password' => array(
			'email',
			'password'
		),
		'admin' => array(
			'login', 'email', 'name', 'surname', 'group_id', 'language'
		),
		'admin_w_password' => array(
			'login', 'email', 'name', 'surname', 'group_id', 'password', 'language'
		),
	);
	protected $fieldsDefinition = array(
		'login' => array(
			'not_empty' => true
		),
		'email' => array(
			'not_empty' => true,
			'type' => 'email'
		),
		'password' => array(
		),
		'name' => array(
		),
		'surname' => array(
		),
		'group_id' => array(
			'type' => 'int'
		),
		'language' => array(
			'type' => 'int'
		)
	);

	function __construct($initData = null){
		parent::__construct($initData);
        if( !(VERSION != '1.1' && VERSION > 1.1) )
        {
            $this->readExtraProperties();
        }
    }

	public static function _autoload( $className )
    {
		parent::_autoload( $className );
        dbRegisterRawTableDefs( self::getTableDef() );
    }
    
    public function __get($name)
    {
        if ($name == 'languageCode')
        {
            $language = getObject( 'leafLanguage', $this->language) ;
            if (!$language)
            {
                return null;
            }
            return $language->code;
        }
        return parent::__get($name);
    }

    public static function getTableDef()
    {
        $_tableDefsStr = array
        (
            self::tableName => array
            (
                'fields' =>
                '
                    id    int auto_increment
                    login varchar(255)
                    name  varchar(255)
                    surname  varchar(255)
                    email  varchar(255)
                    password  varchar(40)
                    group_id  int
                    add_date  datetime
                    last_login datetime
                    language int
                    fail_counts int default(0)
                    fail_last_attempt datetime
                '
                ,
                'indexes' => '
                    primary id
                    unique login
                    index password
                ',
                'foreignKeys' => '
                    group_id ' . leafUserGroup::tableName. '.id RESTRICT RESTRICT
                ',
            ),
        );
        return $_tableDefsStr;
    }

    public static function deauthorize()
    {
        unset($_SESSION[SESSION_NAME]);
    }

    public static function getCurrentUserGroupId()
    {
        if(isset($_SESSION[SESSION_NAME]['user']['group_id']))
        {
            return $_SESSION[SESSION_NAME]['user']['group_id'];
        }
    }

	public static function authorize()
	{
	    $userIsLoggedIn = (!empty($_SESSION[SESSION_NAME]['user']['id']));

	    $isLoginPost = (
	       (isset($_POST['leafAuthUsername']))
	       &&
	       (isset($_POST['leafAuthPassword']))
        );

        $isLoginTicketRequest = (!empty($_GET['loginTicket']));

        $loginTicket = null;
        if ($isLoginTicketRequest)
        {
            $loginTicket = leafLoginTicket::getByCodeAndIp( $_GET['loginTicket'], $_SERVER['REMOTE_ADDR'] );
            if ($loginTicket)
            {
                if ($loginTicket->isExpired())
                {
                    $loginTicket->delete();
                    $loginTicket = null;
                }
            }
        }


        $authorizeOk = false;

        $userDataQp = array(
            'select' => 'u.*, g.default_module',
            'from'   => static::tableName . ' AS u',
            'leftJoins' => array
            (
                leafUserGroup::tableName . ' AS g ON u.group_id = g.id'
            ),
            'where'  => array()
        );

        if ($isLoginTicketRequest)
        {
            if ($loginTicket)
            {

                $userDataQp['where'][] = 'u.id = ' . $loginTicket->userId;
                $userRow = dbGetRow( $userDataQp );
                $loginOk = (!empty($userRow));

                if ($loginOk)
                {
                    // login ticket request ok
        			$authorizeOk = true;
                }
                else
                {
                    // login ticket request fail (user id not found)
                    $authorizeOk = false;
                }

                $loginTicket->delete();

            }
            else
            {
                // login ticket not found
                $authorizeOk = false;
            }

        }
        else
        {
            // not login ticket

            if (!$userIsLoggedIn)
            {
                if ($isLoginPost)
                {
                    // not logged in, attempting login

                    $userDataQp['where'][] = 'u.login = "' . dbSE($_POST['leafAuthUsername']) . '"';
                    $userDataQp['where'][] = 'u.fail_counts < "'.self::failMaxCounts.'" OR (u.fail_counts = "'.self::failMaxCounts.'" AND u.fail_last_attempt < "'.date('Y-m-d H:i:s', strtotime('-'.self::failExpireTime)).'")';
                    
                    
                    $userDataQp['where'][] = '
                        u.password = "' . md5($_POST['leafAuthPassword']) . '"
            			OR
            			u.password = "' . sha1($_POST['leafAuthPassword']) . '"
                    ';

                    $userRow = dbGetRow( $userDataQp );
                    $loginOk = (!empty($userRow));

                    if ($loginOk)
                    {
                        // login post ok
            			$authorizeOk = true;
                    }
                    else
                    {
                        // Get user

                        $user = dbGetRow('SELECT * FROM ' . static::tableName . ' WHERE login = "'.dbSE($_POST['leafAuthUsername']).'"');

                        // Add fail counts and set last fail time as now
                        if( ! empty($user) && $user['fail_counts'] < self::failMaxCounts)
                        {
                            dbQuery('UPDATE ' . static::tableName . ' SET `fail_counts` = `fail_counts` +1, fail_last_attempt = NOW() WHERE id = "'.dbSE($user['id']).'"');
                        }
                        
                        // login post fail
                        $authorizeOk = false;
                    }
                }
                else
                {
                    // not logged in, not attempting login
        			$authorizeOk = false;
                }
            }
            elseif ($userIsLoggedIn)
            {
                // re-validate user against db ( if password is changed or user is deleted after logging in )

                $userDataQp['where'][] = 'u.id = "' . dbSE( $_SESSION[SESSION_NAME]['user']['id'] ) . '"';
                $userDataQp['where'][] = 'u.password = "' . dbse( $_SESSION[SESSION_NAME]['user']['password'] ) . '"';

                $userRow = dbGetRow( $userDataQp );
                $revalidateOk = (!empty($userRow));

                if ($revalidateOk)
                {
                    $authorizeOk = true;
                }
                else
                {
                    // logged in, but user deleted or password changed
                    // do not
                    $authorizeOk = false;
                }
            }
        }

        // load or unload session user
        if ($authorizeOk)
        {
            $_SESSION[SESSION_NAME]['user'] = $userRow;
            leaf_set('_user', $_SESSION[SESSION_NAME]['user']);
            dbQuery('UPDATE ' . static::tableName . ' SET last_login=NOW(), fail_counts=0 WHERE id="'.$_SESSION[SESSION_NAME]['user']['id'].'"');
        }
        else
        {
            unset($_SESSION[SESSION_NAME]);
        }


        // special cases:

        // send login result response to leafbot
        if (
            ($isLoginPost) && (leafBot::isCurrentUserAgent())
        )
        {
            $leafBotResponseData = array(
                'loginOk' => ($authorizeOk) ? 1 : 0
            );


            // create login ticket if requested
            if (
                ($authorizeOk)
                &&
                (array_key_exists('createLoginTicket', $_POST))
            )
            {
                // create login ticket
                $userId = $_SESSION[SESSION_NAME]['user']['id'];

                $loginTicket = leafLoginTicket::create( $userId, $_POST['createLoginTicket'] );
                if ($loginTicket)
                {
                    $leafBotResponseData['loginTicket'] = $loginTicket->getData();
                }
            }


            leafBotRequestResponse::sendJson( $leafBotResponseData );
            // dies here
        }


        // redirect if successful login from browser
        if (
            (
                ($isLoginPost)
                &&
                ($authorizeOk)
            )
            ||
            ($isLoginTicketRequest) // with login tickets redirect in any case
        )
        {
        	$url = (!empty($_POST['redirect_url'])) ? $_POST['redirect_url'] : WWW;
            leafHttp::redirect( $url );
            // dies here
        }

        return $authorizeOk;

	}
    
    public static function hashPassword( $password )
    {
        return sha1( $password );
    }    

    public function variablesSave($variables, $fieldsDefinition = NULL, $mode = false){
        if(!empty($mode))
        {
            $this->setMode($mode);
        }

		$fieldsDefinition = $this->getDefinitions();
		$p = new processing;
		if($this->currentMode == 'user')
		{
			$fieldsDefinition['oldpassword']  = array();
			$fieldsDefinition['password1']  = array();
			$fieldsDefinition['password2']  = array();
			$p->addPostCheck(array($this, 'checkNewPassword'));
		}
		elseif($this->currentMode == 'admin')
		{
			if($this->id == 0 || isset($variables['change_password']))
			{
				$fieldsDefinition['password1']  = array();
				$fieldsDefinition['password2']  = array();
                $p->addPostCheck(array($this, 'checkNewPasswordAdmin'));
			}
			$p->addPostCheck(array($this, 'checkLogin'));
		}
		$p->setVariables($variables);
		if(!empty($variables['getValidationXml']))
		{
			$p->getValidationXml($fieldsDefinition);
		}
		$values = $p->check_values($fieldsDefinition);
		if(!empty($values['password1']) && !empty($values['password2']))
		{
			if($this->currentMode == 'admin')
            {
				$this->setMode('admin_w_password');
			}
			else
			{
				$this->setMode('user_w_password');
			}
			$values['password'] = self::hashPassword($values['password1']);
			unset($values['oldpassword']);
			unset($values['password1']);
			unset($values['password2']);
		}
		$this->assignArray($values);
        $this->save();
        if( !(VERSION != '1.1' && VERSION > 1.1) )
        {
            if(isset($variables['properties']))
            {
                $this->updateProperties($variables['properties']);
            }
        }
	}
	
	/*
	* check old pasword and new
	*/
	public function checkNewPassword($values){
		if(empty($values['password1']) && empty($values['password2']))
		{
			return true;
		}
		//check password
		$q = '
		SELECT
			COUNT(*)
		FROM 
			`' . static::tableName . '` u
		WHERE 
			u.id = "' . $this->id . '" AND
			(
				u.password = "' . md5($values['oldpassword']) . '" OR 
				u.password = "' . sha1($values['oldpassword']) . '"
			)
		'
		;
		//check old password
		if(dbGetOne($q) == 0)
		{
			$error['field'] = array(
				'name' => 'oldpassword'
			);
			$error['errorCode'] = 'wrong_old_password';
			return $error;
		}
		//check password
		if($values['password1'] && $values['password1'] != $values['password2'])
		{
			$error['field'] = array(
				'name' => 'password1'
			);
			$error['errorCode'] = 'passwords_not_equal';
			return $error;
		}
		//ok
		return true;
	}

	/*
	* check only new password
	*/
	public function checkNewPasswordAdmin($values){
		if(empty($values['password1']) || empty($values['password2']))
		{
			$error['field'] = array(
				'name' => 'password1'
			);
			$error['errorCode'] = 'passwords_is_empty';
			return $error;
		}
		//check password
		if($values['password1'] != $values['password2'])
		{
			$error['field'] = array(
				'name' => 'password1'
			);
			$error['errorCode'] = 'passwords_not_equal';
			return $error;
		}
		//ok
		return true;
	}
	
	public function checkLogin($values){
		$q = '
		SELECT
			COUNT(*)
		FROM
			`' . static::tableName . '`
		WHERE
			`login` = "' . dbSE($values['login']) . '" AND
			`id` != "' . $this->id . '"
		';
		if (dbGetOne($q))
		{
			$error['field'] = array(
				'name' => 'login'
			);
			$error['errorCode'] = 'login_exist';
			return $error;
		}
		//ok
		return true;
	}
	
	public function getName(){
		return $this->name  . ' ' . $this->surname;
	}
	
	protected function readExtraProperties(){
		$propertiesDesc = array();
		$q = '
		SELECT
			d.id,
			d.name,
			p.value
		FROM
			`users_properties_desc` `d`
		LEFT JOIN
			`users_properties` `p` ON p.property_id = d.id AND
			p.user_id = "' . $this->id . '"
		';
		$r = dbQuery($q);
		$data = array();
		while($row = $r->fetch())
		{
			$propertiesDesc[$row['name']] = array(
				'saveWith' => 'updateProperties',
				'__property_id' => $row['id']
			);
			if(!is_null($row['value']))
			{
				$data[$row['name']] = $row['value'];
			}
			else
			{
				$data[$row['name']] = NULL;
			}
		}
		$this->assignData($data);
		$this->fieldsDefinition = array_merge($this->fieldsDefinition, $propertiesDesc);
	}
	
	protected function updateProperties($properties){
		$p = new processing;
		$p->db_delete_entry('users_properties', array('user_id' => $this->id));
		$values = array();
		foreach($properties as $key => $value)
		{
			if(!is_null($value))
			{
				$values[] = array(
					'user_id' => $this->id,
					'property_id' => $this->fieldsDefinition[$key]['__property_id'],
					'value' => $value
				);
			}
		}
		if(sizeof($values))
		{
			$p->db_replace_entry('users_properties', $values, false, false, true, true);
		}
	}
	
	public function setPropertiesDesc(){
        if( !(VERSION != '1.1' && VERSION > 1.1) )
        {
            //properties
            $q = '
            SELECT
                *
            FROM
                `' . DB_PREFIX . 'users_properties_desc`
            ORDER BY
                `name`
            ';
            $result = dbQuery($q);
            $properties = array();
            while ($property = $result->fetch())
            {
                if($property['type']==2)
                {
                    $property['options']=explode(';',str_replace(',',';',$property['content']));
                }
                $properties[] = $property;
            }
            $this->propertiesDesc = $properties;
        }
	}

    public static function getQueryParts( $params = array ())
    {
        $queryParts = parent::getQueryParts($params);
		if(isset($params['group_id']))
		{
			$queryParts['where'][] = 't.group_id = "' . dbSE($params['group_id']) . '"';
        }
		if(!empty($params['search']))
		{
			$queryParts['where'][] = '
                t.name LIKE "%' . dbSE($params['search']) . '%"
                OR
                t.surname LIKE "%' . dbSE($params['search']) . '%"
                OR
                t.email LIKE "%' . dbSE($params['search']) . '%"
            ';
        }
        return $queryParts;
	}

    
    public static function getById( $id )
    {
        return getObject(__CLASS__, $id);
    }
    
    public function __toString()
    {
        return $this->getDisplayString();
    }    
        
    public function getDisplayString()
    {
        return trim( trim($this->name) . ' '. trim($this->surname) . ' (' . $this->login . ')');
    }

    public static function getProfileModuleName()
    {
        return 'profile';
    }
    
}
?>
