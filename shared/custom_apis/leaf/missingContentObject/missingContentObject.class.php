<?

class missingContentObject
{
   
    const tableName = 'missingContentObjects';
    
	public static function _autoload()
	{
		$_tableDefsStr = array 
		(
			self :: tableName => array (
				'fields' => '
					objectId int
					requestUri text
					requestTime datetime
					requestIp varchar(255)
				',
				'indexes' => '
					primary objectId
					index requestTime
				',
                'engine' => 'InnoDB'
			),
		);
		
		dbRegisterRawTableDefs($_tableDefsStr);
			    
	}
	    
    public static function log( $objectId )
    {
        $entryData = array
        (
            'objectId'    => $objectId,
            'requestUri'  => get($_SERVER, 'REQUEST_URI'),
            'requestTime' => date('Y-m-d H:i:s'),
            'requestIp'   => get($_SERVER, 'REMOTE_ADDR')
        );
        dbreplace(self::tableName, $entryData);
        
    }
    
}
