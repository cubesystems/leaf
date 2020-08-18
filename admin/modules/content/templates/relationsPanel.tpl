{strip}
	{if is_object($relation)}
		<div class="relatedNodes">
			{foreach from=$relation->getPositions() item=position}
				<div class="item {if $position.languageRoot->object_data.id == $activeLanguageRootId}active{/if}">
					{if $position.languageRoot->object_data.id == $activeLanguageRootId}
						<img src="images/activeNodeKnob.png" class="knob" alt=""/>
					{/if}
					<span class="languageRootName">{$position.languageRoot->object_data.rewrite_name|escape}</span>
					&nbsp;
					<span class="links">
						{if $position.relation}
							<a href="{$smarty.const.WWW}?module={$_module|get_class}&amp;do=edit_object&amp;object_id={$position.relation->nodeId}">
								<span>"</span>{$position.relation->getNodeDisplayString()|escape}<span>"</span>
							</a>
						{elseif $relation->getParentNodeIdIn($position.languageRoot->object_data.id) !== NULL}
							<a class="create" href="{$smarty.const.WWW}?module={$_module|get_class}&amp;do=edit_object&amp;object_id=0&amp;parent_id={$relation->getParentNodeIdIn($position.languageRoot->object_data.id)}&amp;seedRelationId={$relation->id}&amp;_leaf_object_type=22&amp;template={$relation->getTemplate()}">
								{alias code=create}
							</a>
							{if $item->object_data.id != 0}
								&nbsp;
								<span class="or">{alias code=or}</span>
								<button type="button" class="copy noStyling" data-targetNodeId="{$relation->getParentNodeIdIn($position.languageRoot->object_data.id)}">{alias code=copy}</button>
								<button type="button" class="linkUp noStyling" title="{alias code=linkExisting}" data-languageRootId="{$position.languageRoot->object_data.id}">
									<img src="images/icons/bullet_connect.png" alt="{alias code=linkExisting}"/>
								</button>
							{/if}
						{else}
							<em>{alias code=noSuitableAncestor}</em>
						{/if}
					</span>
					
				</div>
			{/foreach}
		</div>
	{elseif is_object($item) && $item->canBeInRelation()}
		<div class="createNewGroupWrap">
			<div class="createGroupMessage">
				{alias code=createGroupMessage}
			</div>
			<div class="buttonWrap">
				<button class="createNewGroup" type="button">{alias code=createNewRelationGroup}</button>
			</div>
		</div>
	{else}
		<div class="cannotCreateGroupWrap">{alias code=cannotCreateGroup}</div>
	{/if}
	<div class="linkUpDialogTemplate" style="display:none;">
		{*<button class="closeButton">{alias code=close}</button>*}
		<img src="images/linkUpDialogCloseButton.png" class="closeButton" title="{alias code=close}" alt="{alias code=close}" />
		<div class="dialogContent"></div>
	</div>
{/strip}
