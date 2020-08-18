<?
class processing{

//	var $error_cast = 'return';
	// default db link - NULL
	var $db_link = NULL;
	var $error_cast = 'die';
// if true, then dbSE for all processed variables
	var $db_string_escape = false;
	var $db_string_entities = false;
	var $db_quotes = false;
	var $str_trim = false;
	var $optional = NULL;
	var $check_names = array(
		'format_in'=>false,
		'format_out'=>false,
		'quotes'=>NULL,
		'input_type'=>'string',
		'not_empty'=>false,
		'type'=>false,
		'se'=>NULL,
		'entities'=>false,
		'out_name'=>false,
		'array'=>false,
		'optional'=>NULL,
		'array_check'=>false,
		'equal_to'=>NULL,
		'trim'=>NULL,
		'disabled'=>NULL,
		'on_empty'=>NULL,
		'zero_to_null'=>NULL,
		'strip_tags'=>NULL,
		'pre_func'=>NULL,
		'post_func'=>NULL,
		'validateFunction' => NULL,
		'empty_to_null' => NULL
	);

	var $typeCheckMethods = array
    (
		'int'       => 'checkInt',
        'signedInt' => 'checkFullInt',
		'file'      => 'checkFullInt',
		'id'        => 'checkId',
		'date'      => 'checkDate',
		'email'     => 'checkEmail',
		'url'       => 'checkUrl',
	);

	var $typeParseMethods = array(
		'date' => 'parseDate',
	);
    
    
	protected $errors = array();
    
    protected $errorCallback = null; 
      
    protected $multiError    = false;
        
    protected $aliasContext = false;
    
    protected $variables = null;
    
	/*
	* format date string
	*/
	protected function parseDate($value)
    {
		return $this->parseDateString($value, 'Y-m-d');
	}
	/*
	* check date string
	*/
	protected function checkDate($field)
    {
		if ($this->parseDate($field['value']))
		{
			return true;
		}
		else
		{
			$this->reportError($field, 'wrong_date_format');
			return false;
		}
	}
	/*
	* check integer string
	*/
	protected function checkInt($field)
    {
		if (ctype_digit((string) $field['value']))
		{
			return true;
		}
		else
		{
			$this->reportError($field, 'value_is_not_integer');
			return false;
		}
	}

	protected function checkFullInt($field)
    {
        $value = (string) $field['value'];
		if (
            (ctype_digit($value))
            ||
            (preg_match('/^-\d+$/', $value))
        )
		{
			return true;
		}
		else
		{
			$this->reportError($field, 'value_is_not_integer');
			return false;
		}
	}

	/*
	* check integer string
	*/
	protected function checkId($field)
    {
		if (isPositiveInt($field['value']) || ($field['optional'] == true && $field['value'] == ''))
		{
			return true;
		}
		else
		{
			$this->reportError($field, 'value_is_not_positive_integer');
			return false;
		}
	}
	/*
	* check email string
	*/
	protected function checkEmail($field)
	{
	    $len = mb_strlen($field['value']);
        if (
            ($len == 0)
            ||
            ($len <= 254 && filter_var($field['value'], FILTER_VALIDATE_EMAIL))
        )
        {
            return true;
        }
        else
        {
			$this->reportError($field, 'value_is_not_valid_email');
			return false;
		}
	}
    
	// url - (http/https/ftp) + pieliek http ja nevar

	protected $postChecks = array
    (
        
    );

	//default request type - $_POST
	public $request_type = 'p';

	public function __construct( $multiErrorOn = null )
    {
		$this->path = SHARED_PATH . 'classes/processing/';
        if (!is_null($multiErrorOn))
        {
            $this->setMultiError( $multiErrorOn );
        }
	}

	function clearErrors()
    {
		$this->errors = array();
	}
    
    public function addErrors( $processingOrErrors )
    {
        if ($processingOrErrors instanceof processing)
        {
            $processingOrErrors = $processingOrErrors->getErrors();
        }
        
        if (empty($processingOrErrors) || !is_array($processingOrErrors))
        {
            return;
        }

        foreach ($processingOrErrors as $error)
        {
            $this->reportError(  get($error, 'field'), get($error, 'code'), $error);
        }
        
        return;
    }
    

