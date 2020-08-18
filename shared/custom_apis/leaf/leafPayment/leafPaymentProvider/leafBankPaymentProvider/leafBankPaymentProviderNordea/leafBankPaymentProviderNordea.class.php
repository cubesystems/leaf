<?php
class leafBankPaymentProviderNordea extends leafBankPaymentProvider
{
    protected static $languageMapping = array(
        'en' => 3,
        'et' => 4,
        'lv' => 6,
        'lt' => 7,
    );

	protected $publicFieldList = array (
	);

	protected $encodeFieldList = array (
		'SOLOPMT_VERSION',
		'SOLOPMT_STAMP',
		'SOLOPMT_RCV_ID',
		'SOLOPMT_AMOUNT',
		'SOLOPMT_REF',
		'SOLOPMT_DATE',
		'SOLOPMT_CUR',
		'MAC',
	);
	
	protected $requestFieldList = array (
		'SOLOPMT_VERSION',
		'SOLOPMT_STAMP',
		'SOLOPMT_RCV_ID',
		'SOLOPMT_RCV_ACCOUNT',
		'SOLOPMT_RCV_NAME',
		'SOLOPMT_LANGUAGE',
		'SOLOPMT_AMOUNT',
		'SOLOPMT_REF',
		'SOLOPMT_DATE',
		'SOLOPMT_MSG',
		'SOLOPMT_RETURN',
		'SOLOPMT_CANCEL',
		'SOLOPMT_REJECT',
		'SOLOPMT_MAC',
		'SOLOPMT_CONFIRM',
		'SOLOPMT_KEYVERS',
		'SOLOPMT_CUR',
	);
	
	protected $responseFieldList = array (
		'SOLOPMT_RETURN_VERSION',
		'SOLOPMT_RETURN_STAMP',
		'SOLOPMT_RETURN_REF',
		'SOLOPMT_RETURN_PAID',
		'SOLOPMT_RETURN_MAC',
	);
	
	protected $decodeFieldList = array (
		'SOLOPMT_RETURN_VERSION',
		'SOLOPMT_RETURN_STAMP',
		'SOLOPMT_RETURN_REF',
		'SOLOPMT_RETURN_PAID',
		'MAC', 
	);
	
    protected $authEncodeFieldList = array(
        'A01Y_ACTION_ID',
        'A01Y_VERS',
        'A01Y_RCVID',
        'A01Y_LANGCODE',
        'A01Y_STAMP',
        'A01Y_IDTYPE',
        'A01Y_RETLINK',
        'A01Y_CANLINK',
        'A01Y_REJLINK',
        'A01Y_KEYVERS',
        'A01Y_ALG',
        'MAC',
    );
    protected $authRequestFieldList = array(
        'A01Y_ACTION_ID',
        'A01Y_VERS',
        'A01Y_RCVID',
        'A01Y_LANGCODE',
        'A01Y_STAMP',
        'A01Y_IDTYPE',
        'A01Y_RETLINK',
        'A01Y_CANLINK',
        'A01Y_REJLINK',
        'A01Y_KEYVERS',
        'A01Y_ALG',
        'A01Y_MAC',
    );
    protected $authResponseFieldList = array(
        'B02K_VERS',
        'B02K_TIMESTMP',
        'B02K_IDNBR',
        'B02K_STAMP',
        'B02K_CUSTNAME',
        'B02K_KEYVERS',
        'B02K_ALG',
        'B02K_CUSTID',
        'B02K_CUSTTYPE',
        'B02K_MAC',
    );
    protected $authDecodeFieldList = array(
        'B02K_VERS',
        'B02K_TIMESTMP',
        'B02K_IDNBR',
        'B02K_STAMP',
        'B02K_CUSTNAME',
        'B02K_KEYVERS',
        'B02K_ALG',
        'B02K_CUSTID',
        'B02K_CUSTTYPE',
        'MAC',
    );
    
    
	protected $signatureField = 'SOLOPMT_MAC';
	
	protected $action = 'https://netbank.nordea.com/pnbepay/epay.jsp';
	
	protected $requireSignatureVerification = false;
	
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
            case self :: DATA_ENCODE:
                $data['SOLOPMT_VERSION'] = '0003';
                $data['SOLOPMT_STAMP'] = $this->getTransaction()->id;
                $data['SOLOPMT_RCV_ID'] = $config['ID'];
                
