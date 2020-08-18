/*

    attaches onsubmit form validation

    usage:
    1) each form needs to have its class name set to "validatable"
    2) include this js file in the page

    no additional scripting is needed.
    the script will scan the page and attach onsubmit event handlers to FORMs where necessary.

*/

/*
    attach the init call to the main onload event
    preserving any existing handlers already present
*/
var jsValidation;
if (typeof jQuery == 'undefined') { 
	var js_validation_old_onload = window.onload;
	window.onload = function()
	{
	    if (typeof js_validation_old_onload == 'function')
	    {
	         js_validation_old_onload();
	    }
	
	    jsValidation = new jsValidationScript();
	    jsValidation.init();
	}
} else {
	$(document).ready(function() {
	    jsValidation = new jsValidationScript();
	    jsValidation.init();
	});			
}


/* class definition */
function jsValidationScript()
{
    this.containerClassName = 'validatable';
    this.form = null;
    this.fieldIds = new Object;
}

jsValidationScript.prototype.init = function()
{
    // get all forms in document, look for needed class name, attach handlers if found
    var forms = document.getElementsByTagName('form');
    var className = ' ' + this.containerClassName + ' ';
    for (var i = 0, item, position; item = forms[i]; i++) {
        if (!item.className)
        {
            continue;
        }

        position = ' ' + item.className + ' ';
        if (position.indexOf( className ) == -1)
        {
            continue;
        }

        this.attachHandlers( item );

    }
}

jsValidationScript.prototype.attachHandlers = function (element)
{
    var self = this;

    element.exOnSubmit = element.onsubmit;

    element.onsubmit = function()
    {
        if (typeof this.exOnSubmit == 'function')
        {
             var response = this.exOnSubmit();
             if (response == false)
             {
                 return false;
             }
        }

        return self.validateForm(this);
    }

	// attach onclick to submit button elements
	var buttons = element.getElementsByTagName('button');
	var inputs = element.getElementsByTagName('input');

	var submitElements = [];

	for (i = 0; i < buttons.length; i++)
	{
	    submitElements[submitElements.length] = buttons[i];
	}

	for (i = 0; i < inputs.length; i++)
	{
	    var type = inputs[i].type.toLowerCase();
	    if (
	       (type != 'submit')
	       &&
	       (type != 'image')
        )
	    {
	        continue;
	    }
	    submitElements[submitElements.length] = inputs[i];
	}


	for(i = 0; i < submitElements.length; i++)
	{
		if(!submitElements[i].onclick)
		{
			submitElements[i].onclick = function()
			{
				this.form.buttonPresedName = this.name;
				this.form.buttonPresedValue = this.value;
			}
		}
	}
    element = null;
    return;
}

jsValidationScript.prototype.validateForm = function (form)
{

    if (
        (!form.tagName)
        ||
        (form.tagName.toLowerCase() != 'form')
    )
    {

        return false;
    }
    this.form = form;


    var fields = this.collectFields(this.form);
    var fieldsData = this.getFieldsData(fields);


    var postData = this.preparePostData(fieldsData);



    var formAction = this.form.getAttributeNode("action").value;
    if (!formAction)
    {
        formAction = document.location.href;
    }

    var response = this.getXmlResponse(formAction, postData);


    var parsedResponse = this.parseXmlResponse(response);

    if (
        (!parsedResponse) // error parsing xml response, allow to submit the form, let the serverside validation handle this
        ||
        (parsedResponse.status == 'ok')
    )
    {
        return true;
    }


    var error;
    if (
        (parsedResponse.errors)
        &&
        (parsedResponse.errors.length)
    )
    {

        for (var i=0; i < parsedResponse.errors.length; i++)
        {
            error = parsedResponse.errors[i];
            this.throwErrorMessage( error );
            if (error.focus)
            {
                var focusElement = this.getFieldByName(error.focus)
                if (
                    (focusElement)
                    &&
                    (typeof focusElement.focus == 'function' || typeof focusElement.focus == 'object')
                )
                {
                    try
                    {
                        focusElement.focus();
                    }
                    catch(e)
                    {
                    }
                }
            }
        }

    }
    return false;
}

jsValidationScript.prototype.collectFields = function ( form )
{
    var elementTypes = ['input', 'select', 'textarea'];
    var fields = [];
    var field;
    for (var i = 0; i < elementTypes.length; i++)
    {
        var tmpFields = form.getElementsByTagName(elementTypes[i]);
        for (var j=0; j < tmpFields.length; j++ )
        {
            field = tmpFields[j];
            var fieldName = field.name;
            var fieldId = field.id;
            if (fieldName && fieldId)
            {
                this.fieldIds[fieldName] = fieldId;
            }

            fields[fields.length] = field;

        }
    }
    return fields;
}

jsValidationScript.prototype.getFieldByName = function (fieldName)
{
    if (this.fieldIds[fieldName])
    {
        return document.getElementById(this.fieldIds[fieldName]);
    }
    
    if (typeof jQuery != 'undefined')
    {
        // attempt to locate by name
        var fields = jQuery(this.form).find('input, select, textarea').filter('*[name="' + fieldName + '"]');
        if (fields.size() > 0)
        {
            return fields[0];
        }
    }
    
    
    return null;
}


