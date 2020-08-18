<div class="templateField"><label for="file">{alias context="admin:contentObjects" code=file}:</label>
    <input type="file" id="file" name="file" value="" />
</div>
{if $id}
    <div class="col1">{alias context="admin:contentObjects" code=file_info}: <a href="{$data.file_www|escape}" target="_blank">{$data.file_name|escape}</a> [{$data.file_size|escape}]</div>
{/if}

{if $options.watermark_image}
    <div id="watermarkRow" {if $data.extension!='jpg' && $data.extension!='jpeg'}style="display:none"{/if}>
    <div class="col1">Add watermark:</div>
    <div><input type="checkbox" name="watermark" value="1" /></div>
    </div>
{/if}

<div id="resizediv" {if $data.extension!='jpg' && $data.extension!='jpeg'}style="display:none"{/if}>
    <div class="templateField"><label for="resize">{alias context="admin:contentObjects" code=resize_image}:</label>
        <select id="resize" name="resize">
            <option value="">-no resize-</option>
        {html_options values=$options.resize output=$options.resize selected=$data.extra_info.resize}
        </select>
    </div>
    {if !$smarty.const.VERSION}
    <div id="thumbnaildiv" {if $data.extension!='jpg' && $data.extension!='jpeg'}style="display:none"{/if}>
    <div class="col1">Make thumbnail:</div>
    <div>
    <select name="thumbnail">
        <option value="">-no thumbnail-</option>
    {html_options values=$options.thumbnail output=$options.thumbnail selected=$data.extra_info.thumbnail_size}
    </select>
    </div>
    {/if}
</div>

{if $id}
    {if $file_types[$data.extension]=='image'}
        
        <div class="templateField"><label for="altText">{alias context="admin:contentObjects" code=altText}:</label>
            <input type="text" id="altText" name="altText" value="{$data.extra_info.altText}" />
        </div>        
        
        <div>
            {if $data.extra_info.thumbnail_size}
                <div class="image"><img src="{$_config.files_www|escape}thumb_{$data.file_name|escape}?unique={"rnd"|uniqid|escape}" alt="{$data.path_name|escape}" /></div>
            {/if}
            <div class="image"><img src="{$data.file_www|escape}?unique={"rnd"|uniqid}" alt="{$data.path_name|escape}" /></div>
        </div>
    {/if}
{/if}