{assign var=typeDef value=$item->getDefinition()}
{assign var=key value=$_module->getUUID()}

<div>
    <input type="hidden" name="actions[{$key}][id]" value="{$item->id|escape}" />
    <select class="shortSelect" name="actions[{$key}][type]">
        {foreach from=$actionTypes key=typeKey item=type}
            <option {if $item->type == $typeKey}selected="selected"{/if} value="{$typeKey}">{alias code="action_type_`$typeKey`"}</option>
        {/foreach}
    </select>

    {if $typeDef.class}
        {assign var=name value="actions[`$key`][contextObjectId]"}
        {input
            type="select" module=$typeDef.controller selectionModel="search"
            name=$name id="`$name``$namespace`"
            selectedObject=$item->getContextObject()
            selectClass=""
            creationDialog=false
            searchUrlPostfix="&searchInNameOnly=1"
        }
    {/if}

    <select name="actions[{$key}][action]">
        {foreach from=$typeDef.actions key=actionKey item=action}
            <option {if $item->action == $actionKey}selected="selected"{/if} value="{$actionKey}">{alias code="action_`$item->type`_`$actionKey`"}</option>
        {/foreach}
    </select>

    {if $item->getValueType()}
    <input type="text" name="actions[{$key}][value]" value="{$item->getFormatedValue()|escape}" class="{$item->getValueType()}Value" />
    {/if}
    <img src="images/icons/delete.png" class="deleteIcon" />
</div>

