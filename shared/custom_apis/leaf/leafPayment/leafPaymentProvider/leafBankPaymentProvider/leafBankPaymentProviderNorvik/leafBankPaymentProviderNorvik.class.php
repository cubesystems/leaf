<?php
class leafBankPaymentProviderNorvik extends leafBankPaymentProvider
{
    const ENCODING = 'UTF-8';
	
    protected static $languageMapping = array(
        'en' => 'ENG',
        'lv' => 'LAT',
        'ru' => 'RUS',
    );

    // Authorization request/response fields list
    protected $authRequestFieldList = array(
        'PARTNER',
        'LANGUAGE',
    );
    
    protected $authEncodeFieldList = array();
    
	protected $authResponseFieldList = array(
		'IB_SND_ID',
		'IB_SERVICE',
		'IB_USER',
		'IB_DATE',
		'IB_TIME',
		'IB_USER_INFO',
		'IB_VERSION',
		'IB_CRC',
		'LANGUAGE',
	);
    
	protected $authDecodeFieldList = array (
		'IB_SND_ID',
		'IB_SERVICE',
		'IB_USER',
		'IB_DATE',
		'IB_TIME',
		'IB_USER_INFO',
		'IB_VERSION',
	);

    
    // Payment request/response fields list
	protected $requestFieldList = array (
        'IB_SND_ID',
        'IB_SERVICE',
        'IB_VERSION',
        'IB_AMOUNT',
        'IB_CURR',
        'IB_PAYMENT_ID',
        'IB_PAYMENT_DESC',
        'IB_FEEDBACK',
        'IB_CRC',
        'LANGUAGE',
	);
    
	protected $encodeFieldList = array (
        'IB_SND_ID',
        'IB_SERVICE',
        'IB_VERSION',
        'IB_AMOUNT',
        'IB_CURR',
        'IB_PAYMENT_ID',
        'IB_PAYMENT_DESC',
        'IB_FEEDBACK',
	);
    
	protected $response0003FieldList = array (
        'IB_SND_ID',
        'IB_SERVICE',
        'IB_VERSION',
        'IB_PAYMENT_ID',
        'IB_AMOUNT',
        'IB_CURR',
        'IB_REC_ID',
        'IB_REC_ACC',
        'IB_REC_NAME',
        'IB_PAYER_ACC',
        'IB_PAYER_NAME',
        'IB_PAYMENT_DESC',
        'IB_PAYMENT_DATE',
        'IB_PAYMENT_TIME',
        'IB_CRC',
        'LANGUAGE',
        'IB_FROM_SERVER',
	);
	
	protected $decode0003FieldList = array (
        'IB_SND_ID',
        'IB_SERVICE',
        'IB_VERSION',
        'IB_PAYMENT_ID',
        'IB_AMOUNT',
        'IB_CURR',
        'IB_REC_ID',
        'IB_REC_ACC',
        'IB_REC_NAME',
        'IB_PAYER_ACC',
        'IB_PAYER_NAME',
        'IB_PAYMENT_DESC',
        'IB_PAYMENT_DATE',
        'IB_PAYMENT_TIME',
	);
	
	protected $response0004FieldList = array (
        'IB_SND_ID',
        'IB_SERVICE',
        'IB_VERSION',
        'IB_REC_ID',
        'IB_PAYMENT_ID',
        'IB_PAYMENT_DESC',
        'IB_FROM_SERVER',
        'IB_STATUS',
        'IB_CRC',
        'IB_LANG',
	);
	protected $decode0004FieldList = array (
        'IB_SND_ID',
        'IB_SERVICE',
        'IB_VERSION',
        'IB_REC_ID',
        'IB_PAYMENT_ID',
        'IB_PAYMENT_DESC',
        'IB_FROM_SERVER',
        'IB_STATUS',
	);
	
	protected $signatureField = 'IB_CRC';
	
    protected $publicKeyPath = 'norvik.pub.pem';
	protected $privateKeyPath = 'norvik.key.pem';
	
	protected $languageCodeList = array(
		'lv' => 'LAT',
		'ru' => 'RUS',
		'en' => 'ENG', 
	);
    
    protected static $idNamePattern = '/^ID=(?P<ID1>\d{6})(?P<ID2>\d{5});NAME=(?P<NAME>.+)$/'; //ID=10533414185;NAME=MARIS
    
    protected $action = 'https://www.epay.lv/epay/login.jsp';
    
    protected $config;
    
