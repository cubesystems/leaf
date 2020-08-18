<?
require_once SHARED_PATH . 'classes/singleton/_init.php';

class alias_cache extends singleton_object
{
    var $registeredAliases  = array();
    var $nextFreeAliasKey   = null;

    var $escapeByDefault    = true;
    var $defaultEscapeMode  = 'html';

    protected static $cachedLanguageIds = array();
    protected static $cachedLanguageCodes = array();

    protected static $useSampleTexts = true;
    
    // *********** shared  ***********
    function alias_cache()  // constructor
    {
        $this->nextFreeAliasKey = 0;
    }

    // public static. returns reference to alias_cache instance (singleton pattern)
    static function & getAliasCacheContainer()
    {
        $instance = & singleton::get( 'alias_cache' );
        if (!$instance)
        {
            trigger_error('Could not get alias_cache container instance.', E_USER_WARNING);
        }
        return $instance;
    }

    // *********** methods for registering aliases ***********
    /* public static */
	static function setContext(& $smarty, $context)
    {
        $contextVarName = 'alias_context';

        if (is_null($context))
        {
            unset ( $smarty->_tpl_vars[ $contextVarName ] );
        }
        else
        {
            $smarty->_tpl_vars[ $contextVarName ] = $context;
        }

        return true;
    }

    /* public static */
	static function setFallbackContext(& $smarty, $context)
    {

        $contextVarName = 'alias_fallback_context';

        if (is_null($context))
        {
            unset ( $smarty->_tpl_vars[ $contextVarName ] );
        }
        else
        {
            $smarty->_tpl_vars[ $contextVarName ] = $context;
        }

        return true;
    }

    /* public static */
	static function setLanguage(& $smarty, $language)
    {
        $languageVarName = 'alias_language';

        if (is_null($language))
        {
            unset ( $smarty->_tpl_vars[ $languageVarName ] );
        }
        else
        {
            $smarty->_tpl_vars[ $languageVarName ] = $language;
        }

        return true;
    }

    /* public static */
    static function getLanguage($smarty)
    {
        $languageVarName = 'alias_language';

        if (isset($smarty->_tpl_vars[ $languageVarName ]))
        {
            return $smarty->_tpl_vars[ $languageVarName ];
        }
        else
        {
            return null;
        }
    }

    /* public static */
    static function getContext($smarty)
    {
        $contextVarName = 'alias_context';

        if (isset($smarty->_tpl_vars[ $contextVarName ]))
        {
            return $smarty->_tpl_vars[ $contextVarName ];
        }
        else
        {
            return null;
        }
    }

    /* public static */
    static function getFallbackContext($smarty)
    {
        $contextVarName = 'alias_fallback_context';

        if (isset($smarty->_tpl_vars[ $contextVarName ]))
        {
            return $smarty->_tpl_vars[ $contextVarName ];
        }
        else
        {
            return null;
        }
    }


    /* public static */
    static function registerAlias ($code, $context, $escape = null, $language = null, $fallbackContext = null, $variables = array(), $enableTags = null, $amount = null)
    {
        if (! $container = & alias_cache::getAliasCacheContainer())
        {
            return false;
        }

        return $container->privateRegisterAlias($code, $context, $escape, $language, $fallbackContext, $variables, $enableTags, $amount);
    }

