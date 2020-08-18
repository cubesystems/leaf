jQuery( window ).load(function()
{
	jQuery( '#search' ).focus();
});

jQuery( document ).ready(function()
{
	// copied from desktop.js
	var getValueFromClass = function( domNode, prefix, delimiter )
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
	};
	//-- copied from desktop.js
	
	jQuery( '.errors .leafTable' ).click(function( event )
	{
		var target = jQuery( event.target );
		
		// row collapsing
		if( target.hasClass( 'expandTool' ) )
		{
			var row = target.parents( 'tr:first' );
			// expand
			if( target.hasClass( 'open' ) == false )
			{
				if( row.hasClass( 'loaded' ) == false )
				{
					var url = new RequestUrl();
					url.removeAll(['module']);
					url.add({ 'do': 'loadMessages', 'hash': getValueFromClass( target, 'hash' ) });
					
					//show loader
					var loaderTimeout = setTimeout(function(){ target.addClass( 'loading' ) }, 200);
					
					jQuery.ajax
					({
						url: url.getUrl(),
						dataType: 'json',
						success: function( json )
						{
							// hide loader
							clearTimeout( loaderTimeout );
							target.removeClass( 'loading' );
							
							var dateCell = row.find('.date ul');
						    var ipCell   = row.find('.ip ul');
						    var urlCell  = row.find('.url ul');
						    
						    var url = new RequestUrl();
						    url.removeAll(['module', 'search', 'page']);
						    var urlString = url.getUrl();
						    
						    for( var i in json )
						    {
						        urlCell.append(  '<li class="remove"><a href="' + urlString + '&do=view&id=' + json[i].id + '"><img src="images/icons/page_white_text.png" alt="" /></a></li>' );
						        dateCell.append( '<li class="remove">' + json[i].date + '</li>' );
						        ipCell.append(   '<li class="remove">' + json[i].ip + '</li>' );
						    }
							
							row.addClass( 'loaded' );
						}
					});
				}
				else
				{
					row.find( '.remove' ).show();
				}
			}
			// collapse
			else
			{
				row.find( '.remove' ).hide();
			}
			target.toggleClass( 'open' );
		}
		
		// pre expanding
		if( target.hasClass( 'preWrap' ) )
		{
			target.toggleClass( 'expanded' );
		}
	});
});