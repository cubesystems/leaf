<script type="text/javascript">
//<![CDATA[
var baseUrl = '{$_component->baseUrl}';
var contentBaseUrl = '{$_component->contentBaseUrl}';
var dialogType = "{$type}";

{literal}
function pickObject(obj){
	var tmp = obj.href.split('/');
	var objId = tmp[tmp.length - 1];
	{/literal}
	document.getElementById("f_href").value = {if !$smarty.get.target_id or $smarty.get.id_prefix}contentBaseUrl + '?object_id=' + {/if}objId;
	{literal}
	return false;
}

function add_link(href){
	{/literal}
	document.getElementById("f_href").value={if !$smarty.get.target_id or $smarty.get.id_prefix}contentBaseUrl + '?object_id='+{/if}href;
	{literal}
}

function set_target(target_name){
	var target_name;
	var target = opener.document.getElementById(target_name);
	if (target)
	{
        target.value = document.getElementById("f_href").value;
        if (target.onchange)
        {
            target.onchange();
        }

	}
	window.close();
}

window.resizeTo(600, 400);
Xinha = window.opener.Xinha;

function i18n(str) {
  return (Xinha._lc(str, 'Xinha'));
}

function Init() {
  __dlg_translate('Xinha');
  __dlg_init();

  // Make sure the translated string appears in the drop down. (for gecko)
  document.getElementById("f_target").selectedIndex = 1;
  document.getElementById("f_target").selectedIndex = 0;

  var param = window.dialogArguments;
  var target_select = document.getElementById("f_target");
  var use_target = true;
  if (param) {
    if ( typeof param["f_usetarget"] != "undefined" ) {
      use_target = param["f_usetarget"];
    }
    if ( typeof param["f_href"] != "undefined" ) {
      document.getElementById("f_href").value = param["f_href"];
      document.getElementById("f_title").value = param["f_title"];
      comboSelectValue(target_select, param["f_target"]);
      if (target_select.value != param.f_target) {
        var opt = document.createElement("option");
        opt.value = param.f_target;
        opt.innerHTML = opt.value;
        target_select.appendChild(opt);
        opt.selected = true;
      }
      updateTargetCheckbox();
    }
  }
  if (! use_target) {
    document.getElementById("f_target_label").style.visibility = "hidden";
    document.getElementById("f_target").style.visibility = "hidden";
    document.getElementById("f_target_other").style.visibility = "hidden";
  }
  var opt = document.createElement("option");
  opt.value = "_other";
  opt.innerHTML = i18n("Other");
  target_select.appendChild(opt);
  document.getElementById("f_href").focus();
  document.getElementById("f_href").select();
};

function onOK() {
  var required = {
    // f_href shouldn't be required or otherwise removing the link by entering an empty
    // url isn't possible anymore.
    // "f_href": i18n("You must enter the URL where this link points to")
  };
  for (var i in required) {
    var el = document.getElementById(i);
    if (!el.value) {
      alert(required[i]);
      el.focus();
      return false;
    }
  }
  // pass data back to the calling window
  var fields = ["f_href", "f_title", "f_target" ];
  var param = new Object();
	for(var i = 0; i < 3; ++i)
	{
		var id = fields[i];
		var el = document.getElementById(id);
		param[id] = el.value;
	}
  if (param.f_target == "_other")
    param.f_target = document.getElementById("f_other_target").value;

  __dlg_close(param);
  return false;
};

function onCancel() {
  __dlg_close(null);
  return false;
};

function updateTargetDropdown()
{
    var input = document.getElementById('targetBlank');
    var select = document.getElementById('f_target');
    select.value = (input.checked) ? '_blank' : '';
}

function updateTargetCheckbox()
{
    var input = document.getElementById('targetBlank');
    var select = document.getElementById('f_target');
    input.checked = (select.value == '_blank');
}
{/literal}
{if !$smarty.get.target_id}
window.onload=Init;
{/if}
group_id = {$group_id};
//]]>
</script>
<div id="linkManager">
	<div id="objectTree">{$objectTree}</div>
	<div id="linkBlock">
		<div class="entry">
			<label for="f_href">URL</label>
			<span><input type="text" id="f_href" /></span>
		</div>
		<div class="entry">
			<label for="f_title">Title</label>
			<span><input type="text" id="f_title" /></span>
		</div>
		<div class="entry">
			<label for="targetBlank">New window</label>
			<span><input type="checkbox" id="targetBlank" onclick="updateTargetDropdown()" /></span>
		</div>
		<div id="targetBlock" class="entry">
			<label for="f_target">Target</label>
			<span><select id="f_target">
			  <option value="">None (use implicit)</option>
			  <option value="_blank">New window (_blank)</option>
			</select></span>
		</div>
		<div id="btnBlock">
			<button type="button" name="ok" onclick="{if $smarty.get.target_id}set_target('{$smarty.get.target_id|escape:javascript|escape}');{else}return onOK(){/if}">OK</button> &nbsp;
			<button type="button" name="cancel" onclick="{if $smarty.get.target_id}window.close(){else}return onCancel();{/if}">Cancel</button>
		</div>
	</div>
</div>
<script type="text/javascript">
//<![CDATA[
document.getElementById('objectTree').style.height = (document.documentElement.clientHeight - 2) + "px";
//]]>
</script>