<form class="searchForm" action="">
	{*<span class="loadingMessage">{alias code="loading"}</span>*}
	<input type="hidden" name="module" value="{$_module|get_class|escape}"/>
	<input type="hidden" name="do" value="{if $smarty.get.do}{$smarty.get.do|escape}{else}all{/if}"/>
	<div class="special-field-wrap">
		{strip}
			<input name="search" type="text" class="search focusOnReady"  value="{$smarty.get.search|escape}"/>
			<button type="submit" class="noStyling">
				<img src="images/icons/magnifier.png" alt="{alias code='submit'}" />
			</button>
		{/strip}
	</div>
</form>