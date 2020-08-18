<?

class tableArchiver
{
    protected $tableName = null;
    protected $dateColumnName = null;
    protected $minRecords = null;
    protected $hoursToKeep = null;
    protected $archiveDir = null;

    protected $dumpFullName = null;

    protected static $config    = null;

    const defaultArchiveDirName  = 'tableArchives';
    const defaultMinRecords  = 5000;
    const defaultHoursToKeep = 24;


	public static function _autoload( $className )
    {
        // parent::_autoload( $className );
        self::loadConfig();
    }

    protected static function loadConfig()
    {
        $config = leaf_get('properties', 'tableArchiver');
        if (empty($config))
        {
            $config = array();
        }

        // archiveDir
        if (empty($config['archiveDir']))
        {
            $config['archiveDir'] = PATH . self::defaultArchiveDirName;
        }
        $archiveDir = realpath( $config['archiveDir'] );
		
        // default minimum number of records a table must contain for it to be processed
        if (empty($config['minRecords']))
        {
            $config['minRecords'] = self::defaultMinRecords;
        }

        if (!ispositiveint($config['minRecords']))
        {
            trigger_error('Invalid minRecords value in tableArchiver config.', E_USER_WARNING);
            $config['minRecords'] = self::defaultMinRecords;
        }

        // default number of hours of entries to keep in table after cleanup
        if (empty($config['hoursToKeep']))
        {
            $config['hoursToKeep'] = self::defaultHoursToKeep;
        }
        $config['hoursToKeep'] = (int) $config['hoursToKeep'];
        if ($config['hoursToKeep'] < 0)
        {
            $config['hoursToKeep'] = 0;
        }


        $dbConfig = leaf_get('properties', 'dbconfig');
		// support for alternative default db configuration approach
		if(isset($dbConfig['db']))
		{
			$dbConfig = $dbConfig['db'];
		}

        if (!$dbConfig)
        {
            trigger_error('DB config not found.', E_USER_ERROR);
        }
        
        
        if (!empty($dbConfig['read']))
        {
            $dbConfig = $dbConfig['read'];
        }
        
        if (
            (empty($dbConfig['hostspec']))
            ||
            (empty($dbConfig['database']))
            ||
            (empty($dbConfig['username']))
            ||
            (!isset($dbConfig['password']))
        )
        {
            trigger_error('Incomplete DB config.', E_USER_ERROR);
        }

        $config['dbConfig'] = $dbConfig;


        self::$config = $config;
        return;
    }


    public function __construct( $tableName, $dateColumnName, $minRecords = null, $hoursToKeep = null, $archiveDir = NULL )
    {
        if (empty(self::$config))
        {
            trigger_error('tableArchiver config not found.', E_USER_ERROR);
        }

        if ((empty($tableName)) || (!is_string($tableName)))
        {
            trigger_error('Invalid table name.', E_USER_ERROR);
        }
        if (!self::tableExists($tableName))
        {
            trigger_error('Table `' . $tableName . '` not found.', E_USER_ERROR);
        }
        $this->tableName = $tableName;



        if ((empty($dateColumnName)) ||(!is_string($dateColumnName)))
        {
            trigger_error('Invalid date column name.', E_USER_ERROR);
        }
        if (!self::tableHasDateColumn($this->tableName, $dateColumnName))
        {
            trigger_error('Invalid date column name `' . $dateColumnName . '` in table `' . $this->tableName . '`.', E_USER_ERROR);
        }
        $this->dateColumnName = $dateColumnName;


        if (
            (is_null($minRecords))
            ||
            ($minRecords < 0)
        )   
        {
            $minRecords = self::$config['minRecords'];
        }
        $this->minRecords = (int) $minRecords;
 

        if (is_null($hoursToKeep))
        {
            $hoursToKeep = self::$config['hoursToKeep'];
        }
        $hoursToKeep = (int) $hoursToKeep;
        if ($hoursToKeep < 0)
        {
            $hoursToKeep = 0;
        }
        $this->hoursToKeep = $hoursToKeep;
		
		
		if( $archiveDir === NULL )
		{
			$archiveDir = self::$config['archiveDir'];
        }

        // realpath return false if directory does not exist, try to create it
        if(realpath( $archiveDir ) === FALSE)
        {
            mkdir($archiveDir);
        }

        $archiveDir = realpath( $archiveDir );
		$this->archiveDir = $archiveDir;
		
        if (
            (!$archiveDir)
            ||
            (!file_exists($archiveDir))
        )
        {
            trigger_error('archiveDir ('. self::$config['archiveDir'] . ') does not exist.', E_USER_ERROR );
        }
        if (!is_dir($archiveDir))
        {
            trigger_error('archiveDir ('. self::$config['archiveDir'] . ') is not a directory.', E_USER_ERROR );
        }
        if (!is_writable($archiveDir))
        {
            trigger_error('archiveDir ('. self::$config['archiveDir'] . ') is not writable.', E_USER_ERROR );
        }

    }

