{alias_context code="admin:content"}
<div class="content noShadow searchBlock" >
    
    <form action="?" class="searchForm globalFieldContainer" method="get">
        <input type="hidden" name="module" value="content" />
        <input type="hidden" name="do" value="search" />
        <input type="text" name="searchString" class="search" id="content_search" autofocus="autofocus" placeholder="{alias code=searchKeyword}" value="{$smarty.get.searchString|escape}" />
        <button type="submit" class="">{alias code=submitSearch}</button>
    </form>
    
    <div class="searchResultsBlock">
        {if $searchProcessed}
        <div class="resultsFoundMessage">{alias code=resultsFound var_count=$resultsCount amount=$resultsCount}</div>
        
        {if $searchResults}
            <ul class="searchResultsList block">
            {foreach from=$searchResults item=result}
                <li>
                    <h3><a href="?module=content&amp;do=edit_object&amp;object_id={$result.id}" title="{$result.name|escape}">{$result.name|escape}</a></h3>
                    <div class="frontendLink"><a href="{$result.id|orp|escape}" title="{$result.name|escape}">{$result.id|orp|escape}</a></div>
                    {if $result.text}
                        <div class="resultDescription">{$result.text|escape|highlight:$searchString}</div>
                    {/if}
                </li>
            {/foreach}
            </ul>
        {/if}
        {/if}
    </div>
    
</div>
