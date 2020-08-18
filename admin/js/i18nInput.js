jQuery(function()
{
	jQuery( '.i18nInput .languageWrap button' ).live( 'click', function()
	{
		var tab = jQuery( this );
		var language = tab.attr( 'data-language' );
		
		var i18nInputs = jQuery( '.i18nInput' );
		i18nInputs.each(function()
		{
			var wrap = jQuery( this );
			wrap.find( '.languageWrap button' ).removeClass( 'active' );
			wrap.find( '.languageWrap button[data-language="' + language + '"]' ).addClass( 'active' );
			var previousTinyMceTable = wrap.find( '.input:visible .mceLayout' );
			var previous =
			{
				tableWidth:   previousTinyMceTable.css( 'width' ),
				tableHeight:  previousTinyMceTable.css( 'height' ),
				iframeWidth:  previousTinyMceTable.find( 'iframe' ).css('width'),
				iframeHeight: previousTinyMceTable.find( 'iframe' ).css('height')
			};
			wrap.find( '.inputWrap .input' ).hide();
			wrap.find( '.inputWrap .input[data-language="' + language + '"]' ).show();
			if( previousTinyMceTable.length > 0 )
			{
				wrap.find( '.input:visible .mceLayout' ).css( 'width', previous.tableWidth );
				wrap.find( '.input:visible .mceLayout' ).css( 'height', previous.tableHeight );
				wrap.find( '.input:visible .mceLayout iframe' ).css( 'width', previous.iframeWidth );
				wrap.find( '.input:visible .mceLayout iframe' ).css( 'height', previous.iframeHeight );
			}
		});
		var wrap = tab.parents( '.i18nInput' );
		// normal input
		wrap.find( '.inputWrap .input[data-language="' + language + '"]' ).focus();
		// textarea
		wrap.find( '.inputWrap .input[data-language="' + language + '"] textarea' ).focus();
		// richtext
		var richtext = wrap.find( '.inputWrap .input[data-language="' + language + '"] textarea:tinymce' );
		if( richtext.length )
		{
			richtext.tinymce().focus();
		}
	});
	// keyboard language navigation
	// TODO: disable autocomplete flickering on ff
	jQuery( '.i18nInput' ).live( 'keyup', function( event )
	{
		var input = jQuery( event.target );
		var wrap = jQuery( this );
		if( ( event.altKey || event.ctrlKey ) && ( event.keyCode == 38 || event.keyCode == 40 ) )
		{
			var active = wrap.find( '.languageWrap button.active' );
			switch( event.keyCode )
			{
				case 38: // up arrow
					active.prev().click();
				break;
				case 40: // down arrow
					active.next().click();
				break;
			}
		}
	});
	// forward keyup events from richtext iframe
	jQuery( 'body' ).bind( 'tinymceinit', function( event )
	{
		var iframe = jQuery( event.target ).find( 'iframe' );
		jQuery( iframe[0].contentDocument ).keyup(function( event )
		{
			iframe.trigger( event );
		});
	});
});