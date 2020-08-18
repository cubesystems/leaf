jQuery(document).ready(function()
{
	// make arrays sortable
	jQuery( 'body .templateField.arrayField .array' ).sortable
	({
		handle: '.drag',
		dropOnEmpty: true,
		opacity: .5,
		scroll: true,
		helper: function(event, element)
		{
			// for all richtexts in dragged array
			if( element.find( '.richtextField' ).length > 0 )
			{
				var collection = element.parent('ul').find( 'textarea.' + tinyMCEConfig.editor_selector );
				for( var i = 0; i < collection.length; i++ )
				{
					// find dom nodes
					var textarea = jQuery(collection[i]);
					var id = textarea.attr( 'id' );
					var editor = element.parent('ul').find( '#' + id + '_parent > table' );
					// preserve dimensions
					textarea.width( editor.width() - 8 );
					textarea.height( editor.height() - 7 );
					// unload tiny
					tinyMCE.execCommand( 'mceRemoveControl', false, id );
				}
			}
			// return ghost element
			var clone = element.clone();
			clone.width( element.width() );
			return clone;
		},
		stop: function( e, ui )
		{
			if( ui.item.find( '.richtextField' ).length > 0 )
			{
				// load tiny
				var collection = ui.item.parent('ul').find( 'textarea.' + tinyMCEConfig.editor_selector );
				for( var i = 0; i < collection.length; i++ )
				{
					tinyMCE.execCommand( 'mceAddControl', false, collection[i].id );
				}
			}
		}
	});
	// button hiding
	jQuery( 'body .templateField.arrayField .array' ).bind( 'change', null, function()
	{
		if( jQuery( this ).find( 'li' ).length == 0 )
		{
			jQuery( this ).parent( '.arrayField' ).find( '.secondary-button' ).hide();
		}
		else
		{
			jQuery( this ).parent( '.arrayField' ).find( '.secondary-button' ).show();
		}
	});
	jQuery( 'body .templateField.arrayField .array' ).trigger( 'change' );
});