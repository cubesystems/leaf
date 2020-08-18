{if is_array($field.value)}
    {assign var=currentLat value=$field.value.lat}
    {assign var=currentLng value=$field.value.lng}
{else}
    {assign var=currentLat value=""}
    {assign var=currentLng value=""}
{/if}

{if !empty($field.properties.defaultZoom)}
    {assign var=defaultZoom value=$field.properties.defaultZoom}
{else}
    {assign var=defaultZoom value="13"}
{/if}

{if !empty($field.properties.centerLat)}
    {assign var=centerLat value=$field.properties.centerLat}
{else}
    {assign var=centerLat value="56.94725473000847"}
{/if}

{if !empty($field.properties.centerLng)}
    {assign var=centerLng value=$field.properties.centerLng}
{else}
    {assign var=centerLng value="24.099142639160167"}
{/if}


{if !empty($field.properties.width)}
    {assign var=width value=$field.properties.width}
{else}
    {assign var=width value="600"}
{/if}

{if !empty($field.properties.height)}
    {assign var=height value=$field.properties.height}
{else}
    {assign var=height value="300"}
{/if}
{*
{if (!empty($field.properties.googleMapsKey))}
    {assign var=googleMapsKey value=$field.properties.googleMapsKey}
{elseif $smarty.const.googleMapsKey}
    {assign var=googleMapsKey value=$smarty.const.googleMapsKey}
{else}
    {assign var=googleMapsKey value=false}
{/if}
*}

{*
{if !$googleMapsKey}
    <div> GOOGLE MAPS KEY MISSING. </div>
{else}
*}

<div id="mapCanvas_{$field.input_id|escape}" class="googleMapContainer" style="width:{$width}px;height:{$height}px"></div>
<div id="mapSearchPanel" style="left:{$width-320}px;">
    <input class="address" type="textbox" value="" placeholder="search" id="googleMapSearch_{$field.input_id|escape}">
</div>
<input class="coords" type="text" id="{$field.input_id|escape}" name="{$field.input_name|escape}" value="{$currentLat|escape};{$currentLng|escape}" style="width:{$width-105}px;" /><button type="button" id="googleMapClearButton_{$field.input_id|escape}">clear</button>

<script type="text/javascript">

    var mapField = {ldelim}
        'mapCanvas' : "{$field.input_id|escape:javascript}",
        'vars' :
        {ldelim}
            'centerLat'   :  {$centerLat|escape:javascript},
            'centerLng'   :  {$centerLng|escape:javascript},
            'defaultZoom' :  {$defaultZoom|escape:javascript},
        {rdelim}
    {rdelim};

    {if !empty($currentLat)}
    mapField.vars.lat = '{$currentLat|escape:javascript}';
    {/if}

    {if !empty($currentLng)}
    mapField.vars.lng = '{$currentLng|escape:javascript}';
    {/if}

    if (typeof googleMapPointFields == 'undefined')
    {ldelim}
        var googleMapPointFields = [];
    {rdelim}

    googleMapPointFields[googleMapPointFields.length] = mapField;

</script>

{*{/if}*}