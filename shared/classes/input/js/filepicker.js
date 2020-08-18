jQuery(function()
{
	jQuery( document ).click(function( event )
	{
		var target = jQuery( event.target );
		if( target.attr( 'tagName' ).toLowerCase() !== 'button' )
		{
			target = target.parents( 'button' );
		}
		if( target.length > 0 && target.attr( 'tagName' ).toLowerCase() == 'button' && target.hasClass( 'filepicker' ) )
		{
			var dialog = new richtextImageDialog( target );
		}
	});
});