    /* private */
    function privateRegisterAlias($code, $context, $escape = null, $language = null, $fallbackContext = null, $variables = array(), $enableTags = null, $amount = null)
    {
        //debug ($code, 0);
        //debug ($context, 0);

        if (!$this) // prevent static calls
        {
            trigger_error('Static calls to privateRegisterAlias not allowed.', E_USER_WARNING);
            return false;
        }

        // validation:
        // assuming that $code is ok

        // $escape can be null, true, false or any escape mode string that has a matching escape function
        // null defaults escape to class properties
        // false disables escaping
        // true enables escaping with default method
        // string enables escaping with a specified method

        if (is_null($escape))
        {
            $doEscape = $this->escapeByDefault;
            $escapeMode = $this->defaultEscapeMode;
        }
        elseif (is_bool($escape))
        {
            $doEscape = $escape;
            $escapeMode = $this->defaultEscapeMode;
        }
        elseif (is_string($escape))
        {
            $methodName = 'escape_' . $escape;
            if (method_exists($this, $methodName))
            {
                $doEscape = true;
                $escapeMode = $escape;
            }
            else // method does not exist
            {
                trigger_error('Bad escape method argument for alias.', E_USER_WARNING);
                return false;
            }
        }

        // validation done. store alias
        return $this->addAlias($code, $context, $doEscape, $escapeMode, $language, $fallbackContext, $variables, $enableTags, $amount);
    }

    /* private */
    // *********** methods for alias language value translation ***********
	function getAliasLanguage($langCodeOrId){
		// return cached
		if (isset(self::$cachedLanguageIds[$langCodeOrId]))
		{
			return self::$cachedLanguageIds[$langCodeOrId];
		}
		// try to find id and code of language
		elseif(!empty($langCodeOrId))
		{
			// check for default language
			if(leaf_get('properties', 'language_id') == $langCodeOrId || leaf_get('properties', 'language_name') == $langCodeOrId)
			{
				$langId = leaf_get('properties', 'language_id');
				self::$cachedLanguageIds[$langId] = $langId;
				self::$cachedLanguageIds[leaf_get('properties', 'language_name')] = $langId;
				self::$cachedLanguageCodes[$langId] = leaf_get('properties', 'language_code');
				return $langId;
			}
			// check in db
			$q = '
			SELECT
				`id`,
				`short`,
				`code`
			FROM
				`languages`
			WHERE
				`id` = "' . dbSE($langCodeOrId) . '"
				OR
				`short` = "' . dbSE($langCodeOrId) . '"
				OR
				`code` = "' . dbSE($langCodeOrId) . '"
			';
			if($result = dbGetRow($q))
			{
				self::$cachedLanguageIds[$result['short']] = $result['id'];
				self::$cachedLanguageIds[$result['id']] = $result['id'];
				self::$cachedLanguageCodes[$result['id']] = $result['code'];
				return $result['id'];
			}
		}
		if(empty($langCodeOrId))
		{
			// return default
			return leaf_get('properties', 'language_id');
		}
		return $langCodeOrId;
	}

	protected static function getLanguageCode( $languageId )
	{
	    if (!ispositiveint($languageId))
	    {
	        // $languageId = self::getLanguage()
	    }
	    if (!array_key_exists( $languageId, self::$cachedLanguageCodes))
	    {
	        self::$cachedLanguageCodes[ $languageId ] = dbgetone('SELECT `code` FROM `languages` WHERE `id` = ' . $languageId);
	    }
	    return self::$cachedLanguageCodes[ $languageId ];
	}

