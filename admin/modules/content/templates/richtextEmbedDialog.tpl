{alias_context code="admin:richtextEmbedDialog"}
<div class="richtextEmbedDialog" style="display: none;">
	<form method="post" action="">
		<div class="inputWrap" style="display: none;">
			<input type="hidden" name="source" value="embedCode"/>
		</div>
		
		<div class="leftPanel">
			<div class="tabs">
				<h3>{alias code=source}</h3>
				<ul>
					<li><a class="embedCode" href="#embedCode">{alias code=embedCode}</a></li>
					{*<li><a class="youtube" href="#youtube">Youtube</a></li>*}
					<li><a class="chooseFromTree" href="#chooseFromTree">{alias code=contentTree}</a></li>
				</ul>
				<div class="embedCode">
					<div class="inputWrap">
						<textarea name="embedCode" cols="40" rows="5"></textarea>
						<div class="clear"></div>
					</div>
				</div>
				{*<div class="youtube">
					<div class="inputWrap">
						<label>Youtube URL:</label>
						<input type="text" name="youtubeUrl" />
						<div class="clear"></div>
					</div>
				</div>*}
				<div class="chooseFromTree">
					<div class="inputWrap">
						<label>{alias code=objectId}:</label>
						{input type="objectlink" name="objectId"}
						<div class="clear"></div>
					</div>
				</div>
			</div>
		</div>
		
		<div class="previewWrap ui-widget-content ui-corner-all">
			<h3>{alias code=preview}</h3>
			<div class="embedWrap noPreview">
				
			</div>
		</div>
		
		<div class="clear"></div>
		
		<div class="insertWrap">
			<button type="submit" class="insertButton">{alias code=insert}</button>
		</div>
	</form>
	
</div>