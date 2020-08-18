// preload html
if( jQuery )
{
	jQuery( window ).load(function()
	{
		richtextEmbedDialog.loadHtml();
	});
}

// constructor
var richtextEmbedDialog = function( editor )
{
	// save reference to self
	var self = this;
	var cls  = richtextEmbedDialog;
	// check dependencies
	if( self.checkDependencies() == false )
	{
		return false;
	}
	// save reference to tinyMce editor
	self.editor = editor;
	// inherit config
	self.config = cls.config;
	// create dialog
	self.createDialog();
	// create namespaced ids
	self.createNamespacedIds();
	// create tabs
	self.createTabs();
	// attach events
	self.attachEvents();
}
// default config 
richtextEmbedDialog.config = 
{
	htmlUrl: '?module=content&do=richtextEmbedDialog',
	saveUrl: '?module=content&do=saveEmbedObject&id=',
	getUrl:  '?module=content&do=getEmbedObject&id=',
	getObjectHtmlUrl:  '?module=content&do=getObjectHtml&id=',
	placeholderSrc: '/shared/3rdpart/tinymce/plugins/media/img/trans.gif'
};
// static method to load html
richtextEmbedDialog.loadHtml = function()
{
	var response = jQuery.ajax
	({
		url: this.config.htmlUrl,
		async: false
	});
	this.prototype.html = response.responseText;
}
// instance specific html loading
richtextEmbedDialog.prototype.loadHtml = function()
{
	var response = jQuery.ajax
	({
		url: this.config.htmlUrl,
		async: false
	});
	this.html = response.responseText;
}
// *** initialization methods ***
// dependencies
richtextEmbedDialog.prototype.checkDependencies = function()
{
	// jQuery
	if( jQuery === undefined )
	{
		console.error( 'TinyMce\'s Leaf Embed plugin requires jQuery' );
		return false;
	}
	// jQuery UI dialog
	if( jQuery('<div></div>').dialog === undefined )
	{
		console.error( 'TinyMce\'s Leaf Embed plugin requires jQuery UI dialog' );
		return false;
	}
	// RequestUrl
	if( RequestUrl === undefined )
	{
		console.error( 'TinyMce\'s Leaf Embed plugin requires RequestUrl' );
		return false;
	}
}
// dialog
richtextEmbedDialog.prototype.createDialog = function()
{
	// save reference to self
	var self = this;
	// load html
	if( self.html === undefined )
	{
		self.loadHtml();
	}
	if( self.html === undefined )
	{
		console.error( 'richtextEmbedDialog: Html load failed.' );
		return false;
	}
	// create dialog
	var dialog = self.dialog = jQuery( self.html ).appendTo( document.body );
	dialog.dialog
	({
		width: 800,
		height: 'auto',
		title: 'Embed Object',
		dialogClass: 'leafDialog', 
		resizable: false
	});
	// create shortcuts for elements
	self.previewWrap 		= dialog.find( '.previewWrap' );
	self.previewEmbedWrap 	= dialog.find( '.previewWrap .embedWrap' );
	self.embedCodeTextarea 	= dialog.find( 'textarea[name=embedCode]' );
	self.youtubeUrlInput	= dialog.find( 'input[name=youtubeUrl]' );
	self.idInput 		= dialog.find( 'input[name=objectId]' );
}
// namespaced ids
richtextEmbedDialog.prototype.createNamespacedIds = function()
{
	// save reference to self
	var self = this;
	// create non-conflicting ids for labels and inputs
	var namespace = self.namespace = 'a' + String((new Date()).getTime()).replace(/\D/gi,'') + '-';
	self.dialog.find( '.inputWrap' ).each(function()
	{
		var label = jQuery( this ).find( 'label' );
		var input = jQuery( this ).find( 'input, select, textarea' );
		var id = namespace + input.attr( 'name' );
		input.attr( 'id',  id );
		label.attr( 'for', id );
	});
	// create non-conflicting ids for tabs
	self.dialog.find( '.tabs > div' ).each(function()
	{
		var className = this.className;
		this.id = namespace + className;
		jQuery( this ).parents( '.tabs' ).find( 'ul li a.' + className ).attr( 'href', '#' + this.id );
	});
}
// tabs
richtextEmbedDialog.prototype.createTabs = function()
{
	// save reference to self
	var self = this;
	// create tabs
	self.dialog.find( '.tabs' ).tabs();
	// setup variables
	self.tabSettings = 
	{
		embedCode:  	{},
		youtubeUrl:  	{},
		chooseFromTree: {}
	};
	self.currentTabName = 'embedCode';
	// attach tab events
	self.dialog.find( '.tabs' ).bind( 'tabsselect', function( event, ui ) 
	{
		self.currentTabName = ui.tab.className.split(' ')[0];
		self.updatePreview( self.tabSettings[ self.currentTabName ] );
		self.lastTabsSelectUi = ui;
	});
	self.dialog.find( '.tabs' ).bind( 'tabsshow', function( event, ui ) 
	{
		self.dialog.find( '.tabs  > .' + self.currentTabName + ' input, .tabs  > .' + self.currentTabName + ' textarea' ).focus();
		self.dialog.find( 'input[name=source]' ).val( self.currentTabName );
	});
}
// file upload
richtextEmbedDialog.prototype.createFileUpload = function()
{
	// save reference to self
	var self = this;
	
}
// attach events
richtextEmbedDialog.prototype.attachEvents = function()
{
	// save reference to self
	var self = this;
	// attach event upon embed code change
	self.embedCodeTextarea.change(function()
	{
		self.tabSettings.embedCode.html = this.value;
		self.updatePreview( self.tabSettings.embedCode );
	});
	// attach event upon youtube url change
	self.youtubeUrlInput.keyup(function(){ jQuery( this ).change() });
	self.youtubeUrlInput.change(function()
	{
		//self.tabSettings.youtubeUrl = this.value;
		//self.updatePreview( self.tabSettings.youtubeUrl );
	});
	// attach event upon objectlink change
	//self.idInput.keyup(function(){ jQuery( this ).change() });
	self.idInput.change(function()
	{
		jQuery.ajax
		({
			url: self.config.getObjectHtmlUrl + self.idInput.val(),
			success: function( html )
			{
				self.tabSettings.chooseFromTree.html = html;
				// force tab selection event
				var index = 1;
				if( self.dialog.find( '.tabs' ).tabs( 'option', 'selected' ) == index )
				{
					self.dialog.find( '.tabs' ).trigger( 'tabsselect', self.lastTabsSelectUi );
				}
				else
				{
					self.dialog.find( '.tabs' ).tabs( 'select', index );
				}
			}
		});
	});
	
	// attach image insertion event to button
	/* self.dialog.find( '.insertButton' ).click(function()
	{
		self.dialog.find( 'form' ).trigger( 'submit' );
	}); */
	
	self.dialog.find( 'form' ).submit(function( event )
	{
		event.preventDefault();
		
		self.dialog.addClass( 'loading' );
		var id = self.id || 0;
		
		jQuery.ajax
		({
			url: self.config.saveUrl + id,
			data: self.dialog.find( 'form' ).serializeArray(),
			type: 'post',
			dataType: 'json',
			success: function( json )
			{
				if( json.ok )
				{
					self.dialog.removeClass( 'loading' );
					// create placeholder
					var img = jQuery( '<img />' );
					img.attr( 'src', 	self.config.placeholderSrc 	);
					img.attr( 'alt', 	'embed' 					);
					img.attr( 'width',  425 );
					img.attr( 'height', 350 );
					img.addClass( 'mceItem' );
					img.addClass( 'embedObject' );
					img.addClass( 'id-' + json.id );
					// resore selection
					if( tinymce.isIE )
					{
						self.editor.selection.moveToBookmark( self.editorSelectionBookmark );
					}
					// insert html
					self.editor.execCommand( 'mceInsertContent', false, img.wrap( '<span />' ).parent().html() );
					// close dialog
					self.dialog.dialog( 'close' );
				}
			}
		});
	});
}
// open dialog
richtextEmbedDialog.prototype.openDialog = function( bookmark )
{
	// save reference to self
	var self = this;
	if( tinymce.isIE && bookmark )
	{
		self.editorSelectionBookmark = bookmark;
	}
	// open dialog
	self.dialog.dialog( 'open' );
	// fillout data from selection
	if( self.editor.selection.getNode().nodeName == 'IMG' )
	{
		self.dialog.addClass( 'loading' );
		var id = self.getValueFromClass( self.editor.selection.getNode(), 'id' );
		jQuery.ajax
		({
			url: self.config.getUrl + id,
			dataType: 'json',
			success: function( json )
			{
				self.dialog.removeClass( 'loading' );
				if( json.ok )
				{
					self.id = id;
					self.tabSettings.chooseFromTree.html = json.objectHtml;
					self.tabSettings.embedCode.html = json.embedCode;
					self.embedCodeTextarea.val( json.embedCode );
					self.idInput.val( json.objectId );
					
					var index = 0;
					if( json.source == 'chooseFromTree' )
					{
						index = 1;
					}
					// force tab selection event
					if( self.dialog.find( '.tabs' ).tabs( 'option', 'selected' ) == index )
					{
						self.dialog.find( '.tabs' ).trigger( 'tabsselect', self.lastTabsSelectUi );
					}
					else
					{
						self.dialog.find( '.tabs' ).tabs( 'select', index );
					}
					//self.embedCodeTextarea.focus();
					//self.updatePreview( self.tabSettings.embedCode );
				}
			}
		});
	}
	// or reset it
	else
	{
		self.tabSettings = 
		{
			embedCode:  	{},
			youtubeUrl:  	{},
			chooseFromTree: {}
		};
		self.dialog.find( '.tabs' ).tabs( 'select', 0 );
		self.embedCodeTextarea.val( '' );
		self.embedCodeTextarea.focus();
		self.updatePreview( self.tabSettings.embedCode );
		self.youtubeUrlInput.val( '' );
		self.idInput.val( '' );
		self.id = 0;
	}
}
// preview and data update method
richtextEmbedDialog.prototype.updatePreview = function( settings )
{
	// save reference to self
	var self = this;
	// update preview
	var html = ' ';
	if( settings.html )
	{
		html = settings.html;
	}
	if
	(
		html.search( /document\.write/g ) !== -1 // document.write found, unknown bastards :/
	)
	{
		self.previewEmbedWrap.html( 'error - no preview available: embed code uses document.write() ' );
	}
	else
	{
		self.previewEmbedWrap.html( html );
	}
}
// info extraction method
richtextEmbedDialog.prototype.getValueFromClass = function( domNode, prefix, delimiter )
{
	if( prefix === undefined )
	{
		prefix = 'id';
	}
	if( delimiter === undefined )
	{
		delimiter = '-';
	}
	if( domNode instanceof jQuery == true )
	{
		domNode = domNode[0];
	}
	var classNames = domNode.className.split( ' ' );
	for( var i = 0; i < classNames.length; i++ )
	{
		if( classNames[i].indexOf( prefix + delimiter ) === 0 )
		{
			return classNames[i].split( delimiter )[1];
		}
	}
	return false;
}