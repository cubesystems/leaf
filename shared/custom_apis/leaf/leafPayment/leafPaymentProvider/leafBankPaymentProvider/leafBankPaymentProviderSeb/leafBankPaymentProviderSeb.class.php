<?php
class leafBankPaymentProviderSeb extends leafBankPaymentProvider
{
    const ENCODING = 'UTF-8';
    
    protected $publicFieldList = array (
	);
	
	protected $encodeFieldList = array(
		'IB_SND_ID',
		'IB_SERVICE',
		'IB_VERSION',
		'IB_AMOUNT',
		'IB_CURR',
		'IB_NAME',
		'IB_PAYMENT_ID',
		'IB_PAYMENT_DESC',
	);
	
	protected $requestFieldList = array(
		'IB_SND_ID',
		'IB_SERVICE',
		'IB_VERSION',
		'IB_AMOUNT',
		'IB_CURR',
		'IB_NAME',
		'IB_PAYMENT_ID',
		'IB_PAYMENT_DESC',
		'IB_CRC',
		'IB_FEEDBACK',
		'IB_LANG',        
	);
    
	protected $response0003FieldList = array(
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
        'IB_LANG',
        'IB_FROM_SERVER',
	);
    
	protected $decode0003FieldList = array(
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
    
	protected $response0004FieldList = array(
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
    
	protected $decode0004FieldList = array(
        'IB_SND_ID',
        'IB_SERVICE',
        'IB_VERSION',
        'IB_REC_ID',
        'IB_PAYMENT_ID',
        'IB_PAYMENT_DESC',
        'IB_FROM_SERVER',
        'IB_STATUS',
	);
    
    protected $authEncodeFieldList = array();
    
    protected $authRequestFieldList = array(
        'IB_SND_ID',
        'IB_SERVICE',
        'IB_LANG',
    );
    
    protected $authResponseFieldList = array(
        'IB_SND_ID',
        'IB_SERVICE',
        'IB_REC_ID',
        'IB_USER',
        'IB_DATE',
        'IB_TIME',
        'IB_USER_INFO',
        'IB_VERSION',
        'IB_CRC',
        'IB_LANG',
    );
    
    protected $authDecodeFieldList = array(
        'IB_SND_ID',
        'IB_SERVICE',
        'IB_REC_ID',
        'IB_USER',
        'IB_DATE',
        'IB_TIME',
        'IB_USER_INFO',
        'IB_VERSION',
    );
    
	
	protected $signatureField = 'IB_CRC';
	
	protected $action = 'https://ibanka.seb.lv/ipc/epakindex.jsp';
		
    protected $publicKeyPath = 'seb_pub.cer';
	protected $privateKeyPath = 'seb_key.pem';
	
    protected $requireSignatureVerification = true;
    
	protected $languageCodeList = array (
		'lv' => 'LAT',
		'ru' => 'RUS',
		'en' => 'ENG', 
	);
	
    
	/**
	 * Perform data processing before encoding it.
	 * 
	 * @param array $data
	 * @return void
	 */
	public function mapData(array $data, $type )
	{		
        $config = leaf_get_property( array( 'payment', get_class( $this ), ) );
        
        switch( $type )
        {
            case self :: DATA_AUTH_ENCODE:
                $data['IB_SND_ID']      = get( $config, 'IB_SND_ID' );
                $data['IB_SERVICE']     = '0005';
                $data['IB_LANG']        = $this->getLanguageCode();
            break;
            
            case self :: DATA_ENCODE:
                $currency = $this->getTransaction()->getCurrency();
                
                if( !$currency )
                {
                    trigger_error( 'Not currency specified for transaction: ' . $this->getTransaction()->id, E_USER_ERROR );
                }
                
                $data['IB_SND_ID']          = get( $config, 'IB_SND_ID' );
                $data['IB_SERVICE']         = '0002';
                $data['IB_VERSION']         = '001';
                $data['IB_AMOUNT']          = $this->getAmount();
                $data['IB_CURR']            = $currency;
                $data['IB_NAME']            = get( $config, 'IB_NAME' );
                $data['IB_PAYMENT_ID']      = $this->getToken();
                $data['IB_PAYMENT_DESC']    = $this->getDescription();
                $data['IB_CRC']             = null;
                $data['IB_FEEDBACK']        = $this->getResponseUrl();
                $data['IB_LANG']            = $this->getLanguageCode();
                
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
        
        return 'LAT';
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
		if ($type == self :: DATA_ENCODE || self :: DATA_AUTH_ENCODE )
		{
			$data = $this->mapData( $data, $type );
		}
		if ($type == self :: DATA_DECODE)
		{
			$service = get( $data, 'IB_SERVICE' );
            
			switch ($service)
			{
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
		
        $return = '';
		$fieldList = $this->getFieldList($type);
		
        if( $fieldList )
        {
            foreach( $fieldList as $fieldName )
            {
                $return .= self::length( $data[$fieldName], self::ENCODING ) . $data[$fieldName];
            }
            
            $return = mb_convert_encoding( $return, self::ENCODING );
            $data[$this->getSignatureField()] = self :: signData($return, $this->getPrivateKeyPath());
        }
		
		return $return;
	}
    
    public function getFullName()
	{
	 	$data = $this->getData();
        
        if( !$data )
        {
            return null;
        }
        
		preg_match( '/^ID=(?P<personCode>\d{6}\-\d{5});NAME=(?P<name>.+)$/', $data['IB_USER_INFO'], $match );
        
		return get( $match, 'name', null );   	
	}
    
    public function getPersonCode()
    {
	 	$data = $this->getData();
        
        if( !$data )
        {
            return null;
        }
        
		preg_match( '/^ID=(?P<personCode>\d{6}\-\d{5});NAME=(?P<name>.+)$/', $data['IB_USER_INFO'], $match );
        
		return get( $match, 'personCode', null ); 
    }
	
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
		return ( get( $data, 'IB_PAYMENT_ID' ) )
			? getObject( 'leafTransaction', $data['IB_PAYMENT_ID'] ) 
			: null;
	}
	
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionStatus()
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
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionError()
	 */
	public function getTransactionError()
	{
		$data = $this->getData();
		switch( get( $data,'IB_SERVICE' ) )
		{
			case '0004': return ( get( $data,'IB_STATUS' ) == 'CANCELLED' ) ? 'user canceled' : 'timeout';
			default: return 'undetermined system error';
		}
	}
	
}
