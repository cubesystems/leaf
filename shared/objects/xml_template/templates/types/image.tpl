<h3>Depreciated!!! use fileobejct instead.</h3>
<a href="#" onclick="window.open('?module=image_manager&amp;single_module=true&amp;target_name={$field.input_id|escape:javascript|escape}','FirstWin','width=502,height=325'); return false"><img src="modules/image_manager/img/image_manager.gif" alt="" /></a> <a href="#" onClick="document.getElementById('{$field.input_id|escape:javascript|escape}').value = ''; document.getElementById('{$field.input_id|escape:javascript|escape}imageDiv').innerHTML = ''; return false;"><img src="images/delete.gif" alt="" /></a>
<div><input type="hidden" name="{$field.input_name|escape}" id="{$field.input_id|escape}" value="{$field.value|escape}" /> <div id="{$field.input_id|escape}imageDiv">
{if $field.value}<img src="{$site_www|escape}?object_id={$field.value|escape}&amp;thumb=true" />{/if}
</div></div>