	public function reportError($field, $errorCode, $errorResult = null)
    {

        if (is_string($field))
        {
            $field = array('name' => $field);
        }
        
        if (!empty($this->errorCallback))
        {
            // only object method callbacks are supported
            if (
                (is_array($this->errorCallback))
                &&
                (is_object($this->errorCallback[0]))
            )
            {
                $callbackClass = get_class($this->errorCallback[0]);
                $reflectionMethod = new ReflectionMethod($callbackClass, $this->errorCallback[1]);
                
                $reflectionMethod->invokeArgs($this->errorCallback[0], array( & $field, & $errorCode, & $errorResult, & $this ) );
            }
        }
        
		// log error as leaf error
		if (defined( 'LOG_PROCESSING_ERRORS' ) && LOG_PROCESSING_ERRORS)
		{
			$self = $this; // to put $this in error context
			$params = array
			(
				'message' => 'Processing Error',
				'file' 	  => __FILE__,
				'line' 	  => __LINE__,
				'level'   => E_NOTICE,
				'context' => get_defined_vars(),
			);
			leafError::create( $params );
		}        

		$error = array
        (
			'field'    =>  $field,
			'code'     =>  $errorCode
		);      
        
        $errorMessage = null;
        if ($errorResult && is_array($errorResult))
        {
            $errorMessage = get($errorResult, 'errorMessage');
        }
        
        if (!$errorMessage)
        {
            // custom error message not given, look in field definition for alias in error_alias or error_msg
            
            $alias = get($field, 'error_alias', get($field, 'error_msg')); // error_msg is the old name of the variabels
            if (!empty($alias))
            {
                $error['alias'] = $alias;
            }
        }
        
        
        if ($errorMessage)
        {
            $error['message'] = $errorMessage;
        }
        
        if ($errorResult && is_array($errorResult))
        {
            // merge any other values into error (preserving main values) 
            unset($errorResult['field'], $errorResult['code'], $errorResult['errorCode'], $errorResult['message'], $errorResult['errorMessage'] );
            $error = array_merge($error, $errorResult);
        }
        
        $this->errors[] = $error;

        
        return false;
 	}
    
    public function setErrorCallback( $callback )
    {
        $this->errorCallback = $callback;
        return true;
    }    

	protected function process_array($array, $request_values)
    {
		unset ($array['array']);
        
		$definitions = array();
		$total = sizeof($request_values);
        
		foreach ($request_values as $key => $value)
		{
			$tmp = $array['array_check'];
			$tmp['name'] = $key;
			$tmp['request'] = 'v';
			$definitions[] = $tmp;
		}
        
		return $this->check_values($definitions, $request_values);
	}

	public function addPostCheck($function, $onlyIfValid = false)
    {
        if (is_string($onlyIfValid))
        {
            $onlyIfValid = array($onlyIfValid);
        }
        
        $postCheck = array
        (
            'function'     => $function,
            'onlyIfValid'  => $onlyIfValid
        );    
        
		$this->postChecks[] = $postCheck;
        return;
	}


    public function validateAndOutput( $definitions, $aliasContext = false, $variables = false, $format = null )
    {
        if ( $variables )
        {
            $this->setVariables( $variables );
        }

		$this->error_cast = 'return';
		$this->check_values( $definitions, $variables, false);
        $this->outputResult( $aliasContext, $format );
        return;
    }
    
    public function outputResult( $aliasContext = false, $format = null )
    {
        $this->aliasContext = $aliasContext;
        
        if (empty($format))
        {
            // format not specified. attempt to detect from passed in values
            $input = $this->getVariables();
            if (
                ($input)
                && 
                (!empty($input['validation']))
                && 
                (is_array($input['validation']))
                &&
                (!empty($input['validation']['format']))
            )
            {
                $format = $input['validation']['format'];
            }
        }
        
        switch ($format)
        {
            case 'json':
                $this->outputJson();
                break;
            
            case 'html':
                $this->outputHtml();
                break;
            
            case 'xml':
            default:
                $this->outputXml();
        }
        
        die();
    }
    
    protected function outputXml()
    {
		$template = new leaf_smarty(SHARED_PATH . 'classes/processing/templates');
		require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');
		alias_cache::setContext($template, $this->getAliasContext() );
		alias_cache::setFallbackContext($template, $this->getAliasFallbackContext() );
		$template->register_outputfilter(array('alias_cache', 'fillInAliases'));
		$template->assign('processing', $this);
		header("Content-Type: text/xml");
		echo '<?xml version="1.0" encoding="UTF-8" standalone="no"?' . '>';
		die($template->fetch('response.xml'));
    }
    
