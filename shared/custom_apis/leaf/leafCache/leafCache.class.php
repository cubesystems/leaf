<?php

class leafCache extends leafBaseObject
{
    const tableName         = 'leafCache';
    
    const TYPE_PLAIN        = 'plaintext';
    const TYPE_SERIALIZED   = 'serialized';
    
    const EXPIRATION        = '1 day';
    
    protected
        $id,
        $cacheKey,
        $value,
        $type,
        $expire,
        $add_date;
        
	protected $fieldsDefinition = array(
        'cacheKey'          => array( 'not_empty' => true ),
        'value'             => array( 'not_empty' => true ),
        'type'              => array( 'not_empty' => true ),
        'expire'            => array( 'optional' => true ),
	);

    protected static $_tableDefsStr = array
	(
        self::tableName => array
        (
            'fields' =>'
                id              int auto_increment
                cacheKey        varchar(255)
                value           longtext
                type            varchar(30)
                expire          int
                add_date        datetime
            ',
            'indexes' => '
                primary         id
                index           cacheKey
                index           expire
                index           add_date   
            ',
            'engine' => 'innodb'
        )
    );
    

	protected $currentMode = 'insert';
	protected $modes = array(
        'insert' => array(
            'cacheKey',
            'value',
            'type',
            'expire',
            'add_date',
        ), 
    );
    
    
    public function getValue()
    {
        $value = $this->value;
        
        if( $this->type == self::TYPE_SERIALIZED )
        {
            $value = unserialize( $value );
        }
        
        return $value;
    }

    
    // Determine if an item exists in the cache
    public static function has( $key )
    {
        $params = array(
            'cacheKey'  => $key,
            'valid'     => true,
        );
        
        $queryParts = self::getQueryParts( $params );
        $queryParts['select'] = '`t`.`id`';
        
        return ( bool ) dbGetOne( $queryParts );
    }
    
    
    // Get an item from the cache
    public static function get( $key, $valid = true )
    {
        $value = null;
        
        $params = array(
            'cacheKey'  => $key,
            'valid'     => true,
        );
        
        $item = self::getCollection( $params )->first();
        
        if( $item )
        {
            $value = $item->getValue();
        }
        
        return $value;
    }
    
    
    // Get an item from the cache
    public static function put( $key, $value, $expire = null )
    {
        if( !$expire )
        {
            $expire = self::expiration();
        }
        else
        {
            // Force expire time to UNIX timestamp
            $expire = strtotime( $expire );
        }
        
        
        $type = self::TYPE_PLAIN;
        
        if( is_array( $value ) || is_object( $value ) )
        {
            $value = serialize( $value );
            $type = self::TYPE_SERIALIZED;
        }
        
        $params = array(
            'cacheKey'      => $key,
            'value'         => $value,
            'type'          => $type,
            'expire'        => $expire,
        );
        
        $leafCache = new leafCache();
        return $leafCache->variablesSave( $params, null, 'insert' );
    }

    
    // Retrieve an item from the cache driver
    public static function retrieve( $key )
    {
        return self::get( $key, $valid = false );
    }
    
    
    // Delete an item from the cache
    public static function forget( $key )
    {
        $item = self::retrieve( $key );
        
        if( $item )
        {
            return $item->delete();
        }
        
        return false;
    }
    
    
    // Get the expiration time as a UNIX timestamp
    public static function expiration()
    {
        return strtotime( "+" . self::EXPIRATION );
    }
    
    
    // Delete expired items
    public static function clear( $expired = true )
    {
        $params = array(
            'expired' => $expired,
        );
        
        $queryParts = self::getQueryParts( $params );
        $queryParts['select'] = '`t`.`id`';
        
        $ids = dbGetAll( $queryParts, null, 'id' );
        
        if( $ids )
        {
            $ids = array_map( 'dbSE', $ids );
            dbQuery('DELETE FROM `' . self::tableName . '` WHERE id IN("' . implode( '","', $ids ) . '")');
        }
    }
    
    
    // Delete items from cache by key
    public static function clearByKey( $key, $expired = false )
    {
        $params = array(
            'cacheKeyLike'  => $key,
            'expired'       => $expired,
        );
        
        $queryParts = self::getQueryParts( $params );
        $queryParts['select'] = '`t`.`id`';
        
        $ids = dbGetAll( $queryParts, null, 'id' );
        
        if( $ids )
        {
            $ids = array_map( 'dbSE', $ids );
            dbQuery('DELETE FROM `' . self::tableName . '` WHERE id IN("' . implode( '","', $ids ) . '")');
        }
    }
    
    
    // Truncate all cache
    public static function truncateCache()
    {
        return dbQuery('TRUNCATE TABLE `' . self::tableName . '`');
    }
    
    
    public static function cleanup()
    {
        dbQuery( "DELETE FROM `" . self::tableName . "` WHERE `expire` < '" . time() . "'" );
    }
    
    
    public static function getQueryParts( $params = array() )
    {
        $queryParts = parent::getQueryParts();
        
        if( get( $params, 'cacheKey' ) )
        {
            $queryParts['where'][] = '`t`.`cacheKey` = "' . dbSE( $params['cacheKey'] ) . '"';
        }
        if( get( $params, 'cacheKeyLike' ) )
        {
            $queryParts['where'][] = '`t`.`cacheKey` LIKE "' . dbSE( $params['cacheKey'] ) . '"';
        }
        if( get( $params, 'expired' ) )
        {
            $queryParts['where'][] = '`t`.`expire` < "' . dbSE( time() ) . '"';
        }
        if( get( $params, 'valid' ) )
        {
            $queryParts['where'][] = '`t`.`expire` > "' . dbSE( time() ) . '"';
        }
        
        return $queryParts;
    }
}
