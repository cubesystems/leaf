<form class="simpleForm {$type}Form" method="post" action="{$action|escape}">
	<input type="hidden" name="redirectUrl" value="{if $redirectUrl}{$redirectUrl|escape}{else}{request_url}{/if}" />
	{foreach from=$fields key=fieldName item=fieldValue}
	<input type="hidden" name="{$fieldName|escape}" value="{$fieldValue|escape}" />
	{/foreach}

	{$content}

	{if $confirmation.show}
	<div class="confirmation">{if !is_null($confirmation.text)}{$confirmation.text|escape}{else}{alias code=$confirmation.alias context=$confirmation.context}{/if}</div>
	{/if}


	{if $button.show}
	{capture assign=buttonTextEscaped}{if !is_null($button.text)}{$button.text|escape}{else}{alias context=$button.context code=$button.alias}{/if}{/capture}
		{if !is_null($button.image)}
		<input class="simpleFormImageBtn" type="image" name="submit" src="{$button.image|escape}" alt="{$buttonTextEscaped}" title="{$buttonTextEscaped}" />
		{else}
		<input type="submit" value="{$buttonTextEscaped}" name="submit" />
		{/if}
	{/if}

</form>