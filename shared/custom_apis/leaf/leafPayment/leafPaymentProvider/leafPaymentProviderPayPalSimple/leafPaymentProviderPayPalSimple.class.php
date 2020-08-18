<?php

class leafPaymentProviderPayPalSimple extends leafPaymentProvider
{

	protected $paypalDetailsResponse = null;
	
	private function getUrlHeaders($bodyData){
	    
		$config = leaf_get_property(array ('payment', get_class($this), ));
		
		//create headers
	    $params = array("http" => array( 
			"method"    => "POST",
		 	"content"   => $bodyData,
		 	"header"    => "CONTENT-TYPE: application/x-www-form-urlencoded\r\n" .
			"X-PAYPAL-SECURITY-USERID: " . $config['username'] . "\r\n" .
			"X-PAYPAL-SECURITY-SIGNATURE: " . $config['signature'] . "\r\n" .
			"X-PAYPAL-SECURITY-PASSWORD: " . $config['password'] . "\r\n" .
			"X-PAYPAL-APPLICATION-ID: " . $config['applicationId'] . "\r\n" .
			"X-PAYPAL-REQUEST-DATA-FORMAT: " . $config['requestFormat'] . "\r\n" .
			"X-PAYPAL-RESPONSE-DATA-FORMAT: " . $config['responseFormat'] . "\r\n" 
		));
		
		return $params;		
	}

	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::determineTransaction()
	 */
	public function determineTransaction()
	{

		$data = $this->getData();		

		if($_SERVER['REQUEST_METHOD'] == 'GET')
		{			
			if( get( $data, 'transaction-id' ) )
            {
				return getObject('leafTransaction', $data['transaction-id']);	
			}
			$data = $this->getPayPalTransactionResponse();	
		}
		else
		{	               
			$data = $this->getPayPalTransactionResponse();
		}
		return get( $data, 'trackingId' ) ? leafTransaction::getByToken( get( $data, 'trackingId' ) ) : null;
	}
	
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionStatus()
	 */
	public function getTransactionStatus()
	{
		$response = $this->getPayPalTransactionResponse();
				
		switch ( get( $response, 'status' ) )
		{
			case 'COMPLETED':
				return leafTransaction::STATUS_PROCESSED;
			case 'CREATED':
			case 'PENDING':
			case 'PROCESSING':
				return leafTransaction::STATUS_ACCEPTED;
			/*
			case 'Reversed':
			case 'Refunded':
				return leafTransaction::STATUS_REVERSED;
			*/
			default:
				return leafTransaction::STATUS_ERROR;
		}
	}
	
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionError()
	 */
	public function getTransactionError()
	{
		$transaction = $this->determineTransaction();
		
		if ($transaction instanceof leafTransaction)
		{
			//$response = $this->getPayPalTransactionResponse();
			/**
			 * @todo: apstrādāt kļūdas
			 */
			return 'Undetermined system error.';
		}
		else
		{
			return 'PDT received an HTTP GET request without a transaction ID.';
		}
	}
	
