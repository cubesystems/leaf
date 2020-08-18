<?php
abstract class leafPaymentProvider
{
	/**
	 * Url to fetch payment service responses.
	 * 
	 * @var string
	 */
	protected $responseUrl = null;
	
	/**
	 * Transaction. 
	 * 
	 * @var leafTransaction
	 */
	protected $transaction = null;
	
	/**
	 * Transaction data array. 
	 * 
	 * @var array
	 */
    protected $data = null;

	/**
	 * Is payment handler needed for provider
	 * 
	 * @var boolean
	 */
	public static $paymentHandlerNeeded = true;
	
	/**
	 * Transaction mutator
	 * 
	 * @param leafTransaction $transaction
	 * @return void
	 */
	public function setTransaction(leafTransaction $transaction)
	{
		$this->transaction = $transaction;
	}
	
	/**
	 * Getter for the transaction.
	 * 
	 * @return leafTransaction
	 */
	public function getTransaction()
	{
		return $this->transaction;
	}
	
	/**
	 * Response URL mutator
	 * 
	 * @param string $responseUrl
	 * @return void
	 */
	public function setResponseURL($responseUrl)
	{
		$this->responseUrl = $responseUrl;
	}
	
	/**
	 * Getter for the response URL.
	 * 
	 * @return string
	 */
	public function getResponseURL()
	{
		if (is_null($this->responseUrl))
		{
			throw new BadMethodCallException('Not bound with response URL.');
		}
		return $this->responseUrl;
	}
	
	/**
	 * Mutator for the data member.
	 *
	 * @param $data array
	 */
	public function setData(array $data = array ())
	{
		$this->data = $data;
		return $this;
	}

	/**
	 * Accessor for the data member.
	 *
	 * @return array leafAuthentication :: $data
	 */
	public function getData()
	{
		if (is_null($this->data))
		{
			throw new BadMethodCallException('Not bound with data array.');
		}
		return $this->data;
	}
	
	/**
	 * Returns a response url with query parts appended.
	 * 
	 * @param array $appendQuery key => value pairs of query arguments
	 * @return string
	 */
	protected function getCurrentUrl(array $appendPath, array $appendQuery = array ())
	{
		$currentUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$urlParts = parse_url($currentUrl);
		$urlParts['path'] = rtrim($urlParts['path'], '/') . '/' . implode('/', $appendPath);
		if (get($urlParts, 'query'))
		{
			parse_str($urlParts['query'], $query);
			$query = array_merge($query, $appendQuery);
			$urlParts['query'] = http_build_query($query);
		}
		return glueUrl($urlParts);
    }

	/**
	 * Determines current user language.
	 * 
	 * @return string
	 */
	public function getLanguage()
    {
        $language = $this->getTransaction()->languageName;
        if(empty($language))
        {
            $language = leaf_get('properties', 'language_code');
        }
		return $language;
	}
	
	/**
	 * Returns the amount to be withheld from customer.
	 * 
	 * @return float
	 */
	public function getAmount()
	{
		return $this->getTransaction()->getAmount();
	}
	
	/**
	 * Returns the transaction currency.
	 * 
	 * @return float
	 */
	public function getCurrency()
	{
		return $this->getTransaction()->getCurrency();
	}
		
	/**
	 * Returns the transaction token.
	 * 
	 * @return string
	 */
	public function getToken()
	{
		return $this->getTransaction()->getToken();
	}
	
	/**
	 * Returns the transaction description.
	 * 
	 * @return string
	 */
	public function getDescription()
	{
		return $this->getTransaction()->getDescription();
	}
	
	/**
	 * Initiates transaction handling. 
	 * 
	 * @param leafTransaction $transaction
	 */
	public function handleTransaction(leafTransaction $transaction)
	{
		$this->setTransaction($transaction);
		$this->handlePayment();
	}
	
	/**
	 * Logs payment response
	 * 
	 * @param leafTransaction $transaction
	 */
	public function logResponse(leafTransaction $transaction)
	{
		$transaction->response = $this->getData();
		$transaction->save();
	}
	
	/**
	 * Retrieves transaction from verified request data.
	 * 
	 * @return leafTransaction
	 */
	abstract public function determineTransaction();
	
	/**
	 * Returns the new transaction status.
	 * 
	 * @return int One of leafTransaction::STATUS_* constants
	 */
	abstract public function getTransactionStatus();
	
	/**
	 * Transaction error string summary (if bank provides any).
	 * 
	 * @return string
	 */
	abstract public function getTransactionError();
	
	/**
	 * Initiates the payment transaction handling sequence.
	 * All of the output is to be done by the payment provider.
	 */
	abstract protected function handlePayment();
	
	/**
	 * Response verification.
	 *
	 * @return true|false|null boolean on success or failure, null on error.
	 */
	abstract public function verifyResponse();

	/**
	 * Revert transaction.
	 * @return boolean, true if sucess
	 */
	public function revertPayment($amountToRevert = null)
	{
		return false;
	}
}
