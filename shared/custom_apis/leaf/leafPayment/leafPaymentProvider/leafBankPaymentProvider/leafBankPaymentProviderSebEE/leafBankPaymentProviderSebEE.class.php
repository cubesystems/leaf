<?php
class leafBankPaymentProviderSebEE extends leafBankPaymentProvider
{
	protected $publicFieldList = array (
	);
	
	protected $encodeFieldList = array (
	);
	
	protected $requestFieldList = array (
	);
    
    
    protected $request1001FieldList = array(
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_STAMP',
        'VK_AMOUNT',
        'VK_CURR',
        'VK_ACC',
        'VK_NAME',
        'VK_REF',
        'VK_MSG',
        'VK_CHARSET',
        'VK_MAC',
        'VK_RETURN',
        'VK_CANCEL',
        'VK_LANG',
    );
    protected $encode1001FieldList = array(
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_STAMP',
        'VK_AMOUNT',
        'VK_CURR',
        'VK_ACC',
        'VK_NAME',
        'VK_REF',
        'VK_MSG',
    );
    
    protected $request1002FieldList = array(
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_STAMP',
        'VK_AMOUNT',
        'VK_CURR',
        'VK_REF',
        'VK_MSG',
        'VK_CHARSET',
        'VK_MAC',
        'VK_RETURN',
        'VK_CANCEL',
        'VK_LANG',
    );
    protected $encode1002FieldList = array(
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_STAMP',
        'VK_AMOUNT',
        'VK_CURR',
        'VK_REF',
        'VK_MSG',
    );
    
    
	protected $response1101FieldList = array (
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_REC_ID',
        'VK_STAMP',
        'VK_T_NO',
        'VK_AMOUNT',
        'VK_CURR',
        'VK_REC_ACC',
        'VK_REC_NAME',
        'VK_SND_ACC',
        'VK_SND_NAME',
        'VK_REF',
        'VK_MSG',
        'VK_T_DATE',
        'VK_CHARSET',
        'VK_MAC',
        'VK_LANG',
        'VK_AUTO',
	);
	protected $decode1101FieldList = array (
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_REC_ID',
        'VK_STAMP',
        'VK_T_NO',
        'VK_AMOUNT',
        'VK_CURR',
        'VK_REC_ACC',
        'VK_REC_NAME',
        'VK_SND_ACC',
        'VK_SND_NAME',
        'VK_REF',
        'VK_MSG',
        'VK_T_DATE',
	);
    
    
	protected $response1901FieldList = array (
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_REC_ID',
        'VK_STAMP',
        'VK_REF',
        'VK_MSG',
        'VK_CHARSET',
        'VK_MAC',
        'VK_LANG',
        'VK_AUTO',
	);
	protected $decode1901FieldList = array (
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_REC_ID',
        'VK_STAMP',
        'VK_REF',
        'VK_MSG',
	);
    
    
	protected $response1902FieldList = array (
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_REC_ID',
        'VK_STAMP',
        'VK_REF',
        'VK_MSG',
        'VK_ERROR_CODE',
        'VK_CHARSET',
        'VK_MAC',
        'VK_LANG',
        'VK_AUTO',
	);
	protected $decode1902FieldList = array (
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_REC_ID',
        'VK_STAMP',
        'VK_REF',
        'VK_MSG',
        'VK_ERROR_CODE',
	);
    
    
	protected $authRequestFieldList = array (
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_REPLY',
        'VK_RETURN',
        'VK_DATE',
        'VK_TIME',
        'VK_CHARSET',
        'VK_MAC',
	);
	protected $authEncodeFieldList = array (
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_REPLY',
        'VK_RETURN',
        'VK_DATE',
        'VK_TIME',
	);
	protected $authResponseFieldList = array (
        'VK_SERVICE',
        'VK_VERSION',
        'VK_USER',
        'VK_DATE',
        'VK_TIME',
        'VK_SND_ID',
        'VK_INFO',
        'VK_CHARSET',
        'VK_MAC',
    );
    protected $authDecodeFieldList = array (
        'VK_SERVICE',
        'VK_VERSION',
        'VK_USER',
        'VK_DATE',
        'VK_TIME',
        'VK_SND_ID',
        'VK_INFO',
    );
    
    
    protected $config = array();
	
	protected $signatureField = 'VK_MAC';
	
	protected $action = 'https://www.seb.ee/cgi-bin/dv.sh/un3min.r';
		