    protected function outputJson()
    {
		require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');

        $hasErrors = $this->hasErrors();
        
        $response = array
        (
            'status' => $hasErrors ? 'error' : 'ok'
        );
        
        if ($hasErrors)
        {
            $response['errors'] = array();
            
            $errors = $this->getErrors();

            // prepare each error for output
            foreach ($errors as $error)
            {
                $code = get( $error, 'code' );
                
                if (!empty($error['message']))
                {
                    $message = $error['message'];
                }
                else
                {
                    $aliasCode = get($error, 'alias', $code);
                    
                    $message =  alias_cache::getAlias( $aliasCode, $this->getAliasContext(), false, null, $this->getAliasFallbackContext() ) ;
                }
                
                $field = get($error, 'field');
                
                $responseError = array
                (   
                    'field'   => ($field && is_array($field)) ? get($field, 'name') : null,
                    'code'    => $code,
                    'message' => $message
                );
                
                // merge in other values 
                unset ($error['field'], $error['code'], $error['message']);
                $responseError = array_merge($responseError, $error);
                
                $response['errors'][] = $responseError;
            }
            
        }

        header('Content-Type: application/json');
        die( json_encode( $response ));
    }
    
    protected function outputHtml()
    {
        require_once( SHARED_PATH . 'classes/leaf_error/leaf_error.class.php');
        $leaf_error = new leaf_error;

        $firstError = true;
        $errors = $this->getErrors();
        foreach ($errors as $error)
        {
            $message  = get( $error, 'message' );

            if (!$message)
            {
                // custom error message not found, use alias + field name
                $field = get( $error, 'field' );
                if (is_array($field))
                {
                    require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');
                    $aliasCode = get($error, 'alias');

                    $appendFieldName = false;
                    if (empty($aliasCode))
                    {
                        $appendFieldName = true;
                        $aliasCode = get($error, 'code');
                    }

                    $message = alias_cache::getAlias( $aliasCode, $this->getAliasContext(), false, null, $this->getAliasFallbackContext() );

                    if ($appendFieldName)
                    {
                        $message .= ' (' . get( $field, 'name') . ')';
                    }

                }
            }

            $params = array
            (
                'msg'    => $message
            );

            if ($firstError)
            {
                $params['header'] = 'Input processing error';
            }

            $leaf_error->addMessage( $params );
            $firstError = false;
        }

        $leaf_error->display();     
        die();
    }
    

	protected function parseDateString($inputDate, $outputFormat = NULL){
		$dateParts = preg_split("/[\D,]+/", $inputDate);
		$mothPreg  = '/^(0?[1-9]|1[012])$/';
		$dayPreg   = '/^(0[1-9]|[12][0-9]|3[01])$/';
		$yearPreg  = '/^([123456789][[:digit:]]{3})$/';
		// parse 1 part date (year)
		if (sizeof($dateParts) == 1)
		{

		}
		// parse 2 part date (year, month)
		if (sizeof($dateParts) == 2)
		{

		}
		// parse 3 part date (year, month, date)
		if(sizeof($dateParts) == 3)
		{
			$year = NULL;
			$month = NULL;
			$day = NULL;
			// year is 1st
			if(preg_match($yearPreg, $dateParts[0]))
			{
				$year = $dateParts[0];
				if(preg_match($mothPreg, $dateParts[1]))
				{
					$month = $dateParts[1];
					if(preg_match($dayPreg, $dateParts[2]))
					{
						$day = $dateParts[2];
					}
				}
			}
			// year is 3rd
			elseif(preg_match($yearPreg, $dateParts[2]))
			{
				$year = $dateParts[2];
				if(preg_match($mothPreg, $dateParts[1]))
				{
					$month = $dateParts[1];
					if(preg_match($dayPreg, $dateParts[0]))
					{
						$day = $dateParts[0];
					}
				}
			}
			if($year && $month && $day)
			{
				$timestamp = strtotime("$year-$month-$day");
			}
		}
		if(!empty($timestamp))
		{
			if($outputFormat)
			{
				return date($outputFormat, $timestamp);
			}
			else
			{
				return true;
			}
		}
		else
		{
			return false;
		}
	}


