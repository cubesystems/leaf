<?php
class leafEmail extends aliasSingleton
{
	protected 
		$subject, $plain, $html;
	
	protected
		$currentSubject, $currentPlain, $currentHtml;
	
	protected
		$message, $transport, $mailer, $header;
	
	protected $fieldsDefinition = array
	(
		'subject' => array( 'not_empty' => true ),
		'plain'   => array( 'not_empty' => true ),
		'html'    => array( 'not_empty' => true ),
	);
	
	public function __construct()
	{
		// include necessary classes
		require_once SHARED_PATH . '3rdpart/swift/swift_required.php';
		// construct message - it is necessary upfront to embed images
		$this->message = Swift_Message::newInstance();
		// construct transport
		if( leaf_get( 'properties', 'leafEmail', 'host' ) )
        {
            $port = 25;
			if( leaf_get( 'properties', 'leafEmail', 'port' ) )
			{
				$port = leaf_get( 'properties', 'leafEmail', 'port' );
			}
            $this->transport = Swift_SmtpTransport::newInstance( leaf_get( 'properties', 'leafEmail', 'host' ), $port );
			$this->transport->setUsername( leaf_get( 'properties', 'leafEmail', 'username' ) );
			$this->transport->setPassword( leaf_get( 'properties', 'leafEmail', 'password' ) );
        }
        else
        {
            $this->transport = Swift_MailTransport::newInstance();
        }
		// construct mailer
		$this->mailer = Swift_Mailer::newInstance( $this->transport );
		// construct header to leverage caching for batch sending
		$this->constructHeader();
		// parent
		return parent::__construct();
	}
	
	public function constructHeader()
	{
		$this->header = new leafEmailHeader();
	}
	
	public function attach( $attachment )
	{
		$this->message->attach( $attachment );
	}
	
	public function send( $to, $variables = array(), $fromName = NULL, $fromEmail = NULL )
	{
		$subject = $this->getProduct( 'subject', $variables );
		$plain   = $this->getProduct( 'plain',   $variables );
		$html    = $this->getProduct( 'html',    $variables );
		return $this->sendFull( $to, $subject, $plain, $html, $fromName, $fromEmail );
	}
	
	public function sendFull( $to, $subject, $plain, $html, $fromName = NULL, $fromEmail = NULL )
	{
		if( $fromName === NULL || $fromEmail === NULL )
		{
			$header = $this->header;
			if( $fromName === NULL )
			{
				$fromName = $header->getProduct( 'name' );
			}
			if( $fromEmail === NULL )
			{
				$fromEmail = $header->getProduct( 'email' );
			}
		}
		
		if( filter_var( $fromEmail, FILTER_VALIDATE_EMAIL ) === false )
		{
			trigger_error( 'invalid fromEmail', E_USER_ERROR );
		}
		
        $html = $this->wrapHtml( $html );

        // support multiple emails divided by comma
        if( is_string( $to ) )
        {
            $to = explode( ',', $to );
        }
		
		// send e-mail
		$message = $this->message;
		
		// subject, html and plain parts are not reset for performance reasons
		// each set method call takes ~40-60ms which can add-up in batch e-mailing
		if( $subject !== $this->currentSubject )
		{
			$this->currentSubject = $subject;
			$message->setSubject( $subject );
		}
		if( $html !== $this->currentHtml )
		{
			$this->currentHtml = $html;
			$message->setBody( $html,  'text/html',  'UTF-8' );
		}
		if( $plain !== $this->currentPlain )
		{
			$this->currentPlain = $plain;
			$message->addPart( $plain, 'text/plain', 'UTF-8' );
		}
		$message->setFrom( $fromEmail, $fromName );
		
		$noOfRecipients = 0;
		if( is_array( $to ) )
		{
			foreach( $to as $recipient )
			{
				$message->setTo( $recipient );
				$noOfRecipients += $this->mailer->send( $message );
			}
		}
		else
		{
			$message->setTo( $to );
			$noOfRecipients += $this->mailer->send( $message );
		}
		
		return $noOfRecipients;
	}
	
	public static function getVars()
	{
		return array();
	}
	
	public static function getVarsString()
	{
		$vars = static::getVars();
		if( count( $vars ) > 0 )
		{
			return '[$' . implode( '], [$', $vars ) . ']';
		}
		return '-';
	}
}
?>
