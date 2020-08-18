<?php
class leafPaymentProviderWire extends leafPaymentProvider
{
    public static $paymentHandlerNeeded = false;

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
	
	public function getTransactionStatus()
	{
	    return leafTransaction::STATUS_PROCESSED;
	}
	
	public function getTransactionError()
    {
	}
	
	protected function handlePayment()
	{
	}
	
	public function verifyResponse()
	{
        $transaction = $this->determineTransaction();
		return ($transaction instanceof leafTransaction);
	}
}
