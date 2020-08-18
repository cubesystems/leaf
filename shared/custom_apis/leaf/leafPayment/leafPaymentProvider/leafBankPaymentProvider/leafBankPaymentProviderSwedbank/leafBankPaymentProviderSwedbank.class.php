<?php
class leafBankPaymentProviderSwedbank extends leafBankPaymentProvider
{
    protected $languageMap = array (
        'lv'    => 'LAT',
        'en'    => 'ENG',
        'ee'    => 'EST',
        'ru'    => 'RUS',
    );
    
    protected $publicFieldList = array (
	);
	
	protected $encodeFieldList = array (
		'VK_SERVICE',	
		'VK_VERSION',
		'VK_SND_ID', 
		'VK_STAMP', 
		'VK_AMOUNT',
		'VK_CURR', 
		'VK_REF', 
		'VK_MSG', 
	);
	
	protected $requestFieldList = array (
		'VK_SERVICE',	
		'VK_VERSION',
		'VK_SND_ID', 
		'VK_STAMP', 
		'VK_AMOUNT',
		'VK_CURR', 
		'VK_REF', 
		'VK_MSG',
		'VK_MAC',  
		'VK_RETURN',
		'VK_LANG',  
		'VK_ENCODING', 
	);
		
	protected $response1901FieldList = array (
		'VK_SERVICE', 
		'VK_VERSION', 
		'VK_SND_ID', 
		'VK_REC_ID', 
		'VK_STAMP', 
		'VK_REF', 
		'VK_MSG', 
		'VK_MAC', 
		'VK_LANG', 
		'VK_AUTO', 
		'VK_ENCODING', 
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
    
    protected $authEncodeFieldList = array(
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_REPLY',
        'VK_RETURN',
        'VK_DATE',
        'VK_TIME',
    );
    protected $authRequestFieldList = array(
        'VK_SERVICE',
        'VK_VERSION',
        'VK_SND_ID',
        'VK_REPLY',
        'VK_RETURN',
        'VK_DATE',
        'VK_TIME',
        'VK_MAC',
        'VK_LANG',
        'VK_ENCODING',
    );
    protected $authResponseFieldList = array(
		'VK_SERVICE', 
		'VK_VERSION',
		'VK_USER',
		'VK_DATE',
		'VK_TIME',
		'VK_SND_ID',
		'VK_INFO',
        'VK_MAC',
        'VK_LANG',
    );
    protected $authDecodeFieldList = array(
		'VK_SERVICE', 
		'VK_VERSION',
		'VK_USER',
		'VK_DATE',
		'VK_TIME',
		'VK_SND_ID',
		'VK_INFO',
    );
    
	
	protected $signatureField = 'VK_MAC';
	
	protected $action = 'https://ib.swedbank.lv/banklink/';
		
	protected $privateKeyPath = 'swedbank_key.pem';
	
    protected $publicKeyPath = 'swedbank_pub.cer';
    
    protected $defaultLanguage = "LAT";

    protected static $idNamePattern = "/^ISIK:(?P<id>.*);NIMI:(?P<name>.*)$/";
    

    public function __construct()
    {
        $config = leaf_get_property( array( 'payment', __CLASS__ ) );
        
        if( get( $config, 'VK_LANG' ) )
        {
            $this->defaultLanguage = get( $config, 'VK_LANG' );
        }
        
        if( get( $config, 'action' ) )
        {
            $this->action = get( $config, 'action' );
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
        $config = leaf_get_property(array ('payment', get_class($this), ));
        
        switch( $type )
        {
            case self :: DATA_AUTH_ENCODE:
                $data['VK_SERVICE']     = '4001';
                $data['VK_VERSION']     = '008';
                $data['VK_SND_ID']      = get( $config, 'VK_SND_ID' );
                $data['VK_REPLY']       = '3002';
                $data['VK_RETURN']      = $this->getResponseUrl();
                $data['VK_DATE']        = date( 'd.m.Y' );
                $data['VK_TIME']        = date( 'H:i:s' );
                $data['VK_LANG']        = $this->getSwedbankLanguage();
                $data['VK_ENCODING']    = 'UTF-8';
            break;
            
            case self :: DATA_ENCODE:
                $currency = $this->getTransaction()->getCurrency();
                
                if( !$currency )
                {
                    trigger_error( 'Not currency specified for transaction: ' . $this->getTransaction()->id, E_USER_ERROR );
                }
                
                $data['VK_SERVICE']     = '1002';
                $data['VK_VERSION']     = '008';
                $data['VK_SND_ID']      = $config['VK_SND_ID'];
                $data['VK_STAMP']       = $this->getToken();
                $data['VK_AMOUNT']      = $this->getAmount();
                $data['VK_CURR']        = $currency;
                $data['VK_REF']         = '';
                $data['VK_RETURN']      = $this->getResponseUrl(); 
                $data['VK_LANG']        = $this->getSwedbankLanguage();
                $data['VK_MSG']         = $this->getDescription();
                $data['VK_ENCODING']    = 'UTF-8';
            break;
        }
        
        // trim out all newlines and spaces
        $data = array_map('trim', $data);
        
		return $data;
	}
	
    protected function getSwedbankLanguage()
    {
        $language = null;
        
        $transaction = $this->getTransaction();
        
        if( $transaction && $transaction->language )
        {
            $language = $transaction->language;
        }
        else
        {
            $language = leaf_get('properties', 'language_code');
        }
        
        if( $language && array_key_exists( $language, $this->languageMap ) )
        {
            return $this->languageMap[$language];
        }
        
        return $this->defaultLanguage;
    }
    
    public function getMapedLanguage( )
    {
        $data = $this->getData();
        
        $language = get( $data, 'VK_LANG' );
        
        return array_search( $language, $this->languageMap );
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
			$service = get( $data, 'VK_SERVICE' );
			switch ($service)
			{
				case '1101':
					$this->responseFieldList = $this->response1101FieldList;
					$this->decodeFieldList = $this->decode1101FieldList;
					break;
				case '1901':
					$this->responseFieldList = $this->response1901FieldList;
					$this->decodeFieldList = $this->decode1901FieldList;
					break;
				case '3002':
					$this->responseFieldList = $this->authResponseFieldList;
					$this->decodeFieldList = $this->authDecodeFieldList;
					break;
				default:
					throw new Exception('Unknown or undefined response service');
			}
		}
		$encoding = get( $data, 'VK_ENCODING', 'UTF-8' );
		$fieldList = $this->getFieldList($type);
		$return = '';
		foreach ($fieldList as $fieldName)
		{
			$return .= self :: length(get($data,$fieldName), $encoding) . $data[$fieldName];
		}
		
		$return = mb_convert_encoding($return, $encoding);
		$data[$this->getSignatureField()] = self :: signData($return, $this->getPrivateKeyPath());
		
		return $return;
	}
    
    public function getFullName()
    {
        $data = $this->getData();
        
        $return = null;
        
        $VK_INFO = get( $data, 'VK_INFO', null );
        
        if( $VK_INFO && preg_match( static::$idNamePattern, $VK_INFO, $match ) )
        {
            $return = get( $match, 'name' );
        }
        
        return $return;
    }
    
    public function getPersonCode()
    {
        $data = $this->getData();
        
        $return = null;
        
        $VK_INFO = get( $data, 'VK_INFO', null );
        
        if( $VK_INFO && preg_match( static::$idNamePattern, $VK_INFO, $match ) )
        {
            $return   = get( $match, 'id' );
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
		return (get($data,'VK_STAMP'))
			? getObject('leafTransaction', get($data,'VK_STAMP'))
			: null;
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
    
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionStatus()
	 */
	public function getTransactionStatus()
	{
		$data = $this->getData();
		switch (get($data,'VK_SERVICE'))
		{
			case '1101': return leafTransaction::STATUS_PROCESSED;
			case '1901': return leafTransaction::STATUS_ERROR;
		}
	}
	
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionError()
	 */
	public function getTransactionError()
	{
		$data = $this->getData();
		switch (get($data,'VK_SERVICE'))
		{
			case '1901': return (get($data,'VK_AUTO') == 'N') ? 'user canceled' : 'timeout';
			default: return 'undetermined system error';
		}
	}
	
}