    protected $publicKeyPath = 'seb_ee/eyp_pub.pem';
	protected $privateKeyPath = 'seb_ee/kaupmees_priv.pem';
	
	protected $languageCodeList = array (
		'lv' => 'LAT',
		'ru' => 'RUS',
		'en' => 'ENG', 
	);
    
    protected $encoding = 'UTF-8';
    
    const DEFAULT_CURRENCY = "EUR";
	
    public function __construct()
    {
        $config = leaf_get_property( array( 'payment', get_class( $this ), ) );
        
        if( $config )
        {
            $this->config = $config;
        }
        
        if( get( $this->config, 'action' ) )
        {
            $this->action = get( $this->config, 'action' );
        }
    }
    
    
	/**
	 * Perform data processing before encoding it.
	 * 
	 * @param array $data
	 * @return void
	 */
	public function mapData(array $data, $type )
	{		
        switch( $type )
        {
            case self :: DATA_AUTH_ENCODE:
                $data['VK_SERVICE']     = '4001';
                $data['VK_VERSION']     = '008';
                $data['VK_SND_ID']      = get( $this->config, 'VK_SND_ID') ;
                $data['VK_REPLY']       = '3002';
                $data['VK_RETURN']      = $this->getResponseUrl();
                $data['VK_DATE']        = date( 'Y-m-d' );
                $data['VK_TIME']        = date( 'H:i:s' );
                $data['VK_CHARSET']     = $this->encoding;
            break;
            
            case self :: DATA_ENCODE:
                
                $transactionCurrency = $this->getTransaction()->getCurrency();
                $currency = self::DEFAULT_CURRENCY;
                
                // Only EUR is allowed
                if( $transactionCurrency != $currency )
                {
                    throw new Exception('Only EUR is allowed');
                }
                
                $data['VK_SERVICE']     = '1002';
                $data['VK_VERSION']     = '008';
                $data['VK_SND_ID']      = get( $this->config, 'VK_SND_ID') ;
                $data['VK_STAMP']       = $this->getToken();
                $data['VK_AMOUNT']      = $this->getAmount();
                $data['VK_CURR']        = $currency;
                $data['VK_REF']         = '';
                $data['VK_MSG']         = $this->getDescription();
                
                $data['VK_CHARSET']     = $this->encoding;
                $data['VK_MAC']         = null;
                $data['VK_RETURN']      = $this->getResponseUrl();
                $data['VK_CANCEL']      = $this->getResponseUrl();
                $data['VK_LANG']        = $this->getLanguageCode();
              
                
                // If specified account number in config, use service 1001
                if( get( $this->config, 'VK_ACC' ) && get( $this->config, 'VK_NAME' ) )
                {
                    $data['VK_ACC']         = get( $this->config, 'VK_ACC' );
                    $data['VK_NAME']        = get( $this->config, 'VK_NAME' );
                    $data['VK_SERVICE']     = '1001';
                }
                
            break;
        }
        
        // trim out all newlines and spaces
        $data = array_map('trim', $data);

		return $data;
	}
	
    public function getLanguageCode()
    {
        $languageCode = leafLanguage::getCurrentCode();
        
        if( array_key_exists( $languageCode, $this->languageCodeList ) )
        {
            return $this->languageCodeList[ $languageCode ];
        }
        
        return 'ENG';
    }
    
    public function getMapedLanguage( )
    {
        $data = $this->getData();
        
        $language = get( $data, 'VK_LANG' );
        
        return array_search( $language, $this->languageCodeList );
    }
    
	/**
	 * Calculates the length of the variable and returns it's length in required form.
	 * 
	 * @param $variable
	 * @return string Zero-padded 3-character string
	 */
	protected function length($variable, $encoding)
	{
		return str_pad( strlen( $variable ), 3, '0', STR_PAD_LEFT );
		//return str_pad(mb_strlen($variable,$encoding), 3, '0', STR_PAD_LEFT); // TODO: mb_strlen calculate incorrect length for string...
	}
	
