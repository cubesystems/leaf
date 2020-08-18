<?php
class leafBankPaymentProviderGEmoney extends leafBankPaymentProvider
{
    protected static $languageMapping = array(
        'en' => 'ENG',
        'lv' => 'LAT',
        'ru' => 'RUS',
    );

    protected $encoding = "UTF-8";

	protected $publicFieldList = array (
	);

	// Payment
	protected $request0002FieldList = array (
		'IB_SND_ID',
		'IB_SERVICE',
		'IB_VERSION',
		'IB_AMOUNT',
		'IB_CURR',
        'IB_PAYMENT_ID',
		'IB_PAYMENT_DESC',
		'IB_PAYMENT_BEN_NAME',
		'IB_PAYMENT_BEN_ID',
		'IB_PAYMENT_BEN_ACC',
		'IB_CRC',
		'IB_FEEDBACK',
		'LANGUAGE',
	);

    protected $encode0002FieldList = array (
		'IB_SND_ID',
		'IB_SERVICE',
		'IB_VERSION',
		'IB_AMOUNT',
		'IB_CURR',
        'IB_PAYMENT_ID',
		'IB_PAYMENT_DESC',
		'IB_PAYMENT_BEN_NAME',
		'IB_PAYMENT_BEN_ID',
		'IB_PAYMENT_BEN_ACC',
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
		'IB_REC_REG',
		'IB_PAYER_ACC',
		'IB_PAYER_NAME',
		'IB_PAYER_ID',
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
		'IB_REC_REG',
		'IB_PAYER_ACC',
		'IB_PAYER_NAME',
		'IB_PAYER_ID',
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
		'LANGUAGE',
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
	
	
	
    // Auth
    protected $authEncodeFieldList = array(
    );
    protected $authRequestFieldList = array(
        'PARTNER',
        'LANGUAGE',
    );
    protected $response0001FieldList = array(
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
    protected $decode0001FieldList = array(
        'IB_SND_ID',
        'IB_SERVICE',
        'IB_USER',
        'IB_DATE',
        'IB_TIME',
        'IB_USER_INFO',
        'IB_VERSION',
    );

    protected $response0008FieldList = array(
        'IB_SND_ID',
        'IB_SERVICE',
        'IB_LANG',
    );


    protected $signatureField = 'IB_CRC';


    protected $action = 'https://test.geonline.lv/IB/itella.jsp';

    protected $publicKeyPath = 'gemoney.pub.pem';
	protected $privateKeyPath = 'gemoney.key.pem';
	
	protected $requireSignatureVerification = true;

    protected $config = array(); 

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
            case self :: DATA_ENCODE:
                
                $data['IB_SND_ID']              = get( $this->config, 'IB_SND_ID' );
                $data['IB_SERVICE']             = '0002';
                $data['IB_VERSION']             = '001';
                $data['IB_AMOUNT']              = $this->getAmount();
                $data['IB_CURR']                = $this->getTransaction()->getCurrency();
                $data['IB_PAYMENT_ID']          = $this->getToken();
                $data['IB_PAYMENT_DESC']        = $this->getTransaction()->getDescription();
                $data['IB_PAYMENT_BEN_NAME']    = get( $this->config, 'IB_PAYMENT_BEN_NAME' );
                $data['IB_PAYMENT_BEN_ID']      = get( $this->config, 'IB_PAYMENT_BEN_ID' );
                $data['IB_PAYMENT_BEN_ACC']     = get( $this->config, 'IB_PAYMENT_BEN_ACC' );
                $data['IB_FEEDBACK']            = $this->getResponseUrl();
                $data['LANGUAGE']               = $this->getGeLanguageCode();
            
            break;
            
            case self :: DATA_AUTH_ENCODE:
                $data['PARTNER']    = get( $this->config, 'IB_SND_ID' );
                $data['LANGUAGE']   = $this->getGeLanguageCode();
            break;
        }
        
        // trim out all newlines and spaces
        $data = array_map( 'trim', $data );

		return $data;
    }

    public function getGeLanguageCode()
    {
        $language       = null;
        $transaction    = $this->getTransaction();
        
        if( $transaction )
        {
            $language = $transaction->languageName;
        }
        
        if( empty( $language ) )
        {
            $language = leaf_get( 'properties', 'language_code' );
        }
        
        if( isset( self::$languageMapping[ $language ] ) )
        {
            return strtoupper( self::$languageMapping[ $language ]);
        }
        else
        {
            return strtoupper( self::$languageMapping[ 'en' ]);
        }
    }
    
    public function getMapedLanguage( )
    {
        $data = $this->getData();
        
        $language = get( $data, 'LANGUAGE' );
        
        return array_search( $language, self::$languageMapping );
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

        $service = get( $data, 'IB_SERVICE' );

        if (!$service && $type == self :: DATA_AUTH_ENCODE)
        {
            $service = 'auth';
        }
        
        switch( $service )
        {
            // Auth
            case '0001':
                $this->responseFieldList    = $this->response0001FieldList;
                $this->decodeFieldList      = $this->decode0001FieldList;
                break;
            case '0008':
                $this->responseFieldList    = $this->response0008FieldList;
                $this->decodeFieldList      = $this->decode0008FieldList;
                break;
            case 'auth':
                $this->encodeFieldList      = $this->authEncodeFieldList;
	            $this->requestFieldList     = $this->authRequestFieldList;
                break;

            // Payments
            case '0002':
                $this->encodeFieldList      = $this->encode0002FieldList;
	            $this->requestFieldList     = $this->request0002FieldList;
                break;
            case '0003':
                $this->responseFieldList    = $this->response0003FieldList;
                $this->decodeFieldList      = $this->decode0003FieldList;
                break;
            case '0004':
                $this->responseFieldList    = $this->response0004FieldList;
                $this->decodeFieldList      = $this->decode0004FieldList;
                break;

            
            default:
                throw new Exception('Unknown or undefined response service');
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

    public function getFullName()
    {
        $data = $this->getData();
        
        $name = get( $data, 'IB_USER_INFO', null );
        
        preg_match( '/^ID=(?P<personCode>\d{6}\-\d{5});NAME=(?P<name>.+)$/', $name, $match );
        
        return get( $match, 'name', null ); 
    }
    
    public function getPersonCode()
    {
        $data = $this->getData();
        
        return get( $data, 'IB_USER', null );
    }
    
	/**
	 * @see leafPaymentProvider::getToken()
	 */
	public function getToken()
	{
		return $this->getTransaction()->id;
	}
	
	/**
	 * @see leafPaymentProvider::getToken()
	 */
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
    
	/**
	 * @see leafPaymentProvider::determineTransaction()
	 */
	public function determineTransaction()
	{
		$data = $this->getData();
		return getObject('leafTransaction', get($data, 'IB_PAYMENT_ID'));
	}
	
	/**
	 * @see leafPaymentProvider::getTransactionStatus()
	 */
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
	
	/**
	 * @see leafPaymentProvider::getTransactionError()
	 */
	public function getTransactionError()
	{
		$data = $this->getData();
		switch( get( $data, 'IB_SERVICE' ) )
		{
			case '0004': return ( get( $data,'IB_STATUS' ) == 'CANCELED' ) ? 'user canceled' : 'timeout';
			default: return 'undetermined system error';
		}
	}
	
}
