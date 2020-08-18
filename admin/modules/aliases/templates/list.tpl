{strip}
	<div id="objectsSidebar">
		<div class="field filterWrap special-field-wrap searchContainer">
			<form class="searchForm" method="get" action="{$_module->getModuleUrl()|escape}">
				<input type="hidden" name="module" value="aliases" />
				<input type="hidden" name="do" value="searchAliases" />
				<input type="text" autocomplete="off" name="filter" class="search filter untouched labelVisible {if !isset($smarty.get.id) || $smarty.get.id}autofocus{/if}" value="{$_module->options.filter|escape}" />
					<button type="submit" class="noStyling">
						<img src="images/icons/magnifier.png" alt="{alias code=search}" />
					</button>
			</form>
		</div>

		<div class="actionsContainer footer">
			<div class="padding">

				<form method="post" action="{$_module->getModuleUrl()|escape}" class="refreshForm">
					<input type="hidden" name="action" value="updateAliases" />
					<button type="submit" title="{alias code=renew_standart_module_translations}">
						<img src="images/icons/arrow_refresh.png" alt="{alias code=renew_standart_module_translations}"/>
						&nbsp;
					</button>
				</form>

				<div class="groupFilterBox">

				    <div class="groupFilter">

				        <h4>{alias code=groupFilterHeading}</h4>
                        <div class="filterSection categoryFilterBox">

                            {foreach item=category from=$_module->options.categories}

                            <div class="optionBox">
                                <label>
                                    <input type="checkbox" class="categoryFilter" value="{$category|escape}"{if in_array($category,$_module->options.visibleCategories)} checked="checked"{/if} />
                                    {capture assign=aliasCode}groupFilter{$category|ucfirst}{/capture}
                                    {alias code=$aliasCode}
                                </label>
                            </div>

                            {/foreach}

                            <div class="clear"><!-- --></div>

                        </div>


                        <div class="filterSection incompleteFilterBox">


                        <div class="optionBox onlyIncompleteBox">
                            <label class="onlyIncompleteBox">
                            <input type="checkbox" id="onlyIncomplete" {if $_module->options.incomplete} checked="checked"{/if}/>{alias code=onlyIncompleteGroups}</label>
                        </div>


						<div class="incompleteLanguages{if !$_module->options.incomplete} disabled{/if}">

						    {foreach item=language from=$_module->options.languages}
                            <div class="optionBox languageBox">
                            <label>
						      <input type="checkbox" class="incompleteLanguage" value="{$language|escape}" {if in_array($language,$_module->options.incompleteLanguages)} checked="checked"{/if}{if !$_module->options.incomplete} disabled="disabled"{/if} />

						      {$language|escape}
                            </label>
                            </div>


						    {/foreach}
                             <div class="clear"><!-- --></div>

						</div>

						</div>
				    </div>

                    <button type="button" class="groupFilterButton" title="{alias code=filterVisibleGroups}">
                        <img src="images/icons/eye.png" alt="" />
                    </button>
                </div>

				<form method="get" action="./" class="newItemForm">
					{foreach from=$_module->moduleUrlParts key=varKey item=varValue}
						<input type="hidden" name="{$varKey|escape}" value="{$varValue|escape}" />
					{/foreach}
					<input type="hidden" name="do" value="edit" />
					<input type="hidden" name="id" value="0" />
					<button type="submit" class="iconAndText">
						<img src="images/icons/add.png" alt=""/>
						{alias code=new_group}
					</button>
				</form>
			</div>
		</div>

		<div class="menuContainer">
			<div class="inner">
				<div class="groupContainer collapse">
					<div class="sectionTitle aliasGroupsHeader">
                    {alias code=groups}

                    {*
                    <div class="incompleteFilterBox">



                        <div class="incompleteFilterButtonWrap{if !$_module->options.incomplete} incompleteInactive{/if}">

                        <input type="hidden" id="incompleteLanguageAnyLabel" value="{alias code=anyLanguage}" />

                        <span class="incompleteFilterButton"><span class="label incompleteFilterButtonLabel">


                        {if $_module->options.incomplete == 'any' || (count($_module->options.incompleteLanguages) == count($_module->options.languages))}
                        {alias code=anyLanguage}
                        {elseif empty($_module->options.incompleteLanguages)}
                        -
                        {else}
                        {foreach item=incompleteLanguage from=$_module->options.incompleteLanguages name=incompleteLanguages}
                        {$incompleteLanguage|mb_strtoupper|escape}{if !$smarty.foreach.incompleteLanguages.last}, {/if}
                        {/foreach}
                        {/if}

                        </span><img alt="" src="images/expandTool/closeHoverShim.png" /></span>

						<div class="incompleteLanguages collapse">

						    {foreach item=language from=$_module->options.languages}
                            <div class="languageBox">
						    <label>
						      <input type="checkbox" class="incompleteLanguage" value="{$language|escape}" {if in_array($language,$_module->options.incompleteLanguages)} checked="checked"{/if} />
						      {$language|escape}
                            </label>
                            </div>


						    {/foreach}


						</div>

                        </div>


                    </div>

                    *}

                    <div class="clear"><!-- --></div>

					</div>

					<ul id="groupList" class="block groupList">

					{assign var=numberOfVisibleLanguages value=$_module->options.visibleLanguages|@count}
						{foreach from=$_module->options.groups item=group key=groupId}

						    {assign var=incompleteLanguages value=$_module->options.incompleteGroups.$groupId}

							<li class="{if $group.id == $smarty.get.id}active {/if}{if empty($group.context)}noContext{/if}{if !empty($incompleteLanguages)} incomplete{if count($incompleteLanguages) == $numberOfVisibleLanguages} incomplete-all{/if}{foreach item=langCode from=$incompleteLanguages} incomplete-{$langCode|escape}{/foreach}{/if} category-{$group.category|escape}">
								<a href="{$_module->getModuleUrl()|escape}&amp;do=edit&amp;id={$group.id|escape}">
									<span class="name">{$group.name|escape}</span>
									<span class="context">
										{if empty($group.context)}
											<em>{alias code=noContext}</em>
										{else}
											{$group.context|escape}
										{/if}
									</span>
								</a>
							</li>
						{/foreach}
						<li class="showAll" style="display:none;" data-alias="{alias code=showAllGroups}">
							<div class="a">
								{alias code=showAllGroups}
							</div>
						</li>
					</ul>
				</div>
				<div class="aliasContainer"></div>
			</div>
		</div>
	</div>
{/strip}
