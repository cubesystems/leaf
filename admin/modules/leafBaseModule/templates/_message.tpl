<div class="message error inverted">
	<div class="inner">
        {assign var=errors value=$processing->getErrors()}
        {foreach item=error from=$errors}
        
        <p>
		{if $error.message}
            {$error.message|escape}
        {else}
            {alias code=$error.code context="validation"}
        {/if}
        </p>
        
        {/foreach}
	</div>
</div>