    /* private */
    function addAlias($code, $context, $doEscape, $escapeMode, $language, $fallbackContext, $variables, $enableTags, $amount)
    {

        if (!$this) // prevent static calls
        {
            trigger_error('Static calls to addAlias not allowed.', E_USER_WARNING);
            return false;
        }
        // assume all arguments are ok

        // attempt to split array indexes
        $codeSplit = explode('.', $code);
        if (count($codeSplit) > 1)
        {
            $arrayIndex = array_pop($codeSplit);
            $code = implode('.', $codeSplit);
        }
        else
        {
            $arrayIndex = null;
        }
		// process language setting
		$language = $this->getAliasLanguage($language);

		if (!is_null($amount))
		{
		    $code = self::getAmountCode( $code, $amount, $language );
		}


        $key = $this->nextFreeAliasKey;
        $delimiter = self::getDelimiter();
        $contextAndCode = (empty($context)) ? $code : $context . $delimiter . $code;

        $fallbackContextAndCode = null;
        if (!is_null($fallbackContext))
        {
            $fallbackContextAndCode = (empty($fallbackContext)) ? $code : $fallbackContext . $delimiter . $code;
        }

        $aliasArray = array
        (
            'key'                    => $key,
            'code'                   => $code,
            'index'                  => $arrayIndex,
            'context'                => $context,
            'language'               => $language,
            'context_and_code'       => $contextAndCode,
            'fallbackContext'        => $fallbackContext,
            'fallbackContextAndCode' => $fallbackContextAndCode,
            'doEscape'               => $doEscape,
            'escapeMode'             => $escapeMode,
            'variables'              => $variables,
            'enableTags'             => $enableTags
        );


        $this->registeredAliases[ $key ] = $aliasArray;
        $this->nextFreeAliasKey++;

        return $this->getPlaceHolderText( $key );
    }

    /* private */
    function getPlaceHolderText ( $aliasKey )
    {
        if (!is_scalar($aliasKey))
        {
            trigger_error('Cannot get placeholder text. Bad alias key.', E_USER_WARNING);
            //dump($aliasKey, 0);
            
            return false;
        }
        elseif (!isset( $this->registeredAliases[ $aliasKey ] ))
        {
            trigger_error('Cannot get placeholder text. Alias not registered.', E_USER_WARNING);
            return false;
        }
        $placeHolderText = '<alias_' . $aliasKey . '>';
        return $placeHolderText;
    }

	public static function getAlias($alias, $context = '', $escape = false, $language = null, $fallbackContext = null , $variables = array(), $enableTags = null, $amount = null, $useSampleTexts = null )
	{
		$list = alias_cache::getAliases(array($alias), $context, $escape, $language, $fallbackContext, $variables, $enableTags, $amount, $useSampleTexts);

		$exploded = explode('.', $alias);
		$code = reset($exploded);

		if (!is_null($amount))
		{
            if (! $container = & alias_cache::getAliasCacheContainer())
            {
                return false;
            }
            $languageId = $container->getAliasLanguage( $language );
    		$code = self::getAmountCode($code, $amount, $languageId );
		}

		return $list[$code];
	}

	public static function getAliases($aliases, $context = '', $escape = false, $language = null, $fallbackContext = null, $variables = array(), $enableTags = null, $amount = null, $useSampleTexts = null)
    {
        if (! $container = & alias_cache::getAliasCacheContainer())
        {
            return false;
        }
		foreach($aliases as $key)
		{
			if(is_array($key) && sizeof($key) == 2)
			{
				self::registerAlias ($key[0], $key[1], $escape, $language, $fallbackContext, $variables, $enableTags, $amount);
			}
			else
			{
				self::registerAlias ($key, $context, $escape, $language, $fallbackContext, $variables, $enableTags, $amount);
			}
		}

        $useSampleTexts = (is_null($useSampleTexts)) ? self::getSampleTextsSetting() : $useSampleTexts;
        
        $container->getRegisteredAliasesFromDb();
        // walk through registered aliases and see which ones have results
		$list = array();
		$container->delimiter = self::getDelimiter();
        foreach ($container->registeredAliases as $aliasKey => $registeredAlias)
        {
			$aliasText = $container->buildAliasValue($registeredAlias, $useSampleTexts);
            $list[$registeredAlias['code']] = $aliasText;
        }
		return $list;
	}



