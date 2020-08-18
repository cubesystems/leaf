<form class="validatable edit" method="post" action="{$_module->getModuleUrl()}&amp;do=save&amp;id={$item->id}" enctype="multipart/form-data">
	{include file=$_module->pathTo('_edit.header')}
	<div class="content">
		<div class="section"> 
			{include file=$_module->pathTo('_edit.input')  name=login}
			{include file=$_module->pathTo('_edit.input')  name=name}
			{include file=$_module->pathTo('_edit.input')  name=surname}
			{include file=$_module->pathTo('_edit.select') alias=group name=group_id options=$groups showEmptyValue=false}
			{include file=$_module->pathTo('_edit.select') name=language options=$languages showEmptyValue=false}
			{include file=$_module->pathTo('_edit.input')  name=email}
		</div>
        
        <div class="section passwords">
        
            {if $item->id > 0}
                {include file=$_module->pathTo('_edit.input')  name=change_password type=checkbox}
                {assign var=disablePasswords value=true}
            {else}
                {assign var=disablePasswords value=false}
            {/if}
            
            <div class="field generator">
                
                <div class="labelWrap"></div>
                <div class="value">
                    <button type="button" class="generate"{if $disablePasswords} disabled="disabled"{/if}>{alias code=generate}</button>
                    <span class="password"><input type="text" readonly="readonly" /></span>
                </div>
                <div class="clear"><!-- --></div>
                
            </div>

            {include file=$_module->pathTo('_edit.input')  name=password1 type=password postHtml=$generateButton autocomplete=off disabled=$disablePasswords}
            
            
            {include file=$_module->pathTo('_edit.input')  name=password2 type=password  autocomplete=off disabled=$disablePasswords postHtml=$generatedContainer}
        </div>
        
	</div>
	{include file=$_module->pathTo('_edit.footer')}
</form>
