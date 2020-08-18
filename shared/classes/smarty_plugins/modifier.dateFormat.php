<?php

require_once $smarty->_get_plugin_filepath('modifier', 'date_format');
require_once $smarty->_get_plugin_filepath('function', 'eval');
            
function smarty_modifier_dateFormat ($date, $formatCode = null )
{
    // always returns a html encoded string!
    
    $dateFormats = leaf_get('properties', 'dateFormats');

    if (
        (empty($dateFormats))
        ||
        (!is_array($dateFormats))
    )
    {
        trigger_error('dateFormats not defined.', E_USER_WARNING);
        return htmlspecialchars( $date );
    }

    if (empty($formatCode))
    {
        $formatCode = key( $dateFormats ); // use first
    }
    

    if (empty($dateFormats[$formatCode]))
    {
        trigger_error('Date format "' . htmlspecialchars($formatCode) . '" not defined.', E_USER_WARNING);
        return htmlspecialchars( $date );
    }

    if (is_string($dateFormats[$formatCode]))
    {
        // same format for all languages
        $formatString = $dateFormats[$formatCode];
    }
    elseif (is_array($dateFormats[$formatCode]))
    {
        $format = $dateFormats[$formatCode];
        $languageCode = leaf_get('properties', 'language_code');

        if (!isset($format[$languageCode]))
        {
            // format string not found for given language

            // attempt to split on dashes (e.g. to use 'en' format for 'en-US')
            $languageCodeParts = explode('-', $languageCode);
            if (count($languageCodeParts) > 1)
            {
                $languageCode = $languageCodeParts[0];
            }

            if (!isset($format[$languageCode]))
            {
                // use first defined format string
                $languageCode = key($format);
            }
        }

        $formatString = $format[$languageCode];
    }

    $result = smarty_modifier_date_format ( $date, $formatString ) ; 
    
    // process aliases in format string
    if (preg_match('/(\{alias\s+)/ui', $result))
    {
        // format string contains aliases
        // evaluate them via smarty
        $smarty = new leaf_smarty( __DIR__ );
        $result = smarty_function_eval( array('var' => $result) , $smarty);
        
        // escape everything else except alias placeholders
        $result = htmlspecialchars($result);
        $result = preg_replace('/(\&lt;)(alias_\d+)(\&gt;)/', '<$2>', $result );
    }
    else
    {
        $result = htmlspecialchars($result);
    }
    
    return $result;

}