	private function buildAliasValue($registeredAlias, $useSampleTexts )
	{
        $originalAliasCode = $aliasCode = $registeredAlias['language'] . $this->delimiter . $registeredAlias['context_and_code'];

        if (
            (!isset($this->resultAliases[$aliasCode]))
            &&
            (!empty($registeredAlias['fallbackContextAndCode']))
        )
        {
            $aliasCode = $registeredAlias['language'] . $this->delimiter . $registeredAlias['fallbackContextAndCode'];
        }

        //debug ($aliasCode, 0);
        //debug ($registeredAlias, 0);

        //$aliasText = null;
        //if (!$this->resultAliasExists[$aliasCode])
        //{

        //}
       
        if (
            (!isset($this->resultAliases[$aliasCode]))
            ||
            (
                (!is_array($this->resultAliases[$aliasCode]))
                &&
                (mb_strlen($this->resultAliases[$aliasCode]) == 0)
            )
        )
        {   // lang/code key not found in results or value is empty string
            // return automatic text extracted from code

            if ($useSampleTexts)
            {
                $aliasText = self::getSampleText( $originalAliasCode );
            }
            else
            {
                $aliasText = '';
            }
            // $aliasText = str_replace($this->delimiter, ':', $originalAliasCode);
        }
		/*
        elseif (is_null($resultAliases[$languageAndCode]['translation_text']))
        {   // alias exists but has no translation in needed language
            $aliasText = $resultAliases[$languageAndCode]['alias_default_text'];
        }
		*/
        else
        {   // alias has translation text
            $alias = $this->resultAliases[$aliasCode];

            if (is_null($registeredAlias['index']))
            {
                $aliasText = (string) $alias;
            }
            else
            {
                if (isset($alias[$registeredAlias['index']]))
                {
                    $aliasText = (string) $alias[$registeredAlias['index']];
                }
                else
                {
                    $aliasText = str_replace($this->delimiter,':', $aliasCode) . '.'  . $registeredAlias['index'];
                }
            }
        }

        if (!empty($registeredAlias['variables']))
        {
            $aliasText = self::fillInVariables( $aliasText, $registeredAlias['variables'] );
        }

        if ($registeredAlias['doEscape'])
        {
            $methodName = 'escape_' . $registeredAlias['escapeMode'];
            $aliasText = $this->$methodName($aliasText);
        }

        if (!empty($registeredAlias['enableTags']))
        {
            $aliasText = self::processTags( $aliasText );
        }

	    return $aliasText;
	}

    static function fillInVariables( $aliasText, $variables )
    {
        if (empty($variables) || !is_array($variables))
        {
            return $aliasText;
        }
        
        foreach ($variables as $variable => $value)
        {
            $search = '[$' . $variable . ']';
            $replace = $value;
            $aliasText = str_replace($search, $replace, $aliasText);
        }
        return $aliasText;
    }
    
    public static function processTags( $aliasText )
    {
        // links
        $matches = array();
        preg_match_all('/(\[link)((:)([^\]]+))?(\])(.*)(\[\/link\])/uU', $aliasText, $matches);

        foreach ($matches[0] as $key => $match)
        {
            $fullString = $matches[0][$key];
            $link       = $matches[4][$key];
            $linkText   = $matches[6][$key];

            if (empty($matches[3][$key]))
            {
                $link = $linkText;
            }


            if (ispositiveint($link))
            {
                $link = orp( $link );
            }
            elseif (substr($link, 0, 1) == '#')
            {
                // do nothing
            }
            elseif (strpos($link, 'javascript:') === 0)
            {
                // do nothing
            }
            elseif (strpos($link,'://') === false)
            {
                $link = 'http://' . $link;
            }

            $linkHtml = '<a href="' . $link . '">' . $linkText . '</a>';
            $aliasText  = str_replace( $fullString, $linkHtml, $aliasText);
        }

        // tags
        $aliasText = preg_replace('/(\[)(strong|em)(\])(.*)(\[\/\\2\])/uU', '<\\2>\\4</\\2>', $aliasText);
            
        return $aliasText;
    }
    
