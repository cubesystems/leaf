<?php
require_once(SHARED_PATH . '3rdpart/XML_Seclibs/xmlseclibs.php');

class leafBankPaymentProviderDIGI extends leafBankPaymentProvider
{
	protected $requireSignatureVerification = false;
    
    protected $requestFieldList = array (
		'xmldata', 
	);
    
    protected $authRequestFieldList = array(
        'xmldata', 
    );
	
	protected $languageCodeList = array(
		'lv' => 'LV',
		'ru' => 'RU',
		'en' => 'EN', 
	);
    
	protected $publicKeyPath = 'digi.cert.pem';
	protected $privateKeyPath = 'digi.key.pem';
	
	protected $action = 'https://online.citadele.lv/amai/start.htm';
    protected $config;
    
    
    public function __construct()
    {
        $this->config = leaf_get_property( array( 'payment', __CLASS__ ) );
    }
    
	/**
	 * Encodes array of values to a signable string.
	 * 
	 * @param array $data
	 * @param DATA_REQUEST|DATA_RESPONSE $type field type
	 * @return string Encoded string
	 */
	public function encodeData( array &$data, $type = null )
	{
        $From           = get( $this->config, 'From' );
        $BenAccNo       = get( $this->config, 'BenAccNo' );   
        $BenName        = get( $this->config, 'BenName' );
        $BenLegalId     = get( $this->config, 'BenLegalId' );
        
        $language       = strtoupper( $this->getLanguageCode() );
        $transaction    = $this->getTransaction();
        
        
        switch( $type )
        {
            case self::DATA_AUTH_ENCODE:
                
                $xml =  '<?xml version="1.0" encoding="UTF-8" ?>' . 
                        '<FIDAVISTA xmlns="http://ivis.eps.gov.lv/XMLSchemas/100017/fidavista/v1-1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://ivis.eps.gov.lv/XMLSchemas/100017/fidavista/v1-1 http://ivis.eps.gov.lv/XMLSchemas/100017/fidavista/v1-1/fidavista.xsd">' . 
                            '<Header>' . 
                                '<Timestamp>' . substr(date('YmdHisu'), 0, 17) . '</Timestamp>' . 
                                '<From>' . $From . '</From>' . 
                                '<Extension>' . 
                                    '<Amai xmlns="http://online.citadele.lv/XMLSchemas/amai/" xsi:schemaLocation="http://online.citadele.lv/XMLSchemas/amai/ http://online.citadele.lv/XMLSchemas/amai/amai.xsd">' . 
                                        '<Request>AUTHREQ</Request>' . 
                                        '<RequestUID>' . time() . '</RequestUID>' . 
                                        '<Version>2.0</Version>' . 
                                        '<Language>' . $language . '</Language>' . 
                                        '<ReturnURL>' . $this->getResponseURL() . '</ReturnURL>' . 
                                        '<SignatureData />' . 
                                    '</Amai>' . 
                                '</Extension>' . 
                            '</Header>' . 
                        '</FIDAVISTA>';
                
            break;
            
            case self::DATA_ENCODE:
                
                $xml =  '<?xml version="1.0" encoding="UTF-8" ?>' . 
                        '<FIDAVISTA xmlns="http://ivis.eps.gov.lv/XMLSchemas/100017/fidavista/v1-1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://ivis.eps.gov.lv/XMLSchemas/100017/fidavista/v1-1 http://ivis.eps.gov.lv/XMLSchemas/100017/fidavista/v1-1/fidavista.xsd">' . 
                            '<Header>' . 
                                '<Timestamp>' . substr(date('YmdHisu'), 0, 17) . '</Timestamp>' . 
                                '<From>' . $From . '</From>' . 
                                '<Extension>' . 
                                    '<Amai xmlns="http://online.citadele.lv/XMLSchemas/amai/" xsi:schemaLocation="http://online.citadele.lv/XMLSchemas/amai/ http://online.citadele.lv/XMLSchemas/amai/amai.xsd">' . 
                                        '<Request>PMTREQ</Request>' . 
                                        '<RequestUID>' . $this->getToken() . '</RequestUID>' . 
                                        '<Version>2.0</Version>' . 
                                        '<Language>LV</Language>' . 
                                        '<ReturnURL>' . $this->getResponseURL() . '</ReturnURL>' . 
                                        '<SignatureData />' . 
                                        '<PaymentRequest>' . 
                                            '<ExtId>' . $transaction->id . '</ExtId>' .
                                            '<DocNo>' . $transaction->id . '</DocNo>' . 
                                            '<TaxPmtFlg>N</TaxPmtFlg>' .
                                            '<Ccy>' . $this->getCurrency() . '</Ccy>' .
                                            '<PmtInfo>' . $this->getDescription() . '</PmtInfo>' .
                                            '<BenSet>' .
                                                '<Priority>N</Priority>' . 
                                                '<Comm>OUR</Comm>' .
                                                '<Amt>' . $this->getAmount() . '</Amt>' .
                                                '<BenAccNo>' . $BenAccNo . '</BenAccNo>' . 
                                                '<BenName>' . $BenName . '</BenName>' .
                                                '<BenLegalId>' . $BenLegalId . '</BenLegalId>' . 
                                                '<BenCountry>LV</BenCountry>' .  
                                            '</BenSet>' . 
                                        '</PaymentRequest>' . 
                                    '</Amai>' . 
                                '</Extension>' . 
                            '</Header>' . 
                        '</FIDAVISTA>';
                
            break;
        }
        
        if( isset( $xml ) )
        {
            $doc = new DOMDocument();
            $doc->formatOutput = false;
            $doc->preserveWhiteSpace = false; 
            $doc->loadXML( $xml );
            
            $objDSig = new XMLSecurityDSig();
            
            $objDSig->setCanonicalMethod( XMLSecurityDSig::EXC_C14N );
            
            $objDSig->addReference(
                $doc, 
                XMLSecurityDSig::SHA1, 
                array( 'http://www.w3.org/2000/09/xmldsig#enveloped-signature' ), 
                array( 'force_uri' => true, )
            );
            
            $objKey = new XMLSecurityKey( XMLSecurityKey::RSA_SHA1, array( 'type' => 'private', ) );
            $objKey->loadKey( self::getPrivateKeyPath(), TRUE );
            
            $appendTo = $doc->getElementsByTagName('SignatureData')->item(0);
            $objDSig->sign( $objKey, $appendTo );
             
            $objDSig->add509Cert( file_get_contents( self::getPublicKeyPath() ) );
            
            $data['xmldata'] = $doc->saveXML();
        }
		
		return;
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
    
    
	public function getPersonCode()
	{
		$data = $this->getData();
        $XMLData = get( $data, 'xmldata' );
        
        $doc = new DOMDocument;
        $doc->loadXML( $XMLData );
        
		return implode('-', str_split( $doc->getElementsByTagName('PersonCode')->item(0)->nodeValue, 6 ) );
	}
	

	public function getFullName()
	{
		$data = $this->getData();
        $XMLData = get( $data, 'xmldata' );
        
        $doc = new DOMDocument;
        $doc->loadXML( $XMLData );
        
		return $doc->getElementsByTagName('Person')->item(0)->nodeValue;
	}
	

	public function verifyData(array $data)
    {
        $doc = new DOMDocument;
		$doc->loadXML( $data['xmldata'] );

		/* Verifying response signature */
		$objXMLSecDSig = new XMLSecurityDSig();
		
		$objDSig = $objXMLSecDSig->locateSignature($doc);
		if (! $objDSig)
        {
			throw new Exception("Cannot locate Signature Node");
		}
		
		$objXMLSecDSig->canonicalizeSignedInfo();
		$objXMLSecDSig->idKeys = array('wsu:Id');
		$objXMLSecDSig->idNS = array('wsu'=>'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd');
		
		if( !$objXMLSecDSig->validateReference() )
        {
			throw new Exception("Reference Validation Failed");
		}
		
		$objKey = $objXMLSecDSig->locateKey();
		if( !$objKey )
        {
			throw new Exception("We have no idea about the key");
		}
		
		$objKeyInfo = XMLSecEnc::staticLocateKeyInfo( $objKey, $objDSig );
		
		if ( !$objKeyInfo->key )
        {
			$objKey->loadKey( self::getPublicKeyPath(), TRUE );
		}
		
		$signatureVerification = (boolean) $objXMLSecDSig->verify($objKey);
		
		/* Verifying response type */
		$typeVerification = false;
		$type = $doc->getElementsByTagName('Request')->item(0);
		$typeVerification = ( $type instanceof DOMNode ) 
			? in_array( $type->nodeValue, array( 'PMTRESP', 'PMTSTATRESP', 'AUTHRESP' ) ) 
			: false;
			
		return ( $typeVerification && $signatureVerification );
    }
    
    
  
	public function determineTransaction()
	{
		$data = $this->getData();
		
        $transaction = null;
        $XMLData = get( $data, 'xmldata' );
        
        if( !$XMLData )
        {
            return null;
        }
        
        $doc = new DOMDocument;
        $doc->formatOutput = true;
        $doc->loadXML( $XMLData );
        
        switch( $doc->getElementsByTagName('Request')->item(0)->nodeValue )
        {
            case 'PMTSTATRESP':
                $transaction = new getObject( 'leafTransaction', $doc->getElementsByTagName('ExtId')->item(0)->nodeValue );
            case 'PMTRESP':
                $transaction = leafTransaction::getByToken( $doc->getElementsByTagName('RequestUID')->item(0)->nodeValue );
        }
        
        return $transaction;
	}
	

	public function getTransactionStatus()
	{
		$data = $this->getData();
        $XMLData = get( $data, 'xmldata' );
        
        if( !$XMLData )
        {
            return leafTransaction::STATUS_ERROR;
        }
        
        $doc = new DOMDocument;
        $doc->formatOutput = true;
        $doc->loadXML( $XMLData );
        
		switch( $doc->getElementsByTagName('Request')->item(0)->nodeValue )
		{
            case 'PMTRESP':
                $code = $doc->getElementsByTagName('Code')->item(0)->nodeValue;
				return ($code == '100') 
					? leafTransaction::STATUS_ACCEPTED 
					: leafTransaction::STATUS_ERROR;
            break;
        
			case 'PMTSTATRESP':
				$statCode = $doc->getElementsByTagName('StatCode')->item(0)->nodeValue;
				return ($statCode == 'E') 
					? leafTransaction::STATUS_PROCESSED
					: leafTransaction::STATUS_ERROR;
            break;
		}
	}
	

	public function getTransactionError()
	{
		$data = $this->getData();
        $XMLData = get( $data, 'xmldata' );
        
        if( !$XMLData )
        {
            return 'empty response';
        }
        
        $doc = new DOMDocument;
        $doc->formatOutput = true;
        $doc->loadXML( $XMLData );
		
		switch( $doc->getElementsByTagName('Request')->item(0)->nodeValue )
		{
			case 'PMTSTATRESP':
				return '';
			default:
				return ($doc->getElementsByTagName('Code')->item(0)->nodeValue == '200')
					? 'user canceled' 
					: 'system error: ' . $doc->getElementsByTagName('Message')->item(0)->nodeValue;
		}
	}
    
}
