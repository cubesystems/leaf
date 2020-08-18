{alias_context code="admin:richtextImageDialog"}
<div class="richtextImageDialog" style="display: none;">
	<div class="leftPanel">
		<div class="tabs">
			<h3>{alias code=imageSource}</h3>
			<ul>
				<li><a class="uploadFile" href="#uploadFile">{alias code=computer}</a></li>
				<li><a class="enterUrl" href="#enterUrl">{alias code=internet}</a></li>
				<li><a class="chooseFromTree" href="#chooseFromTree">{alias code=contentTree}</a></li>
				<li><a class="search" href="#search">{alias code=search}</a></li>
			</ul>
			<div class="uploadFile">
				<div class="inputWrap">
					<label>{alias code=imageFile}:</label>
					<div class="fileFieldWrap">
						<input type="file" name="file" size="15" />
						<button type="submit">{alias code=upload}</button>
					</div>
					<div class="clear"></div>
				</div>
				<input type="hidden" name="_leaf_object_type" value="21" />
				<input type="hidden" name="objectName" value="someName" />
				<input type="hidden" value="1" name="postCompleted"/>
				{if $item->object_data.id == 0}
					<input type="hidden" name="parent_id" value="{$item->object_data.parent_id}" />
				{else}
					<input type="hidden" name="parent_id" value="{$item->object_data.id}" />
				{/if}
			</div>
			<div class="enterUrl">
				<div class="inputWrap">
					<label>{alias code=imageUrl}:</label>
					<input type="text" name="imageUrl" />
					<div class="clear"></div>
				</div>
			</div>
			<div class="chooseFromTree">
				<div class="inputWrap">
					<label>{alias code=objectId}:</label>
					{input type="objectlink" name="objectId"}
					<div class="clear"></div>
				</div>
			</div>
			<div class="search">
				<div class="inputWrap">
					<label>{alias code=objectName}:</label>
					{input
						type="select" module="content" selectionModel="search"
						name=search id="search`$namespace`"
						creationDialog=false
						searchUrl="?module=content&do=searchFiles&ajax=1&json=1&html=0"
					}
					<div class="clear"></div>
				</div>
			</div>
		</div>
		
		<div class="infoWrap ui-widget-content ui-corner-all">
			<h3>{alias code=imageInfo}</h3>
			<div class="inputWrap">
				<label for="leafMediaImageAlt">{alias code=description}:</label>
				<input type="text" name="imageAlt" id="leafMediaImageAlt" />
				<div class="clear"></div>
			</div>
			<div class="inputWrap">
				<label for="leafMediaImageTitle">{alias code=name}:</label>
				<input type="text" name="imageTitle" id="leafMediaImageTitle" />
				<div class="clear"></div>
			</div>
			<div class="inputWrap">
				<label for="leafMediaImageClass">{alias code=position}:</label>
				<select name="imageClass" id="leafMediaImageClass">
					<option value="">&nbsp;</option>
					<option value="contentImageLeft">{alias code=onTheLeft}</option>
					<option value="contentImageRight">{alias code=onTheRight}</option>
				</select>
				<div class="clear"></div>
			</div>
		</div>	
	</div>
	
	<div class="previewWrap ui-widget-content ui-corner-all">
		<h3>{alias code=preview}</h3>
		<div class="imageWrap noImage">
			<img src="" class="preview" alt=""/>
		</div>
	</div>
	
	<div class="clear"></div>
	
	<div class="insertWrap">
		<button type="button" class="insertButton">{alias code=insert}</button>
	</div>
</div>
