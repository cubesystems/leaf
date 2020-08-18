<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$properties.language_code|escape}" xml:lang="{$properties.language_code|escape}">
{capture assign=bodyHtml}
<body class="{$properties.language_code|escape} body-{$openedObject->object_data.template|stringtolatin:true:true|escape}">
<script type="text/javascript" src="js/js_check.js"></script>

<div class="mainContainer">
    <div class="skipLinks">
		<a href="{request_url}#content">{alias code=skipLinks}</a>
	</div>

    {banner object=$rootVars.logo alt=$rootVars.logoText link=$root->object_data.id containerTag=div containerClass=logoBox}

	{assign var=languages value=$menu->getLanguages()}
	{if count($languages) > 1}
	<div class="languageMenuBox">
		<ul class="block languageMenu">
		{foreach item=item from=$languages name=languages}
			<li{if $item.active} class="active"{/if}>
				<a href="{$item.object_id|orp|escape}{if $item.path_part}{$item.path_part|escape}{/if}{if $item.get_query}?{$item.get_query|escape}{/if}">{$item.name|escape}</a>
			</li>
		{/foreach}
		</ul>
	</div>
	{/if}

    menu:
    <div class="mainMenuBox">
		{include file=menu.stpl menu=$menu->getMainMenu()}
    </div>


    content:
	<div class="contentBox">
		<div class="content" id="content">
			<h1>{$openedObject|escape}</h1>
			{$openedObject->content}
		</div>
	</div>

</div>

<div class="footerContainer">
    footer
    <a class="cubeLink" href="http://www.cube.lv/" target="_blank" title="{alias code=cubeLogoText}">
		<img src="images/cube.png" alt="{alias code=cubeLogoText}" />
	</a>
</div>

{foreach item=item from='js'|leaf_get}
	<script type="text/javascript" src="{$item|escape}"></script>
{/foreach}

{foreach item=set key=condition from='js_ie'|leaf_get}
	<!--[if {$condition}]>
		{foreach item=item  from=$set}
			<script type="text/javascript" src="{$item|escape}"></script>
		{/foreach}
	<![endif]-->
{/foreach}

</body>
{/capture}
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8"  />
<meta name="description" content="{$metaDescription|escape}" />
<meta name="keywords" content="{$metaKeywords|escape}">
<meta name="author" content="{if $smarty.const.DEVELOPED_BY == "cubesystems"}Cube Systems / www.cubesystems.lv{else}Cube / www.cube.lv{/if}" />
<meta name="Robots" content="index,follow" />
<base href="{$smarty.const.WWW|escape}" /><!--[if IE]></base><![endif]-->

{foreach from='rss'|leaf_get item = item}
<link rel="alternate" type="application/rss+xml" title="{$item.title|escape}" href="{$item.url|escape}" />
{/foreach}

<link rel="shortcut icon" href="{$smarty.const.WWW|escape}favicon.ico" />
<title>{foreach item=item from=$menu->getTitle()}{$item.name|escape} : {/foreach} {$rootVars.siteTitle|escape}</title>

{foreach item=item from='css'|leaf_get}
<link rel="stylesheet" type="text/css" href="{$item|escape}" />
{/foreach}

<link rel="stylesheet" type="text/css" {if !isset($smarty.get.print)} media="print"{/if} href="styles/print.css" />

{foreach item=set key=condition from='css_ie'|leaf_get}
	<!--[if {$condition}]>
    {foreach item=item  from=$set}
        <link rel="stylesheet" type="text/css" href="{$item|escape}" />
	{/foreach}
	<![endif]-->
{/foreach}

{if $properties.googleAnalyticsId}
	{literal}
		<script type="text/javascript">
			var _gaq = _gaq || [];
			_gaq.push(['_setAccount','{/literal}{$properties.googleAnalyticsId|escape}{literal}']);
			_gaq.push(['_trackPageview']);
			setTimeout(function()
			{
				var ga = document.createElement('script');
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				ga.setAttribute('async', 'true');
				document.documentElement.firstChild.appendChild(ga);
			},10);
		</script>
	{/literal}
{/if}
</head>
{$bodyHtml}
</html>
