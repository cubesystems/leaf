<?php
class leafEcommMerchant
{
	/**
	 * Variable $url
	 * @access private
	 * @var string
	 */
	var $url;
	 /**
	 * Variable $keystore
	 * @access private
	 * @var string
	 */
	var $keystore;
	
	/**
	 * Variable $keystorePassword
	 * @access private
	 * @var string
	 */
	var $keystorePassword;

	/**
	 * Variable $verbose
	 * @access private
	 * @var boolean
	 */
    var $verbose;

	/**
	 * Variable $proxy
	 * @access private
	 * @var string
	 */
    var $proxy;
	
	private $domain;
	
    private $outgoingIP;

    public static $currencyTranslations = array
    (
        'LVL' => '428',
        'EUR' => '978',
        'RUB' => '643',
        'USD' => '840',
    );

	/**
	 * Constructor sets up {$link, $keystore, $keystorePassword, $verbose}
	 * @param string $url url to declare
	 * @param string $keystore value of the keystore
	 * @param string $keystorePassword value of the keystorePassword
	 * @param boolean $verbose TRUE to output verbose information. Writes output to STDERR, or the file specified using CURLOPT_STDERR.
	*/
	public function __construct($verbose = false)
	{
        $config = leaf_get('properties', 'payment', 'leafPaymentProviderECOMM');
        if(empty($config))
        {
            $config = leaf_get('properties', 'ecomm');
        }
		
		$this->domain = $config['domain'];
		$this->url = $config['domain'] . ':8443/ecomm/MerchantHandler';
		$this->keystore = $config['cert_url'];
		$this->keystorePassword = $config['cert_pass'];
		$this->verbose = $verbose;
		if (isset($config['outgoingIP']))
		{
			$this->outgoingIP = $config['outgoingIP'];
		}
		if (isset($config['proxy']))
		{
			$this->proxy = $config['proxy'];
		}
	}

