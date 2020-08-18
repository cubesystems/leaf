<?php
require_once(SHARED_PATH . '3rdpart/PayPal/Utils.php');
require_once(SHARED_PATH . '3rdpart/PayPal/EWPServices.php');

class leafPaymentProviderPayPal extends leafPaymentProvider
{
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::determineTransaction()
	 */
	public function determineTransaction()
	{
		$data = $this->getData();
		
		if ($_SERVER['REQUEST_METHOD'] == 'GET')
		{
			return (get($data, 'cm'))
				? leafTransaction::getByToken($data['cm'])
				: (get($data, 'transaction-id')
					? getObject('leafTransaction', $data['transaction-id'])
					: null
				);
		}
		else
		{
			return leafTransaction::getByToken(get($data, 'custom'));
		}
	}
	
	/**
	 * @see www_aleksandrs/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionStatus()
	 */
	public function getTransactionStatus()
	{
		$response = $this->getPayPalTransactionResponse();
		
		$parsedResponse = ($_SERVER['REQUEST_METHOD'] == 'GET') 
			? get($response, 'httpParsedResponseAr')
			: $response;
		
		switch (get($parsedResponse, 'payment_status'))
		{
			case 'Processed':
			case 'Completed':
				return leafTransaction::STATUS_PROCESSED;
			case 'Pending':
				return leafTransaction::STATUS_ACCEPTED;
			case 'Reversed':
			case 'Refunded':
				return leafTransaction::STATUS_REVERSED;
			default:
				return leafTransaction::STATUS_ERROR;
		}
	}
	
	public function getTransactionMessage()
	{
		$response = $this->getPayPalTransactionResponse();
		
		$parsedResponse = ($_SERVER['REQUEST_METHOD'] == 'GET') 
			? get($response, 'httpParsedResponseAr')
			: $response;
			
		return urldecode(get($parsedResponse, 'memo', null));
	}
	
	/**
	 * @see www_aleksandrs/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionError()
	 */
	public function getTransactionError()
	{
		$transaction = $this->determineTransaction();
		
		if ($transaction instanceof leafTransaction)
		{
			$response = $this->getPayPalTransactionResponse();
			
			if ($_SERVER['REQUEST_METHOD'] == 'GET' && !get($response, 'status'))
			{
				return get($response, 'error_msg') . get($response, 'error_no');
			}
			else
			{
				switch (get($response, 'payment_status'))
				{
					case 'Denied':
						return 'Transaction denied by merchant.';
					case 'Expired':
						return 'This authorization has expired and cannot be captured.';
					case 'Failed':
						return 'The payment has failed. This happens only if the payment was made from your customer’s bank account.';
					case 'Voided':
						return 'This authorization has been voided.';
					default:
						return 'Undetermined system error.';
				}
			}
		}
		else
		{
			return 'PDT received an HTTP GET request without a transaction ID.';
		}
		return 'Undetermined system error.';
	}
	
