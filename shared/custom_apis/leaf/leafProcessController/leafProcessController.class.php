<?php
class leafProcessController
{
	/**
	 * reporting level constants
	 * REPORT_NONE   - reports nothing
	 * REPORT_ERRORS - reports non-running processes
	 * REPORT_ALL    - reports all events (run and kill) as they occur
	 */
	const REPORT_NONE   = 0;
	const REPORT_ERRORS = 1;
	const REPORT_ALL    = 2;


	/**
	 * seconds to sleep before running process
	 */
    const DELAYED_START_TIMEOUT    = 2;

	/**
	 * chosen reporting level 
	 * self::REPORT_ERRORS is the default
	 */
	protected static $reportingLevel = 1;
	
	/**
	 * emails to send reports and alerts to
	 */
	protected static $emails = array();

	/**
	 * store current revision 
	 */
    protected static $CURRENT_REVISION = null;
	
	/**
	 * processes
	 * example configuration:
		$processes = array
		(
			'wowza' => array
			(
				// command to execute
				'command' 	 => '/usr/local/bin/php ' . $_SERVER['DOCUMENT_ROOT'] . '/cli/wowza.cli.php',
				
				// following entries are optional
				
				// log
				'log' => $_SERVER['DOCUMENT_ROOT'] . '/../log/flash.socket.php.log',
				
				// name of the process to kill before the process itself is terminated
				'killBefore' => '[java]',
				
				// command to execute before killing the process
				'executeBeforeKill' => "ps ax | grep executorkill | grep -v grep | awk '{print $1}' | xargs kill",
			),
			...
		);
	 */
	protected static $processes = array();
	
	/********************* constructor methods *********************/
	
	public static function _autoload( $className )
    {
        //
        $config = leaf_get( 'properties', 'process');
        // backward compat
        if(empty($config))
        {
            $config = leaf_get( 'properties', 'processConfig');
        }

        if(!empty($config['processes']))
        {
            self::addProcesses( $config['processes'] );
        }
        if(!empty($config['reportingLevel']))
        {
            self::setReportingLevel( $config['reportingLevel'] );
        }
        if(!empty($config['emails']))
        {
            self::addEmails( $config['emails'] );
        }
        self::$CURRENT_REVISION = getValue("REVISION");
    }
	
	/********************* get methods *********************/
	
	public static function getProcesses()
	{
		return self::$processes;
	}
	
	public static function getProcessConfig( $processName )
	{
		if( in_array( $processName, array_keys( self::$processes ) ) )
		{
			return self::$processes[ $processName ];
		}
		return NULL;
	}
	
	public static function getProcessInfo( $command )
	{
		$response = array
		(
			'isRunning' => false,
			'pid' 		=> NULL,
		);

		// replace quotes because ps show processes without it
		$command = str_replace('"', '', $command);
		
		$cmd = 'ps xwwo pid,command  | grep -v grep';
		exec( $cmd, $output, $status );
		
		if( $status === 0 && !empty( $output ) )
		{
			foreach( $output as $line )
			{
				$matches = array();
				$exp = '/^(\d+) ' . preg_quote( $command, '/' ) . '(.*)$/';
				if( preg_match( $exp, trim( $line ), $matches ) )
				{
					$response['isRunning'] = true;
					$response['pid'] = (int) $matches[1];
				}
			}
		}
		
		return $response;
    }

    public static function getNameByPid($pid)
    {
		$cmd = 'ps xwwo pid,command  | grep -v grep | grep ' . $pid;
		exec( $cmd, $output, $status );
		
		if( $status === 0 && !empty( $output ) )
		{
			foreach( $output as $line )
            {
				$matches = array();
                $exp = '/^(\d+) (.*)$/';
				if( preg_match( $exp, trim( $line ), $matches ) )
                {
                    if($pid == (int) $matches[1])
                    {
                        $command = $matches[2];
                        foreach(self::$processes as $name => $process)
                        {
                            if($process['command'] == $command)
                            {
                                return $name;
                            }
                        }
                    }
                }
			}
		}
    }
	
	/********************* set methods *********************/
	
	public static function addEmails( $emails )
	{
		self::$emails = array_merge( self::$emails, $emails );
	}
	
	public static function addProcesses( $processes )
	{
		self::$processes = array_merge( self::$processes, $processes );
	}
	
	public static function setReportingLevel( $levelString )
	{
		$constant = __CLASS__ . '::' . $levelString;
		if( defined( $constant ) )
		{
			self::$reportingLevel = constant( $constant );
			return true;
		}
		return false;
	}
	
	/********************* boolean methods *********************/
	
	protected static function isAllRunning()
	{
		foreach( self::$processes as $name => $config )
		{
			$info = self::getProcessInfo( $config['command'] );
			if( $info['isRunning'] == false )
			{
				return false;
			}
		}
		return true;
	}
	
	/********************* actions *********************/
	
	public static function run( $processName, $allowReporting = true, $delayedStart = false )
	{
		$response = false;
		
		if( in_array( $processName, array_keys( self::$processes ) ) )
		{
			$log = '/dev/null';
			if( !empty( self::$processes[ $processName ]['log'] ) )
			{
				$log = trim( self::$processes[ $processName ]['log'] );
			}
			$modifier = '.[' . date( 'Y-m-d_H.i.s' ) . ']';
			$log = str_replace( '{modifier}', $modifier, $log );
            $cmd = 'nohup ' . self::$processes[ $processName ]['command'] . ' >> ' . $log . ' 2>&1 &';
            if($delayedStart)
            {
                $cmd = 'sleep ' . self::DELAYED_START_TIMEOUT .  ' && ' . $cmd;
            }
			exec( $cmd, $output, $status );
			
			if( $status === 0 )
			{
				$response = true;
			}
		}
		
		if( self::$reportingLevel === self::REPORT_ALL && $allowReporting )
		{
			self::sendEvent( $processName, 'run', $response );
		}
		leafEvent::slog( __CLASS__ . '.class.php', 'run("' . $processName . '")' );
		
		return $response;
	}
	
