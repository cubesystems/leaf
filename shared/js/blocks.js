Blocks = function ()
{

}

Blocks.submitFormAsBlock = function (form, blockName, callBack, params)
{
    if (
        (!Forms.isForm(form))
        ||
        (!blockName)
        ||
        (!form.action)
    )
    {
        return null;
    }
    var action = form.getAttribute('action').split('#')[0];
    var method = form.getAttribute('method');
    if (!method)
    {
        method = 'GET';
    }
    method = method.toUpperCase();

    var dataObj = Forms.getFormData(form);

    // add block name and random to params
    dataObj.block = blockName;
    if (method == 'GET')
    {
        dataObj.random = Math.random();
    }

    var data = Forms.formDataToString( dataObj );

    // if GET method, move all params from data to main action URL, set data to null
    if (method == 'GET')
    {
        action = action.concat('?', data );
        data = null;
    }

    loadXmlHttp ( action, callBack, params, method, data, true);

    return;
}

Blocks.loaded = true;
