<?php
class leafTransaction extends leafBaseObject
{
	const tableName = 'leafTransactions';

	protected
		$objectId,
		$objectClass, 
		$objectSearchString, 
		$token, 
		$provider, 
		$type,
		$name,
		$personCode,
		$referenceNo,
		$amount,
		$reverseAmount,
		$currency,
		$description, 
		$status,
		$error,
		$response,
		$languageName,
		$add_date,
		$author_ip;
		
	// Derived
	protected
		$customer,
		$ordered;

	protected $fieldsDefinition = array
	(
		'objectId' 			 => array( 'optional'  => true, ),
		'objectClass' 		 => array( 'optional'  => true, ),
		'objectSearchString' => array( 'optional'  => true, ),
		'token' 			 => array( 'not_empty' => true, ),
		'provider' 			 => array( 'not_empty' => true, ),
		'referenceNo' 		 => array( 'optional'  => true, 'empty_to_null' => true, ),
		'amount' 			 => array( 'not_empty' => true, ),
        'reverseAmount'  	 => array( 'not_empty' => true, ),
		'currency' 			 => array( 'not_empty' => true, ),
		'description' 		 => array( 'optional'  => true, 'empty_to_null' => true, ),
		'status' 			 => array( 'not_empty' => true, 'on_empty' => self::STATUS_CREATED, ),
		'response' 			 => array( 'type' => 'array', 'optional'  => true, 'empty_to_null' => true, ),
		'error' 			 => array( 'optional'  => true, 'empty_to_null' => true, ),
		'languageName' 		 => array( 'optional'  => true ),
	);

	protected static $_tableDefsStr = array
	(
		self::tableName => array
		(
			'fields' => '
				id		 		   int auto_increment
				objectId		   int
				objectClass	       varchar(255)
				objectSearchString varchar(255)
				token			   varchar(32)
				provider		   varchar(64)
				referenceNo	       varchar(127)
				amount			   int
				reverseAmount	   int
				currency		   varchar(3)
				description	       text
				status			   tinyint(1)
				error			   text
				response		   text
				languageName	   varchar(20)
				add_date		   datetime
				author_ip		   varchar(255)
			',
			'indexes' => '
				primary id
				index objectId
				index token
			',
			'engine' => 'InnoDB',
		),
	);
	
	const STATUS_CREATED     = 1;
	const STATUS_INITIALIZED = 2;
	const STATUS_ACCEPTED    = 3;
	const STATUS_PROCESSED   = 4;
	const STATUS_ERROR       = 5;
	const STATUS_REVERSED    = 6;

    protected $currentMode = 'default';

	protected $modes = array
    (
        'default' => array
        (
            'objectId', 'objectClass', 'objectSearchString',
            'token', 'provider', 'referenceNo', 'amount', 'currency',
            'description', 'status', 'response',
            'error', 'languageName'
        ),
        'revert' => array
        (
            'reverseAmount', 'status'
        ),
    );

	public static function _autoload( $className )
	{
		parent::_autoload( $className );
		dbRegisterRawTableDefs( self::$_tableDefsStr );
	}
	
	public static function getQueryParts($params = array ())
	{
		$queryParts['select'][]	= 't.*';
		$queryParts['from'][] = '`' . self::getClassTable(__CLASS__) . '` AS `t`';
		$queryParts['orderBy'] = 't.id DESC';
		
		if ( get( $params, 'token' ) )
		{
			$queryParts['where'][] = 't.token = \'' . dbSE($params['token']) . '\'';
		}
		
		if ( get( $params, 'referenceNo' ) )
		{
			$queryParts['where'][] = 't.referenceNo = \'' . dbSE($params['referenceNo']) . '\'';
		}
		
		if ( get( $params, 'name' ) )
		{
			$queryParts['where'][] = 't.name = \'' . dbSE($params['name']) . '\'';
		}
		
		if ( get( $params, 'provider' ) )
		{
			$queryParts['where'][] = 't.provider = \'' . dbSE($params['provider']) . '\'';
		}
			
		if ( get( $params, 'dateFrom' ) && get( $params, 'dateTo' ) )
		{
			$queryParts['where'][] = 't.add_date BETWEEN \'' . dbSE($params['dateFrom']) . ' 00:00:00\' AND \'' . dbSE($params['dateTo']) . ' 23:59:59\'';
		}
		elseif ( get( $params, 'dateFrom' ) )
		{
			$queryParts['where'][] = 't.add_date >= \'' . dbSE($params['dateFrom']) . ' 00:00:00\'';
		}
		elseif ( get( $params, 'dateTo' ) )
		{
			$queryParts['where'][] = 't.add_date <= \'' . dbSE($params['dateTo']) . ' 23:59:59\'';
		}

        if ( get( $params, 'objectId' ) )
        {
            $queryParts['where'][] = 't.objectId = "' . dbSE( $params['objectId'] ) . '"';
        }
        if ( get( $params, 'objectClass' ) )
        {
            $queryParts['where'][] = 't.objectClass = "' . dbSE( $params['objectClass'] ) . '"';
        }
        if ( get( $params, 'onlyPaid' ) )
        {
            $queryParts['where'][] = 't.status IN("' . self::STATUS_ACCEPTED . '", "' . self::STATUS_PROCESSED . '")';
        }
		
		if ( get( $params, 'search' ) )
		{
			//$queryParts['leftJoins'][] = 'leafOrderItems `i` ON `i`.`orderId` = `o`.`id`';
			$queryParts['groupBy'] = '`t`.`id`';
			
            $search = self::getSearchPattern($params['search']);
			$searchFields = array (
				"round(t.amount / 100, 2)                   LIKE '{$search}'",
				"t.add_date                                 LIKE '{$search}'",
				"date_format(t.add_date, '%Y.%m.%d %k:%i')  LIKE '{$search}'",
				"date_format(t.add_date, '%d.%m.%Y %k:%i')  LIKE '{$search}'",
			);
			
			$queryParts['where'][] = '(' . implode(') OR (', $searchFields) . ')';
		}
		
		return $queryParts;
	}
	