	protected function _check_values()
    {
        
		foreach ($this->processing_fields as $key => $field)
		{
            if (!$this->isMultiErrorOn() && $this->hasErrors())
            {
                // in single-error mode return after first detected error
                return false;
            }
                
			// detect arrays
			if ($field['array'] == true)
			{
				$this->processing_fields[$key]['value'] = $this->process_array( $field, $field['value'] );
			}
            
			// non-existing value
			if (
                ($field['value'] === NULL)
                && 
                ($field['zero_to_null'] === NULL)
                && 
                ($field['empty_to_null'] === NULL)
            )
			{
                $this->reportError($field, 'missing_value');
                continue;
			}
            
			// skip if empty value and empty_to_null
			if ($field['empty_to_null'] === true && $field['value'] == '')
			{
				continue;
			}
            
			// not_empty cannot be empty
			if ($field['not_empty'] && $field['value'] === '')
			{
                $this->reportError($field, 'empty_value');
                continue;
			}

            
			// type check
			if (isset($this->typeCheckMethods[$field['type']]))
			{
				$checkMethod = $this->typeCheckMethods[$field['type']];
				if (!$this->$checkMethod($field))
				{
                    // type check methods will call reportError if needed
                    continue;
				}
			}
            
			// check by 'format_in'
			if ($field['format_in'] && !preg_match($field['format_in'], $field['value']))
			{
				$this->reportError($field, 'wrong_format');
                continue;
			}
            
			// check by `equal_to`
			if($field['equal_to'] && strcmp($field['equal_to'], $field['value']) != 0)
			{
				$this->reportError($field, 'wrong_value');
                continue;
			}
            
			// validate function
			if ($this->processing_fields[$key]['validateFunction'])
			{
				$validateFunction = $this->processing_fields[$key]['validateFunction'];
				if (is_array(get($validateFunction, 0)))
				{
					$callback = $validateFunction[0];
					$params = array_slice($validateFunction, 1);
					array_push($params, $this->processing_fields[$key]['value']);
				}
				else
				{
					$callback = $validateFunction;
					$params = array 
                    (
						$this->processing_fields[$key]['value'], 
					);
				}
				
				if (($return = call_user_func_array($callback, $params)) !== true)
				{
                    if (
                        (is_array($return))
                        &&
                        (
                            (isset($return['field']))
                            || 
                            (isset($return['errorCode']))
                        )
                    )
                    {
                        // single error returned. convert to list
                        $return = array($return);
                    }
                    
                    if (!$this->isMultiErrorOn())
                    {
                        // use only first error
                        $return = array_slice( $return, 0, 1 );
                    }
                    
                    foreach ($return as $returnErr)
                    {
                        $errorField = get( $returnErr, 'field', $field);
                        $errorCode  = get( $returnErr, 'errorCode', 'wrong_format' );

                        $this->reportError($errorField, $errorCode, $returnErr);
                    }
                    continue;
				}
			}
		}
     

     
		// run postChecks
		if (!empty($this->postChecks))
		{
			$values = $this->_return_simple_array();
			foreach ($this->postChecks as $postCheck)
			{
                if (!$this->isMultiErrorOn() && $this->hasErrors())
                {
                    // in single-error mode return after first detected error
                    return false;
                }
                
                $onlyIfValid = $postCheck['onlyIfValid'];

                if ($this->hasErrors())
                {
                    if ($onlyIfValid === true)
                    {
                        continue; // skip this postcheck
                    }
                    
                    if (is_array($onlyIfValid))
                    {
                        
                        $allFieldsWithErrors = $this->getFieldsWithErrors();
                        $importantFieldsWithErrors = array_intersect( $onlyIfValid, $allFieldsWithErrors );
                        
                        if (!empty($importantFieldsWithErrors))
                        {
                            // some of $onlyIfValid fields have errors. skip this postcheck
                            continue;
                        }
                    }
                }
        
                $return = call_user_func($postCheck['function'], $values);
				if ($return !== true)
				{
                    if (isset($return['errorCode']))
                    {
                        // single error returned
                        $errors = array( $return );
                    }
                    else
                    {
                        // multiple errors returned
                        $errors = $return;
                    }
                    
                    foreach ($errors as $error)
                    {
                        $errorField = get( $error, 'field', null );
                        $errorCode  = get( $error, 'errorCode' );
                    
                        $this->reportError($errorField, $errorCode, $error);
                        
                        if (!$this->isMultiErrorOn())
                        {                        
                            return false;
                        }
                    }
				}
			}

		}
        
        return !$this->hasErrors();
	}

	function _return_simple_array()
    {
		$data = array();
        
		foreach ($this->processing_fields as $key => $field)
		{
			//change out name
			if ($this->processing_fields[$key]['out_name'])
			{
				$data[$this->processing_fields[$key]['out_name']] = $this->processing_fields[$key]['value'];
			}
			else
			{
				$data[$this->processing_fields[$key]['name']] = $this->processing_fields[$key]['value'];
			}
		}
        
		return $data;
	}


