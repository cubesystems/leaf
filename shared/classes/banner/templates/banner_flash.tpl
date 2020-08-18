{strip}

{assign var=embedTargetId value=$containerId}
{if $instance->swfobjectVersion == 2}
    {assign var=embedTargetId value="`$containerId`-"|uniqid}
{/if}

{php}
    // add $url to $variables

    $vars = $this->get_template_vars('variables');
    if (empty($vars))
    {
        $vars = array();
    }

    $url = $this->get_template_vars('url');
    if (!empty($url))
    {
        $vars['clickTAG'] = $url;
    }
    $this->assign('variables', $vars);
{/php}



{if $variables}

{/if}

<{$containerTag}{if $containerClass} class="{$containerClass|escape}"{/if} id="{$containerId|escape}"{if ($setContainerWidth && $data.extra_info.image_width) || $setContainerHeight && $data.extra_info.image_height} style="{if $setContainerWidth && $data.extra_info.image_width}width: {$data.extra_info.image_width}px;{/if}{if $setContainerHeight && $data.extra_info.image_height}height: {$data.extra_info.image_height}px{/if}"{/if}>{if $instance->swfobjectVersion == 2}<span id="{$embedTargetId|escape}">{/if}<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="{$width|escape}" height="{$height|escape}">
    <param name="movie" value="{$fileUrl|escape}" />
    {if $params}
    {foreach key=param item=value from=$params}
    <param name="{$param|escape}" value="{$value|escape}" />
    {/foreach}
    {/if}
    {if $variables}
    <param name="flashVars" value="{foreach key=variable item=value from=$variables name=variables}{$variable|escape}={$value|escape:url|escape:html}{if !$smarty.foreach.variables.last}&amp;{/if}{/foreach}" />
    {/if}
    <!--[if !IE]>-->
    <object type="application/x-shockwave-flash" data="{$fileUrl|escape}" width="{$width|escape}" height="{$height|escape}">
    {if $params}
    {foreach key=param item=value from=$params}
    <param name="{$param|escape}" value="{$value|escape}" />
    {/foreach}
    {/if}
    {if $variables}
    <param name="flashVars" value="{foreach key=variable item=value from=$variables name=variables}{$variable|escape}={$value|escape:url|escape:html}{if !$smarty.foreach.variables.last}&amp;{/if}{/foreach}" />
    {/if}
    <!--<![endif]-->
        {if $altFlashContent}{$altFlashContent}{elseif $getFlashImage}{strip}
        <span class="getFlashImage">
        {assign var=g value=$getFlashImage}
            <a href="http://www.adobe.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash">
            <img src="{$g.file|escape}" alt="" style="width: {$g.width|escape}px; height: {$g.height|escape}px; {if $g.topBorder || $g.rightBorder || $g.bottomBorder || $g.leftBorder} border-style: solid; border-color: {$g.borderColor|escape}; border-width: {$g.topBorder|escape}px {$g.rightBorder|escape}px {$g.bottomBorder|escape}px {$g.leftBorder|escape}px;{/if}" />
            </a>
        </span>
        {/strip}{/if}
    <!--[if !IE]>-->
    </object>
    <!--<![endif]-->
</object>{if $instance->swfobjectVersion == 2}</span>{/if}</{$containerTag}>
{/strip}
<script type="text/javascript">
// <![CDATA[
{if $instance->swfobjectVersion == 2}
    {literal}
    var flashvars = {};
    var params = {};
    var attributes = {{/literal}name: "{$elementId|escape:"javascript"}", id: "{$elementId|escape:"javascript"}"{literal}};
    {/literal}
    {if $bgColor}
        params.bgcolor = "{$bgColor|escape:"javascript"}";
    {/if}
    {if $params}
        {foreach key=param item=value from=$params}
        params.{$param|escape:'javascript'} = "{$value|escape:'javascript'}";
        {/foreach}
    {/if}
    {if $variables}
        {foreach key=variable item=value from=$variables}
        flashvars.{$variable|escape:'javascript'} = "{$value|escape:'url'|escape:'javascript'}";
        {/foreach}
    {/if}
    swfobject.embedSWF("{$fileUrl|escape:"javascript"}", "{$embedTargetId|escape:"javascript"}", "{$width|escape:"javascript"}", "{$height|escape:"javascript"}", "{$version|escape:"javascript"}",{if $useExpressInstall}"{$expressInstallUrl|escape:'javascript'}"{else}null{/if}, flashvars, params, attributes);
{else}
    var so = new SWFObject("{$fileUrl|escape:"javascript"}", "{$elementId|escape:"javascript"}", "{$width|escape:"javascript"}", "{$height|escape:"javascript"}", "{$version|escape:"javascript"}"{if $bgColor}, "{$bgColor|escape:"javascript"}"{/if});
    {if $params}
        {foreach key=param item=value from=$params}
        so.addParam("{$param|escape:'javascript'}", "{$value|escape:'javascript'}");
        {/foreach}
    {/if}
    {if $useExpressInstall}
        so.useExpressInstall("{$expressInstallUrl|escape:'javascript'}");
    {/if}
    {if $variables}
        {foreach key=variable item=value from=$variables}
        so.addVariable("{$variable|escape:'javascript'}", "{$value|escape:'url'|escape:'javascript'}");
        {/foreach}
    {/if}

    so.write("{$embedTargetId|escape:'javascript'}");
{/if}
// ]]>
</script>