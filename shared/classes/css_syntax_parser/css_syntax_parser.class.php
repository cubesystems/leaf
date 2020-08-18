<?

class css_syntax_parser
{
    const removeCommentsSearch       = '/(\/\*)(.*)(\*\/)/sU';
    const removeCommentsReplace      = '';

    const defsSplitPattern           = '/(?<!\\\\)\}/mi'; // split on } which is not preceded by \

    const classNameSplitPattern      = '/(?<!\\\\)\{/mi'; // split on { which is not preceded by \

    const rulesSplitPattern          = '/(?<!\\\\);/mi'; // split on ; which is not preceded by \
    const ruleNameValueSplitPattern  = '/(?<!\\\\):/mi'; // split on : which is not preceded by \
    const ruleNameValidationPattern  = '/^[a-z0-9\-_]+$/i';
    const ruleUnescapeSearch         = '/(\\\\)(\{|\}|:|;|")/mi';
    const ruleUnescapeReplace        = '\\2';


    public static function parseFile( $fileName )
    {
        // returns array or false on error

        // 1) load file
        // 2) split class defs on '}'
        // 3) foreach class def split class name / definition on '{';
        // 4) split definitions on ';'
        // 5) split name/value pairs on ':';

        // 1)
        $fileContent = file_get_contents( $fileName );
        if (!$fileContent)
        {
            return false;
        }
        // strip comments
        $fileContent = self::stripComments( $fileContent );

        // 2)
        $classSplit = preg_split(self::defsSplitPattern, $fileContent, null, PREG_SPLIT_NO_EMPTY);

        $classDefStrings = array();
        foreach ($classSplit as $classDef)
        {
            $classDef = trim($classDef);

            // for now only class definitions supported
            if (substr($classDef,0,1) != '.')
            {
                continue;
            }

            // 3)
            $classSplit = preg_split( self::classNameSplitPattern, $classDef, null, PREG_SPLIT_NO_EMPTY );
            if (count($classSplit) != 2)
            {
                continue;
            }

            $className      = trim( substr( $classSplit[0], 1 ));
            $classDefString = trim( $classSplit[1] );

            $classDefStrings[$className] = $classDefString;
        }

        //debug ($classDefStrings,false);

        // 4)
        $classDefs = array();
        foreach ($classDefStrings as $className => $rulesString)
        {
            $rules = self::getRules( $rulesString );
            if (empty($rules))
            {
                continue; // no valid attributes found in class, skip class
            }

            $classDefs[$className] = $rules;
        }
        return $classDefs;
    }


    public static function stripComments( $string )
    {
        $string = preg_replace( self::removeCommentsSearch , self::removeCommentsReplace, $string);
        return $string;
    }


    public static function getRules( $rulesString )
    {
        $rulesString = self::stripComments( $rulesString );

        $rules = array();

        $rulesSplit = preg_split(self::rulesSplitPattern, $rulesString, null, PREG_SPLIT_NO_EMPTY);

        foreach ($rulesSplit as $rule)
        {
            // 5)
            $nameValueSplit = preg_split(self::ruleNameValueSplitPattern, $rule, null, PREG_SPLIT_NO_EMPTY);
            if (count($nameValueSplit) != 2)
            {
                continue; // not in 2 parts
            }
            $attrName  = $nameValueSplit[0];
            $attrValue = $nameValueSplit[1];

            $attrName = strtolower(trim($attrName));

            if (!preg_match(self::ruleNameValidationPattern , $attrName))
            {
                continue; // bad attribute name format, skip attribute
            }

            // unescape special chars in value
            $attrValue = trim($attrValue);
            if (
                (substr($attrValue,0,1) == '"')
                &&
                (substr($attrValue,-1) == '"')
            )
            {
                $attrValue = substr($attrValue,1,-1);
            }
            $attrValue = preg_replace(self::ruleUnescapeSearch, self::ruleUnescapeReplace , $attrValue);

            $hits = array();
            if (preg_match('/@(?<pixelRatio>[1-9].?[0-9]?)x$/i', $attrValue , $hits))
            {
                $attrValue = str_replace( '@' . $hits['pixelRatio'] . 'x', '', $attrValue );

                $rules[$attrName . '-' . $hits['pixelRatio']] = $attrValue;
                if (strpos( $hits['pixelRatio'], '.0' ) !== false)
                {
                    $rules[$attrName . '-' . str_replace( '.0', '', $hits['pixelRatio'] ) ] = $attrValue;
                }
                else
                {
                    $rules[$attrName . '-' . $hits['pixelRatio'] . '.0' ] = $attrValue;
                }
            }
            else
            {
                $rules[$attrName] = $attrValue;
            }
        }

        // sort alphabetically
        return $rules;
    }

}

?>