	protected function _process_values()
    {
		foreach($this->processing_fields as $key => $field)
		{
			//pre function
			if($this->processing_fields[$key]['pre_func'] !== NULL)
			{
				$this->processing_fields[$key]['value'] =  call_user_func($this->processing_fields[$key]['pre_func'], $this->processing_fields[$key]['value']);
			}
			//strip_tags
			if($this->processing_fields[$key]['strip_tags'] !== NULL)
			{
				$this->processing_fields[$key]['value'] =  strip_tags($this->processing_fields[$key]['value'], $this->processing_fields[$key]['strip_tags']);
			}
			//trim
			if((($this->str_trim && $this->processing_fields[$key]['trim'] === NULL) || $this->processing_fields[$key]['trim']) && !$this->processing_fields[$key]['array'])
			{
				$this->processing_fields[$key]['value'] =  trim($this->processing_fields[$key]['value']);
			}
			//run type parse method
			if(isset($this->typeParseMethods[$this->processing_fields[$key]['type']]))
			{
				$parseMethod = $this->typeParseMethods[$this->processing_fields[$key]['type']];
				$this->processing_fields[$key]['value'] =  $this->$parseMethod($this->processing_fields[$key]['value']);
			}
			//set on_empty case
			if($this->processing_fields[$key]['on_empty'] !== NULL && $this->processing_fields[$key]['value'] == '')
			{
				$this->processing_fields[$key]['value'] =  $this->processing_fields[$key]['on_empty'];
			}
			//convert to 'format_out'
			if($this->processing_fields[$key]['format_in'] && $this->processing_fields[$key]['format_out'])
			{
				$this->processing_fields[$key]['value'] = preg_replace($this->processing_fields[$key]['format_in'], $this->processing_fields[$key]['format_out'], $this->processing_fields[$key]['value']);
			}
			//htmlspecialchars
			if(($this->db_string_entities || $this->processing_fields[$key]['entities']) && !$this->processing_fields[$key]['array'])
			{
				$this->processing_fields[$key]['value'] = htmlspecialchars($this->processing_fields[$key]['value'], ENT_QUOTES);
			}
			//empty_to_null
			if($this->processing_fields[$key]['empty_to_null'])
			{
				if($this->processing_fields[$key]['value'] == '' && !is_int($this->processing_fields[$key]['value']))
				{
					$this->processing_fields[$key]['value'] = NULL;
				}
			}
			//zero_to_null
			if($this->processing_fields[$key]['zero_to_null'])
			{
				if($this->processing_fields[$key]['value'] == 0)
				{
					$this->processing_fields[$key]['value'] = NULL;
				}
			}
			//db_string_escape
			if((($this->db_string_escape && $this->processing_fields[$key]['se'] === NULL) || $this->processing_fields[$key]['se']) && !$this->processing_fields[$key]['array'])
			{
				$this->processing_fields[$key]['value'] = dbSE($this->processing_fields[$key]['value']);
			}
			//add quotes
			if((($this->db_quotes && $this->processing_fields[$key]['quotes'] === NULL) || $this->processing_fields[$key]['quotes']) && !$this->processing_fields[$key]['array'])
			{
				$this->processing_fields[$key]['value'] = '"' . $this->processing_fields[$key]['value'] . '"';
			}
			//post function
			if($this->processing_fields[$key]['post_func'] !== NULL)
			{
				$this->processing_fields[$key]['value'] = call_user_func($this->processing_fields[$key]['post_func'], $this->processing_fields[$key]['value']);
			}
		}
	}

	public function setVariables($variables)
    {
		$this->request_type = 'v';
		$this->variables = $variables;
	}
    
    public function getVariables() 
    {
        $variables = null;
        switch ($this->request_type)
        {
            case 'v':
                $variables = $this->variables;
                break;
            case 'p':
                $variables = $_POST;
                break;
            case 'g':
                $variables = $_GET;
                break;
            case 'f':
                $variables = $_FILES;
                break;            
        }
        if (!$variables)
        {
            $variables = array();
        }
        return $variables;
    }

