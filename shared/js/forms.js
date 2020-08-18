var Forms = function()
{

}

Forms.isForm = function (form)
{
    if (
        (!form)
        ||
        (!form.tagName)
        ||
        (form.tagName.toLowerCase() != 'form')
    )
    {
        return false;
    }
    return true;
}

Forms.getFields = function (form)
{
    // returns an array with references to all form field elements (input, select, textarea)

    if (!Forms.isForm(form))
    {
        return null;
    }

    var fields = [];
    var tagNames = ['input', 'select', 'textarea'];
    for (var i=0; i < tagNames.length; i++)
    {
        var el;
        var elements = form.getElementsByTagName( tagNames[i] );
        for (var j=0; j < elements.length; j++)
        {
            el = elements[j];
            fields[fields.length] = el;
        }
    }
    return fields;
}

Forms.getFieldValue = function ( field )
{
    var type = Forms.getFieldType ( field );
    if (type == 'select-multiple')
    {
        var multiValue = [];
        for (var i=0; i<field.options.length; i++)
        {
            if (field.options[i].selected)
            {
                multiValue[multiValue.length] = field.options[i].value;
            }
        }
        return multiValue;
    }
    else
    {
        return field.value;
    }
}

Forms.collectFormValues = function (form)
{
    // collect current form values to store in a variable for later restoration
    // all field elements should have unique ids

    if (!Forms.isForm(form))
    {
        return;
    }

    var fields = Forms.getFields(form);
    if (!fields)
    {
        return null;
    }

    var formValues = {};
    for (var i = 0; i < fields.length; i++)
    {
        field = fields[i];
        if (!field.id)
        {
            continue;
        }

        var tagName = Forms.getTagName ( field );
        var type = Forms.getFieldType ( field );

        var fieldValue = {
            "tagName" : tagName,
            "type" : type
        };


        if ((type == 'radio') || (type == 'checkbox'))
        {
            fieldValue.checked = field.checked;
        }
        else
        {
            fieldValue.value = Forms.getFieldValue(field);
        }

        formValues[field.id] = fieldValue;
    }

    return formValues;
}

Forms.getTagName = function ( field )
{
    // get lowercased tag name of fiel element
    if (
        (!field)
        ||
        (!field.tagName)
        ||
        (!field.tagName.toLowerCase)
    )
    {
        return null;
    }
    var tagName = field.tagName.toLowerCase();
    return tagName;
}

Forms.getFieldType = function ( field )
{
    var tagName = field.tagName.toLowerCase();

    if (tagName == 'input')
    {
        var type = field.type.toLowerCase();
    }
    else if (
        (tagName == 'select')
        &&
        (field.multiple)
    )
    {
        type = 'select-multiple';
    }
    else
    {
        // set text type for select / textarea elements
        type = 'text';
    }
    return type;
}

Forms.restoreFormValues = function (form, formValues)
{
    // restore form field values from variable previously created with Forms.collectFormValues()

    if (
        (!Forms.isForm(form))
        ||
        (!formValues)
    )
    {
        return;
    }

    for (fieldId in formValues)
    {
        var field = document.getElementById( fieldId );
        if (!field)
        {
            continue;
        }

        var fieldValue = formValues[fieldId];

        var type = fieldValue.type;

        if ((type == 'radio') || (type == 'checkbox'))
        {
            field.checked = fieldValue.checked;
        }
        else if (type == 'text')
        {
            field.value = fieldValue.value;
        }

    }

    return true;
}


Forms.getFormData = function (form)
{
    // returns form data as it would be submitted
    // reurns an object with field names as keys and field values as values
    // in case of array fields, the value will be an array and the key will have [] as the last 2 chars

    // { name : value, name : value, name : [ value, value, value ] }
    //

    var fields = Forms.getFields( form );
    if (!fields)
    {
        return null;
    }
    var data = {};
    for (var i=0; i < fields.length; i++)
    {
        var field = fields[i];

        // skip disabled elements and elements with no name
        if (
            (field.disabled)
            ||
            (!field.name)
        )
        {
            continue;
        }

        // get field type
        var type = Forms.getFieldType ( field );

        // skip non-checked checkboxes and radio buttons
        if (
            (
                (type == 'checkbox')
                ||
                (type == 'radio')
            )
            &&
            (!field.checked)
        )
        {
            continue;
        }


        var fieldValue = Forms.getFieldValue(field);

        // check if single value or array
        var fieldName = field.name;
        var fieldIsArray = (fieldName.substr(-2) == '[]');

        // store value in data variable. this differs between single and array values
        if (fieldIsArray)
        {
            // create array if necessary,
            if ((!data.hasOwnProperty(fieldName)))
            {
                data[fieldName] = [];
            }

            if (type == 'select-multiple')
            {
                for (var j=0; j < fieldValue.length; j++)
                {
                    data[fieldName][data[fieldName].length] = fieldValue[j];
                }
            }
            else
            {
                // assign to end of the array
                data[fieldName][data[fieldName].length] = fieldValue;
            }
        }
        else
        {
            // single value. simply set the value, overwriting any previous values
            data[fieldName] = fieldValue;
        }
    }
    return data;

}

Forms.formDataToString = function ( formData )
{
    var fieldName;
    var nameValuePairs = [];
    for (fieldName in formData)
    {
        var fieldIsArray = (fieldName.substr(-2) == '[]');
        if (fieldIsArray)
        {
            for (var i = 0; i < formData[fieldName].length; i++)
            {
                nameValuePairs[nameValuePairs.length] = { "name" : fieldName, "value" : formData[fieldName][i] }
            }
        }
        else
        {
            nameValuePairs[nameValuePairs.length] = { "name" : fieldName, "value" : formData[fieldName] }
        }
    }

    if (!nameValuePairs)
    {
        return '';
    }

    for (var i=0; i < nameValuePairs.length; i++)
    {
        nameValuePairs[i] =
            Forms.escapeStringForQuery( nameValuePairs[i].name ).concat
                (
                    '=',
                    Forms.escapeStringForQuery( nameValuePairs[i].value.toString() )
                )
            ;
    }
    var dataString = nameValuePairs.join('&');
    return dataString;

}

Forms.escapeStringForQuery = function (string)
{
    // escapes a string for use ir query params
    // encodes utf8 multibyte chars as multiple separate symbols

    string = string.replace(/\r\n/g,"\n");
    string = string.replace(/\n/g,"\r\n");
    var utftext = "";

    for (var n = 0; n < string.length; n++) {

        var c = string.charCodeAt(n);

        if (c < 128) {
            utftext += String.fromCharCode(c);
        }
        else if((c > 127) && (c < 2048)) {
            utftext += String.fromCharCode((c >> 6) | 192);
            utftext += String.fromCharCode((c & 63) | 128);
        }
        else {
            utftext += String.fromCharCode((c >> 12) | 224);
            utftext += String.fromCharCode(((c >> 6) & 63) | 128);
            utftext += String.fromCharCode((c & 63) | 128);
        }

    }

    utftext = escape(utftext);
    return utftext;

}


Forms.loaded = true;