	/**
	 * Retrieves PayPal transaction notification data array.
	 */
	public function getPayPalTransactionResponse()
	{
		$config = leaf_get_property(array ('payment', get_class($this), ));
		$data = $this->getData();
		
		if ($_SERVER['REQUEST_METHOD'] == 'GET')
		{
			$postFields =	'cmd=' . urlencode('_notify-synch');
			if(!empty($data['tx']))
			{
				$postFields .=	'&tx=' . urlencode(htmlspecialchars($data['tx']));
			}
			$postFields .=	'&at=' . urlencode($config['identityToken']);
			
			return Utils::PPHttpPost($this->getAction() . '/cgi-bin/webscr', $postFields, true);
		}
		else
		{
			return $data;
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see www_aleksandrs/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::handlePayment()
	 */
	public function handlePayment()
	{
		$config = leaf_get_property(array ('payment', get_class($this), ));
		
		$transaction = $this->getTransaction();
		
		$address = $transaction->ordered->getBillingAddress();
		$customer = $transaction->ordered->customer;
		
		$buttonParams = array (
			'cmd' => '_xclick',
			'business' => $config['email'],
			'cert_id' => $config['siteCert']['id'],
			'charset' => 'UTF-8',
			'item_name' => 'Order #' . (!empty($transaction->description) ? $transaction->description : $transaction->ordered->id),
			'amount' => $this->getAmount(),
			'currency_code' => $this->getCurrency(),
			'return' => $this->getResponseURL(),
			'cancel_return' => $this->getResponseURL() . 'fail?transaction-id=' . $transaction->id,
			'notify_url' => $this->getResponseURL(),
			'no_shipping' => 1, 
			'rm' => 1, 
			'custom' => $this->getTransaction()->getToken(),
			// address
			'address_override' => 1,
			'address1' => $address->streetAddress,
			'address2' => $address->addressLine,
			'city' => $address->city,
			'country' => $address->country->code,
			'email' => $customer->email,
			'first_name' => $customer->firstName,
			'last_name' => $customer->lastName,
			'night_phone_b' => $address->phone, 
			'zip' => $address->postalCode,
		);
		
		if (empty($buttonParams['address2'])) unset($buttonParams['address2']);
		if (strtoupper($address->country->code) == 'US')
		{
			$buttonParams['state'] = substr($address->province, 0, 2);
		}
		
		$data = EWPServices::encryptButton(
			$buttonParams,
			realpath($config['siteCert']['path']),
			realpath($config['siteKey']['path']),
			$config['siteKey']['password'],
			$config['ppCert']['path'],
			$this->getAction(),
			PATH . 'images/pp.gif'
		);
		
		if (!get($data, 'status'))
		{
			$transaction->status = leafTransaction::STATUS_ERROR;
			$transaction->response = $data;
			$transaction->save();
			leafHttp :: redirect($this->getResponseURL() . 'fail?transaction-id=' . $transaction->id);
		}
		else
		{
			require(dirname(__FILE__) . '/templates/redirectForm.php');
			die;
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
		if (!get($config, 'action'))
		{
			throw new Exception('No action configured for payment provider class [' . __CLASS__ . ']!');
		}
		return $config['action'];
	}
	
	/**
	 * @see www_aleksandrs/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getAmount()
	 */
	public function getAmount()
	{
		$transactionCurrency = $this->getTransaction()->ordered->currency;
		$baseCurrency = knCurrency :: getCollection(array ('code' => knConfig :: getInstance()->get('paypal', 'baseCurrency')))->first();
		$amount = $this->getTransaction()->getAmount();
			
		if(!$transactionCurrency)
		{
			$transactionCurrency = knCurrency::getCurrentCurrency();
		}
			
		return sprintf('%0.2f', $amount * $transactionCurrency->rate / $baseCurrency->rate);
	}
	
	/**
	 * @see www_aleksandrs/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getCurrency()
	 */
	public function getCurrency()
	{
		$currency = knCurrency :: getCollection(array ('code' => knConfig :: getInstance()->get('paypal', 'baseCurrency')))->first();
		return $currency->code;
	}
	
	/**
	 * @see www_aleksandrs/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::verifyResponse()
	 */
	public function verifyResponse()
	{
		$data = $this->getData();
		$transaction = null;
		
		if ($_SERVER['REQUEST_METHOD'] == 'GET')
		{
			if (!get($data, 'tx') && !get($data, 'transaction-id'))
			{
				// PayPal returned a response without the tx parameter
				return false;
			}
			if (get($data, 'cm'))
			{
				$transaction = $this->determineTransaction();
			}
			// Could not initialize transaction, redirected from self
			if (get($data, 'transaction-id'))
			{
				$transaction = getObject('leafTransaction', $data['transaction-id']);
			}
		}
		elseif ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			if (!get($data, 'custom'))
			{
				// no custom field found
				return false;
			}
			elseif ($this->verifyPayPalRequest())
			{
				$transaction = $this->determineTransaction();
			}
		}
		
		return ($transaction instanceof leafTransaction);
	}
	
	/**
	 * Sends a _notify-validate request to PayPal API endpoint to verify request 
	 * credibility.
	 * 
	 * @return boolean Request verification result
	 */
	public function verifyPayPalRequest()
	{
		$config = leaf_get_property(array ('payment', get_class($this), ));
		$data = $this->getData();
		
		// Prepare request post data
		$postFields =	'cmd=' . urlencode('_notify-validate');
		foreach ($_POST as $key => $value)
		{
			$postFields .= '&' . $key . '=' . urlencode($value);
		}
		
		// Post back to PayPal to validate 
		$header = 
			'POST /cgi-bin/webscr HTTP/1.0' . "\r\n" .
			'Content-Type: application/x-www-form-urlencoded' . "\r\n" .  
			'Content-Length: ' . strlen($postFields) . "\r\n\r\n"; 
		
		$return = false;
		// Try to connect
		$fp = fsockopen($config['IPNEndpoint'], 443, $errno, $errstr, 30);
		if (!$fp)
		{
			// Could not connect
			leafError::create(array
			(
				'message' => 'Could not connect to paypal: error #' . $errno . ', "' . $errstr . '"',
				'file' => __FILE__,
				'line' => __LINE__,
				'level' => E_ERROR,
				'context' => get_defined_vars(),
			));
		}
		else
		{
			// Connected
			// Send request
			fputs($fp, $header . $postFields);
			while (!feof($fp))
			{ 
				$res = fgets($fp, 1024); 
				if (strcmp($res, "VERIFIED") == 0)
				{ 
					$return = true;
				}
			} 
			fclose ($fp);  
		}
		
		return $return;
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
	
	/**
	 * Revert transaction.
	 * @return boolean, true if sucess
	 */
	public function revertPayment($amountToRevert = null)
	{
		$response = $this->transaction->response;
		if(!$response)
		{
			return false;
		}
		else if(is_string($response))
		{
			$response = unserialize($response);
		}

		// Set request-specific fields.
		$transactionID = urlencode(!empty($response['tx']) ? $response['tx'] : $response['txn_id']);
		$refundType = urlencode('Full');	
		// Add request-specific fields to the request string.
		$nvpStr = "&TRANSACTIONID=$transactionID&REFUNDTYPE=$refundType";

		// Execute the API operation; see the PPHttpPost function above.
		$httpParsedResponseAr = $this->PPHttpPost('RefundTransaction', $nvpStr);

		if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
		{
			return true;
		}
		else
		{
			trigger_error('cant reverse payment ' . print_r($httpParsedResponseAr, true), E_USER_ERROR);
			return false;
		}
	}
	
	public function PPHttpPost($methodName_, $nvpStr_)
	{
		$config = leaf_get_property(array ('payment', get_class($this), ));
		
		// Set up your API credentials, PayPal end point, and API version.
		$API_UserName = urlencode($config['username']);
		$API_Password = urlencode($config['password']);
		$API_Signature = urlencode($config['signature']);
		$API_Endpoint = $config['APIEndpoint'];
		$version = urlencode('51.0');
	
		// Set the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
	
		// Turn off the server and peer verification (TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
	
		// Set the API operation, version, and API signature in the request.
		$nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";
	
		// Set the request as a POST FIELD for curl.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
	
		// Get response from the server.
		$httpResponse = curl_exec($ch);
	
		if(!$httpResponse) {
			exit("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
		}
	
		// Extract the response details.
		$httpResponseAr = explode("&", $httpResponse);
	
		$httpParsedResponseAr = array();
		foreach ($httpResponseAr as $i => $value) {
			$tmpAr = explode("=", $value);
			if(sizeof($tmpAr) > 1) {
				$httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
			}
		}
	
		if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
			exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
		}
	
		return $httpParsedResponseAr;
	}	
	
}
