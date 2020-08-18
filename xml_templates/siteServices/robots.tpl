User-agent: *
{if !$allowBots && !$smarty.const.DEV_MODE}
Disallow: /
{else}

{if !empty($disallowRelative)}
{foreach item=item from=$disallowRelative}
Disallow: {$item}
{/foreach}
{/if}

{assign var=url value=$sitemap|orp}
Sitemap: {"/\/$/"|preg_replace:'':$url} 
{/if}