	/**
	 * Encodes array of values to a signable string.
	 * 
	 * @param array $data
	 * @param DATA_REQUEST|DATA_RESPONSE $type field type
	 * @return string Encoded string
	 */
	public function encodeData(array &$data, $type = null)
	{
		if ($type == self :: DATA_ENCODE || self :: DATA_AUTH_ENCODE )
		{
			$data = $this->mapData( $data, $type );
		}
        
        $service = get( $data, 'VK_SERVICE', null );
        
        switch ($service)
        {
            // Payment requests
            case '1001':
                $this->requestFieldList = $this->request1001FieldList;
                $this->encodeFieldList = $this->encode1001FieldList;
                break;
            case '1002':
                $this->requestFieldList = $this->request1002FieldList;
                $this->encodeFieldList = $this->encode1002FieldList;
                break;
            
            // Payment responses
            case '1101':
                $this->responseFieldList = $this->response1101FieldList;
                $this->decodeFieldList = $this->decode1101FieldList;
                break;
            case '1901':
                $this->responseFieldList = $this->response1901FieldList;
                $this->decodeFieldList = $this->decode1901FieldList;
                break;
            case '1902':
                $this->responseFieldList = $this->response1902FieldList;
                $this->decodeFieldList = $this->decode1902FieldList;
                break;
            
            // Auth request
            case '4001':
                $type = self::DATA_AUTH_ENCODE;
                $this->requestFieldList = $this->authRequestFieldList;
                $this->encodeFieldList = $this->authEncodeFieldList;
                break;
            
            // Auth response
            case '3002':
                $type = self::DATA_AUTH_DECODE;
                $this->responseFieldList = $this->authResponseFieldList;
                $this->decodeFieldList = $this->authDecodeFieldList;
                break;
            
            default:
                throw new Exception('Unknown or undefined VK_SERVICE');
        }
        
		$fieldList = $this->getFieldList( $type );
        
		$return = '';
        $encode = array();
		foreach ( $fieldList as $fieldName )
		{
            $return .= self :: length( get( $data,$fieldName ), $this->encoding ) . $data[ $fieldName ];
		}
        
		$return = mb_convert_encoding( $return, $this->encoding );
		$data[$this->getSignatureField()] = self :: signData($return, $this->getPrivateKeyPath());
        
		return $return;
	}
    
    public function getFullName( )
	{
	 	$data = $this->getData();
        
        if( !$data )
        {
            return null;
        }


        $vkInfo = self::parseVkInfo($data['VK_INFO']);
        $name = get( $vkInfo, 'NIMI', null );
        
        if( $name )
        {
            $parts = explode( ",", $name );
            $parts = array_map( 'trim', $parts );
            
            $name = get( $parts, 1 ) . ' ' . get( $parts, 0 );

            return $name;
        }
	}
    
    public function getPersonCode()
    {
	 	$data = $this->getData();
        
        if( !$data )
        {
            return null;
        }
        
        $vkInfo = self::parseVkInfo($data['VK_INFO']);
        return get($vkInfo, 'ISIK', null);
    }

    public static function parseVkInfo($string)
    {
        $data = array();

        $tmp = explode(';', $string);
        foreach($tmp as $val)
        {
            $tmp2 = explode(':', $val);
            $data[$tmp2[0]] = trim($tmp2[1]);
        }

        return $data;
    }
    
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::determineTransaction()
	 */
	public function determineTransaction()
	{
		$data = $this->getData();
        
        if( get( $data, 'VK_STAMP' ) )
        {
            return getObject( 'leafTransaction', $data['VK_STAMP'] );
        }
            
        return null;
	}
	
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getToken()
	 */
	public function getToken()
	{
		return $this->getTransaction()->id;
	}
    
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionStatus()
	 */
	public function getTransactionStatus()
	{
		$data = $this->getData();
        
		switch( get( $data,'VK_SERVICE' ) )
		{
			case '1101':
                return leafTransaction::STATUS_ACCEPTED;
            break;
        
			case '1901':
                return leafTransaction::STATUS_ERROR;
            break;
        
			case '1901':
                return leafTransaction::STATUS_ERROR;
            break;
		}
	}
	
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionError()
	 */
	public function getTransactionError()
	{
		$data = $this->getData();
        
		switch( get( $data,'IB_SERVICE' ) )
		{
			case '1901':
                return 'user canceled';
            break;
        
			case '1902':
                if( get( $data, 'VK_ERROR_CODE' ) )
                {
                    return get( $data, 'VK_ERROR_CODE' );
                }
                
                return 'rejected';
            break;
        
			default:
                return 'undetermined system error';
            break;
		}
	}
    
    public function getRequestType( $data )
    {
		$service = get( $data, 'VK_SERVICE' );
        
        switch( $service )
        {
            case "3002":
                
                return self::DATA_AUTH_RESPONSE;
                break;
                
            case "1101":
            case "1901":
                
                return self::DATA_RESPONSE;
                break;
        }
    }
	
}
