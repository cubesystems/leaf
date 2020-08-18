<?php
class leafBankPaymentProviderPBS extends leafBankPaymentProvider
{
	protected $publicFieldList = array (
	);
	
	protected $encodeFieldList = array (
		'Merchant_id',
		'Version',
		'Customer_refno',
		'Currency',
		'Amount',
		'VAT',
		'Payment_method',
		'Purchase_date',
		'Response_URL',
		'Goods_description',
		'Language',
		'Capture_now',
		'Comment',
		'To_accnt',
		'From_accnt',
		'Payson_guarantee',
		'Country',
		'Cancel_URL',
		'Exclude_method',
		'Exclude_card',
		'OCR_number',
		'Personal_identity',
		'Last_dayofpayment',
		'Name',
		'Street_address',
		'Complementary_address',
		'City_address',
		'Postalcode_address',
		'Card_fee',
		'Auth_null',
	);
	
	protected $requestFieldList = array (
		'Merchant_id', 
		'Version', 
		'Payment_method', 
		'Response_URL', 
		'Cancel_URL', 
		'Language', 
		'Customer_refno', 
		'Goods_description', 
		'Amount', 
		'Currency', 
		'MAC', 
	);
		
	protected $responseFieldList = array (
		'Merchant_id',
		'Version',
		'Customer_refno',
		'Transaction_id',
		'Status',
		'Status_code',
		'AuthCode',
		'3DSec',
		'Batch_id',
		'Currency',
		'Payment_method',
		'Card_num',
		'Exp_date',
		'Card_type',
		'Risk_score',
		'Issuing_bank',
		'Ip_country',
		'Issuing_country',
		'Authorized_amount',
		'Fee_amount',
		'MAC', 
	);
	protected $decodeFieldList = array (
		'Merchant_id',
		'Version',
		'Customer_refno',
		'Transaction_id',
		'Status',
		'Status_code',
		'AuthCode',
		'3DSec',
		'Batch_id',
		'Currency',
		'Payment_method',
		'Card_num',
		'Exp_date',
		'Card_type',
		'Risk_score',
		'Issuing_bank',
		'Ip_country',
		'Issuing_country',
		'Authorized_amount',
		'Fee_amount',
	);
	
	protected $signatureField = 'MAC';
	
	protected $action = 'https://epayment.auriganet.eu/paypagegw';
	
	protected $requireSignatureVerification = false;
	
	/**
	 * Perform data processing before encoding it.
	 * 
	 * @param array $data
	 * @return void
	 */
	public function mapData(array $data)
	{		
		$config = leaf_get_property(array ('payment', get_class($this), ));
		
		$data['Merchant_id'] = $config['merchantId'];
		$data['Version'] = '3';
		$data['Payment_method'] = 'KORTINDK';
		$data['Response_URL'] = $this->getResponseUrl();
		$data['Cancel_URL'] = $this->getResponseUrl();
		$data['Language'] = 'ENG';
		$data['Customer_refno'] = $this->getToken();
		$data['Goods_description'] = $this->getDescription();
		$data['Amount'] = $this->getAmount();
		$data['Currency'] = 'DKK';
		
		return $data;
	}
	
	/**
	 * @see leafPaymentProvider::getAmount()
	 */
	public function getAmount()
	{
		return $this->getTransaction()->getAmount() * 100;
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
		
		if ($type == self :: DATA_ENCODE)
		{
			$data = $this->mapData($data);
		}
		$encodeable = array ();
		$fieldList = $this->getFieldList($type);
		foreach ($fieldList as $fieldName)
		{
			$encodeable[] = get($data, $fieldName, null, false);
		}
		$return = md5(implode('', $encodeable) . $config['secretWord']);
		$this->signature = $return;
		
		$data[$this->getSignatureField()] = $return;
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
		return (get($data, 'Customer_refno'))
			? getObject('leafTransaction', get($data, 'Customer_refno'))
			: null;
	}
	
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionStatus()
	 */
	public function getTransactionStatus()
	{
		$data = $this->getData();

		switch (get($data, 'Status'))
		{
			case 'A':
				switch (get($data, 'Status_code'))
				{
					case '0':
						return leafTransaction::STATUS_PROCESSED;
					case '2':
					case '4':
					case '6':
					case '7':
					case '9':
					case '11':	
						return leafTransaction::STATUS_ACCEPTED;
					case '1':
					case '3':
					case '8':
					default:
						return leafTransaction::STATUS_ERROR;
				}
			case 'E':
			default:
				return leafTransaction::STATUS_ERROR;
		}
	}
	
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionError()
	 */
	public function getTransactionError()
	{
		$data = $this->getData();
		return (get($data, 'Status') && get($data, 'Status_code'))
			? $data['Status'] . $data['Status_code']
			: 'undetermined system error';
	}
	
	/**
	 * @see leafBankPaymentProvider::verifyData()
	 */
	public function verifyData(array $data)
	{
		$responseSignature = get($data, 'MAC' , null);
		$this->encodeData($data, leafBankPaymentProvider::DATA_DECODE);
		return ($this->getSignature() === $responseSignature);
	}
	
}
