<?

class leafBankAuth
{
    public static function init( $providerClass, $data = array( ) )
    {
        $responseUrl = self::getAuthHandlerUrl( $providerClass ); 
        
        $paymentProvider = leafPayment::factoryPaymentProvider( $providerClass );
        $paymentProvider->setResponseURL( $responseUrl );
        $paymentProvider->setData( $data );
        $paymentProvider->handleAuth();
    }
    
    
    public static function getResponse( $providerClass, $response )
    {
        $return = null;
        
        $payment = new leafPayment();
        $payment->setPaymentProvider( leafPayment :: factoryPaymentProvider( $providerClass ) );

        if( $payment->verifyResponse( $response ) )
        {
            $return = $payment->getPaymentProvider();
        }
        
        return $return;
    }
    
    
    public static function getHandlerList()
    {
        $services = objectTree::getFirstChild( 0, 'services' );
        $queryParts = objectTree::getVisibleChildrenQueryParts( $services, 'payment/handler' );
        $queryParts['where'][] = "`x`.`enabledAuthentication` = 1";
        $queryParts['select'] = array( 'o.*', '`x`.`type`' );
        
        return dbGetAll( $queryParts );
    }
    
    
    public static function getHandlerData( $providerClass )
    {
        $services = objectTree::getFirstChild( 0, 'services' );
        $queryParts = objectTree::getVisibleChildrenQueryParts( $services, 'payment/handler' );
        
        $queryParts['where'][] = "`x`.`type` = '" . dbSE( $providerClass ) . "'";
        $queryParts['where'][] = "`x`.`enabledAuthentication` = 1";
        
        return dbGetRow( $queryParts );
    }
    
    
    public static function getAuthHandlerUrl( $providerClass )
    {
        $handler = self::getHandlerData( $providerClass );
        
        $handlerId = get( $handler, 'id' );
        
        if( $handlerId )
        {
            $url = orp( $handlerId );
        }
        
        return $url;
    }
    
    
    public static function getRewriteByClass( $providerClass )
    {
        $handler = self::getHandlerData( $providerClass );
        return get( $handler, 'rewrite_name' );
    }
}   