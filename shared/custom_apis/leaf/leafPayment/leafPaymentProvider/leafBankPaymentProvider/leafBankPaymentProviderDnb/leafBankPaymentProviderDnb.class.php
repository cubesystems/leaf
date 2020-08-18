<?php
class leafBankPaymentProviderDnb extends leafBankPaymentProvider
{
	const DEFAULT_CURRENCY = 'LVL';
    const DEFAULT_ENCODING = 'UTF-8';
    
    protected $publicFieldList = array (
	);
	
	protected $encodeFieldList = array (
		'VK_SERVICE',	
		'VK_VERSION',
		'VK_SND_ID', 
		'VK_STAMP', 
		'VK_AMOUNT',
		'VK_CURR',
		'VK_ACC',
		'VK_NAME',
		'VK_REG_ID',
		'VK_SWIFT',
		'VK_REF', 
		'VK_MSG',
		'VK_RETURN',
		'VK_RETURN2',
	);
	
	protected $requestFieldList = array (
		'VK_SERVICE',	
		'VK_VERSION',
		'VK_SND_ID', 
		'VK_STAMP', 
		'VK_AMOUNT',
		'VK_CURR',
		'VK_ACC',
		'VK_NAME',
		'VK_REG_ID',
		'VK_SWIFT',
		'VK_REF', 
		'VK_MSG',
		'VK_RETURN',
		'VK_RETURN2',
		'VK_MAC',  
		'VK_TIME_LIMIT', 
		'VK_LANG',  
	);

	protected $response1102FieldList = array (
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
        'VK_REC_REG_ID',
        'VK_REC_SWIFT',
        'VK_SND_ACC',
        'VK_SND_NAME',
        'VK_REF',
        'VK_MSG',
        'VK_T_DATE',
        'VK_T_STATUS',
        'VK_MAC',
        'VK_LANG',
	);
    
	protected $decode1102FieldList = array (
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
        'VK_REC_REG_ID',
        'VK_REC_SWIFT',
        'VK_SND_ACC',
        'VK_SND_NAME',
        'VK_REF',
        'VK_MSG',
        'VK_T_DATE',
        'VK_T_STATUS',
	);

    
    
    protected $authEncodeFieldList = array(
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_STAMP',
        'VK_RETURN',
    );
    protected $authRequestFieldList = array(
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_STAMP',
        'VK_RETURN',
        'VK_MAC',
        'VK_LANG',
    );
    protected $authResponseFieldList = array(
        'VK_SERVICE', 
        'VK_VERSION', 
        'VK_SND_ID', 
        'VK_REC_ID', 
        'VK_STAMP',
        'VK_T_NO',
        'VK_PER_CODE',
        'VK_PER_FNAME',
        'VK_PER_LNAME',
        'VK_COM_CODE',
        'VK_COM_NAME',
        'VK_TIME',
        'VK_MAC',
        'VK_LANG',
    );
    protected $authDecodeFieldList = array(
        'VK_SERVICE', 
        'VK_VERSION', 
        'VK_SND_ID', 
        'VK_REC_ID', 
        'VK_STAMP',
        'VK_T_NO',
        'VK_PER_CODE',
        'VK_PER_FNAME',
        'VK_PER_LNAME',
        'VK_COM_CODE',
        'VK_COM_NAME',
        'VK_TIME',
    );
    

    protected $signatureField = 'VK_MAC';
      
    protected $action = 'https://ib.dnb.lv/login/index.php';
      
    protected $privateKeyPath   = 'dnb.key.pem';
    protected $publicKeyPath    = 'dnb.cert.pem';

