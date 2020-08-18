<?php
abstract class leafBankPaymentProvider extends leafPaymentProvider
{
	const DATA_REQUEST = 1;
	const DATA_RESPONSE = 2;
	const DATA_PUBLIC = 3;
	const DATA_ENCODE = 4;
	const DATA_DECODE = 5;
    const DATA_AUTH_ENCODE = 6;
    const DATA_AUTH_REQUEST = 7;
    const DATA_AUTH_RESPONSE = 8;
    const DATA_AUTH_DECODE = 9;

	/**
	 * Data signature.
	 * 
	 * @var string
	 */
	protected $signature = null;
	
	/**
	 * Signature field.
	 * 
	 * @var string
	 */
	protected $signatureField = null;
	
	/**
	 * Field list to be displayed in the public form filled in by the user.
	 *  
	 * @var array
	 */
	protected $publicFieldList = array ();
	/**
	 * Field list of fields to be sent to the authentication service.
	 * 
	 * @var array
	 */
	protected $encodeFieldList = array ();
	/**
	 * Field list of fields to be sent to the authentication service.
	 * 
	 * @var array
	 */
	protected $requestFieldList = array ();
	/**
	 * Field list of fields expected to be returned from the authentication service.
	 * 
	 * @var array
	 */
	protected $responseFieldList = array ();
	/**
	 * Field list of fields to be used in generating verification string of the 
	 * data provided by the authentication service.
	 * 
	 * @var array
	 */
	protected $decodeFieldList = array ();
	
	protected $method = 'post';
	
	/**
	 * Whether encoded data is to be signed. 
	 * 
	 * @var boolean
	 */
	protected $signatureRequired = true;
	
	/**
	 * Path to private key, relative to CERTS_PATH.
	 * 
	 * @var string
	 */
	protected $privateKeyPath = 'private.pem';
	
	/**
	 * Path to public key, relative to CERTS_PATH.
	 * 
	 * @var string
	 */
	protected $publicKeyPath = 'public.pem';
	
	/**
	 * Auth type requires signature verification or not.
	 * 
	 * @var boolean
	 */	
	protected $requireSignatureVerification = true;
	
	/**
	 * Language code list.
	 * key - site language code
	 * value - bank request language code
	 * Fallback value is the first value.
	 * 
	 * @var array
	 */
	protected $languageCodeList = array ();
	
	/**
	 * Appends the signature to the data to be sent. 
	 * 
	 * @param array $data
	 * @param string $signature signature
	 * @return void
	 */
	public function appendSignature(array &$data)
	{
		$data[$this->getSignatureField()] = $this->getSignature();
	}
	
	/**
	 * Retrieves the action for the redirect form.
	 * 
	 * @return string
	 */
	public function getAction()
	{
		$config = leaf_get_property(array ('payment', get_class($this), ));
		return get( $config, 'action', $this->action );
	}
	
	/**
	 * Retrieves the method for the redirect form.
	 * 
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}
	
	/**
	 * Get request type from service code
	 * 
	 * @return string
	 */
    public function getRequestType( $data )
    {
        return;
    }
    
	/**
	 * Signature mutator
	 * 
	 * @param string $signature
	 * @return void
	 */
	public function setSignature($signature)
	{
		$this->signature = $signature;
	}
	
	/**
	 * Getter for the signature.
	 * 
	 * @return string
	 */
	public function getSignature()
	{
		return $this->signature;
	}
	
	/**
	 * Getter for the signatureRequired member.
	 * 
	 * @return boolean
	 */
	public function signatureRequired()
	{
		return $this->signatureRequired;
	}
	
	/**
	 * Getter for the private key path.
	 * 
	 * @return string
	 */
	public function getPrivateKeyPath()
	{
		return CERTS_PATH . $this->privateKeyPath;
	}
	
	/**
	 * Getter for the public key path.
	 * 
	 * @return string
	 */
	public function getPublicKeyPath()
	{
		return CERTS_PATH . $this->publicKeyPath;
	}
	