    // *********** methods for alias output (template output processing) ***********
    static function fillInAliases ($templateOutput, & $smarty = null)
    {
        if (! $container = & alias_cache::getAliasCacheContainer())
        {
            return false;
        }


        // gets $templateOutput with alias placeholder texts
        // loads all previously registered aliases from db
        // replaces their placeholder texts with actual values

        // get alias texts from db
        $container->getRegisteredAliasesFromDb();
        $search = $replace = array();
        // walk through registered aliases and see which ones have results
		$container->delimiter = self::getDelimiter();
		// debug ($container->registeredAliases);
        foreach ($container->registeredAliases as $aliasKey => $registeredAlias)
        {
			$aliasText = $container->buildAliasValue($registeredAlias, self::getSampleTextsSetting());
            $placeHolderText = $container->getPlaceHolderText( $aliasKey );
            $search[] = '/' . $placeHolderText . '/';
            $replace[] = $aliasText;
        }

		$replacedOutput = preg_replace($search, $replace, $templateOutput);
        return $replacedOutput;
    }

    /* private */
    function getRegisteredAliasesFromDb()
    {
        // returns assoc array from db with context_and_code for array keys
        // creates missing aliases

        $codes = $createIfMissing = $createFirstIfBothMissing = array();

        $sqlConditions = array();
        $delimiter = self::getDelimiter();


        foreach ($this->registeredAliases as $key => $val)
        {
            $codes[$val['language']][] = '"' . dbSE($val['context_and_code']) . '"';

            // no fallback context given. alias will be created if missing
            if (empty($val['fallbackContextAndCode']))
            {
                $createIfMissing[] = array
                (
                    'name'              => $val['context_and_code'],
                    'requestedLanguage' => $val['language'],
                    'context'           => $val['context']
                );
            }
            else
            {
                // fallback context given. also load fallback alias.
                // create requested alias if both are missing
                $codes[$val['language']][] = '"' . dbse( $val['fallbackContextAndCode'] ) . '"';

                $createFirstIfBothMissing[] = array
                (
                    'name'              => $val['context_and_code'],
                    'otherName'         => $val['fallbackContextAndCode'],
                    'requestedLanguage' => $val['language'],
                    'context'           => $val['context']
                );
            }

        }


        // debug ($codes, 0);

		$langQueries = array();
        $translations = array();
		$delimiter = self::getDelimiter();
		if(empty($codes))
		{
            return $translations;
		}
		foreach($codes as $langId => $langCodes)
		{
			$langQueries[] = '
				SELECT
					t.type,
					"' . $langId . '" AS `lang`,
					t.name,
					t_d.translation
				FROM
					translations AS `t`
					LEFT JOIN
					translations_data AS `t_d`
						ON
						t.id = t_d.translation_id
						AND
						t_d.language_id = "' . $langId . '"
				WHERE
					t.name IN (' . implode(', ', $langCodes) . ') AND
					t_d.language_id = "' . $langId . '"
			';
		}
		$q = implode(' UNION ', $langQueries);

		$rows = dbgetall( $q );


        $foundAliasNames = array();
		foreach ($rows as $row)
		{
		    $foundAliasNames[$row['name']] = true;
			$translationKey = $row['lang'] . $delimiter . $row['name'];
			if ($row['type'] == 0)
			{
				$translations[$translationKey] = $row['translation'];
			}
			else
			{
			    // explode array translations
				$temp = explode(';', $row['translation']);
				$temp_values = array();
				foreach ($temp as $temp_value)
				{
					if ($temp_value != '')
					{
						$temp_value = explode('=>', $temp_value);
						if(sizeof($temp_value) == 2)
						{
							$temp_values[$temp_value[0]] = $temp_value[1];
						}
					}
				}
				$translations[$translationKey] = $temp_values;
			}
		}

		// collect missing aliases to create
		$aliasesToCreate = array();
		foreach ($createIfMissing as $tmp)
		{
		    $key = $tmp['requestedLanguage'] . $delimiter . $tmp['name'];
		    if (!isset($translations[$key]))
		    {
		        $aliasesToCreate[] = $tmp;
		    }
		}

		foreach ($createFirstIfBothMissing as $tmp)
		{
		    $key = $tmp['requestedLanguage'] . $delimiter . $tmp['name'];
		    if (!isset($translations[$key]))
		    {
                $key = $tmp['requestedLanguage'] . $delimiter . $tmp['otherName'];
                if (!isset($translations[$key]))
                {
                    $aliasesToCreate[] = $tmp;
                }
		    }
		}

		// create missing aliases with blank translations
		// also create new groups for missing contexts
		if (!empty($aliasesToCreate))
		{
            // debug ($aliasesToCreate);

            $languageIds = dbgetall('select id from languages', null, 'id');

    		$existingContexts = self::getExistingContexts();
    		$existingContextAliases = array();
            
    		$aliasesCreated = array();
    		// create missing contexts / groups
    		foreach ($aliasesToCreate as $alias)
    		{
    		    if (isset($aliasesCreated[$alias['name']]))
    		    {
    		        // already created
    		        continue;
    		    }

    		    if (!isset($existingContexts[$alias['context']]))
    		    {
    		        // group not found for context, create
        		    $newContextId = dbinsert('translations_groups', array('name' => $alias['context']));
                    $existingContexts[$alias['context']] = $newContextId;
    		    }

    		    // load existing aliases for group
    		    if (!isset($existingContextAliases[$alias['context']]))
    		    {
    		        $existingContextAliases[$alias['context']] = array();
    		        
    		        $codes = dbgetall( 'SELECT id, name FROM translations WHERE group_id = "' . dbSE( $existingContexts[$alias['context']] ) . '" ' );
    		        
    		        foreach( $codes as $code )
    		        {
    		          $existingContextAliases[$alias['context']][$code['name']] = $code['id'];
    		        }
    		    }

    		    // create alias row if not exists
                if( !array_key_exists( $alias['name'], $existingContextAliases[$alias['context']] ) )
                {
                    $aliasRow = array
                    (
                        'group_id' => $existingContexts[$alias['context']],
                        'name'     => $alias['name'],
                        'type'     => 0
                    );
                    $aliasId = dbinsert('translations', $aliasRow);
                }
                else
                {
                    $aliasId = $existingContextAliases[$alias['context']][$alias['name']];
                }

    		    
    		    // create empty translation only in requested language, 
    		    // to avoid duplicate translations in case a translation already has entries in other languages

                $row = array
                (
                    'translation_id' => $aliasId,
                    'language_id'    => $alias['requestedLanguage'],
                    'translation'    => ''
                );
                dbinsert( 'translations_data', $row );

                // mark as created
                $aliasesCreated[$alias['name']] = true;
                

    		    // add to return array with empty value
    		    $key = $alias['requestedLanguage'] . $delimiter . $alias['name'];
    		    $translations[$key] = null;
    		}
            self::registerDbChanges();
            
		}

		$this->resultAliases = $translations;
    }


