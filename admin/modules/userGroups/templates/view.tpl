<div class="header">
	<div class="padding">
		<h2>{$item->getDisplayString()|escape}</h2>
	</div>
</div>
<div class="content view">
	<div class="section">
		{include file=$_module->pathTo('_view.field') name=name}
		{include file=$_module->pathTo('_view.field') name=default_module}
	</div>
    <div class="section">
        <table class="leafTable labelFirstColumn alternateRows permissionsTable">
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
                    <td>{if !$process.value}<img src="images/icons/tick.png" alt="" />{else}-{/if}</td>
                    <td>{if $process.value}<img src="images/icons/tick.png" alt="" />{else}-{/if}</td>
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

{include file=$_module->pathTo('_view.footer')}
