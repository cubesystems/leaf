/* old - wrong way */
function openXmlHttpGet(url,result,data,method){
	var xmlhttp = false;
	if(!data)
	{
		data = null;
	}
	if(!method)
	{
		method = 'GET';
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
	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
	  xmlhttp = new XMLHttpRequest();
	}
	if(xmlhttp === false)
	{
		return false;
	}
	xmlhttp.open(method, url, false);
	xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded")
	xmlhttp.send(data);
	if(result)
	{
		return xmlhttp.responseText;
	}
}
/* use this one */
function loadXmlHttp(url, callback, params, method, data, callbackOnCompletedOnly)
{
	var attributes = false;
	if( typeof(url) == 'object' )
	{
		attributes = url;
		url = attributes.url;
		callback = attributes.callback;
		params = attributes.params;
		method = attributes.method;
		data = attributes.data;
		callbackOnCompletedOnly = attributes.callbackOnCompletedOnly;
	}
	if(!data)
	{
		data = null;
	}
	var xmlhttp = false;
	if(!method)
	{
		method = 'GET';
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
	xmlhttp.open(method, url, true);
	if(callback)
	{
		xmlhttp.onreadystatechange = function(){
		    if (
                (!callbackOnCompletedOnly)
                ||
                (xmlhttp.readyState == 4)
            )
		    {
		        callback(xmlhttp, params);
		    }
		};
	}
    xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xmlhttp.send(data);
	if( attributes !== false )
	{
		return xmlhttp;
	}
	return false;
}