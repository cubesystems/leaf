/* global Leaf singleton */
var Leaf = 
{
	config: 
	{
		treeSlideSpeed: 200
	},
	
	createTree: function( config )
	{
		// open dialog
		var dialog = jQuery( '<div>Loading...</div>' );
		jQuery(document.body).append( dialog );
		dialog.dialog
		({
			width: 400,
			height: 400,
			title: 'Insert Link',
			buttons: 
			{
				
			},
			dialogClass: 'leafDialog', 
			//modal: true,
			overlay: 
			{
				'background-color': 'black',
				opacity: '.5'
			},
			close: function()
			{
				//dialog.dialog( 'destroy' ).remove();
			}
		});
		var callback = function( response )
		{
			// load data
			dialog.html( response.responseText );
			// attach selection handler
			dialog.find( '.root' ).bind( 'select', {}, function( event, id )
			{
				dialog.dialog( 'close' );
				// user defined callback
				if( typeof config.callback == 'function' )
				{
					config.callback( id );
				}
			});
		}
		loadXmlHttp
		({
			url: '?module=content&do=linkDialog',
			callback: callback,
			callbackOnCompletedOnly: true
		});
		return dialog;
	},
	
	toggleTreeNode: function( id, domNode, event )
	{
		// stop event
		if( event.preventDefault )
		{
			event.preventDefault();
			event.stopPropagation();
		}
		event.cancelBubble = true;
		//
		var parent = jQuery( domNode ).parent();
		var children = parent.parent().children( '.children:first' );
		if( parent.children( '.expand-tool' ).hasClass( 'open' ) )
		{
			parent.children( '.expand-tool' ).removeClass( 'open' );
			children.slideUp( Leaf.config.treeSlideSpeed );
		}
		else
		{
			parent.children( '.expand-tool' ).addClass( 'open' );
			if( children.hasClass( 'unloaded' ) > 0 )
			{
				var loadingTimeout = setTimeout( function(){ parent.addClass( 'loading' ) }, 200 );
				var callback = function( response )
				{
					clearTimeout( loadingTimeout );
					children.html( response.responseText );
					children.removeClass( 'unloaded' );
					parent.removeClass( 'loading' );
					if( response.responseText == '' )
					{
						parent.addClass( 'no-children' );
					}
					children.hide();
					children.slideDown( Leaf.config.treeSlideSpeed );
				}
				loadXmlHttp
				({
					url: '?module=content&do=getNode&id=' + id,
					callback: callback,
					callbackOnCompletedOnly: true
				});
			}
			else
			{
				children.slideDown( Leaf.config.treeSlideSpeed );
			}
		}
	},

	selectTreeNode: function( domNode, id )
	{
		jQuery( domNode ).parents( '.root:first' ).find( '.node.selected' ).removeClass( 'selected' );
		jQuery( domNode ).addClass( 'selected' );
		jQuery( domNode ).parents( '.root:first' ).trigger( 'select', [ id ] );
	},
	openLinkDialog: function( domNode, event, url )
	{
		// stop event
		if( event.preventDefault )
		{
			event.preventDefault();
		}
		// IE
		else if (jQuery.browser.msie && parseInt(jQuery.browser.version) < 7)
		{
			window.open( url, '', 'scrollbars=no,menubar=no,height=600,width=800,resizable=yes,toolbar=no,location=no,status=no' );
			return false;
		}
		
		// check for existing dialog
		if( domNode.dialog !== undefined )
		{
			domNode.dialog.dialog( 'open' );
		}
		// create tree dialog
		else 
		{
			domNode.dialog = Leaf.createTree
			({
				callback: function( id )
				{
					jQuery( domNode ).siblings( 'input' ).val( id );
					jQuery( domNode ).siblings( 'input' ).change();
				}
			});	
		}
	}
};