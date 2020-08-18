jQuery( document ).ready(function()
{
    var searchForm  = jQuery( 'form.searchForm' );
	var searchInput = searchForm.find( 'input.search' );
	var searchTimeout;
	var searchRequest;
	var lastSearchQuery = searchInput.val();
    
	// attach keypress event
	searchForm.bind( 'search', function()
	{
		// cancel previous timeout
		clearTimeout( searchTimeout );
		// cancel previous unfinished request
		if( searchRequest !== undefined )
		{
			searchRequest.abort();
		}
		searchTimeout = setTimeout(function()
		{
			// set loading icon
			searchForm.addClass( 'loading' );
            
			// construct url
			var url = new RequestUrl( false );
			url.add( searchForm.serializeArray() );
			//url.add({ ajax: 1 });
			// send request
			searchRequest = jQuery.ajax
			({
				url: url.getUrl(),
				success: function( response )
				{
					searchForm.removeClass( 'loading' );
                    
					var content = jQuery( response );
					jQuery( '.searchResultsBlock' ).html( content.find( '.searchResultsBlock' ).html() );
				}
			});
		}, 800 );
	});
	
	searchInput.keyup( function()
	{
        if( jQuery( this ).val() == lastSearchQuery )
		{
			return;
		}
        
		lastSearchQuery = jQuery( this ).val();
		searchForm.trigger( 'search' );
	});

    
    
    
    jQuery('#content_search').focus();

});