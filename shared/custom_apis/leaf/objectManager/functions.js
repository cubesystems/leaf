Array.prototype.inArray = function(search_term) {
  var i = this.length;
  if (i > 0) {
	 do {
		if (this[i] === search_term) {
		   return true;
		}
	 } while (i--);
  }
  return false;
}

function pickObject(hrefObj, fileName){
	if(fileName.length > 0)
	{ 
		var dot = fileName.lastIndexOf(".");
		if(dot != -1)
		{
			var extension = fileName.substr(dot + 1, fileName.length);
			if(imageExtensions.inArray(extension))
			{
				document.getElementById('preview').innerHTML = '<img src="' + imageDir + fileName + '" alt=""  />';
				if(f_url=parent.document.getElementById("f_url"))
				{
					f_url.value = contentBaseUrl + '?object_id=' + hrefObj.name;
				}
			}
		}
	}
	return false;
}

function get_childs(obj){
	var tmp = obj.href.split('/');
	var objId = tmp[tmp.length - 1];
	var target;
	if(target = document.getElementById('childs' + objId))
	{
		obj.className = 'objectTreeNodeOpen';
		target.parentNode.removeChild(target);
	}
	else
	{
		obj.className = 'objectTreeNodeClose';
		document.getElementById('objectli' + objId).innerHTML += openXmlHttpGet('?' + baseUrl + 'getNodeTree=' + objId, true);
	}
	return false;
}

//Gets the browser specific XmlHttpRequest Object
function getXmlHttpRequestObject() {
	if (window.XMLHttpRequest) {
		return new XMLHttpRequest();
	} else if(window.ActiveXObject) {
		return new ActiveXObject("Microsoft.XMLHTTP");
	} else {
		alert("Your Browser does not support leaf");
	}
}

var searchReq = getXmlHttpRequestObject();
var suggestTimeout;

function searchSuggestStart(obj){
	clearTimeout(suggestTimeout);
	return (function(){
		if (searchReq.readyState == 4 || searchReq.readyState == 0)
		{
			if(obj.value != '')
			{
				searchReq.open("GET", obj.form.action + 'suggest=' + escape(obj.value), true);
				searchReq.onreadystatechange = handleSearchSuggest; 
				searchReq.send(null);
			}
			else
			{
				var ss = document.getElementById('search_suggest')
				ss.innerHTML = '';
			}
		}		
	});
}

function searchSuggest(obj) {
	clearTimeout(suggestTimeout);
	var functRef = searchSuggestStart(obj);
	suggestTimeout = setTimeout(functRef, 500);
}

function searchSuggestForm(){
	var obj = document.getElementById('txtSearch');
	var functRef = searchSuggestStart(obj);
	functRef();
	return false;
}


function handleSearchSuggest() {
	if (searchReq.readyState == 4)
	{
		var ss = document.getElementById('search_suggest');
		ss.innerHTML = searchReq.responseText;
	}
}