	/**
	 * Retrieves PayPal transaction notification data array.
	 */
	public function getPayPalTransactionResponse()
	{

		//nav ko n reizes greizties pie paypal!
		if(isset($this->paypalDetailsResponse))
		{
			return $this->paypalDetailsResponse;
		}
    			
		$config = leaf_get_property(array ('payment', get_class($this), ));

		$data = $this->getData();
		
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && get( $data, 'pay_key' ) )
		{
			$data['payKey'] = $data['pay_key']; 
		}
		if ( get( $data, 'payKey' ) )
		{
        	$keyArray = array();
			try
			{				
				//Create request payload with minimum required parameters
				$bodyParams = array (
					"requestEnvelope.errorLanguage" => "en_US",
					"payKey" => $data['payKey'],
				);

				// convert payload array into url encoded query string
				$bodyData = http_build_query($bodyParams, "", chr(38));
				
				//create request and add headers
			    $params = $this->getUrlHeaders($bodyData); 
			
			    //create stream context
			     $ctx = stream_context_create($params);
			    
			
			    //open the stream and send request
			     $fp = @fopen($config['detailsUrl'], "r", false, $ctx);
			
			    //get response
			  	 $response = stream_get_contents($fp);
			
			  	//check to see if stream is open
			     if ($response === false) {
			        throw new Exception("php error message = " . "$php_errormsg");
			     }
			           
			    //close the stream
			     fclose($fp);
		    
			    //parse the ap key from the response
			    $keyArray = explode("&", $response);
			        
			    foreach ($keyArray as $rVal){
			    	list($qKey, $qVal) = explode ("=", $rVal);
						$kArray[$qKey] = $qVal;
			    }			   
			}
			catch(Exception $e)
			{
			  	  throw new Exception("php error message = " . $e->getMessage());
			}				
			return $this->paypalDetailsResponse = $kArray;			
		}
		else
		{
			return $data;
		}		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::handlePayment()
	 */
	public function handlePayment()
	{
		$config = leaf_get_property( array( 'payment', __CLASS__ ) );
		$email = get( $config, 'email' );
        
        $transaction = $this->getTransaction();

        $ordered = $transaction->getOrdered();
        if( !empty( $ordered ) && method_exists( $ordered, 'getPaymentEmail' ) )
        {
            $paymentEmail = $ordered->getPaymentEmail();
            
            if( $paymentEmail )
            {
                $email = $paymentEmail;
            }
        }
        
		if( !isset($email) || trim($email) == '' ){
			error_log('Reciever e-email not set for #' . $transaction->ordered->id . '.');
			leafHttp::redirect($this->getResponseURL() . 'fail?transaction-id=' . $transaction->id);
		}
		
		//Create request payload with minimum required parameters
		$bodyParams = array (
			"requestEnvelope.errorLanguage"         => "en_US",
			"actionType"                            => "PAY",
			"currencyCode"                          => $this->getCurrency(),
			"cancelUrl"                             => $this->getResponseURL() . 'fail?transaction-id=' . $transaction->id,
			"returnUrl"                             => $this->getResponseURL() . '?payKey=${paykey}',
			"ipnNotificationUrl"                    => $this->getResponseURL(),
			"receiverList.receiver(0).email"        => $email,
			"receiverList.receiver(0).amount"       => sprintf('%0.2f', $this->getAmount()),
			"receiverList.receiver(0).invoiceId"    => $transaction->ordered->id,
			"trackingId"                            => $this->getTransaction()->getToken(),
			"memo"                                  => 'Order #' . $transaction->ordered->id,
			);
        
		// convert payload array into url encoded query string
        $bodyData = http_build_query($bodyParams, "", chr(38));
        
		try
		{
		
		    //create request and add headers
			$params = $this->getUrlHeaders($bodyData);
			
		    //create stream context
			 $ctx = stream_context_create($params);
		        
			//open the stream and send request
		     $fp = fopen($config['payUrl'], "r", false, $ctx);
		
		    //get response
             $response = stream_get_contents($fp);
		
		  	//check to see if stream is open
		     if ($response === false) {
				throw new Exception("php error message = " . "$php_errormsg");
		     }
		           
		    //close the stream
		     fclose($fp);
		
		    //parse the ap key from the response
		    $keyArray = explode("&", $response);
		        
		    foreach ($keyArray as $rVal){
		    	list($qKey, $qVal) = explode ("=", $rVal);
					$kArray[$qKey] = $qVal;
            }
		       
		    //set url to approve the transaction		
			if ( get( $kArray, 'responseEnvelope.ack', false) != "Success")
            {
				$transaction->status = leafTransaction::STATUS_ERROR;
				$transaction->response = serialize($response);
				$transaction->error = get($kArray, 'error(0).message');
				$transaction->save();
				leafHttp :: redirect($this->getResponseURL() . 'fail?transaction-id=' . $transaction->id);
			}
			else
            {
				leafHttp :: redirect($this->getAction() . "?cmd=_ap-payment&paykey=" . $kArray["payKey"]);
				die;
			}
		}
		catch(Exception $e)
        {
		  	  throw new Exception("php error message = " . $e->getMessage());
		}
	}
	
	/**
	 * Retrieves the action for the redirect form.
	 * 
	 * @return string
	 */
	public function getAction()
	{
		$config = leaf_get_property(array ('payment', get_class($this), ));
		if ( !get( $config, 'action' ) )
		{
			throw new Exception('No action configured for payment provider class [' . __CLASS__ . ']!');
		}
		return $config['action'];
	}
	
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::verifyResponse()
	 */
	public function verifyResponse()
	{
		$data = $this->getData();
		$transaction = null;
		
		if ($_SERVER['REQUEST_METHOD'] == 'GET')
		{
			if ( !get( $data, 'payKey' ) && !get( $data, 'transaction-id' ) )
			{
				// PayPal returned a response without the tx parameter
				return false;
			}
			if ( get( $data, 'payKey' ) )
			{
				$transaction = $this->determineTransaction();
			}
			// Could not initialize transaction, redirected from self
			if ( get( $data, 'transaction-id' ) )
			{
				$transaction = getObject('leafTransaction', $data['transaction-id']);
			}
		}
		elseif ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			if ( !get( $data, 'pay_key' ) )
			{
				// no custom field found
				return false;
			}
			$transaction = $this->determineTransaction();
		}		
		return ($transaction instanceof leafTransaction);
	}
		
	/**
	 * Gets IPN listener URI.
	 * 
	 * @return string IPN listener URI
	 */
	public function getListenerUrl()
	{
		return leafPayment :: getTypeHandlerUrl(__CLASS__);
	}
	
}
