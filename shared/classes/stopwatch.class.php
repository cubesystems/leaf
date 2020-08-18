<?

class stopWatch {

   var $starttime;
   var $startmemory;
   var $endtime;
   var $result;
   var $name;
   var $markers = array();
   protected static $globalInstances = array();
   protected static $defaultInstanceName = 'default';

   // constructor, inits instance, resets values
   public function stopWatch ($autoStart = true, $name = '')
   {
       $this->endtime = false;
       $this->starttime = false;
       $this->name = $name;
       if ($autoStart)
       {
           $this->start();
       }
   }

   // start
   public function start ()  {
       $this->starttime = microtime();
       $this->startmemory = $this->getMemoryUsage();
   }

   //
   public function stop () {
       $this->endtime = microtime();
       $this->result = self::calculate($this->starttime, $this->endtime);
   }

    public function addMarker( $msg )
    {
        $this->markers[] = array(
            'msg'    => $msg,
            'time'   => $this->getResult(),
            'memory' => $this->getMemoryUsage()
        );
        return true;
    }

    protected static function calculate ($start, $end)
    {
       $start = explode (' ' , $start);
       $end = explode (' ' ,  $end);
       $time_full = $end[1] - $start[1];
       $time_fract = $end[0] - $start[0];

       if ($time_fract < 0) {
           $time_fract++;
           $time_full--;
       }

       $period = strpos($time_fract, '.');
       $time_total = $time_full;
       if ($period !== FALSE) {
           $time_total .= substr($time_fract,$period);
       }

       return $time_total; //$this->result = $time_total; //$this->endtime - $this->starttime;
   }

    public static function getDuration( $start, $end )
    {
        return round( self::calculate( $start, $end ), 4);
    }
        
    public function getResult ()
    {
        if ($this->endtime === false) {
            return self::calculate($this->starttime, microtime());
        } else {
            return $this->result;
        }
    }

    function output ($die = false)
    {
       echo '<div>' . $this->name . $this->getResult() . "</div>\r\n";
       if ($die)
       {
           die();
       }
    }

    public function outputLog( $die = false )
    {
        $output = $this->getLog();

        if (php_sapi_name() == 'cli')
        {
            $output = "\n" . trim($output) . "\n\n";
        }
        else
        {
            $output = '<pre style="font-family: courier; font-size: 12px; color: #000000; background: #dddddd;border: 1px solid #ffffff; position: relative; z-index: 9999">' . "\n" .  $output . "\n</pre>";
        }
        
        echo $output;
        
        if ($die)
        {
            die();
        }
    }
    
    public function getLog( )
    {
        $name = (!empty($this->name)) ? ' [' . $this->name . ']' : '';
        $previousMarker = 0;
        $markerCount = count($this->markers);
        $longestMarkerNumber = strlen($markerCount);
        $memoryPadLength = 13;

        $lines = array("\nTime & memory usage" . $name . ': ');
        
        if ($markerCount == 1)
        {
            $marker = current($this->markers);
            $lines[0] .= $marker['time'];
        }
        else
        {
            
            $markers = array
            (
                array('time' => 0, 'memory' => $this->startmemory, 'msg' => 'Stopwatch started')
            );
            
            $previousMemory = $this->startmemory;
            
            $markers = array_merge($markers, $this->markers);
                        
            $padChar = '.';

            $markerNumber = 0;
            foreach ($markers as $marker)
            {
                $periodStart = round( $previousMarker, 6);
                $periodEnd = round( $marker['time'], 6);
                $periodLength = round($periodEnd - $periodStart, 4);

                $memory = $marker['memory'];
                
                $memoryDiff = ($memory - $previousMemory);
                if ($memoryDiff >= 0)
                {
                    $memoryDiff = '+' . $memoryDiff;
                }
                
                $memory     = number_format($memory, 0, '.', ' ');
                
                $memory = str_pad( $memory, $memoryPadLength, $padChar, STR_PAD_LEFT);
                
                $memoryDiff = str_pad('(' . $memoryDiff . ')', $memoryPadLength , $padChar, STR_PAD_RIGHT);
                
                $memoryString = $padChar . $memory . $padChar . $padChar . $memoryDiff . $padChar . $padChar;
 
                    
                $timeParts = explode('.', $periodEnd);
                if (count($timeParts) == 1)
                {
                    $timeParts[1] = '';
                }
                $paddedTime =
                    str_pad( $markerNumber , $longestMarkerNumber, $padChar, STR_PAD_LEFT)
                    . ':'
                    . str_pad( $timeParts[0], 4, $padChar, STR_PAD_LEFT)
                    . '.'
                    . str_pad( $timeParts[1], 7, $padChar, STR_PAD_RIGHT)
                ;
                //debug ($timeParts);

                // $paddedTime = str_pad( $marker['time'], 12, '.', STR_PAD_LEFT);
                $paddedOffset = str_pad( '(+' . $periodLength . ')', 13, '.', STR_PAD_RIGHT);

                $lines[] = $paddedTime . $paddedOffset . $memoryString .  $marker['msg'];
                $previousMarker = $marker['time'];
                $markerNumber++;
            }
        }
        
        $log = implode("\n", $lines);
        
        return $log;
    }


    // static functions
    public static function go( $instanceName = null)
    {
        $class = __CLASS__;
        $instance = new $class($instanceName);

        if (is_null($instanceName))
        {
            $instanceName = self::$defaultInstanceName;
        }

        self::$globalInstances[ $instanceName ] = $instance;
        self::$globalInstances[ $instanceName ]->start();
    }

    public static function mark($msg, $instanceName = null)
    {
        if (is_null($instanceName))
        {
            $instanceName = self::$defaultInstanceName;
        }

        if (empty(self::$globalInstances[$instanceName]))
        {
            return false;
        }
        return self::$globalInstances[$instanceName]->addMarker($msg);
    }


    public static function end( $instanceName = null, $return = false )
    {
        if (is_null($instanceName))
        {
            $instanceName = self::$defaultInstanceName;
        }

        if (empty(self::$globalInstances[$instanceName]))
        {
            return false;
        }

        self::$globalInstances[$instanceName]->mark('--- END ---', $instanceName);
        self::$globalInstances[$instanceName]->stop();

        if ($return)
        {
            return self::$globalInstances[$instanceName]->getLog();
        }
        self::$globalInstances[$instanceName]->outputLog();
    }


    protected function getMemoryUsage()
    {
        if (!function_exists('memory_get_usage'))
        {
            return -1;
        }
        return memory_get_usage();
    }

}


?>