                $data['SOLOPMT_LANGUAGE'] = $this->getNordeaLanguageId();
                $data['SOLOPMT_AMOUNT'] = $this->getAmount();
                $data['SOLOPMT_REF'] = $this->getReferenceNumber();
                $data['SOLOPMT_DATE'] = date('d.m.Y');
                $data['SOLOPMT_MSG'] = $this->getDescription();
                $data['SOLOPMT_RETURN'] = $this->getResponseUrl() . 'ok.htm';
                $data['SOLOPMT_CANCEL'] = $this->getResponseUrl() . 'canceled.htm';
                $data['SOLOPMT_REJECT'] = $this->getResponseUrl() . 'rejected.htm';
                $data['MAC'] = $config['MAC'];
                $data['SOLOPMT_CONFIRM'] = 'YES';
                $data['SOLOPMT_KEYVERS'] = '0001';
                $data['SOLOPMT_CUR'] = $this->getTransaction()->getCurrency();
                
                if( get( $config, 'payAction' ) )
                {
                    $this->action = get( $config, 'payAction' );
                }
                
                if( get( $config, 'SOLOPMT_RCV_ACCOUNT' ) )
                {
                    $data['SOLOPMT_RCV_ACCOUNT'] = get( $config, 'SOLOPMT_RCV_ACCOUNT' );
                }
                if( get( $config, 'SOLOPMT_RCV_NAME' ) )
                {
                    $data['SOLOPMT_RCV_NAME'] = get( $config, 'SOLOPMT_RCV_NAME' );
                }                
            
            break;
            
            case self :: DATA_AUTH_ENCODE:
                $data['A01Y_ACTION_ID'] = '701';
                $data['A01Y_VERS']      = '0002';
                $data['A01Y_RCVID']     = get( $config, 'ID' );
                $data['A01Y_LANGCODE']  = $this->getNordeaLanguageCode();
                $data['A01Y_STAMP']     = date('YmdHis') . str_pad( rand( 0, 999999 ), 6, 0, STR_PAD_LEFT ); //ggggmmddhhmmssxxxxxx
                $data['A01Y_IDTYPE']    = '02'; 
                $data['A01Y_RETLINK']   = $this->getResponseUrl();
                $data['A01Y_CANLINK']   = $this->getResponseUrl();
                $data['A01Y_REJLINK']   = $this->getResponseUrl();
                $data['A01Y_KEYVERS']   = '0001';
                $data['A01Y_ALG']       = '01'; // MD5
                $data['MAC']            = $config['MAC'];
                
                if( get( $config, 'eidAction' ) )
                {
                    $this->action = get( $config, 'eidAction' );
                }
                