	protected function _prepare_values($fields_definition, $variables = false, $return = false)
    {
		$this->processing_fields = $fields_definition;
        
		if (!empty($this->variables))
		{
			$variables = $this->variables;
		}
		/*
		request:
				p - POST
				g - GET
				f - FILE
				v - variable
		*/

		foreach($this->processing_fields as $key => $field)
		{
			//parse string
			if(is_string($field))
			{
				$this->processing_fields[$key] = array('name' => $field);
			}
			//check for empty array
			else if(is_array($field) && empty($field['name']))
			{
				if(!isPositiveInt($key))
				{
					$this->processing_fields[$key]['name'] = $key;
				}
				else
				{
					 trigger_error('wrong variable definition', E_USER_ERROR);
				}
			}

			if(!isset($this->processing_fields[$key]['request']))
			{
				$this->processing_fields[$key]['request'] = $this->request_type;
			}
			//get value from $_POST
			if($this->processing_fields[$key]['request'] == 'p')
			{
				if(isset($_POST[$this->processing_fields[$key]['name']]))
				{
					$this->processing_fields[$key]['value'] = $_POST[$this->processing_fields[$key]['name']];
				}
				else
				{
					$this->processing_fields[$key]['value'] = NULL;
				}
			}
			//get value from $variables
			else if($this->processing_fields[$key]['request'] == 'v' && is_array($variables))
			{
				if(isset($variables[$this->processing_fields[$key]['name']]))
				{
					$this->processing_fields[$key]['value'] = $variables[$this->processing_fields[$key]['name']];
				}
				else
				{
					$this->processing_fields[$key]['value'] = NULL;
				}
			}
			//get value from $_GET
			else
			{
				if(isset($_GET[$this->processing_fields[$key]['name']]))
				{
					$this->processing_fields[$key]['value'] = $_GET[$this->processing_fields[$key]['name']];
				}
				else
				{
					$this->processing_fields[$key]['value'] = NULL;
				}
			}

		//values check - part 1
			//detect empty check names
			foreach($this->check_names as $check_name => $check_default)
			{
				if(!isset($this->processing_fields[$key][$check_name]))
				{
					$this->processing_fields[$key][$check_name] = $check_default;
				}
			}

			//parse unchecked checkbox type
			if($this->processing_fields[$key]['input_type'] == 'checkbox' && $this->processing_fields[$key]['value'] === NULL)
			{
				if($this->processing_fields[$key]['type'] == 'int')
				{
					$this->processing_fields[$key]['value'] = 0;
				}
				else
				{
					$this->processing_fields[$key]['value'] = '';
				}
			}
			if(
				$this->processing_fields[$key]['disabled']
				||
				(
					(
						(
							$this->optional === true
							&&
							$this->processing_fields[$key]['optional'] === NULL
						)
						||
						$this->processing_fields[$key]['optional'] === true
					)
					&&
					$this->processing_fields[$key]['value'] === NULL
				)
			)
			{
				unset($this->processing_fields[$key]);
			}
		}
		if($return)
		{
			return $this->processing_fields;
		}
	}

	public function check_values($fields_definition, $variables = false, $process_values = true)
    {
		$this->_prepare_values($fields_definition, $variables);

        $validationPassed = $this->_check_values();  // $validaionPassed is always boolean
        
        if ($validationPassed !== true)
		{
            switch ($this->error_cast)
            {
                case 'return':
                    return false;
                    
                case 'die':
                    $this->outputHtml();
                    break;

                default:
                    
                    trigger_error('Unsupported error_cast value: ' . $this->error_cast, E_USER_ERROR);                    
                    die();
            }
		}
        
        if (!$process_values)
        {
            return true;
        }
        
        $this->_process_values();
        return $this->_return_simple_array();
	}

	public function db_create_entry($table, $fieldsOrRows, $triger_sql = false, $triger_ok = false, $escape = false, $add_quotes = false, $quote_fields = array()){
	    $fieldsOrRowsErrorMessage = 'Processing error: invalid or empty $fieldsOrRows argument. ';
	    $fieldsOrRowsErrorLevel = E_USER_ERROR;
		if($triger_sql)
		{
			$result = dbGetOne($triger_sql, false, $this->db_link);
			if($result != $triger_ok)
			{
				return;
			}
		}

		if (
            (!is_array($fieldsOrRows))
            ||
            (empty($fieldsOrRows))
        )
		{
            trigger_error($fieldsOrRowsErrorMessage, $fieldsOrRowsErrorLevel);
		    return false;
		}

		if (is_array(current($fieldsOrRows)))
		{
		    // multiple rows
		    $rows = $fieldsOrRows;
            $exampleRow = current($fieldsOrRows);
            if (
                (!is_array($exampleRow))
                ||
                (empty($exampleRow))
            )
            {
                trigger_error($fieldsOrRowsErrorMessage, $fieldsOrRowsErrorLevel);
                return false;
            }
		}
		else
		{
            // one row
		    $exampleRow = $fieldsOrRows;
            $rows = array( $fieldsOrRows );

		}
        $keys = array_keys($exampleRow);

        // concatenate row values
        foreach ($rows as $key => $row)
        {
            if (
                (!is_array($row))
                ||
                (empty($row))
            )
            {
                trigger_error($fieldsOrRowsErrorMessage, $fieldsOrRowsErrorLevel);
                return false;
            }

            // escape / quote
    		foreach ($row as $fieldKey => $fieldValue)
    		{
				//check for array and serialize it
				if(is_array($fieldValue))
				{
					$row[$fieldKey] = serialize($fieldValue);
				}
				// check for NULL and maintain $quote_fields array
				if($row[$fieldKey] === NULL)
				{
					$row[$fieldKey] = 'NULL';
					if(
						$add_quotes && !in_array($fieldKey, $quote_fields)
					)
					{
						$quote_fields[] = $fieldKey;
					}
					elseif(
						!$add_quotes && in_array($fieldKey, $quote_fields)
					)
					{
						unset($quote_fields[array_search($fieldKey, $quote_fields)]);
					}
				}
				// add escape
                if ($escape)
                {
                    $row[$fieldKey] = dbSE($row[$fieldKey]);
                }
				//add quotes
                if ($add_quotes xor in_array($fieldKey, $quote_fields))
                {
                    $row[$fieldKey] = '"' . $row[$fieldKey] . '"';
                }

    		}

            $rows[$key] = implode(', ', $row);
        }

		$q = '
		INSERT
		INTO
			`' . $table . '`
		(
			`' . implode('`,`',$keys) . '`
		)
		VALUES
		(
			' . implode('), (', $rows) . '
		)
		';
		dbQuery($q, $this->db_link);
		return dbInsertId($this->db_link);
	}

