{strip}
{assign var=direction value=asc}
{if $smarty.get.orderBy == $column && $smarty.get.direction == $direction}
	{assign var=direction value=desc}
{/if}
<a href="{request_url add="orderBy=`$column`&direction=`$direction`" remove="ajax&json"}"{if $smarty.get.orderBy == $column || !$smarty.get.orderBy && $default} class="currentOrder {$direction}"{/if}>
	{if !$alias}{assign var=alias value=$column|cat:Label}{/if}
	{alias code=$alias}
</a>
{/strip}