	public static function kill( $processName )
	{
		$response = false;
		
		if( in_array( $processName, array_keys( self::$processes ) ) )
		{
			$processInfo = self::getProcessInfo( self::$processes[ $processName ]['command'] );
			
			if( $processInfo['isRunning'] )
			{
				// pre-kills :)
				if( !empty( self::$processes[ $processName ]['killBefore'] ) )
				{
					$info = self::getProcessInfo( self::$processes[ $processName ]['killBefore'] );
					if( $info['isRunning'] )
					{
						posix_kill( $info['pid'], SIGTERM );
					}
				}
				// pre-kill executes
				if( !empty( self::$processes[ $processName ]['executeBeforeKill'] ) )
				{
					exec( self::$processes[ $processName ]['executeBeforeKill'] );
				}
				// now let's kill main process too
				posix_kill( $processInfo['pid'], SIGTERM );
				$errorCode = posix_get_last_error();
				if( $errorCode == 0 )
				{
					$response = true;
				}
			}
			else
			{
				$response = true;
			}
		}
		
		if( self::$reportingLevel === self::REPORT_ALL )
		{
			self::sendEvent( $processName, 'kill', $response );
		}
		leafEvent::slog( __CLASS__ . '.class.php', 'kill("' . $processName . '")' );
		
		return $response;
	}
	
	/**
	 * in case all processes should be running all the time, call this method at regular intervals
	 */
	public static function ensureAllIsRunning()
	{
		if( self::isAllRunning() === false )
		{
			// construct report body and attempt to restart
			$bodyParts = array();
			$maxNameLength = 14;
			foreach( self::$processes as $name => $config )
			{
				$info = self::getProcessInfo( $config['command'] );
				$bodyParts[ $name ] = $info['isRunning'];
				$maxNameLength = max( $maxNameLength, strlen( $name ) );
				if( $info['isRunning'] == false )
				{
					self::run( $name, false );
				}
			}
			
			$reportBody = str_pad( '[process name]', $maxNameLength + 1 ) . ' [status]' . "\n\n";
			foreach( $bodyParts as $name => $isRunning )
			{
				$reportBody .= str_pad( $name . ':', $maxNameLength + 2 );
				if( $isRunning )
				{
					$reportBody .= 'running';
				}
				else
				{
					$reportBody .= 'STOPPED';
				}
				$reportBody .= "\n";
			}
			
			$reportBody .= "\nrestarted all processes: ";
			// wait a while - in case any of the processes decide to die
			sleep( 10 );
			// see if everything got back up
			if( self::isAllRunning() )
			{
				$reportBody .= 'TRUE';
			}
			else
			{
				$reportBody .= 'FALSE';
				$succeeded = false;
			}
			// send the report
			if( self::$reportingLevel > self::REPORT_NONE )
			{
				self::send( 'Error Report', $reportBody );
			}
			
			leafEvent::slog( __CLASS__ . '.class.php', 'ensureAllIsRunning()', $reportBody );
		}
		else
		{
			leafEvent::slog( __CLASS__ . '.class.php', 'ensureAllIsRunning()', 'all is ok' );
		}
		return true;
	}
	
	// email reporting //
	
	protected static function send( $subject, $body )
	{
		require_once SHARED_PATH . '3rdpart/swift/swift_required.php';
		
		// create message //
		require_once SHARED_PATH . '3rdpart/swift/swift_required.php';
		$message = Swift_Message::newInstance();
		
		// sender requisites
		$host = 'unknownHost.cube.lv';
		if( !empty( $_SERVER['HTTP_HOST'] ) )
		{
			$host = $_SERVER['HTTP_HOST'];
			$addressData = parse_url($_SERVER['HTTP_HOST']);
			if( !empty( $addressData['host'] ) )
			{
				$host = $addressData['host'];
			}
		}
		$message->setFrom( array( 'no-reply@' . $host => 'Leaf Process Controller' ) ); 
		// recipients
		$message->setTo( self::$emails );
		// subject
		$message->setSubject( $subject );
		// message body
		$message->setBody( $body, 'text/plain' );
		
		// create mailer //
		$transport = Swift_MailTransport::newInstance();
		$mailer = Swift_Mailer::newInstance( $transport );
		
		$result = $mailer->send( $message );
		return $result;
	}
	
	protected static function sendEvent( $processName, $action, $result )
	{
		if( $action === 'run' || $action === 'kill' )
		{
			$subject = 'Event Occured';
			// name
			$body = 'process name:     "' . $processName . '"' . "\n";
			// command
			$body .= 'command:          ';
			$config = self::getProcessConfig( $processName );
			if( $config !== NULL )
			{
				$body .= '"' . $config['command'] . '"';
			}
			else
			{
				$body .= '[unknown]';
			}
			$body .= "\n";
			// action
			$body .= 'action:           ' . $action . '()'     . "\n";
			// result
			$body .= 'action succeeded: ';
			if( $result == true )
			{
				$body .= 'TRUE';
			}
			else
			{
				$body .= 'FALSE';
			}
			
			// send
			return self::send( $subject, $body );
		}
    }

    public static function exitIfNeeded()
    {
        $newRevision = dbGetOne("SELECT `value` FROM `system_values` WHERE `name` = 'REVISION'");
        if(self::$CURRENT_REVISION != $newRevision)
        {
            $processName = self::getNameByPid(getmypid());
            if($processName)
            {
                self::run($processName, false, true);
            }
            exit;
        }
    }
}
?>