    // *********** return escape methods ***********

    /* private */
    function escape_html ($string)
    {
        $string = htmlspecialchars($string);
        $string = preg_replace('/(&amp;)(#)((\d)+)(;)/', '&\\2\\3\\5', $string);
        return $string;
    }

    /* private */
    function escape_html_nl2br ($string)
    {
        $string = htmlspecialchars($string);
        $string = preg_replace('/(&amp;)(#)((\d)+)(;)/', '&\\2\\3\\5', $string);
        $string = nl2br($string);
        return $string;
    }

    /* private */
    function escape_html_with_links ($string)
    {
        $string = htmlspecialchars($string);
        $string = preg_replace('/(&amp;)(#)((\d)+)(;)/', '&\\2\\3\\5', $string);

        $string = preg_replace('/(\\S+@\\S+\\.\\w+)/', '<a href="mailto:\\1">\\1</a>', $string);
        return $string;
    }

    /* private */
    function escape_html_nobr ($string)
    {
        $string = htmlspecialchars($string);
        $string = preg_replace('/(&amp;)(#)((\d)+)(;)/', '&\\2\\3\\5', $string);

        $string = preg_replace('/\s/', '&nbsp;', $string);
        return $string;
    }

    protected static function getAmountCode( $code, $amount, $languageId )
    {
        $languageCode = self::getLanguageCode( $languageId );
        if (!$languageCode)
        {
            return $code;
        }

        $amount = (string) abs((int) $amount);

        if ($amount == 0)
        {
            return $code . 'None';
        }


        
        if (
            // only 1 needs singular form in english
            (($languageCode == 'en') && ($amount == '1'))
            ||
             // for other languages numbers like 21, 31, 101 (but not 11, 111) also use singular form
            (($languageCode != 'en') && (preg_match('/(^|[^1])1$/', $amount)))
        )
        {
            return $code . 'Singular';
        }

        switch ($languageCode)
        {
            case 'ru':
                if (preg_match('/(^|[^1])(2|3|4)$/', $amount))
                {
                    return $code . 'Plural234';
                }

            // intentional fall through
            case 'en':
            case 'lv':
            default:
        }
        return $code;
    }