	/**
	 * Get field names to be encoded.
	 * 
	 * @param DATA_PUBLIC|DATA_REQUEST|DATA_RESPONE $type field type
	 * @return array field list
	 */
	public function getFieldList($type)
	{
		switch ($type)
		{
			case leafBankPaymentProvider :: DATA_PUBLIC:
				$return = $this->publicFieldList;
			break;
			case leafBankPaymentProvider :: DATA_ENCODE:
				$return = $this->encodeFieldList;
			break;
			case leafBankPaymentProvider :: DATA_REQUEST:
				$return = $this->requestFieldList;
			break;
			case leafBankPaymentProvider :: DATA_RESPONSE:
				$return = $this->responseFieldList;
			break;
			case leafBankPaymentProvider :: DATA_DECODE:
				$return = $this->decodeFieldList;
			break;
            case leafBankPaymentProvider :: DATA_AUTH_ENCODE:
                $return = $this->authEncodeFieldList;
            break;
            case leafBankPaymentProvider :: DATA_AUTH_REQUEST:
                $return = $this->authRequestFieldList;
            break;
            case leafBankPaymentProvider :: DATA_AUTH_RESPONSE:
                $return = $this->authResponseFieldList;
            break;
            case leafBankPaymentProvider :: DATA_AUTH_DECODE:
                $return = $this->authDecodeFieldList;
            break;
			default:
				throw new UnexpectedValueException('Unknown field list type: [' . $type . ']');
			break;
		}
		return $return;
	}
    
    public function getFullName()
    {
        return null;
    }
    
    public function getPersonCode()
    {
        return null;
    }
    
	/**
	 * Extracts the signature from data array.
	 * 
	 * @param array $data
	 * @return string base64 encoded signature
	 */
	public function extractSignature(array $data)
	{
		return get( $data, $this->getSignatureField() );
	}
	
	/**
	 * Retrieves the signature field key. 
	 * @return string
	 */
	protected function getSignatureField()
	{
		return $this->signatureField;
	}
	
	/**
	 * Encodes array of values to a signable string.
	 * 
	 * @param array $data
	 * @param DATA_REQUEST|DATA_RESPONE $type field type
	 * @return string Encoded string
	 */
	abstract public function encodeData(array &$data, $type = null);
	
	/**
	 * Check if payment provider requires signature verification.
	 * 
	 * @return bool
	 */
	public function requireSignatureVerification()
	{
		return $this->requireSignatureVerification;
	}

	/**
	 * Signs encoded data with private key.
	 *
	 * @param string $data
	 * @param string $privateKeyPath Path to private key file.
	 * @return string Base64 encoded signature.
	 */
	public static function signData($data, $privateKeyPath)
	{
		$privateKey = file_get_contents($privateKeyPath);
		$pKeyId = openssl_get_privatekey($privateKey);

		// compute signature
		$signature = '';
		openssl_sign($data, $signature, $pKeyId);

		// free the key from memory
		openssl_free_key($pKeyId);

		// encode to base64
		$signatureBase64 = base64_encode($signature);

		return $signatureBase64;
	}

	/**
	 * Verify signature. Data and an encoded signature of the data are passed 
	 * along with the path to the corresponding public key. The result of the
	 * attempt to verify the signature is returned. 
	 * 
	 * @param string $data Encoded data
	 * @param string $signatureEncoded Base64 encoded data signature
	 * @param string $publicKeyPath Path to public key file
	 * @return true|false|null Boolean on success or failure, null on error.
	 */
	public static function verifySignature($data, $signatureEncoded, $publicKeyPath)
	{
		$publicKey = file_get_contents($publicKeyPath);

		$signature = base64_decode($signatureEncoded);
		$pKeyId = openssl_get_publickey($publicKey);

		$status = openssl_verify($data, $signature, $pKeyId);
		openssl_free_key($pKeyId);

		if ($status == 1)
		{
			$return = true;
		}
		elseif ($status == 0)
		{
			$return = false;
		}
		else
		{
			$return = null;
		}

		return $return;
	}
	
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::verifyResponse()
	 */
	public function verifyResponse()
	{
		if ($this->requireSignatureVerification())
		{
			$data = $this->getData();
			$signature = $this->extractSignature($data);
            
            $requestType = leafBankPaymentProvider::DATA_DECODE;
            
			$string = $this->encodeData( $data, $requestType );
			$return = self :: verifySignature( $string, $signature, $this->getPublicKeyPath() );
		}
		else
		{
			$return = $this->verifyData( $this->getData() );
		}
		return $return;
	}
	
	/**
	 * Internal data verification.
	 * 
	 * @param $data
	 * @return boolean
	 */
	public function verifyData(array $data)
	{
		return true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see www_aleksandrs/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::handlePayment()
	 */
	public function handlePayment()
	{
		$data = $this->getData();
		$this->encodeData($data, self::DATA_ENCODE);
		$fieldList = $this->getFieldList(self::DATA_REQUEST);
		require(dirname(__FILE__) . '/templates/redirectForm.php');
		die;
	}
	
    public function handleAuth()
    {
        $data = $this->getData();
        $this->encodeData( $data, self :: DATA_AUTH_ENCODE );
        $fieldList = $this->getFieldList( self :: DATA_AUTH_REQUEST );
		require( dirname( __FILE__ ) . '/templates/redirectForm.php');
		die;
    }
    
}
