<?php
class leafPayment
{
	const TYPE_DIGI 	= 'leafBankPaymentProviderDIGI';
	const TYPE_ECOMM 	= 'leafPaymentProviderECOMM';
	const TYPE_GEMONEY 	= 'leafBankPaymentProviderGEmoney';
	const TYPE_NORDEA 	= 'leafBankPaymentProviderNordea';
	const TYPE_NORVIK 	= 'leafBankPaymentProviderNorvik';
	const TYPE_SEB 		= 'leafBankPaymentProviderSeb';
	const TYPE_SEB_EE   = 'leafBankPaymentProviderSebEE';
	const TYPE_SWEDBANK = 'leafBankPaymentProviderSwedbank';
	const TYPE_DNB      = 'leafBankPaymentProviderDnb';
    const TYPE_PBS 		= 'leafBankPaymentProviderPBS';
    const TYPE_PAYPAL   = 'leafPaymentProviderPayPal';
    const TYPE_PAYPAL_SIMPLE = 'leafPaymentProviderPayPalSimple';
	const TYPE_WIRE     = 'leafPaymentProviderWire';
	
	/**
	 * Current payment provider to handle transaction.
	 * 
	 * @var null|leafPaymentProvider
	 */
	protected $paymentProvider = null;
	
	/**
	 * Returns available payment provider type list.
	 * 
	 * @return array 
	 */
	public static function listPaymentProviderTypes()
    {
        $configuredProviders = array();
        $paymentsConfig = leaf_get_property( array( 'payment' ), false );
        
        if( is_array( $paymentsConfig ) )
        {
            $configuredProviders = array_keys( $paymentsConfig );
        }
        
		return $configuredProviders;
	}
	
	/**
	 * Factories payment provider by type.
	 * 
	 * @param string $paymentProviderType One of leafPayment::TYPE_* constants
	 * @throws UnexpectedValueException
	 * @return leafPaymentProvider
	 */
	public static function factoryPaymentProvider($paymentProviderType = null)
    {
        if (!in_array($paymentProviderType, self :: listPaymentProviderTypes()))
		{
			throw new UnexpectedValueException('Non existant payment provider type: [' . $paymentProviderType . '].');
		}
		
		return new $paymentProviderType;
	}
	
	/**
	 * Mutator for the payment provider member.
	 * 
	 * @param leafPaymentProvider $paymentProvider
	 */
	public function setPaymentProvider(leafPaymentProvider $paymentProvider)
	{
		$this->paymentProvider = $paymentProvider;
	}	
	
	/**
	 * Accessor for the payment provider member.
	 *
	 * @throws BadMethodCallException
	 * @return leafPaymentProvider 
	 */
	public function getPaymentProvider()
	{
		if (is_null($this->paymentProvider))
		{
			throw new BadMethodCallException('Not bound with payment provider.');
		}
		return $this->paymentProvider;
	}
	
	/**
	 * Initializes the transaction handling process.
	 * 
	 * @param leafTransaction $transaction
	 */
	public function handleTransaction(leafTransaction $transaction)
	{
		$transaction->updateStatus(leafTransaction :: STATUS_INITIALIZED);
		
		$this->setPaymentProvider(self :: factoryPaymentProvider($transaction->getProviderType()));
		$paymentProvider = $this->getPaymentProvider();
		$paymentProvider->setResponseURL(self::getTypeHandlerUrl(get_class($paymentProvider)));
		
		return $this->getPaymentProvider()->setData()->handleTransaction($transaction);
	}
	
	/**
	 * Retrieves transaction from verified response.
	 * 
	 * @return leafTransaction
	 */
	public function getTransaction()
	{
		return $this->getPaymentProvider()->determineTransaction();
	}
	
    public function getRequestType( $data )
    {
        $provider = $this->getPaymentProvider();
        
        if( method_exists( $provider, 'getRequestType' ) )
        {
            return $provider->getRequestType( $data );
        }
        
        return;
    }
    
	/**
	 * Response verification.
	 * 
	 * @param array $data
	 * @return true|false|null boolean on success or failure, null on error.
	 */
	public function verifyResponse(array $data = array ())
	{
		try
		{
			$paymentProvider = $this->getPaymentProvider();
			$paymentProvider->setData($data);
			return $paymentProvider->verifyResponse();
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	public function logResponse(leafTransaction $transaction)
	{
		$paymentProvider = $this->getPaymentProvider();
		$paymentProvider->logResponse($transaction);
	}
	
	/**
	 * Returns the new transaction status.
	 * 
	 * @return int One of leafTransaction::STATUS_* constants
	 */
	public function getTransactionStatus()
	{
		return $this->getPaymentProvider()->getTransactionStatus();
	}
	
	/**
	 * Returns the transaction error description.
	 * 
	 * @return strubg
	 */
	public function getTransactionError()
	{
		return $this->getPaymentProvider()->getTransactionError();
    }

	/**
	 * Returns the transaction customer message.
	 * 
	 * @return string|null
	 */
	public function getTransactionMessage()
	{
		return $this->getPaymentProvider()->getTransactionMessage();
	}
	
	/**
	 * Determines content tree payment handler object url.
	 * 
	 * @param string $paymentType One of leafPayment :: TYPE_* constants
	 * @return string Response handler url
	 */
	public static function getTypeHandlerUrl($paymentType)
	{
        $url = null;
        
		$queryParts = objectTree::getObjectsQueryParts(
			'payment/handler',
			false,
			true
		);

		$queryParts['where'][] = 'x.type = \'' . dbSE($paymentType) . '\'';
		$row = dbGetRow($queryParts);
        
		if (!empty($row))
		{
			$url = orp($row['id']);

            if (!empty($url))
            {
                // force https for client return url unless explicitly specified otherwise
                if (
                    (!defined("FORCE_HTTPS_FOR_PAYMENT_HANDLERS"))
                    ||
                    (FORCE_HTTPS_FOR_PAYMENT_HANDLERS)
                )
                {
                    $url = preg_replace('/^http:\/\//i', 'https://', $url);
                }
            }
		}
        
		$className = $paymentType;
		$handlerCheckVariable = 'paymentHandlerNeeded';
        if (!$url && $className::$$handlerCheckVariable)
		{
			throw new DomainException('Could not find a payment handler in content tree for type: [' . $paymentType . '].');
		}
        
        return $url;
	}
	
}