    public function __construct( )
    {
        $this->config = leaf_get_property( array( 'payment', __CLASS__ ) );
        
        if( isset( $this->config['action'] ) )
        {
            $this->action = $this->config['action'];
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
        $SNB_ID = strtoupper( get( $this->config, 'IB_SND_ID' ) );
        
        switch( $type )
        {
            case self::DATA_AUTH_ENCODE:
                
                $data['PARTNER']            = $SNB_ID;
                $data['LANGUAGE']           = $this->getLanguageCode();
                
            break;
            
            case self::DATA_ENCODE:
                
                $currency = $this->getTransaction()->getCurrency();
                
                if( !$currency )
                {
                    trigger_error( 'Not currency specified for transaction: ' . $this->getTransaction()->id, E_USER_ERROR );
                }
                
                $data['IB_SND_ID']          = $SNB_ID;
                $data['IB_SERVICE']         = '0002';
                $data['IB_VERSION']         = '001';
                $data['IB_AMOUNT']          = $this->getAmount();
                $data['IB_CURR']            = $currency;
                $data['IB_PAYMENT_ID']      = $this->getToken();
                $data['IB_PAYMENT_DESC']    = $this->getDescription();
                $data['IB_FEEDBACK']        = $this->getResponseUrl();
                $data['LANGUAGE']           = $this->getLanguageCode();
                
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
        
        return 'LVL';
    }
    
	/**
	 * Calculates the length of the variable and returns it's length in required form.
	 * 
	 * @param $variable
	 * @return string Zero-padded 3-character string
	 */
	protected function length( $variable )
	{
		return str_pad( mb_strlen( $variable, self::ENCODING ), 3, '0', STR_PAD_LEFT );
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
		if( $type == self::DATA_ENCODE || self::DATA_AUTH_ENCODE )
		{
			$data = $this->mapData( $data, $type );
		}
		if( $type == self::DATA_DECODE)
		{
			$service = get( $data, 'IB_SERVICE' );
            
			switch( $service )
			{
				case '0001':
					$this->responseFieldList = $this->authResponseFieldList;
					$this->decodeFieldList = $this->authDecodeFieldList;
					break;
				case '0003':
					$this->responseFieldList = $this->response0003FieldList;
					$this->decodeFieldList = $this->decode0003FieldList;
					break;
                case '0004':
					$this->responseFieldList = $this->response0004FieldList;
					$this->decodeFieldList = $this->decode0004FieldList;
					break;
				default:
					throw new Exception('Unknown or undefined response service');
			}
		}
        
		$fieldList = $this->getFieldList($type);
		$return = '';
        
        if( !$fieldList )
        {
            return;
        }
        
		foreach( $fieldList as $fieldName )
		{
			$return .= self::length( $data[$fieldName] ) . $data[$fieldName];
		}
		
		$return = mb_convert_encoding( $return, self::ENCODING );
		$data[$this->getSignatureField()] = self::signData( $return, $this->getPrivateKeyPath() );
		
		return $return;
	}
    
    public function getFullName()
	{
	 	$data = $this->getData();
        
		preg_match( self::$idNamePattern, $data['IB_USER_INFO'], $match );
        
		return get( $match, 'NAME', null );   	
	}
    
    public function getPersonCode()
    {
	 	$data = $this->getData();
        $personCode = null;
        
		if( preg_match( self::$idNamePattern, $data['IB_USER_INFO'], $match ) )
        {
            $personCode = $match['ID1'] . '-' . $match['ID2'];
        }
        
		return $personCode; 
    }
    
	public function determineTransaction()
	{
		$data = $this->getData();
		return ( get( $data, 'IB_PAYMENT_ID' ) )
			? leafTransaction::getByToken( $data['IB_PAYMENT_ID'] )
			: null;
	}
	
    
    public function getMapedLanguage( )
    {
        $data = $this->getData();
        $language = get( $data, 'IB_LANG' );
        
        return array_search( $language, self::$languageMapping );
    }
    

	public function getTransactionStatus()
	{
		$data = $this->getData();
		switch( get( $data,'IB_SERVICE' ) )
		{
			case '0003':
                return leafTransaction::STATUS_ACCEPTED;
            break;
        
			case '0004':
                
                if( strtoupper( get( $data, 'IB_STATUS' ) ) == 'ACCOMPLISHED' )
                {
                    return leafTransaction::STATUS_PROCESSED;
                }
                
                return leafTransaction::STATUS_ERROR;
                
            break;
		}
	}
	

	public function getTransactionError()
	{
		$data = $this->getData();
		switch( get( $data,'IB_SERVICE' ) )
		{
			case '0004': return ( get( $data,'IB_STATUS' ) == 'CANCELED' ) ? 'user canceled' : 'timeout';
			default: return 'undetermined system error';
		}
	}
	
    
    public function getRequestType( $data )
    {
		$service = get( $data, 'IB_SERVICE' );
        
        switch( $service )
        {
            case "0001":
            case "0008":
                
                return self::DATA_AUTH_RESPONSE;
                break;
                
            case "0002":
                
                return self::DATA_REQUEST;
                break;
                
            case "0003":
            case "0004":
                
                return self::DATA_RESPONSE;
                break;
                
        }
    }
}
