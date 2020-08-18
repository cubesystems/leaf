/**
 * TODO: loading events
 * TODO: instant client-side validation
 */
var Validation = function( nodeOrSelector, params )
{
	// self
	var v = this;
    
	// check dependencies
	v.checkDependencies();

	// form
	v.form = nodeOrSelector;
    
	if (!(v.form instanceof jQuery))
	{
		v.form = jQuery( v.form );
	}
    
    if (v.form.length > 1)
    {
        v.form = v.form.first();
        v.logError('Multiple forms are not supported for single validation script instance.');
    }

    
    v.clickedButton = null;
    
	// default config
	// v.isInstant  = false; // TODO
	// v.onEvents   = null;  // TODO
    
	v.submitOnOk    = true;
	v.ui            = true;
    
	// instance config
	if( typeof params == 'object' )
	{
		if (params.submitOnOk === false)
		{
            v.logError('submitOnOk is deprecated. Use event.preventDefault() in event handlers to prevent autosubmit.');            
			v.submitOnOk = false; 
		}
        
		if (params.ui == false)
		{
			v.ui = false;
		}
	}
	
	/* attach events */
    v.form.delegate('input[type="submit"], input[type="image"], button', 'click', function(event) 
    {
		var target = jQuery( event.target );
		// register only submit buttons - buttons with type="submit" or without type attribute at all
		// direct target[0].type property is used because of inconsistent attr() method return values
		// between older and newer jQuery versions
        if( target.is( 'button' ) && target[0].type != 'submit' )
		{
			return;
		}
		v.clickedButton = target;
    });
    
	// submit
	v.form.submit(function( event )
	{
		event.preventDefault();
		v.validateForm();
	});

    
    jQuery( document ).bind( 'ok error fail', function( event, targetValidation, error )
    {
		if (targetValidation !== v || event.isDefaultPrevented() || !v.form[0])
		{
			return;
		}        
     
        switch (event.type)
        {
            case 'ok':      // validation passed
                if (v.submitOnOk)
                {
                    v.submitForm();
                }
                break;
                
            case 'error':   // validation error
                if (v.ui)
                {
                    alert( error.message );
                }
                v.clickedButton = null;
                break;
                
            case 'fail':  	// fail (internal validation failure, not a user error)
                
                v.submitForm();
                break;
        }
    });


}

Validation.prototype.logError = function( msg )
{
    if (!('console' in window))
    {
        return;
    }
    
    var f = ('error' in console) ? 'error' : 'log';
    console[f](msg);
    
}

// dependencies
Validation.prototype.checkDependencies = function()
{
	// jQuery
	if( window.jQuery === undefined )
	{
        v.logError('Validation requires jQuery.');
		return false;
	}
	return true;
}

Validation.prototype.validateForm = function()
{
	var v = this;
	
	var data = v.form.serializeArray();
    var files = v.form.find('input[type=file]');
 	files.each(function()  
 	{
        data.push({ name: jQuery( this ).attr( 'name' ), value: jQuery( this ).val() });  
 	});
    if ((v.clickedButton) && (v.clickedButton.length > 0) && v.clickedButton.attr('name'))
    {
        data.push({ name: v.clickedButton.attr('name'), value: v.clickedButton.val() });
    }
    data.push({ name: 'getValidationXml', value: 1 });
    data.push({ name: 'validation[format]', value: 'json' });
    
	jQuery.ajax
	({
		url:  v.form.attr( 'action' ),
		type: v.form.attr( 'method' ),
		data: data,
		dataType: 'json',
        cache : false,
        converters: {
            "* text": window.String, 
            "text json": jQuery.parseJSON, 
            "text xml": jQuery.parseXML,
            "xml json" : function( xml )   // convert old xml response to the new json format
            {
                var json = null;
                if (xml)
                {
                    json = {};
                    
                    xml = jQuery( xml );
                    json.status = xml.find('response').attr('status');
                    
                    var errors = [];
                    xml.find( 'response > error' ).each(function()
                    {
                        var errorNode = jQuery(this);
                        errors.push(
                        {
                            field   : errorNode.children( 'focus' ).text(),
                            code    : errorNode.children( 'code' ).text(),
                            message : errorNode.children( 'message'  ).text()
                            
                        });
                    });    
                    
                    if (errors.length > 0)
                    {
                        json.errors = errors;
                    }
                }
                return json;
            }
        },
		success: function( response, textStatus, jqXHR )
		{
            var result = ((response) && (typeof response.status != 'undefined')) ? response.status : null;
            
            var errors = [];
            
			switch (result)
			{
				case 'ok':
					v.form.trigger( 'ok', [ v ] );
                break;
                
				case 'error':
                    
                    if (typeof response.errors != 'undefined')
                    {
                        var keyMap = 
                        {
                            'field' : 'fieldName',
                            'code'  : 'errorCode'
                        }
                        
                        jQuery.each( response.errors, function( index, error )
                        {
                            var errObj = { };
                            
                            // copy all other values to error object
                            for (var key in error)
                            {
                                var localKey = (typeof keyMap[key] != 'undefined') ? keyMap[key] : key;
                                errObj[localKey] = error[key];
                            }
							
                            errors.push( errObj );
                            
                        });
                    }
                    else
                    {
                        errors.push(
                        {
                            message   : response.message,
                            errorCode : response.errorCode,
                            fieldName : response.errorFields[0].name
                        });
                    }
                    
				break;
				default:
                    
                    v.form.trigger( 'fail', [ v ] );
                    
				break;
			}
            
            v.firstFocusCalled = false;
            
            jQuery.each( errors, function(index, error) 
            {
                var field = null;
                
                var eventTarget = null;
                v.focus = (!v.firstFocusCalled); // focus only the first error field
                
                if (error.fieldName != '__form__') 
                {
                    field = v.form.find( '*[name="' + error.fieldName + '"]' ).first();
                }
                
                if (field && field.length > 0)
                {
                    eventTarget = field;
                }
                else
                {
                    eventTarget = v.form;
                    v.focus = false;
                }
                
                eventTarget.trigger( 'beforeError', [ v, error ] );
                
                if (v.focus)
                {
                    eventTarget.focus();
                    
                    v.firstFocusCalled = true;
                }
                eventTarget.trigger( 'error', [ v, error ] );
            });
		},
		complete: function( jqXHR, textStatus )
		{
			if( textStatus !== 'success' )
			{
                v.form.trigger( 'fail', [ v ] );
			}
		}
	});
}

Validation.prototype.submitForm = function()
{
	var v = this;
    
    // append clicked button as a hidden field 
    // because no button value will be sent when submitting the form via .submit()
    if ((v.clickedButton) && (v.clickedButton.length > 0) && v.clickedButton.attr('name'))
    {
        var input = jQuery('<input type="hidden" />');
        input.attr('name',  v.clickedButton.attr('name'));
        input.val( v.clickedButton.val() );
        input.appendTo(v.form);
    }
    v.form[0].submit();
}
