{strip}
	<script type="text/javascript">
	var googleTranslateApiKey = {if $_module->options.googleTranslate.APIKey}'{$_module->options.googleTranslate.APIKey|escape:javascript|escape}'{else}null{/if} ;
	</script>
	<div class="panelLayout module-aliases method-edit">
		<div class="secondaryPanel">
			{include file=list.tpl}
		</div>

		<div class="primaryPanel">

			<div class="visibleLanguagesSwitch">
				<button type="button">
					{alias code=visibleLanguages}
					<img src="images/expandTool/closeHoverShim.png" alt=""/>
				</button>

				<ul class="block">
					{foreach from=$_module->options.languages key=languageId item=languageName}
						<li>
							<label>
								<input type="checkbox" name="{$languageName|escape}" value="{$languageId|escape}" {if $_module->isLanguageVisible($languageId)}checked="checked"{/if}/>
								{$languageName|escape}
							</label>
						</li>
					{/foreach}
				</ul>

			</div>

			<form id="editForm" action="{$_module->getModuleUrl()|escape}" method="post">
				<input type="hidden" name="action" value="save" />
				<input type="hidden" name="id" value="{$id|escape}" />
				<div class="header padding">
					<div class="groupInfo">
						<div class="field">
							<label for="name">{alias code=group}:</label>
							<input type="text" name="name" id="name" value="{$_module->options.groups[$id].name|escape}" {if empty($smarty.get.id)}class="autofocus"{/if} />
						</div>
						<div class="field">
							<label for="context">{alias code=context}:</label>
							<input size="50" type="text" name="context" id="context" value="{$translations.context|escape}" />
						</div>
					</div>

					<div class="aliasesTable aliasesTableHead">
						<div class="th codeHeader">{alias code=name}</div>
						{foreach from=$_module->options.languages key=language_id item=language_name}
							<div class="th languageHeader languageId-{$language_id|escape} languageCode-{$language_name|escape} {if in_array($language_id, $translations.languagesWithMachineTranslations)}hasMachineTranslations{/if} {*{if is_array($expandedLangs) &&  in_array($language_id, $expandedLangs)}expanded{/if}*}" data-languageId="{$language_id|escape}" data-languageCode="{$_module->options.languages.$language_id|escape}"  {if $_module->isLanguageVisible($language_id)==false}style="display:none;"{/if}>

                                <div class="approveColumnButtonBox">
                                    <button type="button" class="noStyling approveColumn" title="{alias code=approveMachineTranslationForColumn}">
                                        <img src="{$smarty.const.WWW|escape}images/icons/tick.png" alt="" />
                                    </button>
                                </div>

								<span class="name">{$language_name|escape}</span>
							</div>
						{/foreach}
					</div>

				</div>
				<div class="content noShadow">
          {include file=translationsTable.tpl translations=$translations.translations languages=$_module->options.languages}

					<table id="nodeT">
						<tr id="cloneme">
							<td class="translation_name codeColumn">
								<input type="text" name="_translations_name" value="" />
								<input type="hidden" name="_translations_type" value="" class="short" />
								<input type="hidden" name="_translations_id" value="" />
							</td>
							{foreach name = languages from=$_module->options.languages key=language_id item=language}
								<td class="translationCell languages col_{$language_id|escape} languageId-{$language_id|escape} languageCode-{$language_name|escape} {*{if is_array($expandedLangs)}{if in_array($language_id, $expandedLangs)} expanded{/if}{/if}*}" data-languageId="{$language_id|escape}" data-languageCode="{$_module->options.languages.$language_id|escape}" {if $_module->isLanguageVisible($language_id)==false}style="display:none;"{/if}>
								  <div class="wrap">
									<input type="text" class="translationText" name="_translations_lang_{$language_id|escape}" value="" />
                  </div>
                  <input type="hidden" class="machineTranslation" name="_translations_lang_{$language_id|escape}_machine" value="0" />
								</td>
							{/foreach}
							<td class="deleteColumn">
								<span style="cursor:pointer;" onclick="removeEntry(this)">
									<img src="images/icons/delete.png" alt="" />
								</span>
							</td>
						</tr>
					</table>

					{if $_module->options.googleTranslate.APIKey}
						<div id="machineTranslationButtonBox" class="machineTranslationButtonBox">
							<button id="machineTranslationButton" class="noStyling machineTranslationButton" type="button" tabindex="-1">
								<img class="normal" src="{$smarty.const.WWW|escape}images/icons/google.gif" alt="" title="{alias code=translateWithGoogle}" />
								<img class="notAvailable" src="{$smarty.const.WWW|escape}images/icons/google-not-available.gif" alt="" title="{alias code=googleTranslateNotAvailable}" />
							</button>
							<div id="machineTranslationSelector" class="machineTranslationSelector">
								<div class="sourceLanguageLabel">
									{alias code=translateFrom}
								</div>
								<div class="sourceLanguages">
									{foreach from=$_module->options.languages key=language_id item=language_name}
										<div class="sourceLanguage sourceLanguage-{$_module->options.languages.$language_id|escape}">
										   <button type="button" class="noStyling" value="{$_module->options.languages.$language_id|escape}">{$language_name|escape}</button>
										</div>
									{/foreach}
								</div>
							</div>
						</div>
						{if !$_module->options.googleTranslate.disableBatchRequests} 
							<div class="machineColumnTranslationButtonBox machineColumnTranslationButtonBoxTemplate">
								<button class="noStyling machineColumnTranslationButton" type="button"  title="{alias code=translateColumnWithGoogle}">
									<img class="normal" src="{$smarty.const.WWW|escape}images/icons/google.gif" alt="" />
								</button>
								<div class="machineColumnTranslationSelector">
									<div class="sourceLanguageLabel">
										{alias code=translateFrom}
									</div>
									<div class="sourceLanguages">
										{foreach from=$_module->options.languages key=language_id item=language_name}
											<div class="sourceLanguage sourceLanguage-{$_module->options.languages.$language_id|escape}">
											   <button type="button" class="noStyling" value="{$_module->options.languages.$language_id|escape}">{$language_name|escape}</button>
											</div>
										{/foreach}
									</div>
								</div>
							</div>
						{/if}
					{/if}
				</div>
				<div class="footer">
					<div class="padding">
						<button type="button" class="{*iconAndText*}" onclick="new_variable()" title="Alt+N" style="padding-left:6px;padding-right:6px;">
							<img src="images/icons/add.png" alt=""/>
							{*{alias code=new_translation}*}
						</button>
						<button class="iconAndText" type="submit" style="margin-left:20px;" >
							<img src="images/icons/disk.png" alt=""/>
							{alias code=save}
						</button>
					</div>
				</div>
			</form>
			{if $id}
				<div id="operationsBox" class="footer operationsBox">
					<div class="padding">
						<form class="deleteForm" data-confirmation="{alias code=deleteConfirmation var_name=$_module->options.groups[$id].name|escape}" action="{$_module->getModuleUrl()|escape}&amp;do=delete" method="post">
							<input type="hidden" name="id" value="{$id|escape}"/>
							<button type="submit" class="iconAndText">
								<img src="images/icons/delete.png" alt="" />
								{alias code=delete}
							</button>
						</form>
						<form method="get" action="./">
							<input type="hidden" name="module" value="aliases" />
							<input type="hidden" name="do" value="export" />
							<input type="hidden" name="id" value="{$id|escape}" />
							<button type="submit" class="iconAndText">
								<img src="images/icons/page_white_put.png" alt="" />
								{alias code=export}
							</button>
						</form>
						<form id="importForm" class="importForm" method="post" action="./?module=aliases" enctype="multipart/form-data" target="translations">
							<input type="hidden" name="action" value="import" />
							<input type="hidden" name="id" value="{$id|escape}" />
							<label for="translations_file">{alias code="import"}:&nbsp;</label>
							<input type="file" class="file" id="translations_file" name="translations_file" size="1" />
							{*<button type="submit" onclick="return importTranslations(this);" > {alias code=import} </button>*}
						</form>
						<iframe width="100%" height="200" name="translations" id="translations" ></iframe>
					</div>
				</div>
			{/if}
		</div>
	</div>
{/strip}