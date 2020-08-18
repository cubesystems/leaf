function suggest_rewrite()
{
	var obj_name = document.getElementById('name');
	var url = document.location.href + '&suggest_rewrite_name=' + encodeURIComponent(obj_name.value);
	var guesed_name = openXmlHttpGet(url, true);
	document.getElementById('rewrite_name').value = openXmlHttpGet(url, true);
             updateUrlPart();
}

$(document).ready(function() {
    objectEditInstance = new objectEditScript();
    objectEditInstance.init();
});			

         function updateUrlPart()
         {
             var urlPart = $('#rewrite_name').val();
             if(urlPart == '' && objectId)
             {
                 urlPart = objectId;
             }
             $('.objectUrlPart').html(urlPart);
         }

function objectEditScript()
{

}
objectEditScript.prototype.init = function()
{
    var form = document.getElementById('objectEditForm');
    
    if ((!form) || (!jsValidation))
    {
        return false;
    }

    form.submitInProgress = false;

    // assign validation (modifies form.onsubmit)
    jsValidation.attachHandlers(form);
    if (!form.onsubmit)
    {
        return false;
    }

    // copy validation code to another attribute
    form.validationOnSubmit = form.onsubmit;

    // reassign onsubmit with included double-submit protection
    form.onsubmit = function()
    {
		if (form.submitInProgress)
        {
            return false;
        }
        form.submitInProgress = true;
        var validationOk = this.validationOnSubmit();
        if (validationOk)
        {
            return true;
        }
        // validation not ok
        form.submitInProgress = false; // re-allow submits
        return false; // deny current submit
    }
}