    public static function cleanTable($tableName, $dateColumnName, $minRecords = null, $hoursToKeep = null, $archiveDir = NULL )
    {
        leafEvent::setSource('tableArchiver');

        $class = __CLASS__;
        leafEvent::log('cleanTable started', func_get_args());

        $archiver = new $class( $tableName, $dateColumnName, $minRecords, $hoursToKeep, $archiveDir );
        $result = $archiver->run();

        $text = ($result) ? 'all' : 'not';
        leafEvent::log('cleanTable ended, ' . $text . ' ok');
        leafEvent::unsetSource();
        return $result;
    }

    protected function run()
    {
        // check if the table has enough records for cleanup
        if (!$this->tableNeedsCleanup())
        {
            leafEvent::log('Table ' . $this->tableName . ' does not need cleanup.', $this);
            return true;
        }

        $keepDate = null;
        if (!empty($this->hoursToKeep))
        {
            $keepDate = date('Y-m-d H:i:s', strtotime('-' . $this->hoursToKeep . ' hours'));
            if (!$this->tableHasRecordsBefore($keepDate))
            {
                leafEvent::log('Table ' . $this->tableName . ' has no records before ' . $keepDate . '.' , $this);
                // no records would be deleted
                return true;
            }
        }

        leafEvent::log('Dumping table ' . $this->tableName . '.' , $this );

        $dumpOk     = $this->dumpTable();

        if (!$dumpOk)
        {
            leafEvent::log('Table dump failed.' , $this, leafEvent::T_WARNING );
            return false;
        }

        leafEvent::log('Dump ok.', $this);
        $result = $this->deleteRecordsBefore( $keepDate );

        if ($result)
        {
            leafEvent::log('Delete ok.');
        }
        else
        {
            leafEvent::log('Delete not ok.', $this, leafEvent::T_WARNING );
        }

        return $result;
    }


    public static function tableExists( $tableName )
    {
        $row = dbgetrow('SHOW TABLES LIKE "' . dbse($tableName) . '"');
        return (bool) $row;
    }


    protected static function tableHasDateColumn( $tableName, $columnName)
    {
        $q = '
            SHOW COLUMNS FROM `' . $tableName . '`
            WHERE
                `Field` LIKE "' . dbse($columnName) . '"
                AND
                `Type` = "datetime"
        ';
        $row = dbgetrow($q);
        return (bool) $row;
    }

    protected function tableNeedsCleanup()
    {
        $numberOfRecords = $this->getNumberOfRecordsInTable();
        if ($numberOfRecords < $this->minRecords)
        {
            return false; // not enough records in table to perform cleanup
        }
        return true;
    }

    protected function getNumberOfRecordsInTable()
    {
        return (int) dbgetone('SELECT COUNT(*) FROM `' . $this->tableName . '`');
    }

    protected function tableHasRecordsBefore( $keepDate )
    {
        $q = '
            SELECT COUNT(*)
            FROM `' . $this->tableName . '`
            WHERE `' . $this->dateColumnName . '` <= "' . dbse($keepDate) . '"
        ';
        $numberOfRecords = (int) dbgetone($q);
        return ($numberOfRecords > 0);
    }

    protected function deleteRecordsBefore( $keepDate )
    {
        $q = 'DELETE FROM `' . $this->tableName . '`';
        if (!is_null($keepDate))
        {
            $q .= ' WHERE `' . $this->dateColumnName . '` < "' . dbse($keepDate) . '"';
        }

        leafEvent::log('Deleting old records.', $q );

        $deleteOk = (bool) dbquery( $q );
        return $deleteOk;
    }

    protected function dumpTable()
    {
        $archiveStamp = time();

        $dbConfig = self::$config['dbConfig'];

        $server   = escapeshellarg( $dbConfig['hostspec'] );
        $user     = escapeshellarg( $dbConfig['username'] );
        $password = escapeshellarg( $dbConfig['password'] );
        $database = escapeshellarg( $dbConfig['database'] );

        $table    = escapeshellarg( $this->tableName );
        $dumpName = $this->tableName . '_' . date('Y_m_d_H_i_s', $archiveStamp) . '.sql';


        $fullArchiveDir = $this->archiveDir . '/' . date('Y-m');
        if (!file_exists($fullArchiveDir))
        {
            mkdir($fullArchiveDir, 0775, true);
        }
        if (!file_exists($fullArchiveDir))
        {
            trigger_error('Could not create archive dir ' . $fullArchiveDir, E_USER_ERROR);
        }

        $this->dumpFullName = escapeshellarg( $fullArchiveDir . '/' . $dumpName );

        $command =
        'MYSQL_PWD='  . $password . ' mysqldump -h ' . $server . ' -u ' . $user . ' --quote-names --opt ' . $database . ' --tables ' . $table . ' > ' . $this->dumpFullName;

        $output = array();
        $status = null;

        exec( $command, $output, $status );

        if ($status != 0)
        {
            // dump failed
            trigger_error( 'mysqldump failed.', E_USER_WARNING);
            return false;
        }


        $command = 'gzip ' . $this->dumpFullName;
        $output = array();
        $status = null;

        exec( $command, $output, $status );
        if ($status != 0)
        {
            // gzip failed
            trigger_error( 'gzip failed.', E_USER_WARNING);
            return false;
        }

        return true;
    }

}

?>
