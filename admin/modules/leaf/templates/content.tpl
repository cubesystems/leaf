<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{$siteTitle|escape} : Leaf</title>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<meta http-equiv="content-type" content="text/html; charset=utf-8"  />
<link rel="stylesheet" type="text/css" href="styles/style.css" />
{section name=entry loop=$css}
	<style type="text/css">	@import "{$css[entry]|escape}"; </style>
{/section}
<!--[if lt IE 7]>
    <style type="text/css">
		@import "styles/ie6.css";
    </style>
<![endif]-->
<!--[if IE]>
    <style type="text/css">
		@import "styles/ie_all.css";
    </style>
<![endif]-->
<!--[if lt IE 6]>
	<style type="text/css">
		@import "styles/ie5.css";
	</style>
<![endif]-->
{foreach item=set key=condition from=$css_ie}
	<!--[if {$condition}]>
		<style type="text/css">
			{foreach item=item  from=$set}
				@import "{$item|escape}";
			{/foreach}
		</style>
	<![endif]-->
{/foreach}
</head>
<body>
	{if !isset($smarty.get.single_module) && $menu}
		{strip}
			<div id="menu">
				<div id="leafUserToolbar">
                    {if $profileModuleName}
					<a href="?module={$profileModuleName|rawurlencode|escape}" {if $smarty.get.module == $profileModuleName}class="active"{/if}>
						<span class="imageContainer">
							<img src="images/profileLarge.png" alt="" />
						</span>
						<span>{alias context=admin code=my_profile}</span>
					</a>
                    {/if}
					<a href="?leafDeauthorize=1">
						<span class="imageContainer">
							<img src="images/logoutLarge.png" alt="" />
						</span>
						<span>{alias context=admin code=logout}</span>
					</a>
				</div>
				<ul>
					{foreach from=$menu item=moduleItem}
						<li>
							{if $moduleItem.isGroup}
								<a href="?module={$moduleItem.module_name|escape}&amp;submenuGroup={$moduleItem.groupName|escape}" {if $moduleItem.isActive}class="active"{/if}>
									<span class="imageContainer">
										<img src="{$moduleItem.icon|escape}" alt="" />
									</span>
									<span class="nameWrap">{alias code="`$moduleItem.module_name`:`$moduleItem.groupName`" context="admin:moduleNames"}</span>
								</a>
							{else}
								<a href="?module={$moduleItem.module_name|escape}" {if $smarty.get.module == $moduleItem.module_name}class="active"{/if}>
									<span class="imageContainer">
										<img src="modules/{$moduleItem.module_name|escape}/icon.png" alt="" />
									</span>
									<span class="nameWrap">{$moduleItem.name|escape}</span>
								</a>
							{/if}
						</li>
					{/foreach}
				</ul>
				<div id="menuBottom"><!-- --></div>
			</div>
		{/strip}

	{/if}
	{if isset($smarty.get.single_module)}
		{$module}
	{else}
		<div id="mainLeafContent">
			{$module}
		</div>
	{/if}
	{foreach from=$js item=src}
		<script type="text/javascript" src="{$src|escape}"></script>
	{/foreach}
	{foreach item=set key=condition from=$js_ie}
		<!--[if {$condition}]>
			{foreach item=item  from=$set}
				<script type="text/javascript" src="{$item|escape}"></script>
			{/foreach}
		<![endif]-->
	{/foreach}
</body>
</html>
