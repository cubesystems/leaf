{assign var=typeDef value=$item->getDefinition()}
{assign var=key value=$_module->getUUID()}

<div>
    <input type="hidden" name="triggers[{$key}][id]" value="{$item->id|escape}" />
    <select class="shortSelect" name="triggers[{$key}][type]">
        {foreach from=$triggerTypes key=typeKey item=type}
            <option {if $item->type == $typeKey}selected="selected"{/if} value="{$typeKey}">{alias code="trigger_type_`$typeKey`"}</option>
        {/foreach}
    </select>

    {if $typeDef.class}
        {assign var=name value="triggers[`$key`][contextObjectId]"}
        {input
            type="select" module=$typeDef.controller selectionModel="search"
            name=$name id="`$name``$namespace`"
            selectedObject=$item->getContextObject()
            selectClass=""
            creationDialog=false
            searchUrlPostfix="&searchInNameOnly=1"
        }
    {/if}

    <select name="triggers[{$key}][operator]">
        {foreach from=$typeDef.operators key=operatorKey item=operator}
            <option {if $item->operator == $operatorKey}selected="selected"{/if} value="{$operatorKey}">{alias code="operator_`$item->type`_`$operatorKey`"}</option>
        {/foreach}
    </select>
    {if $item->getValueType()}
        <input type="text" name="triggers[{$key}][value]" value="{$item->getFormatedValue()|escape}" class="{$item->getValueType()}Value" />
    {/if}
    <img src="images/icons/delete.png" class="deleteIcon" />
</div>
