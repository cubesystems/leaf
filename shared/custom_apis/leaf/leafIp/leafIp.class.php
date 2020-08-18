<?php

class leafIp
{
    /**
     * get user IP address, if using transparent proxy, then get real ip :)
     * 
     * @return string - IP
     */
    
    public static function getIp()
    {
        $ip = null;
        $ipFields = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        );
        
        foreach( $ipFields as $field )
        {
            if(
                getenv( $field )
                &&
                strcasecmp( getenv( $field ), 'unknown' )
            )
            {
                $ip = getenv('HTTP_CLIENT_IP');
                break;
            }
        }
        
        if( !$ip && !empty( $_SERVER['$_SERVER'] ) )
        {
            $ip = $_SERVER['$_SERVER'];
        }
        elseif( !$ip )
        {
            $ip = 'unknown';
        }
        
        return $ip;
    }



	/**
	 * Get country name by IP address
	 * 
	 * @param string $ip
	 * @return object leafCountry
	 */
    
	public static function getCountry( $ip = null )
	{
		if( !$ip )
		{
			$ip = self::getIp();
        }
        
        if(
            !function_exists( 'geoip_country_code_by_name' )
            ||
            !class_exists( 'leafCountry' )
        )
        {
            return null;
        }
        
        $countryName = @geoip_country_code_by_name( $ip );
        $country = leafCountry::getByName( $countryName );
        
        return $country;
	}
    
    
    /**
     * Check if IP is in specified range
     * 
     * Network ranges can be specified as:
     *  1. Wildcard format:     1.2.3.*
     *  2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
     *  3. Start-End IP format: 1.2.3.0-1.2.3.255
     *
     * @param string $ip
     * @param mixed $range
     * 
	 * @return bool
     */
    
    public static function isIpInRange( $ip, $range )
    {
        if( $ip == $range )
        {
            return true;
        }
        
        if( strpos( $range, '/' ) !== false )
        {
            // $range is in IP/NETMASK format
            list( $range, $netmask ) = explode( '/', $range, 2) ;
            
            if( strpos($netmask, '.' ) !== false )
            {
                // $netmask is a 255.255.0.0 format
                $netmask = str_replace( '*', '0', $netmask );
                $netmask_dec = ip2long( $netmask );
                return ( ( ip2long( $ip ) & $netmask_dec ) == ( ip2long( $range ) & $netmask_dec ) );
            }
            else
            {
                // $netmask is a CIDR size block
                // fix the range argument
                $x = explode( '.', $range );
                while( count( $x ) < 4 ) $x[] = '0';
                list( $a, $b, $c, $d ) = $x;
                $range = sprintf( "%u.%u.%u.%u", empty( $a ) ? '0' : $a, empty( $b ) ? '0' : $b, empty( $c ) ? '0' : $c, empty( $d ) ? '0' : $d );
                $range_dec = ip2long( $range );
                $ip_dec = ip2long( $ip );
                
                # Strategy 2 - Use math to create it
                $wildcard_dec = pow( 2, ( 32 - $netmask ) ) - 1;
                $netmask_dec = ~ $wildcard_dec;
                
                return ( ( $ip_dec & $netmask_dec ) == ( $range_dec & $netmask_dec ) );
            }
        }
        else
        {
            // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
            if( strpos( $range, '*' ) !== false )
            { 
                // Just convert to A-B format by setting * to 0 for A and 255 for B
                $lower = str_replace( '*', '0', $range );
                $upper = str_replace( '*', '255', $range );
                $range = "$lower-$upper";
            }
            
            if( strpos( $range, '-' ) !== false )
            {
                list( $lower, $upper ) = explode( '-', $range, 2 );
                $lower_dec = (float) sprintf( "%u",ip2long( $lower ) );
                $upper_dec = (float) sprintf( "%u", ip2long( $upper ) );
                $ip_dec = (float) sprintf( "%u", ip2long( $ip ) );
                return ( ( $ip_dec >= $lower_dec ) && ( $ip_dec <= $upper_dec ) );
            }
            
            return false;
        }
    }
    
}