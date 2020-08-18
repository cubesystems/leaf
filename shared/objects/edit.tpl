{alias_context code="admin:contentObjects"}
{if $error_code}
	<div style="color:#f00; font-size:130%; font-weight:bold; text-align:center; margin:100px 0 0 0; ">
		{if $error_code == "unallowed template"}
			<span title="{if $parent}{$parent->object_data.template|escape} -&gt; {/if}{$_object->object_data.template|escape}">{alias code=template_not_allowed}</span>
		{else}
            {alias code=unknown_error}
		{/if}
	</div>
{else}
	<form enctype="multipart/form-data" method="post" action="{$_object->save_url|escape}" id="objectEditForm" class="editForm {if $_object->areSnapshotsEnabled()}snapshotsEnabled{/if}{if $smarty.cookies.showContentNodeRelationPanel} showRelationPanel{/if}">
		<div class="content noShadow">
			{if $_object->object_data.id == 0}
				<input type="hidden" name="parent_id" value="{$_object->object_data.parent_id|escape}" />
				<input type="hidden" name="_leaf_object_type" value="{$_object->object_data.type|escape}" />
				{if !empty($smarty.get.seedRelationId)}
					<input type="hidden" name="seedRelationId" value="{$smarty.get.seedRelationId|escape}"/>
				{/if}
			{/if}
			<div class="globalFieldContainer collapsableSection {if $smarty.cookies.colapseSection_globalFieldContainer}collapsed{/if}">
				<div class="templateField objectNameContainer nameWrap">
					<div class="labelWrap">
						<label for="name" title="ID: {$_object->object_data.id|escape}">
							{alias context="admin:contentObjects" code=name}:
						</label>
					</div>
					<input id="name" name="name" type="text" value="{$_object->object_data.name|escape:'html'}" />
				</div>
				{if $_object->object_type == 22}					
					<div class="templateField templateWrap">
						<div class="labelWrap">
							<label for="template" title="{$template|escape}">{alias context="admin:contentObjects" code=template}:</label>
						</div>
						{if $_object->_config.change_templates}
							<select id="template" name="template" onchange="change_template(this)" title="{$template|escape}">
								{html_options options=$templates selected=$template}
							</select>
						{else}						
							{$templates[$template]}
							<input type="hidden" id="template" name="template" value="{$template|escape}" />
						{/if}
					</div>
					
				{/if}
                
                {if $_object->areSnapshotsEnabled()}
                    
                {assign var=snapshots value=$_object->getSnapshots()}
                {if $snapshots && count($snapshots)}
                <div class="templateField snapshotsWrap">
                    <div class="labelWrap">
                        <label for="snapshot">{alias context="admin:contentObjects" code=snapshot}:</label>
                    </div>

                    {assign var=currentSnapshot value=$_object->getCurrentSnapshot()}
                    <select id="snapshot" name="snapshot" onchange="loadSnapshot(this)">
                        {foreach item=snapshot from=$snapshots name=snapshots}
                            
                            {if (($_object->loadedSnapshot) && ($_object->loadedSnapshot->id == $snapshot->id))}
                                {assign var=isSelectedSnapshot value=true}
                            {else}
                                {assign var=isSelectedSnapshot value=false}
                            {/if}
                            
                            {if $currentSnapshot && $currentSnapshot->id == $snapshot->id}
                                {assign var=isCurrentSnapshot value=true}
                            {else}
                                {assign var=isCurrentSnapshot value=false}
                            {/if}
                            
                            <option value="{$snapshot->id|escape}"{if $isSelectedSnapshot} selected="selected"{/if}>{if $smarty.foreach.snapshots.first}{alias code=latestSnapshot}{else}{$snapshot->createdAt|escape}{/if}</option>
                        {/foreach}
                    </select>

                </div>
                {/if}
                
                {/if}
                    
				<div class="clear"></div>
				<div class="templateField rewriteNameWrap">
					<div class="labelWrap">
						<label for="rewrite_name">{alias context="admin:contentObjects" code=rewrite_name}:</label>
					</div>
					<input type="text" id="rewrite_name" onchange="updateUrlPart()" name="rewrite_name" value="{$rewrite_name|escape}" />
					<button type="button" onclick="suggest_rewrite()" class="noStyling" title="{alias code=suggest_rewrite context='admin:contentObjects'}">
						<img src="images/icons/keyboard.png" alt="{alias code=suggest_rewrite context='admin:contentObjects'}"/>
					</button>
					<img class="duplicateRewriteNameWarning" src="images/icons/warning.png" alt="{alias code=duplicateRewriteName context=validation}" title="{alias code=duplicateRewriteName context=validation}" />
					<div class="objectUrlPartContainer">
						{if $parentUrl}
							{strip}

                                {assign var=urlParts value=$parentUrl|@parse_url}
								<a href="{$parentUrl|escape}{if $rewrite_name}{$rewrite_name|escape}{elseif $id}{$id|escape}{/if}/">

								    {if $urlParts.scheme && $urlParts.host}
								    <span class="host">{$urlParts.scheme|escape}://{$urlParts.host|escape}</span>
								    {/if}
									<span class="ancestor">{$urlParts.path|escape}</span>
									<span class="hidden" style="display:none;">/../</span>
									<span class="objectUrlPart">{if $rewrite_name}{$rewrite_name|escape}{elseif $id}{$id|escape}{/if}</span>/
								</a>

							{/strip}
						{/if}
					</div>
				</div>
				<div class="templateField orderNoWrap">
					<div class="labelWrap">
						<label for="order_nr">{alias context="admin:contentObjects" code=order}:</label>
					</div>
					<select id="order_nr" name="order_nr">
						{foreach item = option key = key from = $order_select}
							<option value="{$key|escape}" {if $order_nr==$key} selected="selected"{/if}>
								{alias context="admin:contentObjects" code=$option.alias} {$option.name|mb_truncate:"UTF8":88:"..."|escape:"html"}
							</option>
						{/foreach}
					</select>
				</div>

				<div class="clear"></div>

				<div class="sideLabel visibleWrap">
					<input
						onchange="document.getElementById('visible').value=(this.checked ? 1 : 0)"
						name="visible_box" id="visible_box" type="checkbox" value="1"
						{if $visible || $_object->new_object} checked="checked"{/if}
					/>
					<input id="visible" type="hidden" name="visible" value="{if $_object->new_object}1{else}{$visible|escape}{/if}" />
					<label for="visible_box">{alias context="admin:contentObjects" code=visible}</label>
				</div>
				<div class="sideLabel protectedWrap">
					<input
						onchange="document.getElementById('protected').value=(this.checked ? 1 : 0)"
						name="protected_box" id="protected_box" type="checkbox" value="1"
						{if $protected} checked="checked"{/if}
					/>
					<input id="protected" type="hidden" name="protected" value="{$protected|intval}" />
					<label for="protected_box">{alias context="admin:contentObjects" code=protected}</label>
				</div>

				<button class="noStyling toggleSection collapse" type="button" title="{alias code=collapseSection}">
					<img src="images/icons/130.png" alt="{alias code=collapseSection}" />
				</button>
				<button class="noStyling toggleSection expand" type="button" title="{alias code=expandSection}">
					<img src="images/icons/129.png" alt="{alias code=expandSection}" />
				</button>


				<div class="clear persistent"></div>

			</div>



			{include file = edit.tpl}


		</div>

		<div class="content relationsPanel" data-confirmCopy="{alias code=confirmCopy}">
			<div class="panelTitle">{alias code=relatedNodes}</div>
			<div class="body">
				<img src="images/loader.gif" class="loader" alt="{alias code=loading}" />
			</div>

		</div>

		<div class="footer center">
			<div class="padding">
				<button type="submit" class="iconAndText">
					<img src="images/icons/disk.png" alt="" />
					{alias code=save}
				</button>

				{if $id}
					<a href="{$_object->object_data.id|orp}" class="button iconAndText" style="margin-left:50px;">
						<img src="images/icons/world_go.png" alt="" />
						ID: {$_object->object_data.id|escape}
					</a>
				{/if}

				<button class="toggleRelationPanelButton noStyling" type="button" title="{alias code=toggleRelationPanel}">
					<img src="images/spacer.png" width="16" height="16"  alt="{alias code=toggleRelationPanel}" />
				</button>

			</div>
		</div>

		<input type="hidden" name="postCompleted" value="1" />
	</form>
	<script type="text/javascript">
	//<![CDATA[
     var objectId = {$id};
	//]]>
	</script>
{/if}
