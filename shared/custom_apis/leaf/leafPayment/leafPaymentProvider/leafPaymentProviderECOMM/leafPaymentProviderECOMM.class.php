<?php
class leafPaymentProviderECOMM extends leafPaymentProvider
{
	/**
	 * Ecomm merchant instance.
	 * @var leafEcommMerchant
	 */
	protected $merchant;
	
	/**
	 * Instance constructor.
	 * Initializes the merchant member variable.
	 */
	public function __construct()
	{
		$this->merchant = new leafEcommMerchant();
	}
	
	/**
	 * @see www/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::determineTransaction()
	 */
	public function determineTransaction()
	{
		$data = $this->getData();
		return (get($data, 'trans_id'))
			? leafTransaction::getByReferenceNumber($data['trans_id'])
			: (get($data, 'transaction-id')
				? getObject('leafTransaction', $data['transaction-id'])
				: null
			);
	}
	
	/**
	 * @see www_aleksandrs/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionStatus()
	 */
	public function getTransactionStatus()
	{
		$pathPart = get($this->getData(), 'path_part');
		$pathPart = str_replace('/', '', $pathPart);
		
		switch ($pathPart)
		{
			case 'ok':
				$transaction = $this->determineTransaction();
				$transactionResult = $this->merchant->getTransResult($transaction->referenceNo, $transaction->author_ip);
				
				return (
					in_array(
						get($transactionResult, 'RESULT_CODE'), 
						array (
							'000', 
							'001', 
							'002', 
							'003', 
							'004', 
							'005', 
							'006', 
							'007',
						)
					) || 
					get($transactionResult, 'RESULT') == 'OK'
				)
					? leafTransaction::STATUS_PROCESSED 
					: leafTransaction::STATUS_ERROR;
			case 'fail':
				return leafTransaction::STATUS_ERROR;
			default:
				throw new Exception('Error determining transaction status.');
		}
	}
	
	/**
	 * @see www_aleksandrs/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getTransactionError()
	 */
	public function getTransactionError()
	{
		$transaction = $this->determineTransaction();
		
		$transactionResult = $this->merchant->getTransResult($transaction->referenceNo, $transaction->author_ip);
		return get($transactionResult, 'RESULT_CODE', get($transactionResult, 'RESULT', 'undetermined system error'));
	}
	
	/**
	 * @see www_aleksandrs/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::handlePayment()
	 */
	protected function handlePayment()
	{
		$transaction = $this->getTransaction();
		
		$response = $this->merchant->startSMSTrans(
			$this->getAmount(), 
			$this->getCurrency(), 
			$this->getTransaction()->author_ip, 
			$this->getDescription(), 
			$this->getLanguage()
		);
		
		if (get($response, 'TRANSACTION_ID'))
		{
			$transaction->referenceNo = $response['TRANSACTION_ID'];
			$transaction->save();
			
            $config = leaf_get('properties', 'payment', 'leafPaymentProviderECOMM');
            if(empty($config))
            {
                $config = leaf_get('properties', 'ecomm');
            }
			$redirectUrl = $config['domain'] . '/ecomm/ClientHandler?trans_id=' . urlencode($transaction->referenceNo);
		}
		else
		{
			$transaction->status = leafTransaction::STATUS_ERROR;
			$transaction->response = $response;
			$transaction->save();
			$redirectUrl = $this->getResponseURL() . 'fail?transaction-id=' . $transaction->id;
		}
	
		leafHttp :: redirect($redirectUrl);
	}
	
	/**
	 * @see www_aleksandrs/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getAmount()
	 */
	public function getAmount()
	{
		return $this->getTransaction()->getAmount() * 100;
	}
	
	/**
	 * @see www_aleksandrs/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::getCurrency()
	 */
	public function getCurrency()
	{
        $currencyCode = $this->getTransaction()->currency;
        if(isset(leafEcommMerchant::$currencyTranslations[$currencyCode]))
        {
            return leafEcommMerchant::$currencyTranslations[$currencyCode];
        }
        else
        {
			trigger_error('Unsupported currency: ' . $currencyCode, E_USER_ERROR);
        }
	}
	
	/**
	 * @see www_aleksandrs/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::verifyResponse()
	 */
	public function verifyResponse()
	{
		$data = $this->getData();
		
		$transaction = null;
		if (get($data, 'trans_id'))
		{
			$transaction = $this->determineTransaction();
		}
		// Could not initialize transaction, redirected from self
		if (get($data, 'transaction-id'))
		{
			$transaction = getObject('leafTransaction', $data['transaction-id']);
		}
		
		return ($transaction instanceof leafTransaction);
	}
	
	/**
	 * @see www_aleksandrs/shared/custom_apis/leaf/leafPaymentTransactions/leafPaymentProvider/leafPaymentProvider::logResponse()
	 */
	public function logResponse(leafTransaction $transaction)
	{
		if (!empty($transaction->referenceNo))
		{
			$transaction->response = $this->merchant->getTransResult($transaction->referenceNo, $transaction->author_ip);
			$transaction->save();
		}
    }


	public function revertPayment($amountToRevert = null)
    {
        if(is_null($amountToRevert))
        {
            $amountToRevert = $this->transaction->amount;
        }

        $transactionResult = $this->merchant->reverse($this->transaction->referenceNo, $amountToRevert);
        $resultCode = get($transactionResult, 'RESULT');

        if($resultCode == 'OK')
        {
            return true;
        }
        else
        {
            return false;
        }
	}
}
