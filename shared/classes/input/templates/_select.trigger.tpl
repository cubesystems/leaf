<button type="button" class="trigger noStyling">
	{if $removeButtonImage != true}
		{if $buttonImage}
			<img src="{$buttonImage|escape}" alt="" />
		{else}
			<img src="images/icons/page_white_add.png" alt="" />
		{/if}
	{/if}
	{$buttonText|escape}
</button>