	public function variablesSave($variables, $fieldsDefinition = null, $mode = false)
	{
		if (empty($this->id))
		{
			$variables['token'] = self :: generateToken();
			$variables['status'] = self :: STATUS_CREATED;
			if (is_array($mode))
			{
				$mode[] = 'token';
				$mode[] = 'status';
			}
		}
		
		return parent :: variablesSave($variables, $fieldsDefinition, $mode);
	}
	
	public static function generateToken()
	{
		do
		{
			$token = md5(uniqid(time(), true) . microtime());
			$qp = self :: getQueryParts(array (
				'token' => $token,
			));
			$qp['select'] = 'COUNT(*) > 0';
			$tokenExists = (boolean) dbGetOne($qp);
		}
		while ($tokenExists);
		
		return $token;
    }

	/**
	 * Revert transaction.
	 * @return boolean, true if sucess
	 */
	public function revert($amountToRevert = null)
    {
        $this->setMode('revert');
		$provider = new $this->provider();
        $provider->setTransaction($this);
		$revertStatus = $provider->revertPayment($amountToRevert);
		if($revertStatus)
        {
            $this->reverseAmount = $amountToRevert;
			$this->updateStatus(self::STATUS_REVERSED);
		}
		return $revertStatus;
	}
	
	/**
	 * Retrieves transaction by token.
	 * 
	 * @param string $token
	 * @return leafTransaction
	 */
	public static function getByToken($token)
	{
		return self :: getCollection(array (
			'token' => $token, 
		))->first();
	}
	
	/**
	 * Retrieves transaction by reference number.
	 * 
	 * @param string $referenceNumber
	 * @return leafTransaction
	 */
	public static function getByReferenceNumber($referenceNumber)
	{
		return self :: getCollection(array (
			'referenceNo' => $referenceNumber, 
		))->first();
	}
	
	/**
	 * Retrieves transaction payment provider type.
	 * 
	 * @return string Payment provider type, one of leafPayment :: TYPE_* constants
	 */
	public function getProviderType()
	{
		return $this->provider;
	}
	
	/**
	 * Returns the transaction token.
	 * 
	 * @return string
	 */
	public function getToken()
	{
		return $this->__get('token');
	}
	
	/**
	 * Returns transaction amount.
	 * 
	 * @return string
	 */
	public function getAmount()
	{
		return sprintf('%0.2f', $this->__get('amount') / 100);
	}
	
	/**
	 * Returns transaction currency. 
	 * 
	 * @return string
	 */
	public function getCurrency()
	{
		return $this->__get('currency');
	}
	
	/**
	 * Returns transaction description. 
	 * 
	 * @return string
	 */
	public function getDescription()
	{
		return $this->__get('description');
	}

	/**
	 * Updates transaction status.
	 * 
	 * @param int $status
	 */
	public function updateStatus($status)
    {
        if($this->status == $status)
        {
            return;
        }
        else if(
            $this->status == self::STATUS_REVERSED || 
            (
                $this->status == self::STATUS_PROCESSED && $status != self::STATUS_REVERSED
            )
        )
        {
            trigger_error('Status updating to "' . $status  . '" denied, because of current status "' . $this->status . '"', E_USER_ERROR);
        }
		$this->status = $status;
		$this->save();
	}
	
	/**
	 * Sets the $customer member variable.
	 */
	public function setCustomer()
	{
		$this->customer = $this->__get('ordered')->customer;
	}
	
	/**
	 * Sets the $ordered member variable.
	 */
	public function setOrdered()
	{
		$this->ordered = $this->getOrdered();
    }

    public function getOrdered()
    {
		return getObject($this->objectClass, $this->objectId);
    }
	
	protected static function getSearchPattern( $userInputStr )
	{
		$search = trim($userInputStr);
		$search = str_replace('%', '\%', $search);
		$search = str_replace('_', '\_', $search);
		$search = preg_replace('/\s+/u', ' ', $search);
        $search = dbSE($search);
        $search = str_replace(' ', '%', $search);
        $search = '%' . $search . '%';
		return $search;
	}
}