jsValidationScript.prototype.getFieldsData = function (fields)
{
    // process fields
    var fieldsData = [];

    for (var i=0; i<fields.length; i++)
    {
        var field = fields[i];
        var fieldData = null;

        var tagName = field.tagName.toLowerCase();
        var type = null;
        if (tagName == 'input')
        {
            type = field.type.toLowerCase();
        }

        // skip disabled fields
        if (field.disabled)
        {
            continue;
        }

        // default straightforward cases
        if (
            (tagName == 'select')
            ||
            (tagName == 'textarea')
            ||
            (
                (tagName == 'input')
                &&
                (
                    (type == 'text')
                    ||
                    (type == 'password')
                    ||
                    (type == 'file')
                    ||
                    (type == 'hidden')
                    ||
                    (type == 'email')
                    ||
                    (type == 'url')
                    ||
                    (type == 'number')
                )
            )
        )
        {
            // assign name and value
            fieldData = {
                'name' : field.name,
                'value' : field.value
            }
        }
        else if (tagName == 'input')
        {
            switch (type)
            {
                case 'checkbox':
                case 'radio':
                    if (field.checked)
                    {
                        fieldData = {
                            'name' : field.name,
                            'value' : field.value  
                        }
                    }
                    break;

                case 'button':
                case 'reset':
                case 'image':
                case 'submit':
                    
            }

        }
        if (fieldData)
        {
            fieldsData[fieldsData.length] = fieldData;
        }

    }
    return fieldsData;
}


jsValidationScript.prototype.preparePostData = function(fieldsData)
{
    var fieldName, fieldValue;
    var nameValuePairs = [];

    for (var i = 0, field; field = fieldsData[i]; i++)
    {
        fieldName = encodeURIComponent(field.name);
        fieldValue = encodeURIComponent(field.value);
        nameValuePairs[nameValuePairs.length] = fieldName + '='  + fieldValue;
    }

    nameValuePairs[nameValuePairs.length] = 'getValidationXml=1';

    nameValuePairs[nameValuePairs.length] = 'random=' + Math.random();

	if(this.form.buttonPresedName && this.form.buttonPresedValue)
	{
    	nameValuePairs[nameValuePairs.length] = escape(this.form.buttonPresedName) + '=' + escape(this.form.buttonPresedValue);
	}
    var postData = nameValuePairs.join('&');
    return postData;
}

jsValidationScript.prototype.getXmlResponse = function(url, data){
	var xmlhttp = false;
	if (!data)
	{
	    data = null;
	}
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	// JScript gives us Conditional compilation, we can cope with old IE versions.
	// and security blocked creation of the objects.
	 try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	 } catch (e) {
	  try {
	   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	  } catch (E) {
	   xmlhttp = false;
	  }
	 }
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest != 'undefined')
	{
	  xmlhttp = new XMLHttpRequest();
	}
	if(xmlhttp === false)
	{
		return false;
	}
	xmlhttp.open('POST', url, false);
    xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xmlhttp.send(data);

	var xmlResponse = xmlhttp.responseXML;
	if (!xmlResponse)
	{
	    //alert ( xmlhttp.responseText);
	    return false;
	}

	//ie! (we got also xml header as node) and opera! (extra text node)
	if(xmlResponse.childNodes.length == 2)
	{
		if(xmlResponse.childNodes[1].nodeType == 3)
		{
			return xmlResponse.childNodes[0];
		}
		else
		{
			return xmlResponse.childNodes[1];
		}
	}
	else
	{
		return xmlResponse.childNodes[0];
	}
}

jsValidationScript.prototype.parseXmlResponse = function(responseNode)
{
    var status;
    if (
        (!responseNode)
        ||
        (responseNode.tagName.toLowerCase() != 'response')
        ||
        (!(status = responseNode.getAttribute('status')))
        ||
        (
            (status != 'ok')
            &&
            (status != 'error')
        )
    )
    {
        return null; // bad mofo
    }

    var parsedResponse = new Object();
    parsedResponse.status = status;

    // ok
    if (parsedResponse.status == 'ok')
    {
        return parsedResponse;
    }

    // errors
    var errorNodes = responseNode.getElementsByTagName('error');
    var errors = new Array();
    var errorNode, messageText, fieldNameText, focusText, error;
    for (var i = 0; i < errorNodes.length; i++)
    {
        var errorNode = errorNodes[i];
        messageText   = this.getXmlChildNodeTextByTagName(errorNode, 'message');
        fieldNameText = this.getXmlChildNodeTextByTagName(errorNode, 'field_name');
        fieldTitleText =  this.getXmlChildNodeTextByTagName(errorNode, 'field_title');
        focusText     = this.getXmlChildNodeTextByTagName(errorNode, 'focus');

        error = {
            'message' : messageText,
            'field_name' : fieldNameText,
            'field_title' : fieldTitleText
        };

        if (focusText)
        {
            error.focus = focusText;
        }
        errors[errors.length] = error;

    }

    parsedResponse.errors = errors;

    return parsedResponse;
}

jsValidationScript.prototype.throwErrorMessage = function(error)
{
    var message = error.message;
	if( !message )
	{
		alert ( 'Validation Error' );
	}
	else
	{
		var fieldName = error.field_name;
		var fieldTitle = error.field_title;
		var fieldText = fieldTitle || fieldName;
		var finalMessage = message.replace(/\%f/gi, '"' + fieldText + '"')
		alert ( finalMessage  );
	}
    return true;
}

jsValidationScript.prototype.getXmlChildNodeTextByTagName = function(parentNode, childNodeName)
{
    var children = parentNode.getElementsByTagName(childNodeName);
    var textNode;
    if  (
            (children.length != 1)
            ||
            (!children[0].childNodes)
            ||
            (!(textNode = children[0].childNodes[0]))
            ||
            (textNode.nodeType != 3)
    )
    {
        return null;
    }

    return textNode.nodeValue;

}
