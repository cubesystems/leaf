// preload html
if( jQuery )
{
	jQuery( window ).load(function()
	{
		richtextImageDialog.loadHtml();
	});
}

// constructor
var richtextImageDialog = function( editorOrContext )
{
	// save reference to self
	var self = this;
	var cls  = richtextImageDialog;
	// check dependencies
	if( self.checkDependencies() == false )
	{
		return false;
	}
	// context
	if( editorOrContext instanceof jQuery )
	{
		self.context = 'standalone';
		self.contextElement = editorOrContext;
	}
	else
	{
		self.context = 'richtext';
		// save reference to tinyMce editor
		self.editor = editorOrContext;
	}
	// inherit config
	self.config = cls.config;
	// create dialog
	self.createDialog();
	// create namespaced ids
	self.createNamespacedIds();
	// create tabs
	self.createTabs();
	if( self.context == 'richtext' )
	{
		// create file upload
		self.createFileUpload();
	}
	// attach events
	self.attachEvents();
}
// default config 
richtextImageDialog.config = 
{
	htmlUrl: 	 '?module=content&do=richtextImageDialog&objectId=',
	fileSaveUrl: '?module=content&do=save_object&object_id=0'
};
// static method to load html
richtextImageDialog.loadHtml = function()
{
	var url = new RequestUrl();
	var response = jQuery.ajax
	({
		url: this.config.htmlUrl + url.get( 'object_id' ),
		async: false
	});
	this.prototype.html = response.responseText;
}
// instance specific html loading
richtextImageDialog.prototype.loadHtml = function()
{
	var url = new RequestUrl();
	var response = jQuery.ajax
	({
		url: this.config.htmlUrl + url.get( 'object_id' ),
		async: false
	});
	this.html = response.responseText;
}
// *** initialization methods ***
// dependencies
richtextImageDialog.prototype.checkDependencies = function()
{
	// jQuery
	if( jQuery === undefined )
	{
		console.error( 'Leaf Image dialog requires jQuery' );
		return false;
	}
	// jQuery UI dialog
	if( jQuery('<div></div>').dialog === undefined )
	{
		console.error( 'Leaf Image dialog requires jQuery UI dialog' );
		return false;
	}
	// RequestUrl
	if( RequestUrl === undefined )
	{
		console.error( 'Leaf Image dialog requires RequestUrl' );
		return false;
	}
}
// dialog
richtextImageDialog.prototype.createDialog = function()
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
		console.error( 'richtextImageDialog: Html load failed.' );
		return false;
	}
	// create dialog
	var dialog = self.dialog = jQuery( self.html ).appendTo( document.body );
	
	// strip out upload and url tabs in standalone context
	if( self.context == 'standalone' )
	{
		self.dialog.find( '.uploadFile' ).remove();
		self.dialog.find( '.enterUrl' ).remove();
	}
	
	dialog.dialog
	({
		width: 800,
		height: 'auto',
		title: 'Insert Image',
		dialogClass: 'leafDialog leafMediaDialog visibleOverflow', 
		resizable: false
	});
	// create shortcuts for elements
	self.previewImage 		= dialog.find( '.previewWrap img' );
	self.previewImageWrap 	= dialog.find( '.previewWrap .imageWrap' );
	self.altInput 			= dialog.find( 'input[name=imageAlt]' );
	self.titleInput 		= dialog.find( 'input[name=imageTitle]' );
	self.classSelect 		= dialog.find( 'select[name=imageClass]' );
	
	self.attributeElements = jQuery( [ self.altInput[0], self.titleInput[0], self.classSelect[0] ] );
	
	self.urlInput  = dialog.find( 'input[name=imageUrl]' );
	self.idInput   = dialog.find( 'input[name=objectId]' );
	self.fileInput = dialog.find( 'input[name=file]' );
	
	// create search field
	setTimeout(function()
	{
		var select = new ObjectSelect( dialog.find( '.input.selectWrap' )[0] );
	},0);
}
// namespaced ids
richtextImageDialog.prototype.createNamespacedIds = function()
{
	// save reference to self
	var self = this;
	// create non-conflicting ids for labels and inputs
	var namespace = self.namespace = 'a' + String((new Date()).getTime()).replace(/\D/gi,'') + '-';
	self.dialog.find( '.inputWrap' ).each(function()
	{
		var label = jQuery( this ).find( 'label' );
		var input = jQuery( this ).find( 'input, select' );
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
richtextImageDialog.prototype.createTabs = function()
{
	// save reference to self
	var self = this;
	// create tabs
	self.dialog.find( '.tabs' ).tabs();
	// setup variables
	self.tabSettings = 
	{
		uploadFile:  	{},
		enterUrl:  		{},
		chooseFromTree: {},
		search: 		{}
	};
	self.currentTabName = 'uploadFile';
	// attach tab events
	self.dialog.find( '.tabs' ).bind( 'tabsselect', function( event, ui ) 
	{
		self.currentTabName = ui.tab.className.split(' ')[0];
		self.updatePreview( self.tabSettings[ self.currentTabName ] );
		self.lastTabsSelectUi = ui;
	});
	self.dialog.find( '.tabs' ).bind( 'tabsshow', function( event, ui ) 
	{
		self.dialog.find( '.tabs  > .' + self.currentTabName + ' input' ).focus();
	});
}
// file upload
richtextImageDialog.prototype.createFileUpload = function()
{
	// save reference to self
	var self = this;
	// change file input field name
	self.dialog.find( 'input[name=objectName]' ).attr( 'name', 'name' );
	// create iframe
	var uploadIframeId = self.namespace + 'uploadIframe';
	var uploadIframe = self.dialog.uploadIframe = jQuery( '<iframe />' );
	uploadIframe.hide();
	uploadIframe[0].id   = uploadIframeId;
	uploadIframe[0].name = uploadIframeId;
	uploadIframe.appendTo( document.body );
	// create form
	var uploadForm = self.dialog.uploadForm = self.dialog.find( '.tabs > .uploadFile' ).wrap( '<form />' ).parent();
	uploadForm.addClass( 'uploadForm' );
	uploadForm[0].action 	= self.config.fileSaveUrl;
	uploadForm[0].method 	= 'post';
	uploadForm[0].enctype 	= 'multipart/form-data';
	uploadForm[0].encoding 	= 'multipart/form-data';
	uploadForm[0].target 	= uploadIframeId;
	// attach event upon file change
	self.dialog.find( 'input[name=file]' ).change(function()
	{
		var fileName = this.value.replace(/(c:\\)*fakepath\\/i, '');
		self.dialog.find( 'input[name=name]' ).val( fileName );
	});
	// attach submit event
	uploadForm.submit(function()
	{
		uploadForm.addClass( 'loading' );
		uploadIframe.load(function()
		{
			uploadForm.removeClass( 'loading' );
			if( uploadIframe[0].contentDocument )
			{
				var body = uploadIframe[0].contentDocument.body;
			}
			else // ie
			{
				var body = window.frames[ uploadIframeId ].document.body;
			}
			var loadedFormAction = jQuery( body ).find( '#objectEditForm' ).attr( 'action' );
			var url = new RequestUrl( loadedFormAction );
			self.idInput.val( url.get( 'object_id' ) );
			self.dialog.find( '.tabs' ).tabs( 'select', 2 );
			self.idInput.change();
			self.fileInput.val( '' );
		});
	});
}
// attach events
richtextImageDialog.prototype.attachEvents = function()
{
	// save reference to self
	var self = this;
	// attach event upon alt, title and class change
	self.attributeElements.keyup(function(){ jQuery( this ).change() });
	self.attributeElements.change( function()
	{ 
		self.tabSettings[ self.currentTabName ][ this.name ] = this.value;
	});
	// attach event upon url change
	self.urlInput.keyup(function(){ jQuery( this ).change() });
	self.urlInput.change(function()
	{
		self.tabSettings.enterUrl.src = this.value;
		self.updatePreview( self.tabSettings.enterUrl );
	});
	// attach event upon objectlink change
	self.idInput.keyup(function(){ jQuery( this ).change() });
	self.idInput.change(function()
	{
		jQuery.ajax
		({
			url: '?module=content&do=getObjectData&id=' + this.value,
			dataType: 'json',
			success: function( json )
			{
				// store settings
				self.tabSettings.chooseFromTree = json;
				self.tabSettings.chooseFromTree.imageAlt   = json.name;
				self.tabSettings.chooseFromTree.imageTitle = json.name;
				// apply them
				self.updatePreview( self.tabSettings.chooseFromTree );
			}
		});
	});
	
	// attach image insertion event to button
	self.dialog.find( '.insertButton' ).click(function()
	{
		if( self.previewImage.attr( 'src' ) )
		{
			var img = jQuery( '<img />' );
			img.attr( 'src', 	self.previewImage.attr( 'src' ) );
			img.attr( 'alt', 	self.altInput.val() 			);
			img.attr( 'title', 	self.titleInput.val() 			);
			// remove possible classes
			for( var i = 0; i < self.classSelect[0].options.length; i++ )
			{
				img.removeClass( self.classSelect[0].options[i].value );
			}
			// add selected class
			img.addClass( self.classSelect.val() );
			// mark image if it came from content
			if( self.currentTabName == 'chooseFromTree' )
			{
				img.addClass( 'fromTree' );
				img.addClass( 'id-' + self.idInput.val() );
			}
			switch( self.context )
			{
				case 'standalone':
					var imageData = self.tabSettings[ self.currentTabName ];
					var targetElement = self.contextElement.parents( 'form' ).find( 'input[name="' + self.contextElement.attr( 'data-for' ) + '"]' );
					targetElement.val( self.idInput.val() );
					targetElement.focus();
					targetElement.trigger( 'change', [ imageData ] ); // trigger change event
				break;
				case 'richtext':
					// resore selection
					if( tinymce.isIE )
					{
						self.editor.selection.moveToBookmark( self.editorSelectionBookmark );
					}
					// insert html
					self.editor.execCommand( 'mceInsertContent', false, img.wrap( '<span />' ).parent().html(),{skip_undo : 1} );
				break;
				
			}
		}
		self.dialog.dialog( 'close' );
	});
	
	self.dialog.bind( 'itemselect', function( event, values, node )
	{
		self.currentTabName = 'chooseFromTree';
		self.idInput.val( values.id );
		self.idInput.change();
	});
}
// open dialog
richtextImageDialog.prototype.openDialog = function( bookmark )
{
	// save reference to self
	var self = this;
	// save editor caret position
	if( tinymce.isIE && bookmark )
	{
		self.editorSelectionBookmark = bookmark;
	}
	// open dialog
	self.dialog.dialog( 'open' );
	// fillout data from selection
	if( self.editor.selection.getNode().nodeName == 'IMG' )
	{
		var image = jQuery( self.editor.selection.getNode() );
		var data = 
		{
			src: 		image.attr( 'src' ),
			imageAlt: 	image.attr( 'alt' ),
			imageTitle: image.attr( 'title' )
		};
		if( image.hasClass( 'fromTree' ) )
		{
			var switchTo = 'chooseFromTree';
			var index = 2;
			self.idInput.val( self.getValueFromClass( image ) );
			self.urlInput.val( '' );
		}
		else
		{
			var switchTo = 'enterUrl';
			var index = 1;
			self.urlInput.val( data.src );
			self.idInput.val( '' );
		}
		// set class name
		self.classSelect.val( '' )
		var options = self.classSelect[0].options;
		for( var i = 0; i < options.length; i++ )
		{
			if( image.hasClass( options[i].value ) )
			{
				data.imageClass = options[i].value;
			}
		}
		// save data
		self.tabSettings[ switchTo ] = data;
		// force tab selection event
		if( self.dialog.find( '.tabs' ).tabs( 'option', 'selected' ) == index )
		{
			self.dialog.find( '.tabs' ).trigger( 'tabsselect', self.lastTabsSelectUi );
		}
		else
		{
			self.dialog.find( '.tabs' ).tabs( 'select', index );
		}
	}
	// or reset it
	else
	{
		self.tabSettings = 
		{
			uploadFile:  	{},
			enterUrl:  		{},
			chooseFromTree: {},
			search: 		{}
		};
		self.dialog.find( '.tabs' ).tabs( 'select', 0 );
		self.idInput.val( '' );
		self.urlInput.val( '' );
		self.classSelect.val( '' );
	}
}
// preview and data update method
richtextImageDialog.prototype.updatePreview = function( settings )
{
	// save reference to self
	var self = this;
	// src
	if( settings.src !== undefined )
	{
		self.previewImage.attr( 'src', settings.src );
		self.previewImageWrap.removeClass( 'noImage' );
	}
	else
	{
		self.previewImageWrap.addClass( 'noImage' );
	}
	// alt
	if( settings.imageAlt !== undefined )
	{
		self.altInput[0].value = settings.imageAlt;
		self.previewImage.attr( 'alt', settings.imageAlt );
	}
	else
	{
		self.altInput.val( '' );
	}
	// title
	if( settings.imageTitle !== undefined )
	{
		self.titleInput[0].value = settings.imageTitle;
	}
	else
	{
		self.titleInput.val( '' );
	}
	// class
	self.classSelect.val( '' );
	if( settings.imageClass !== undefined )
	{
		self.classSelect.val( settings.imageClass );
	}
}
// info extraction method
richtextImageDialog.prototype.getValueFromClass = function( domNode, prefix, delimiter )
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