    public static function getExistingContexts()
    {
        // get current contexts
        $q =
        '
            SELECT
                IF(INSTR(t.name, CHAR(0)),SUBSTRING_INDEX(t.name, CHAR(0), 1),"") `context`,
                g.id
            FROM
                `translations` `t`
            LEFT JOIN
                `translations_groups` `g` ON g.id = t.group_id
            GROUP BY
                t.group_id
        ';
        $existingGroups = dbGetAll($q, 'context', 'id');
        return $existingGroups;
    }

    protected static function getDelimiter()
    {
        return chr(0);
    }

    public static function getSampleText( $aliasName )
    {
        $delimiter = self::getDelimiter();

        $nameParts = explode($delimiter, $aliasName);

        $code = end($nameParts);

        // convert dashes and underscores to spaces
        $sampleText = preg_replace('/-|_/u', ' ', $code);

        // spacify camel case
        $sampleText = preg_replace( '/([a-z0-9])([A-Z])/u', '$1 $2', $sampleText );


        // un-titlecase words (try to leave uppercase acronyms intact)
        $sampleText = preg_replace_callback( '/(\b)([A-Z]*)([A-Z])([a-z])/u', array(__CLASS__, 'getSampleTextReplaceCallback'), $sampleText);

        // uc first text
        $sampleText = mb_strtoupper( mb_substr($sampleText, 0, 1) ) . mb_substr($sampleText, 1);

        // remove double spaces
        $sampleText = preg_replace('/\s{2,}/', ' ', $sampleText);

        // trim empty ends
        $sampleText = trim($sampleText);

        // debug ($sampleText, 0);
        return $sampleText;
    }

    protected static function getSampleTextReplaceCallback($matches)
    {
        // debug($matches,0);

        $result = '';
        if (!empty($matches[2]))
        {
            if (mb_strlen($matches[2]) == 1)
            {
                $matches[2] = mb_strtolower($matches[2]);
            }
            $result .= $matches[2] . ' ';
        }
        $result .= mb_strtolower($matches[3]) . $matches[4];

        return $result;
    }
    
    public static function getSampleTextsSetting( ) 
    {
        return self::$useSampleTexts;
    }
    
    public static function toggleSampleTexts( $onOff ) 
    {
        self::$useSampleTexts = (bool) $onOff;
        return true;
    }
    
    public static function clear()
    {
        if (!$container = & alias_cache::getAliasCacheContainer())
        {
            return false;
        }      
        $container->registeredAliases = array();
        $container->nextFreeAliasKey = 0;
    }
    
    
    public static function registerDbChanges()
    {
        setValue('aliases.lastChanges', time());
    }
    
}

?>
