{strip}
<{$containerTag}{if $containerClass} class="{$containerClass|escape}"{/if}{if $containerId} id="{$containerId|escape}"{/if}

{if (($setContainerWidth && $data.extra_info.image_width) || ($setContainerHeight && $data.extra_info.image_height))} style="{if ($setContainerWidth && $data.extra_info.image_width)}width: {$data.extra_info.image_width}px;{/if} {if ($setContainerHeight && $data.extra_info.image_height)}height: {$data.extra_info.image_height}px;{/if}"{/if}>

{if $url}
    <a href="{$url|escape}" {if $target} target="{$target|escape}"{/if}{if $hreflang} hreflang="{$hreflang|escape}"{/if}{if $title} title="{if $titleEscape}{$title|escape}{else}{$title}{/if}"{/if}{if $linkRel} rel="{if $linkRelEscape}{$linkRel|escape}{else}{$linkRel}{/if}"{/if}>
{/if}

{if (($data.extension == "png" || $setSizeAttributes) && ($data.extra_info.image_width) && ($data.extra_info.image_height))}
    {if $tag=="input"}
    {assign var=style value="width: `$data.extra_info.image_width`px; height: `$data.extra_info.image_height`px; `$style`"}
    {else}
    {assign var=sizeAttributes value=" width=\"`$data.extra_info.image_width`\" height=\"`$data.extra_info.image_height`\""}
    {/if}
{else}
    {assign var=sizeAttributes value=''}
{/if}

    <{if $tag==input}input type="image" {if $imageName}name="{$imageName|escape}"{/if} {if $imageValue}value="{$imageValue|escape}"{/if} {else}img{/if} src="{$fileUrl|escape}"{$sizeAttributes} {if !empty($style)}style="{$style}"{/if} alt="{if $altEscape}{$alt|escape}{else}{$alt}{/if}" {if $elementId} id="{$elementId|escape}"{/if} {if $title && !$url} title="{if $titleEscape}{$title|escape}{else}{$title}{/if}"{/if}{if $useMap} usemap="{$useMap|escape}"{/if} />

{if $url}
    </a>
{/if}

</{$containerTag}>
{/strip}