	protected $languageCodeList = array (
		'lv' => 'LAT',
		'ru' => 'RUS',
		'en' => 'ENG', 
	);

      
    public function __construct()
    {
        $config = leaf_get( 'properties', 'payment', __CLASS__ );
        
        if( get( $config, 'cert_url' ) )
        {
            $this->publicKeyPath = $config['cert_url'];
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
        $config = leaf_get( 'properties', 'payment', __CLASS__ );
                
        switch( $type )
        {
            case self :: DATA_AUTH_ENCODE:
                $data['VK_SERVICE']     = '3001';
                $data['VK_VERSION']     = '101';
                $data['VK_SND_ID']      = get( $config, 'VK_SND_ID' );
                $data['VK_STAMP']       = time();
                $data['VK_RETURN']      = $this->getResponseUrl();
                $data['VK_LANG']        = 'LAT';                
            break;
        
            case self :: DATA_ENCODE:
                $currency = $this->getTransaction()->getCurrency();
                
                if( !$currency )
                {
                    $currency = self::DEFAULT_CURRENCY;
                }
                
                $data['VK_SERVICE']     = '1002';
                $data['VK_VERSION']     = '101';
                $data['VK_SND_ID']      = $config['VK_SND_ID'];
                $data['VK_STAMP']       = time();
                $data['VK_AMOUNT']      = $this->getAmount();
                $data['VK_CURR']        = $currency;
                $data['VK_ACC']         = get( $config, 'VK_ACC', null );
                $data['VK_NAME']        = get( $config, 'VK_NAME', null );
                $data['VK_REG_ID']      = get( $config, 'VK_REG_ID', null );
                $data['VK_SWIFT']       = get( $config, 'VK_SWIFT', null );
                $data['VK_REF']         = $this->getToken();
                $data['VK_MSG']         = $this->getDescription();
                $data['VK_RETURN']      = $this->getResponseUrl();
                $data['VK_RETURN2']     = $this->getResponseUrl();
                $data['VK_TIME_LIMIT']  = date( 'd.m.Y H:i:s', strtotime( '+1 hour' ) );
                $data['VK_LANG']        = 'LAT';
            break;
        }

        // trim out all newlines and spaces
        $data = array_map('trim', $data);
        
		return $data;
	}
	
	/**
	 * Calculates the length of the variable and returns it's length in required form.
	 * 
	 * @param $variable
	 * @return string Zero-padded 3-character string
	 */
	protected function length($variable, $encoding)
	{
		return str_pad(mb_strlen($variable, $encoding), 3, '0', STR_PAD_LEFT);
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
        if( $type == self :: DATA_ENCODE || $type == self :: DATA_AUTH_ENCODE )
		{
			$data = $this->mapData( $data, $type );
		}
		if( $type == self :: DATA_DECODE )
		{
			$service = get( $data, 'VK_SERVICE' );
            
			switch( $service )
			{
				case '1102':
					$this->responseFieldList    = $this->response1102FieldList;
					$this->decodeFieldList      = $this->decode1102FieldList;
					break;
                case '2001':
					$this->responseFieldList    = $this->authResponseFieldList;
					$this->decodeFieldList      = $this->authDecodeFieldList;
                    break;
				default:
					throw new Exception('Unknown or undefined response service');
			}
		}
        
		$encoding = self::DEFAULT_ENCODING;
		$fieldList = $this->getFieldList( $type );
        
		$return = '';
		foreach ($fieldList as $fieldName)
		{
			$return .= self :: length( get( $data, $fieldName ), $encoding ) . $data[ $fieldName ];
		}
		$return = mb_convert_encoding( $return, $encoding );
		$data[ $this->getSignatureField() ] = self :: signData( $return, $this->getPrivateKeyPath() );
		
		return $return;
	}
    
    public function getFirstName()
    {
        $data = $this->getData();

        $return = null;

        $VK_PER_FNAME   = get( $data, 'VK_PER_FNAME', null );
        $VK_COM_NAME   = get( $data, 'VK_COM_NAME', null );

        // individual person
        if( $VK_PER_FNAME )
        {
            $return = $VK_PER_FNAME;
        }
        // enterprise
        else if( $VK_COM_NAME )
        {
            $return = $VK_COM_NAME;
        }

        return $return;
    }

    public function getLastName()
    {
        $data = $this->getData();

        $return = null;

        $VK_PER_LNAME   = get( $data, 'VK_PER_LNAME', null );

        // individual person
        if( $VK_PER_LNAME )
        {
            $return = $VK_PER_LNAME;
        }

        return $return;
    }

    public function getFullName()
    {
        $data = $this->getData();
        $firstName = $this->getFirstName();
        $lastName = $this->getLastName();

        $return = $firstName;
        if(!empty($lastName))
        {
            $return .= ' ' . $lastName;
        }

        return $return;
    }

    
    public function getPersonCode()
    {
        $data = $this->getData();
        
        $return = null;
        
        $VK_PER_CODE    = get( $data, 'VK_PER_CODE', null );
        $VK_COM_CODE    = get( $data, 'VK_COM_CODE', null );
        
        // individual person
        if( $VK_PER_CODE )
        {
            $return = $VK_PER_CODE;
        }
        // enterprise
        else if( $VK_COM_CODE )
        {
            $return = $VK_COM_CODE;
        }
        
        return $return;        
    }
    
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getToken()
	 */
	public function getToken()
	{
		return $this->getTransaction()->id;
	}
	
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::determineTransaction()
	 */
	public function determineTransaction()
	{
		$data = $this->getData();
		return ( get( $data, 'VK_REF' ) )
			? getObject( 'leafTransaction', get( $data, 'VK_REF' ) )
			: null;
	}
	
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionStatus()
	 */
	public function getTransactionStatus()
	{
		$data = $this->getData();
		switch ( get( $data, 'VK_T_STATUS' ) )
		{
            case '1':       return leafTransaction::STATUS_PROCESSED;
			case '2':       return leafTransaction::STATUS_PROCESSED;
			case '3':       return leafTransaction::STATUS_ERROR;
			case '1000':    return leafTransaction::STATUS_ERROR;
			case '1001':    return leafTransaction::STATUS_ERROR;
			case '1002':    return leafTransaction::STATUS_ERROR;
			case '1003':    return leafTransaction::STATUS_ERROR;
		}
	}
	
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionError()
	 */
	public function getTransactionError()
	{
		$data = $this->getData();
		switch ( get( $data, 'VK_T_STATUS' ) )
		{
			case '3':    
        return 'Payment canceled';
        break;
			case '1000':    
        return 'Incorrect request parameters';
        break;
			case '1001':    
        return 'Unknown seller';
        break;
			case '1002':    
        return 'Wrong signature';
        break;
			case '1003':    
        return 'Internal error, please contact with payment provider';
        break;
			default: 
        return 'Undetermined system error';
        break;
		}
	}

    public function getResponseLanguage()
    {
        $data = $this->getData();
        $bankLanguageCode = get($data, 'VK_LANG');
        $language = array_search($bankLanguageCode, $this->languageCodeList);

        if(empty($language))
        {
            $language = leaf_get('properties', 'language_code');
        }

        return $language;
    }
}
