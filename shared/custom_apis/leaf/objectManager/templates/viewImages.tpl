<div id="imageManager">
	<div id="objectTree">{$objectTree}</div>
	<div id="previewParent"><div id="preview"></div></div>
	<table id="imageInsertTable">
		<tr>
		<td style="width:340px; ">
		<form id="insertBlock" action="./" method="get">
			<input value="" name="class" id="f_class" type="hidden" />
			<table cellpadding="2" cellspacing="0">
			<tr><td>Image File:<br /><input name="url" id="f_url" type="text" size="30" />
			</td><td>Align:<br />
			<select name="align" id="f_align">
			<option id="optNotSet" value=""> Not set </option>
			<option id="optLeft" value="left"> Left </option>
			<option id="optRight" value="right"> Right </option>
			</select></td></tr>
			<tr><td>Alt File:<br />
			  <input type="text" name="alt" id="f_alt" />
			</td>
			<td></td></tr>
			<tr><td colspan="2">
			<button type="button" onclick="{if $smarty.get.target_name}return set_target('{$smarty.get.target_name|escape:javascript|escape}');{else}return onOK();{/if}">&nbsp;Ok&nbsp;</button>  &nbsp; <button type="button" onclick="return onCancel();">Cancel</button>
			</td></tr>
			</table>
		</form>
		</td>
		</tr>
	</table>

	{include file="searchBox.tpl"}
</div>
<script type="text/javascript">
//<![CDATA[
var baseUrl = '{$_component->baseUrl}';
var contentBaseUrl = '{$_component->contentBaseUrl}';
var imageExtensions = Array("{'","'|implode:$_component->imageExtensions}");
var imageDir = "{$imageDir}";
var dialogType = "{$type}";
{literal}
function Init() {
	__dlg_translate('HTMLArea');
	__dlg_init(null, {width:800,height:500});
	var param = window.dialogArguments;
	if (param)
	{
		//replace unneeded path part
		var link = document.createElement('a');
		link.setAttribute('href', param["f_url"]);
		if(link.hostname == document.location.hostname)
		{
			var tmp =  param["f_url"].split('?');
			param["f_url"] = '{/literal}{$site_www}{literal}?' + tmp[1];
		}
		var link = null;
		if(param["f_alt"] == '___')
		{
			param["f_alt"] = '';
		}
		document.getElementById("f_url").value = param["f_url"];
		document.getElementById("f_alt").value = param["f_alt"];
		document.getElementById("f_class").value = param["f_class"];
		if(param["f_class"]=='contentImageRight')
		{
			document.getElementById("f_align").value='right';
		}
		else if(param["f_class"]=='contentImageLeft')
		{
			document.getElementById("f_align").value='left';
		}
	}
	document.getElementById("f_url").focus();
};

function set_target(target_name){
	var image_id = document.getElementById("f_url").value;
	var image_path = document.getElementById('preview').src;
	opener.document.getElementById(target_name+'imageDiv').innerHTML = '<img src="'+image_path+'" />';
	opener.document.getElementById(target_name).value=image_id;
	window.close();
}

function onOK() {
  var required = {
	"f_url": "You must enter the URL"
  };
  for (var i in required) {
	var el = document.getElementById(i);
	if (!el.value) {
	  alert(required[i]);
	  el.focus();
	  return false;
	}
  }
  var alignField=document.getElementById('f_align');
  var classField=document.getElementById('f_class');
  if(alignField.value=='right')
  {
	classField.value='contentImageRight';
  }
  else if(alignField.value=='left')
  {
	classField.value='contentImageLeft';
  }

  // pass data back to the calling window
  var fields = new Array("f_url", "f_alt", "f_class");
  var param = new Object();
  for(var i = 0; i < 3; ++i)
  {
	var id = fields[i];
	var el = document.getElementById(id);
	param[id] = el.value;
  }
  __dlg_close(param);
  return false;
};

function onCancel() {
{/literal}
{if !$smarty.get.target_name}
  __dlg_close(null);
{else}
	window.close();
{/if}
{literal}
  return false;
};
{/literal}
{if !$smarty.get.target_name}
window.onload = Init;
{/if}
//]]>
</script>