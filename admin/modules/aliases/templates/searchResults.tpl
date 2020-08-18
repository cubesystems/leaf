<div class="panelLayout module-aliases method-edit">
    <div class="secondaryPanel">
        {include file = "list.tpl"}
    </div>

	<div class="primaryPanel">

	<div class="contentModule" id="editForm">
            <h1>{alias code=searchTranslationsHeading}{if isset($smarty.get.filter)} ({$smarty.get.filter|escape}){/if}</h1>

            <div class="content">


                <div id="dialog" class="content-tree-wrap">
                    <ul class="block root">
                        {foreach from=$aliases item=item key=key}
                            <li>
                                <a href="{$_module->header_string|escape}&do=edit&id={$item.group_id|escape}#translation_{$key|escape}">
                                    <strong>{$item.name|escape}</strong>{if $item.translation|escape} ({$item.translation|escape}){/if}
                                    - <small>{$item.context|escape}/{$item.groupName|escape}</small>
                                </a>
                            </li>
                        {/foreach}
                    </ul>
                </div>

            </div>
        </div>
    </div>
</div>