	public function db_replace_entry($table,$fieldsOrRows,$triger_sql = false,$triger_ok = false, $escape = false, $add_quotes = false, $quote_fields = array()){
	    $fieldsOrRowsErrorMessage = 'Processing error: invalid or empty $fieldsOrRows argument. ';
	    $fieldsOrRowsErrorLevel = E_USER_ERROR;
		if($triger_sql)
		{
			$result = dbGetOne($triger_sql, false, $this->db_link);
			if($result != $triger_ok)
			{
				return;
			}
		}

		if (
            (!is_array($fieldsOrRows))
            ||
            (empty($fieldsOrRows))
        )
		{
            trigger_error($fieldsOrRowsErrorMessage, $fieldsOrRowsErrorLevel);
		    return false;
		}

		if (is_array(current($fieldsOrRows)))
		{
		    // multiple rows
		    $rows = $fieldsOrRows;
            $exampleRow = current($fieldsOrRows);
            if (
                (!is_array($exampleRow))
                ||
                (empty($exampleRow))
            )
            {
                trigger_error($fieldsOrRowsErrorMessage, $fieldsOrRowsErrorLevel);
                return false;
            }
		}
		else
		{
            // one row
		    $exampleRow = $fieldsOrRows;
            $rows = array( $fieldsOrRows );

		}
        $keys = array_keys($exampleRow);

        // concatenate row values
        foreach ($rows as $key => $row)
        {
            if (
                (!is_array($row))
                ||
                (empty($row))
            )
            {
                trigger_error($fieldsOrRowsErrorMessage, $fieldsOrRowsErrorLevel);
                return false;
            }

            // escape / quote
    		foreach ($row as $fieldKey => $fieldValue)
    		{
				//check for array and serialize it
				if(is_array($fieldValue))
				{
					$row[$fieldKey] = serialize($fieldValue);
				}
				// check for NULL and maintain $quote_fields array
				if($row[$fieldKey] === NULL)
				{
					$row[$fieldKey] = 'NULL';
					if(
						$add_quotes && !in_array($fieldKey, $quote_fields)
					)
					{
						$quote_fields[] = $fieldKey;
					}
					elseif(
						!$add_quotes && in_array($fieldKey, $quote_fields)
					)
					{
						unset($quote_fields[array_search($fieldKey, $quote_fields)]);
					}
				}
				// add escape
                if ($escape)
                {
                    $row[$fieldKey] = dbSE($row[$fieldKey]);
                }
				//add quotes
                if ($add_quotes xor in_array($fieldKey, $quote_fields))
                {
                    $row[$fieldKey] = '"' . $row[$fieldKey] . '"';
                }

    		}

            $rows[$key] = implode(', ', $row);
        }

		$q = '
		REPLACE
		INTO
			`' . $table . '`
		(
			`' . implode('`,`',$keys) . '`
		)
		VALUES
		(
			' . implode('), (', $rows) . '
		)
		';
		return dbQuery($q, $this->db_link);
	}

