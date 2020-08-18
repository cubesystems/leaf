<ul id="childs{$root}">
    {foreach from=$objects item=object}
        <li id="objectli{$object->object_data.id}" {if !$object->haveChildren()}class="noChildren"{/if}>
            {if $object->haveChildren()}
                <a href="{$smarty.const.WWW}{$object->object_data.id}" onclick="get_childs(this); return false;" class="objectTreeNodeOpen" id="objectNodeImg{$object->object_data.id}">
                </a>
            {/if}
            <a href="{$smarty.const.WWW}{$object->object_data.id}" onclick="pickObject(this, '{$object->object_data.name|escape}'); return false;" name="{$object->object_data.id}" id="object_{$object->object_data.id}"  class="{if $object->object_data.id==$group_id}activeObject{/if} {if !$object->object_data.visible}hidden{/if}">
                <img src="{$object->getIconUrl()}" class="noIEPngFix" alt="" /> 
                {$object->object_data.name|escape}
            </a>
        </li>
    {/foreach}
</ul>
