<?php

class scriptController
{
	const tableName = 'runningScripts';
    
    const emptyScriptKeyValue = '';
    
	public static function _autoload( $className )
    {
        $_tableDefsStr = array
        (
            self::tableName => array
            (
                'fields' =>
                '
                    id                  int auto_increment
                    hostName            varchar(255)
                    scriptName          varchar(128)
                    scriptKey           varchar(128)
                    pid                 varchar(64)
                    createdAt           datetime
                '
                ,
                'indexes' => '
                    primary id
                    unique scriptKey scriptName,scriptKey
                ',
                'engine' => 'innodb'
                
            )
        );        
        dbRegisterRawTableDefs( $_tableDefsStr );        
    }
    
    
    public static function register( $scriptKey = null, $scriptName = null  )
    {
        if (is_null( $scriptKey ))
        {
            $scriptKey = self::emptyScriptKeyValue;
        }
        
        if (is_null($scriptName))
        {
            $scriptName = self::detectScriptName();
        }
        
        if (!$scriptName)
        {
            trigger_error('Cannot register process with empty scriptName', E_USER_WARNING);
            return false;
        }
        
        $hostName   = gethostname();
        
        $pid        = getmypid();
        
        $values = array
        (
            'hostName'   => $hostName,
            'scriptName' => $scriptName,
            'scriptKey'  => $scriptKey,
            'pid'        => $pid,
            'createdAt'  => date('Y-m-d H:i:s')
        );

        self::cleanup( $hostName );
        
        $entryId = dbInsert( self::tableName, $values, null, array( ),  NULL, false, MYSQL_ERROR_DUPLICATE_KEY_ENTRY );
      
        if ($entryId !== false)
        {
            register_shutdown_function( array(__CLASS__, 'unregister'), $entryId );
        }
        
        return $entryId;
    }
    
    public static function registerOrDie( $scriptKey = null, $scriptName = null  )
    {
        $result = self::register( $scriptKey,  $scriptName );
        
        if ($result === false)
        {
            die();
        }
        
        return $result;
    }
    
    public static function unregister( $entryId )
    {
        if (is_null($entryId))
        {
            return;
        }
        
        dbdelete( self::tableName, $entryId );
        return;
    }
    
    protected static function detectScriptName()
    {
        $backTrace = debug_backtrace();
        
        if (!$backTrace)
        {
            return null;
        }
        
        foreach ($backTrace as $entry)
        {
            $file = get($entry, 'file');
            if ((empty($file) || $file == __FILE__))
            {
                continue;
            }
            
            $file = realpath( $file );
            $rootPath = realpath( PATH );
            
            // strip site path if possible
            
            if (substr($file, 0, strlen($rootPath)) == $rootPath)
            {
                $file = substr( $file, strlen($rootPath) );
            }
            
            return $file;
        }
        
        return null;
        
    }
    
    
    protected static function listProcessIds()
    {
        exec( 'ps xo pid', $lines, $returnStatus );
        
        if ($returnStatus != 0)
        {
            return null; // exec failed
        }
        
        $pids = array();
        foreach ($lines as $line)
        {
            $line = trim($line);
            if (!ispositiveint($line)) 
            {
                continue;
            }
            $pids[] = $line;
        }
        return $pids;
    }   

    
    protected static function cleanup( $hostName )
    {
        $pids = self::listProcessIds();   
        
        if (!is_array($pids))
        {
            return false;
        }
        
        $condition = '`hostName` = "' . dbse($hostName) . '"';
        
        if (!empty($pids))
        {
            $condition .= ' AND `pid` NOT IN(' . implode(', ',  $pids) . ')';
        }
        
        dbdelete( self::tableName, $condition );

        return;
    }

    

    
}