	public function db_update_entry($table, $fields, $where_q, $escape = true, $add_quotes = true, $quote_fields = array()){
		//old way!
		if(is_array($table))
		{
			$tmp = $fields;
			$fields = $table;
			$table = $tmp;
		}
		//check for variables
		if(empty($fields))
		{
			return false;
		}
		$q = '';
	 	foreach ($fields as $fieldKey => $fieldValue)
		{
			//check for array and serialize it
			if(is_array($fields[$fieldKey]))
			{
				$fields[$fieldKey] = serialize($fields[$fieldKey]);
			}
			//check for NULL and maintain $quote_fields array
			if($fields[$fieldKey] === NULL)
			{
				$fields[$fieldKey] = 'NULL';
				if(
					$add_quotes && !in_array($fieldKey, $quote_fields)
				)
				{
					$quote_fields[] = $fieldKey;
				}
				elseif(
					!$add_quotes && in_array($fieldKey, $quote_fields)
				)
				{
					unset($quote_fields[array_search($fieldKey, $quote_fields)]);
				}
			}
			if($escape)
			{
				$fields[$fieldKey] = dbSE($fields[$fieldKey]);
			}
			if(
				(
					$add_quotes
					&&
					!in_array($fieldKey, $quote_fields)
				)
				||
				(
					!$add_quotes
					&&
					in_array($fieldKey, $quote_fields))
				)
			{
				$fields[$fieldKey] = '"' . $fields[$fieldKey] . '"';
			}
			$q .= ($q ? ', ' : '') . '`' . $fieldKey . '`=' . $fields[$fieldKey];
		}
		//smart variable
		if(is_array($where_q))
		{
			 $where_q = '`' . key($where_q) . '` = "' . dbSE(current($where_q)) . '"';
		}
		$q = 'UPDATE `' . $table . '` SET ' . $q . ' WHERE ' . $where_q;
		dbQuery($q, $this->db_link);
	}

	public function db_delete_entry($table, $where_q){
		//smart variable
		if(is_array($where_q))
		{
			 $where_q = '`' . key($where_q) . '` = "' . dbSE(current($where_q)) . '"';
		}
		$q = 'DELETE FROM `' . $table . '` WHERE ' . $where_q;
		dbQuery($q, $this->db_link);
	}

   
	public function getValidOrDie( $variables, $definitions )
	{
		$this->error_cast = 'return';
		$this->setVariables( $variables );
		$values = $this->check_values( $definitions );
		
		if ( $this->hasErrors() )
		{
			if (!empty($variables['getValidationXml']))
			{
                $this->outputResult();
			}
            else
            {
                $this->outputHtml();
            }
		}
        
		return $values;
	}
    
    
    public function setMultiError( $on = true )
    {
        $this->multiError = (bool) $on;
    }
    
    public function isMultiErrorOn()
    {
        return $this->multiError;
    }
    
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    public function getFieldsWithErrors()
    {
        $fieldsWithErrors = array();
        foreach ($this->errors as $error)
        {
            $fieldsWithErrors[] = $error['field']['name'];
        }
        return array_unique( $fieldsWithErrors );
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
    
    public function setAliasContext( $context )
    {
        $this->aliasContext = $context;
        return;
    }
    
    protected function getAliasContext()
    {
        $context = $this->aliasContext;
        if (!is_string($context))
        {
            $context = $this->isMultiErrorOn() ? 'validation:multi' : 'validation';
        }
        return $context;
    }
    
    protected function getAliasFallbackContext()
    {
        $mainContext = $this->getAliasContext();
        
        $fallbackContext = 'validation';
        if ($this->isMultiErrorOn() && ($mainContext != 'validation:multi'))
        {
            $fallbackContext = 'validation:multi';
        }
        else
        {
            $fallbackContext = 'validation';
        }

        return $fallbackContext;
    }
    

    // compatibility stuff for older code
    
    
    // provide access to some no longer existing properties
    public function __get( $prop )
    {
        switch ($prop)
        {
            case 'errorFields':            
            case 'errorCode':
                
                // return data in old format about first error
                if (!$this->hasErrors())
                {
                    return null;
                }                
                $firstError = reset($this->errors);
                if (!$firstError)
                {
                    return null;
                }
                if ($prop == 'errorFields')
                {
                    return array( get($firstError, 'field') );
                }
                return get( $firstError, 'code' );
                break;
                
            default:
                
                $trace = debug_backtrace();
                trigger_error
                (
                    'Undefined property via __get(): ' . $prop .
                    ' in ' . $trace[0]['file'] .
                    ' on line ' . $trace[0]['line'],
                    E_USER_NOTICE
                );
                return null;
        }
    }
    
    // deprecated methods
	public function getXml($aliasContext = 'validation')
    {
        return $this->outputResult( $aliasContext );
	}
    
	public function getValidationXml($definitions, $aliasContext = 'validation', $variables = false)
    {
        return $this->validateAndOutput($definitions, $aliasContext, $variables);
	}
        

}