            break;
        }
        
        // trim out all newlines and spaces
        $data = array_map( 'trim', $data );
		
		return $data;
    }

    public function getNordeaLanguageId()
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
            return self::$languageMapping[ $language ];
        }
        else
        {
            return self::$languageMapping[ 'en' ];
        }
    }
	
    public function getNordeaLanguageCode()
    {
        $code = 'en';
        $languageId = $this->getNordeaLanguageId();
        
        if( $languageId )
        {
            $code = array_search( $languageId, self::$languageMapping );
        }
        
        return strtoupper( $code );
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
        $config = leaf_get_property(array ('payment', get_class($this), ));
		
        if ( $type == self :: DATA_DECODE || $type == self::DATA_AUTH_DECODE )
        {
            $data['MAC'] = $config['MAC'];
        }
		if ($type == self :: DATA_DECODE)
		{
			$this->signatureField = 'SOLOPMT_RETURN_MAC';
		}
        else if ( $type == self::DATA_AUTH_DECODE )
		{
            $this->signatureField = 'B02K_MAC';
		}
        
		if ( $type == self :: DATA_ENCODE || $type == self :: DATA_AUTH_ENCODE )
		{
			$data = $this->mapData( $data, $type );
		}
        if ( $type == self :: DATA_AUTH_ENCODE )
        {
            $this->signatureField = 'A01Y_MAC';
        }
        
		$encodeable = array ();
        $fieldList = $this->getFieldList($type);
        
		foreach ($fieldList as $fieldName)
		{
			$encodeable[] = trim( get( $data, $fieldName ) );
        }
        
        $plain = implode( '&', $encodeable ) . '&';
        
		$return = strtoupper( md5( $plain ) );
        $this->signature = $return;
		
		$data[$this->getSignatureField()] = $return;
	}
    
    public function getFullName()
    {
        $data = $this->getData();
        
        $name = get( $data, 'B02K_CUSTNAME', null );
        $name = iconv('ISO-8859-1', 'UTF-8//IGNORE', $name);
        $name = html_entity_decode( preg_replace( '/fc([0-9]{1,}){/', "&#$1;", $name ) );

        if( $name )
        {
            $parts = explode(' ', $name);
            $lastName = $parts[0];
            unset($parts[0]);
            $firstName = implode(' ', $parts);
            $name = $firstName . ' ' . $lastName;
        }
        
        return $name;
    }
    
    public function getPersonCode()
    {
        $data = $this->getData();
        
        return get( $data, 'B02K_CUSTID', null );
    }
    
	/**
	 * @see leafPaymentProvider::getToken()
	 */
	public function getToken()
	{
		return $this->getTransaction()->id;
	}
	
	/**
	 * Calculate the nordea standard reference number and check digit.
	 * 1. The reference number can be formed from the payment specifier, for 
	 * example 123456, by calculating a check digit, i.e. the last digit of the 
	 * reference number, by using multipliers 7-3-1. The specifier�s digits are 
	 * multiplied from right to left, and the products are added up. The sum is 
	 * then subtracted from the next highest ten, and the remainder is the check 
	 * digit added to the specifier.
	 * 
	 * Specifier 		1		2		3		4		5		6
	 * Multiplier		1		3		7		1		3		7
	 * Product			1		6		21	4		15	42
	 * Sum					1 + 6 + 21 + 4 + 15 + 42 = 89
	 * Check digit	90 - 89 = 1
	 * The reference number is 1234561
	 */
	protected function getReferenceNumber()
	{
		$id = (string) $this->getTransaction()->id;
		$idDigitList = str_split($id, 1);

        $idDigitList = array_reverse($idDigitList);

		$multipliers = array(7,3,1);
		$sum = 0;
		foreach ($idDigitList as $key => $digit)
		{
			$sum += $digit * $multipliers[$key % 3];
		}
		$nearestTen = ceil($sum / 10) * 10;
		$checkDigit = $nearestTen - $sum;

		return $id . $checkDigit;
	}
	
	public function verifyData(array $data)
	{
        $responseSignature = null;
        $type = self::DATA_DECODE;
        
        if( get( $data, 'SOLOPMT_RETURN_MAC' ) )
        {
            $responseSignature = get( $data, 'SOLOPMT_RETURN_MAC' );
        }
        else if( get( $data, 'B02K_MAC' ) )
        {
            $type = self::DATA_AUTH_DECODE;
            $responseSignature = get( $data, 'B02K_MAC' );
        }
        
		$this->encodeData( $data, $type );
		return ($this->getSignature() === $responseSignature);
	}
	
    public function getRequestType( $data )
    {
        if( isset( $data['B02K_VERS'] ) )
        {
            return leafBankPaymentProvider::DATA_AUTH_RESPONSE;
        }
        
        if( isset( $data['SOLOPMT_RETURN_VERSION'] ) )
        {
            return leafBankPaymentProvider::DATA_RESPONSE;
        }
        
        return null;
    }
    
    public function getMapedLanguage()
    {
        return null;
    }
    
	/**
	 * @see leafPaymentProvider::determineTransaction()
	 */
	public function determineTransaction()
	{
		$data = $this->getData();
		return getObject('leafTransaction', get($data, 'SOLOPMT_RETURN_STAMP'));
	}
	
	/**
	 * @see leafPaymentProvider::getTransactionStatus()
	 */
	public function getTransactionStatus()
	{
        $data = $this->getData();
        $responseType = basename($data['path_part'], '.htm');
        if($responseType == 'ok')
        {
            return leafTransaction::STATUS_PROCESSED;
        }
        else
        {
            return leafTransaction::STATUS_ERROR;
        }
	}
	
	/**
	 * @see leafPaymentProvider::getTransactionError()
	 */
	public function getTransactionError()
	{
		$data = $this->getData();
		switch (basename($data['objects_path'], '.htm'))
		{
			case 'canceled': return 'user canceled';
			case 'rejected': return 'undetermined system error';
		}
	}
	
}
