<?php
class simpleForm extends leafComponent{

	protected static $standardFields = array(
		'id' => 'id',
		'do' => 'action',
	);

	// these will not be allowed to pass through fields list
	protected static $reservedFieldNames = array(
        'redirectUrl',
        'confirmAlias'
	);

	public static $defaultButtonAliasContext = 'admin';
	public static $defaultConfirmationAliasContext = 'admin';
	public static $defaultType = 'delete';

    protected static $fieldAttributePrefix = 'field_';

	public function getContent($params, $content)
	{

		// load prepared params, if set
		if (
            (!empty($params['params']))
            &&
            (is_array($params['params']))
        )
        {
            $preparedParams = $params['params'];
            unset ($params['params']);
            foreach ($preparedParams as $key => $value)
            {
                if (!isset($params[$key]))
                {
                    // no separate value set
                    $params[$key] = $value;
                }
            }
        }

        // set content
		$params['content'] = $content;


        // load mandatory params
		if (empty($params['type']))
		{
			$params['type'] =  self::$defaultType;
		}

		if (empty($params['action']))
		{
			if (
                (!empty($params['module']))
                &&
                (is_string($params['module']))
            )
			{
			    $module = $params['module'];
			}
			else
			{
                $module = $_GET['module'];
			}
			$params['action'] = WWW . '?module=' . urlencode($module);
		}

        // load field params
		$params = $this->parseFieldParams( $params );

		// load submit button
		$params = $this->parseButtonParams( $params );


		// load confirmation
        $params = $this->parseConfirmationParams( $params );

		return $this->buildOutput('form', $params);
	}

	protected function parseFieldParams( $params )
	{
	    // read field values from params and move to a separate 'fields' array

        // start with prepared fields array, if given
        if (
            (!empty($params['fields']))
            &&
            (is_array($params['fields']))
        )
        {
            $fields = $params['fields'];
            foreach ($params['fields'] as $key => $value)
            {
                // prevent invalid items in field in array
                if (
                    (is_null($value))
                    ||
                    (is_scalar($value))
                )
                {
                    continue; // allow null & scalar; booleans will be converted to 1 and 0 on output
                }
                if (is_array($value))
                {
                    continue;
                }
                // non-scalar, non-array, non-null -> remove this
                unset ($fields[$key]);
            }
            unset ($params['fields']);
        }
        else
        {
            // no prepared array given, start with empty fields list
            $fields = array();
        }


        // load predefined standard fields
        if (empty($params['do']))
        {
            $params['do'] = $params['type'];
        }
		foreach (self::$standardFields as $key => $fieldName)
		{
			if (isset($params[$key]))
			{
				$fields[$fieldName] = $params[$key];
				unset( $params[$key] );
			}
		}

		// load fields given in prefixed attributes
        $prefixLength = strlen(self::$fieldAttributePrefix);
		foreach ($params as $key => $value)
		{
			if (substr($key, 0, $prefixLength) == self::$fieldAttributePrefix)
			{
			    $fieldName = substr($key, $prefixLength);
			    $fields[$fieldName] = $value;
				unset($params[$key]);
			}
		}

		foreach ($fields as $fieldName => $value)
		{
		    if (in_array($fieldName, self::$reservedFieldNames))
		    {
		        unset($fields[$fieldName]);
		        continue;
		    }
		    $this->fixFieldOutputValue($fields, $fieldName);
		}

		$params['fields'] = $fields;
		return $params;

	}

	protected function fixFieldOutputValue(& $params, $key)
	{
	    $value = $params[$key];
        if (is_bool($value))
        {
            $params[$key] = ($value) ? 1 : 0;
        }
        elseif (is_null($value))
        {
            unset($params[$key]);
        }
        // elseif (is_array($value))
	    // {
        //     support for array fields may be added if needed
	    // }
        else
        {
            $params[$key] = (string) $value;
        }
        return true;
	}

	protected function parseButtonParams( $params )
	{
	    $button = array(
            'show' => true,
            'image' => null,
            'alias' => null,
            'context' => null,
            'text' => null
	    );

		if (
            (isset($params['button']))
            &&
            ($params['button'] === false)
        )
	    {
	        // button explicitly removed
            $button['show'] = false;
	    }
	    elseif (
            (!empty($params['button']))
            &&
            (is_string($params['button']))
        )
		{
		    // button string is given

		    // simple detection whether the value contains an URL
		    // if URL is detected, assume it is an image URL, use input type image
		    // else assume it is an alias code
		    $mode = (strpos($params['button'], '/') === false) ? 'alias' : 'image';
		    $button[$mode] = $params['button'];
		}


		if (
            (isset($params['buttonText']))
            &&
            (is_string($params['buttonText']))
        )
        {
            // exact button text given
            $button['text'] = $params['buttonText'];
        }

        if (
            (is_null($button['alias']))
            &&
            (isset($params['alias']))
        )
        {
            // use type as alias code (assumes type is already set)
            $button['alias'] = $params['alias'];
        }


        if (
            (is_null($button['text']))
            &&
            (is_null($button['alias']))
        )
        {
            // use type as alias code (assumes type is already set)
            $button['alias'] = $params['type'];
        }



		if (!is_null($button['alias']))
		{
		    if (
                (!empty($params['buttonAliasContext']))
                &&
                (is_string($params['buttonAliasContext']))
            )
            {
                $button['context'] = $params['buttonAliasContext'];
            }
            else
            {
                $button['context'] = self::$defaultButtonAliasContext;
            }
		}

		unset ($params['buttonText'], $params['buttonAliasContext']);

		$params['button'] = $button;
        return $params;
	}

	protected function parseConfirmationParams ( $params )
	{
	    $confirmation = array(
	        'show'    => true,
            'alias'   => null,
            'context' => null,
            'text'    => null
	    );

		if (
            (isset($params['confirmation']))
            &&
            ($params['confirmation'] === false)
        )
	    {
	        $confirmation['show'] = false;
	    }
	    elseif (
            (!empty($params['confirmation']))
            &&
            (is_string($params['confirmation']))
        )
        {
            // alias code given
            $confirmation['alias'] = $params['confirmation'];
        }
        else
        {
            // alias code not given, look for text
    	    if (
                (isset($params['confirmationText']))
                &&
                (is_string($params['confirmationText']))
            )
            {
                $confirmation['text'] = $params['confirmationText'];
            }
            else
            {
                // no text given either, use default alias
                $confirmation['alias'] = $params['type'] . '_confirmation';
            }
        }

        // if in alias mode, set context
        if (!is_null($confirmation['alias']))
        {
		    if (
                (!empty($params['confirmationContext']))
                &&
                (is_string($params['confirmationContext']))
            )
            {
                $confirmation['context'] = $params['confirmationContext'];
            }
            else
            {
                $confirmation['context'] = self::$defaultConfirmationAliasContext;
            }
        }

		unset ($params['confirmationText'], $params['confirmationContext']);

		$params['confirmation'] = $confirmation;
		return $params;
	}

	public static function preload()
	{
		$form = getObject('simpleForm');
		_core_add_js(SHARED_WWW . '3rdpart/jquery/jquery-core.js');
		_core_add_js($form->objectUrl  . 'behaviour.js');
		_core_add_css($form->objectUrl . 'style.css');
	}
}
?>
