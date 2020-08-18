jQuery(function()
{
	var setPanelLayoutHeight = function()
	{
		jQuery( '.panelLayout' ).height( jQuery( 'html' ).height() - jQuery( '#menu' ).height() );
	}
	setPanelLayoutHeight();
	jQuery( window ).resize( setPanelLayoutHeight );
	
	//alert( jQuery( 'html' ).height() );
});