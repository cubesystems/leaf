{alias_context code=$aliasContext}

<div class="geoPointBox{if $version != "flash" && $autoInit === false} no-auto-init{/if}"
    data-version="{$version|escape}"
    
    {if $version != "flash"}
        
    data-key="{$googleMapsKey|escape}"
    {if isset($centerLat)} data-centerLat="{$centerLat|escape}" {/if}
    {if isset($centerLng)} data-centerLng="{$centerLng|escape}" {/if}
    {if isset($defaultZoom)} data-defaultZoom="{$defaultZoom|escape}" {/if}

    {if !empty($lat)}data-lat="{$lat|escape}"{/if}
    {if !empty($lng)}data-lng="{$lng|escape}"{/if}
    
    data-useSearch="{if $useSearch}1{else}0{/if}"
            
    {/if}
>

    
{if $error}
<div class="geoPointError">{literal}{input type="geoPoint"}: {/literal}{alias code=$error}</div>
{else}

    <div class="searchBox"><input type="text" class="search" placeholder="{alias code=searchLocation}" /></div>
    
    {if empty($width)}
        {assign var=width value="600"}
    {/if}

    {if empty($height)}
        {assign var=height value="300"}
    {/if}
    
    
    {if $version == "flash"}
        
    <input type="hidden" id="{$id|escape}" name="{$name|escape}" class="geoPointValue" value="{$lat|escape};{$lng|escape}" />
    <div id="geoPointFlashBox_{$id|escape}" class="geoPointFlashBox"></div>
    <script type="text/javascript">var geoPointUseAntiCache=false;</script>
    <!--[if IE]><script type="text/javascript">geoPointUseAntiCache=true;</script><![endif]-->
    
    {else}
        
    {if empty($nameSuffix)}
        {assign var=nameSuffix value=""}
    {/if}
        
    {if empty($latName)}
        {assign var=latName value="`$name`Lat"}
    {/if}
    
    {if empty($lngName)}
        {assign var=lngName value="`$name`Lng"}
    {/if}    
    
    <input type="hidden" name="{$latName|escape}" class="geoPointValue gePointLat" value="{$lat|escape}" />
    <input type="hidden" name="{$lngName|escape}" class="geoPointValue gePointLng" value="{$lng|escape}" />

    {"https://maps.googleapis.com/maps/api/js?libraries=places&key=`$googleMapsKey`&sensor=false"|_core_add_js}
    <div class="geoPointMapBox" style="width: {$width|escape}px; height: {$height|escape}px;" ></div>
    
    {/if}

    
    {if $version == "flash"}

    <script type="text/javascript">
    // <![CDATA[

        var geoPoint =
        {ldelim}
            version : '{$version|escape:javascript}',

            args :
            {ldelim}
                swfUrl : '{$smarty.const.SHARED_WWW|escape:javascript}/classes/input/images/geoPoint/googlePoint.swf'.concat( (geoPointUseAntiCache) ? '?antiCache={"foo"|uniqid|escape:javascript}' : ''),
                id : 'geoPointFlash_{$field.input_id|escape:javascript}',
                width: '{$width|escape:javascript}',
                height: '{$height|escape:javascript}',                
                version: '9',                
                bgColor: '#FFFFFF'
            {rdelim},
            params : {ldelim} wmode: 'opaque' {rdelim},                
            vars : {ldelim}{rdelim},
            target : 'geoPointFlashBox_{$id|escape:javascript}'    
                
        {rdelim};

        {if isset($centerLat)}
            geoPoint.vars.centerLat = '{$centerLat|escape:javascript}';
        {/if}

        {if isset($centerLng)}
            geoPoint.vars.centerLng = '{$centerLng|escape:javascript}';
        {/if}

        {if isset($defaultZoom)}
            geoPoint.vars.defaultZoom = '{$defaultZoom|escape:javascript}';
        {/if}

    	geoPoint.vars.fieldId = '{$id|escape:javascript}';
    	geoPoint.vars.key = '{$googleMapsKey|escape:javascript}';

        {if !empty($lat)}
        geoPoint.vars.lat = '{$lat|escape:javascript}';
        {/if}

        {if !empty($lng)}
        geoPoint.vars.lng = '{$lng|escape:javascript}';
        {/if}

        if (typeof geoPointFields == 'undefined')
        {ldelim}
            var geoPointFields = [];
        {rdelim}

        geoPointFields[geoPointFields.length] = geoPoint;

    // ]]>
    </script>
    {/if}

    
    <div class="geoPointCoordsPreview">
        
    {if $version == "flash"}
        
        <span class="preview" id="geoPointCoordsPreview_{$id|escape}">{$lat|escape|default:"-"} / {$lng|escape|default:"-"}</span>

    {else}
    
        <button type="button" class="setPoint">{alias code=setPointToCenter}</button>
        <button type="button" class="clearPoint">{alias code=clearPoint}</button>        
        
        <span class="preview">{$lat|escape|default:"-"} / {$lng|escape|default:"-"}</span>
    {/if}
    
    </div>


{/if}
</div>