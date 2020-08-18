<div class="footer center">
	<div class="padding">
		<div class="stats">
			<span class="total">{$collection->total|number_format:0:".":" "}</span> {alias code="itemsFound"}
		</div>
		<span class="left">
			{if $_module->features.create == true}
				<a class="button createNewItem" href="{$_module->getModuleUrl()}&amp;do=edit&amp;id=0&amp;returnUrl={request_url remove=ajax encode=true}">
					<img src="images/icons/add.png" alt=""/> {alias code='createNewItem'}
				</a>
			{/if}
			<span class="buttonGroup listViewActionSwitcher">
				{strip}
					{if $_module->features.view == true}
					<button type="button" title="{alias code=switchToView}" {if $_module->getListViewAction()=="view"}class="active"{/if} data-action="view">
						<img src="images/icons/eye.png" alt="{alias code=switchToView}"/>
					</button>
                    {/if}
					{if $_module->features.edit == true}
						<button type="button" title="{alias code=switchToEdit}" {if $_module->getListViewAction()=="edit"}class="active"{/if} data-action="edit">
							<img src="images/icons/pencil.png" alt="{alias code=switchToEdit}"/>
						</button>
					{/if}
					{if $_module->features.delete == true}
						<button type="button" title="{alias code=switchToDelete}" {if $_module->getListViewAction()=="confirmDelete"}class="active"{/if} data-action="confirmDelete">
							<img src="images/icons/bin_empty.png" alt="{alias code=switchToDelete}"/>
						</button>
					{/if}
				{/strip}
			</span>
			{include file=$_module->pathTo('all._extras')}
		</span>
		{if $collection->pages > 1 && !$_module->continuousScroll}
			{include file=$_module->useWidget('pageNavigation')}
		{/if}
		&nbsp; {* fixes scroller appearance on .main-panel when there are no pages *}
	</div>
</div>