	/**
	 * Send parameters
	 * 
	 * @param array post parameters
	 * @return string result
	 */
	public function sentPost($params)
	{
		if (!file_exists($this->keystore))
		{
			throw new Exception('Keystore file does not exist: ' . $this->keystore);
		}
		
		if (!is_readable($this->keystore))
		{
			throw new Exception('Keystore file is not readable: ' . $this->keystore);
		}

		$postArguments = array ();
		foreach ($params as $key => $value)
	  {
			 $postArguments[] = $key . '=' . $value;
		}
		$postData = implode('&', $postArguments);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_VERBOSE, (boolean) $this->verbose);
		curl_setopt($curl, CURLOPT_URL, $this->url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		if ($this->outgoingIP)
		{
			curl_setopt($curl, CURLOPT_INTERFACE, $this->outgoingIP);
		}
        curl_setopt($curl, CURLOPT_POST, true);
        if(!empty($this->proxy))
        {
            curl_setopt($curl, CURLOPT_URL, $this->proxy);
        }
        else
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($curl, CURLOPT_SSLCERT, $this->keystore);
            curl_setopt($curl, CURLOPT_CAINFO, $this->keystore);
            curl_setopt($curl, CURLOPT_SSLKEYPASSWD, $this->keystorePassword);
        }
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);

		if (curl_error($curl))
		{
			$result = curl_error($curl);
            trigger_error($result, E_USER_ERROR);
        }

        if(!empty($this->proxy))
        {
            $proxyResponse = json_decode($result);
            $result = $proxyResponse->response;
            if(isset($proxyResponse->error))
            {
                trigger_error($result, E_USER_ERROR);
            }
        }

        curl_close($curl);
		
		$responseData = self :: getResponseArray($result);
		
		if(!empty($responseData['error']) && sizeof($responseData['error']) == 1)
		{
			trigger_error($result, E_USER_ERROR);
		}
		
		return $responseData;
	}

	/**
	 * Registering of SMS transaction
	 * @param int $amount transaction amount in minor units, mandatory 
	 * @param int $currency transaction currency code, mandatory 
	 * @param string $ip client�s IP address, mandatory
	 * @param string $description description of transaction, optional
	 * @param string $language authorization language identificator, optional 
	 * @return string TRANSACTION_ID
	 */
	public function startSMSTrans($amount, $currency, $ip, $description, $language)
	{
		$params = array (
			'command' => 'v',
			'amount'  => $amount,
			'currency'=> $currency,
			'client_ip_addr' => $ip,
			'description' => $description,
			'language'=> $language,
		);
		
		$response = $this->sentPost($params);
		return $response;
	}

	/**
	 * Registering of DMS authorisation
	 * @param int $amount transaction amount in minor units, mandatory 
	 * @param int $currency transaction currency code, mandatory 
	 * @param string $ip client�s IP address, mandatory
	 * @param string $description description of transaction, optional
	 * @param string $language authorization language identificator, optional 
	 * @return string TRANSACTION_ID
	 */
	public function startDMSTrans($amount, $currency, $ip, $description, $language)
	{
		$params = array (
			'command' => 'a',
			'msg_type'=> 'DMS',
			'amount'  => $amount,
			'currency'=> $currency,
			'client_ip_addr'	  => $ip,
			'description'	=> $description,
	  
		);
		$response = $this->sentPost($params);
		return $response;
	}

	/**
	 * Making of DMS transaction
	 * @param int $auth_id id of previously made successeful authorisation
	 * @param int $amount transaction amount in minor units, mandatory 
	 * @param int $currency transaction currency code, mandatory 
	 * @param string $ip client�s IP address, mandatory
	 * @param string $description description of transaction, optional
	 * @return string RESULT, RESULT_CODE, RRN, APPROVAL_CODE
	*/
	public function makeDMSTrans($auth_id, $amount, $currency, $ip, $description, $language)
	{
		$params = array (
			'command' => 't',
			'msg_type'=> 'DMS',
			'trans_id' => $auth_id, 
			'amount'  => $amount,
			'currency'=> $currency,
			'client_ip_addr' => $ip,
			'description'	=> $description,
			'language'=> $language,	
		);

		$str = $this->sentPost($params);
		return $str;
	}

	/**
	 * Transaction result
	 * @param int $transactionId transaction identifier, mandatory
	 * @param string $ip client's IP address, mandatory
	 * @return string RESULT, RESULT_CODE, 3DSECURE, AAV, RRN, APPROVAL_CODE
	 */
	public function getTransResult($transactionId, $ip)
	{
		$params = array (
			'command' => 'c',
			'trans_id' => urlencode($transactionId),
			'client_ip_addr' => $ip,
		);
		$result = $this->sentPost($params);
		
		return $result;
	}

	protected static function getResponseArray($response)
	{
		$data = array ();
		$tmp = explode("\n", $response);
		foreach ($tmp as $tmpData)
		{
			$tmpData = explode(': ', $tmpData);
			$tmpData2 = $tmpData;
			unset($tmpData2[0]);
			$data[$tmpData[0]] = implode(': ', $tmpData2);
		}
		return $data;
	}
	
	/**
	 * Transaction reversal
	 * @param int $transactionId transaction identifier, mandatory
	 * @param int $amount transaction amount in minor units, mandatory 
	 * @return string RESULT, RESULT_CODE
	 */
	public function reverse($transactionId, $amount)
	{
		$params = array (
			'command' => 'r',
			'trans_id' => urlencode($transactionId), 
			'amount' => $amount,
		);

		$result = $this->sentPost($params);
		return $result;
	}

	/**
	 * Closing of business day
	 * @return string RESULT, RESULT_CODE, FLD_075, FLD_076, FLD_087, FLD_088
	 */
	public function closeDay()
	{
	  $params = array (
			'command' => 'b',
		);
		
		$result = $this->sentPost($params);
		return $result;
	}

}
