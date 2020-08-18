<form class="validatable edit" method="post" action="{$_module->getModuleUrl()}&amp;do=save&amp;id={$item->id}" enctype="multipart/form-data">
	{include file=$_module->pathTo('_edit.header')}
	<div class="content">
		<div class="section">
			{include file=$_module->pathTo('_edit.input') name=name}
			{include file=$_module->pathTo('_edit.select') type=enum translateOptions=false name=default_module options=$moduleNames showEmptyValue=false}
		</div>

		<div class="section">
            <table class="leafTable labelFirstColumn alternateRows permissionsTable">
                <tr class="firstRow">
                    <td></td>
                    <td><label for="disableAll"><input type="radio" name="setAll" value="0" id="disableAll" /> {alias code="disable"}</label></td>
                    <td><label for="enableAll"><input type="radio" name="setAll" value="1" id="enableAll" /> {alias code="enable"}</label></td>
                </tr>
                {foreach from=$modules item=module}
                <tr>
                    <th class="nameColumn">
                        {$module.name}
                    </th>
                {foreach item=process from=$module.processes}
                    {if $process.id != 1}
                        </tr><tr>
                            <td class="nameColumn">{$process.name}</td>
                    {/if}
                        <td>
                            <label><input type="radio" name="configurations[{$module.name}][process][{$process.id}]" {if !$process.value}checked="checked"{/if} value="0">{alias code=disabled}</label>
                        </td>
                        <td>
                            <label><input type="radio" name="configurations[{$module.name}][process][{$process.id}]" value="1" {if $process.value}checked="checked"{/if}>{alias code=enabled}</label>
                        </td>
                    </tr>
                {/foreach}
                {foreach from=$module.configurations item=config}
                    <tr>
                        <td>
                            {if $config.description}{$config.description}{else}{$config.name}{/if}
                        </td>
                        <td>
                            {if $config.type=='boolean'}
                                <select name="configurations[{$module.name}][config][{$config.name}]">
                                    {if $smarty.get.target_group}<option value="">Inherit from parent</option>{/if}
                                    <option {if $config.value=='1'} selected="selected" {/if} value="1">Yes</option>
                                    <option {if !$config.value} selected="selected" {/if} value="0">No</option>
                                </select>
                            {elseif $config.type=='select'}
                                <select name="configurations[{$module.name}][config][{$config.name}]">
                                    {if $smart.get.target_group}<option value="">Inherit from parent</option>{/if}
                                    {html_options options=$config.values selected=$config.value}
                                </select>
                            {elseif $config.type=='hidden'}
                                <input type="hidden" value="{$config.value}" name="configurations[{$module.name}][config][{$config.name}]" />
                            {else}
                                <input type="text" value="{$config.value}" name="configurations[{$module.name}][config][{$config.name}]" />
                            {/if}
                        </td>
                    </tr>
                {/foreach}
            {/foreach}
            </table>
		</div>
	</div>


	{include file=$_module->pathTo('_edit.footer')}
</form>
