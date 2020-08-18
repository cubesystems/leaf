jQuery( window ).load(function()
{
	// focus
	jQuery( '.focusOnReady' ).focus();
	// load richtext
	leafBaseModule.initRichtext();
});

jQuery( document ).ready(function()
{
	// ajax search //
    var search = 
    {
        request        : null,
        timeout        : null,

        resultLocations : 
        {
            content : 
            { 
                source : '.content',
                target : jQuery('.primaryPanel .content')
            },
            
            footer :
            {
                source : '.footer',
                target : jQuery('.primaryPanel .footer')
            }
        }
    };
   
    search.form          = jQuery( 'form.searchForm' );
    search.input         = search.form.find( '.search' );
    search.lastQuery     = search.input.val();

    // attach keypress event
    search.form.bind( 'search', function()
    {
        
        // cancel previous timeout
        clearTimeout( search.timeout );
        
        // cancel previous unfinished request
        if (search.request)
        {
            search.request.abort();
        }

        search.timeout = setTimeout(function()
        {
            
            // set loading icon
            search.form.addClass( 'loading' );
            // construct url
            var url = new RequestUrl( false );
            url.add( search.form.serializeArray() );
            
            
            if ('replaceState' in window.history)
            {
                window.history.replaceState( window.history.state, window.title, url.getUrl());
            }
            
            
            url.add({ ajax: 1 });
            // send request
            search.request = jQuery.ajax
            ({
                url: url.getUrl(),
                success: function( response )
                {
                    // remove loading icon
                    search.form.removeClass( 'loading' );
                    
                    // create html holder
                    var html =jQuery('<div />').html( response );
                    for (var key in search.resultLocations)
                    {
                        var location = search.resultLocations[key];
                        jQuery( location.target ).html( html.find( location.source ).html() );
                    }
                    
                    // init continuous scroll
                    jQuery( location.target ).find('.leafTable').trigger( 'scrollinit' );
                    
                    // log
                    leafBaseModule.logResponseSize( search.request );
                }
            });
        }, 200 );
    });

    search.input.keyup( function()
    {
        if( search.input.val() == search.lastQuery )
        {
            return;
        }
        search.lastQuery = search.input.val();
        
        // from jQuery docs:
        // Note: For both plain objects and DOM objects, 
        // if a triggered event name matches the name of a property on the object, 
        // jQuery will attempt to invoke the property as a method 
        // if no event handler calls event.preventDefault(). 
        // If this behavior is not desired, use .triggerHandler() instead.
        search.form.triggerHandler( 'search' );
    });

    setTimeout( function () {  jQuery( search.form ).trigger('searchinit', search ) }, 0);



	// many-to-many relation controls //
    
    jQuery(document).bind('initMultipleItemsBlock', function( e )
    {
        var target = jQuery(e.target);
        if (!target.is('.field .multipleItemsBlock'))
        {
            target = target.find('.field .multipleItemsBlock');
        }
        
        target.each(function()
        {
            var block = jQuery(this);
            var blockName = block.attr('data-name');
            var itemClass = blockName + 'Block';
            

            var updateItemCount = function()
            {
                // manage remove buttons

                var minItems = block.attr('data-min-items');
                if (typeof minItems == 'undefined')
                {
                    minItems = 1;  // for compatibility with earlier code
                }
                minItems = minItems * 1;

                var numberOfItems = block.find('.itemBlock.' + itemClass + ':not(.fadeOutInProgress)').length;
                block.toggleClass('hasOneItem', (numberOfItems == 1));
                block.find( '.removeItemButton' ).prop( 'disabled', ( numberOfItems <= minItems )  );            
            }    
            
            
            block.click( function( event )
            {
            
                var trigger = jQuery( event.target );     
                // webkit browsers go beyond button node when setting click target
                if( trigger.prop( 'tagName' ).toLowerCase() != 'button' )
                {
                    trigger = trigger.parents( 'button' );
                }
                if (!trigger.is('button'))
                {
                    return;
                }
                
                var targetBlock = trigger.parents('.multipleItemsBlock').first();

                
                if (targetBlock.attr('data-name') != blockName)
                {
                    return; // only react to own clicks
                }

                // add
                if( trigger.hasClass( 'addItemButton' ) )
                {
                    // find nodes involved
                    leafBaseModule.addItem( targetBlock );
                }

                // remove
                if( trigger.hasClass( 'removeItemButton' ) )
                {

                    var node  = trigger.parents('.itemBlock').first();

                    node.trigger('removeitem', node);
                    
                    node.addClass( 'fadeOutInProgress' );

                    node.fadeOut( 'fast', function()
                    {
                        if( node[0].tagName.toLowerCase != 'tr' || node[0].tagName.toLowerCase != 'td' )
                        {
                            node.css({ opacity: 0 });
                            node.show();
                            node.slideUp( 'fast', function()
                            {
                                node.remove();
                            });
                        }
                        else
                        {
                            node.remove();
                        }

                    });
                }

                if (
                    (trigger.hasClass( 'addItemButton' ))
                    ||
                    (trigger.hasClass( 'removeItemButton' ))
                )
                {
                    updateItemCount();
                }            

            });            

            block.on('updateIndexes', '.itemBlock.' + itemClass, function(e)
            {   
                // set new index for all fields with array names
                // 
                // in case of nested blocks, this bubbles up and gets called for each parent block also
                // so that each block can update it's own index in the names

                var item = jQuery(this);
                var index = item.attr('data-index');

                var matchPattern  = new RegExp(blockName + '\\]?\\[\\d*\\]')
                var searchPattern = new RegExp('(' + blockName + '\\]?\\[)\\d*(\\])', 'g');            
                var attrs = ['name', 'id', 'for'];            

                item.find('input,select,textarea,button,label').each(function()
                {
                    for (var i=0; i<attrs.length; i++)
                    {
                        var attr = jQuery(this).attr(attrs[i]);
                        if (attr && attr.match(matchPattern))
                        {
                            jQuery(this).attr(attrs[i], attr.replace(searchPattern, '$1' + index + '$2'));
                        }                    
                    }
                });            
            });
            

            
            updateItemCount();            
        });
        
	});
    jQuery(document).trigger('initMultipleItemsBlock');
    
    
	// sub menu section collapsing //
	
	jQuery( '.panelLayout .secondaryPanel' ).click(function( event )
	{
		var target = jQuery( event.target );
		if( target.hasClass( 'sectionTitle' ) )
		{
			var title = target;
		}
		else
		{
			var title = target.parents( '.sectionTitle' );
		}
		if( title.length > 0 )
		{
			var section = title.parents( 'li:first' );
			var ul = section.children( 'ul' );
			
			var cookiePrefix = 'submenu:';
			var sectionName = title.attr( 'data-title' );
			
			if( section.hasClass( 'collapsed' ) )
			{
				ul.show();
				jQuery.cookie( cookiePrefix + sectionName, null );
			}
			else
			{
				ul.hide();
				jQuery.cookie( cookiePrefix + sectionName, 1, { expires: 30 } ); // expires in 30 days
			}
			section.toggleClass( 'collapsed' );
		}
	});
	
	// title updating in edit view
	jQuery( '.edit .titleField' ).keyup(  function(){ leafBaseModule.updateTitle( this ) } );
	jQuery( '.edit .titleField' ).change( function(){ leafBaseModule.updateTitle( this ) } );
	
	// default action switcher in "all"
	
	jQuery( '.listViewActionSwitcher button' ).live( 'click', function( event )
	{
		var button = jQuery( this );
		var action = button.attr( 'data-action' );
		var old    = button.siblings( '.active' ).attr( 'data-action' );
		button.siblings( '.active' ).removeClass( 'active' );
		button.addClass( 'active' );
		// store cookie
		jQuery.cookie( 'leafBaseModule_listViewAction', action, { expires: 365 * 5 } );
		// modify current links
		var table = button.parents( '.panelLayout' ).find( '.content .leafTable' );

		table.find( 'a' ).each(function()
		{
			var link = jQuery( this );
            
			link.attr( 'href', link.attr( 'href' ).replace( '&do=' + old + '&', '&do=' + action + '&' ) );
		});
		table.trigger( 'flushcache' );
	});
	
	// continuous scrolling //
	
	jQuery( '.leafTable[data-continuous="1"]' ).live( 'scrollinit', function()
	{

		var table = jQuery( this );
		var container = table.parent();
		var thead = table.find( '.thead' );
		var tbody = table.find( '.tbody' );
		
		var noOfCols  = thead.children().children().length;
		var rowHeight = tbody.children().first().height();
		
		var rowLoadTolerance   = 15;   // how many rows outside viewport to pre-load
		var rowRenderTolerance = 40; // how many rows outside viewport to render (could be loaded)
		
		var cachedRowLimit = 400 * 40;
		
		var viewportHeight;
		var noOfVisibleRows;
		
		var setupViewport = function()
		{
			viewportHeight = container.height();
			noOfVisibleRows = Math.ceil( viewportHeight / rowHeight );
		}
		setupViewport();
		jQuery( window ).resize(function()
		{
			setupViewport();
			container.scroll();
		});
		

        var total, itemsPerPage, noOfPages;
        var setupTotals = function()
        {
            total = table.attr( 'data-total' );
            itemsPerPage = table.attr( 'data-itemsPerPage' );
            noOfPages = Math.floor( total / itemsPerPage ) + 1;
        }
        setupTotals();        
		
		var timeout;
		var requests = {};
		
		var abortRequests = function()
		{
			for( var i in requests )
			{
				requests[i].abort();
			}
			requests = {};
		}
		
		var currentTopPageNo    = 1;
		var currentBottomPageNo = 1;
		
		var pages = {}; // TODO: manage memory
		
		// store current results as page 1
		pages[ currentTopPageNo ] = tbody.html();
		
		var clone;
		
		var emptyRowCells = '';
		var loadingRowCells = '';
		var thCellWidths = {};
		
		// freeze column widths
		var freeze = function()
		{
			// reset header width
			if( clone )
			{
				clone.remove();
			}
			thead.children().first().children().each(function()
			{
				var cell = jQuery( this );
				var width = cell.width() + 'px';
				cell.css( 'width', '' );
				cell.css( 'min-width', '' );
				cell.css( 'max-width', '' );
			});
			// freeze it
			thead.children().first().children().each(function()
			{
				var cell = jQuery( this );
				var width = cell.width() + 'px';
				cell.css( 'width', width );
				cell.css( 'min-width', width );
				cell.css( 'max-width', width );
			});
			
			// add fixed header
			clone = thead.clone().insertAfter( thead ).css({ position: 'fixed', zIndex: 100}).addClass('fixed');
			clone.css( 'margin-top', '-' + clone.height() + 'px' )
			
			emptyRowCells   = '';
			loadingRowCells = '';
			thCellWidths    = {};
			thead.children().children().each(function( i )
			{
				thCellWidths[ i ] = jQuery( this ).width();
			});
			for( var i = 0; i < noOfCols; i++ )
			{
				emptyRowCells += '<span>&nbsp;</span>';
				if( thCellWidths[i] >= 100 )
				{
					loadingRowCells += '<span>' + table.attr( 'data-loading' ) + '</span>';
				}
				else
				{
					loadingRowCells += '<span>&nbsp;</span>';
				}
			}
			emptyRow   = '<div class="unselectable">' + emptyRowCells + '</div>';
			loadingRow = '<div class="unselectable" style="height:' + rowHeight + 'px;">' + loadingRowCells + '</div>';
		}
		
		freeze();
		
		var getEmptyRows = function( no )
		{
			var rows = [];
			for( var i = 0; i < no; i++ )
			{
				rows.push( loadingRow );
			}
			return rows.join( '' );
		}
		
		// initial setup
		var postHeight = total * rowHeight - tbody.children().length * rowHeight;
		if( postHeight > 0 )
		{
			var post = jQuery( emptyRow ).addClass( 'post' ).appendTo( tbody );
			post.height( postHeight );
		}
		
		container.scroll(function()
		{
			clearTimeout( timeout );
			timeout = setTimeout(function()
			{
				var offsets =
				{
					visible:
					{
						top:    0,
						bottom: 0
					},
					load:
					{
						top:    0,
						bottom: 0
					},
					render:
					{
						top:    0,
						bottom: 0
					}
				};
				
				var pageNumbers =
				{
					load:
					{
						top:    0,
						bottom: 0
					},
					render:
					{
						top:    0,
						bottom: 0
					}
				}
				
				offsets.visible.top    = Math.floor( Math.max( 0, container.prop( 'scrollTop' ) - thead.height() ) / rowHeight );
				offsets.visible.bottom = offsets.visible.top + noOfVisibleRows;
				
				// adjust for loading tolerance
				offsets.load.top    = Math.max( 0, offsets.visible.top - rowLoadTolerance );
				offsets.load.bottom = Math.min( offsets.visible.bottom + rowLoadTolerance, total );
				
				// adjust for rendering tolerance
				offsets.render.top    = Math.max( 0, offsets.visible.top - rowRenderTolerance );
				offsets.render.bottom = Math.min( offsets.visible.bottom + rowRenderTolerance, total );
				
				pageNumbers.load.top    = Math.floor( offsets.load.top / itemsPerPage ) + 1;
				pageNumbers.load.bottom = Math.floor( offsets.load.bottom / itemsPerPage ) + 1;
				
				pageNumbers.render.top    = Math.floor( offsets.render.top / itemsPerPage ) + 1;
				pageNumbers.render.bottom = Math.floor( offsets.render.bottom / itemsPerPage ) + 1;
				
				var preOffset = ( pageNumbers.render.top - 1 ) * itemsPerPage;
				var postOffset = Math.max( 0, total - pageNumbers.render.bottom * itemsPerPage );
				
				if( pageNumbers.load.top != currentTopPageNo || pageNumbers.load.bottom != currentBottomPageNo )
				{
					abortRequests();
					
					var no = 0;
					for( var i in pages )
					{
						if( pages[i] )
						{
							no++;
						}
					}
					
					// clear memory
					if( no * itemsPerPage > cachedRowLimit )
					{
						pages = {};
					}
					
					var pagesToLoad = {};
					for( var i = 0; i <= pageNumbers.load.bottom - pageNumbers.load.top; i++ )
					{
						pagesToLoad[ pageNumbers.load.top + i ] = true;
					}
					
					var pagesToRender = {};
					for( var i = 0; i <= pageNumbers.render.bottom - pageNumbers.render.top; i++ )
					{
						pagesToRender[ pageNumbers.render.top + i ] = true;
					}
					
					var inject = function()
					{
						var rows = '';
						for( var i in pagesToRender )
						{
							if( pages[ i ] === undefined )
							{
								if( i == noOfPages ) // last page is likely to be smaller
								{
									rows += getEmptyRows( total - ( noOfPages - 1 ) * itemsPerPage );
								}
								else
								{
									rows += getEmptyRows( itemsPerPage );
								}
							}
							else
							{
								rows += pages[ i ];
							}
						}
						currentTopPageNo    = pageNumbers.load.top;
						currentBottomPageNo = pageNumbers.load.bottom;
						
						var pre = '';
						if( preOffset > 0 )
						{
							var pre = '<div class="unselectable pre" style="height:' + ( preOffset * rowHeight ) + 'px">' + emptyRowCells + '</div>';
						}
						var post = '';
						if( postOffset > 0 )
						{
							var post = '<div class="unselectable post" style="height:' + ( postOffset * rowHeight ) + 'px">' + emptyRowCells + '</div>';
						}
						tbody.html( pre + rows + post );
						
						// check if row width need to be recalculated
						if
						(
							container.prop( 'clientWidth' ) > 0
							&&
							container.prop( 'clientWidth' ) < container.prop( 'scrollWidth' )
						)
						{
							freeze();
						}
						
						return true;
					}
					
					for( var i in pagesToLoad )
					{
						if( pages[ i ] === undefined )
						{
							(function(){
								var pageNo = i;
								var key = Math.random() + '';
                                
                                var searchUrl = new RequestUrl();
                                searchUrl.add( search.form.serializeArray() );
                                searchUrl.add({ ajax: 1, page: pageNo });
								requests[key] = jQuery.ajax
								({
                                    url: searchUrl.getUrl(),
									success: function( html, status )
									{
										delete requests[key];
										pages[ pageNo ] = jQuery( html ).find( '.leafTable .tbody' ).html();
										inject();
									}
								});	
							})();
						}
					}
					inject();
				}
			},20);
		});
		container.scroll();
		
		table.bind( 'flushcache', function()
		{
			pages = {};
            setupTotals();
		});
	});
	
	jQuery( '.leafTable[data-continuous="1"]' ).trigger( 'scrollinit' );
});

var leafBaseModule =
{
	updateTitle: function( domNode )
	{
		var title = jQuery( domNode ).parents( 'form' ).find( '.header h2' );
		if( !title.data( 'defaultTitle' ) )
		{
			title.data( 'defaultTitle', title.html() );
		}
		if( domNode.value == '' )
		{
			if( title.data( 'defaultTitle' ) )
			{
				title.html( title.data( 'defaultTitle' ) );
			}
		}
		else
		{
			title.html( domNode.value );
		}

	},
	logResponseSize: function( request, json )
	{
		var textSize = request.responseText.length;
		if( textSize > 1024 )
		{
			textSize = Math.round( ( request.responseText.length / 1024 ) * 100 ) / 100 + ' k';
		}
		else
		{
			textSize += ' b';
		}

		var jsonPart = '';
		if( json )
		{
			var jsonSize = json.html.length;
			if( jsonSize > 1024 )
			{
				jsonSize = Math.round( ( json.html.length / 1024 ) * 100 ) / 100 + ' k';
			}
			else
			{
				jsonSize += ' b';
			}

			jsonOverhead = Math.round( ( (request.responseText.length - json.html.length) / request.responseText.length ) * 10000 ) / 100;
			jsonPart = ', json.html: ' + jsonSize + ', json overhead: ' + jsonOverhead + '%';
		}

		console.log( '(leafBaseModule.logResponseSize) raw: ' + textSize + jsonPart );
	},
	getValueFromClass: function( domNode, prefix, delimiter )
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
	},
	getValuesFromClass: function( domNode, prefix, delimiter )
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
		var results = [];
		for( var i = 0; i < classNames.length; i++ )
		{
			if( classNames[i].indexOf( prefix + delimiter ) === 0 )
			{
				results.push( classNames[i].split( delimiter )[1] );
			}
		}
		return results;
	},
	compare: function( item1, item2)
	{
		if( item1.length != item2.length )
		{
			return false;
		}
		for( var p in item1 )
		{
			if( item1[p] !== item2[p] )
			{
				return false;
			}
		}
		for( var p in item2 )
		{
			if( item1[p] !== item2[p] )
			{
				return false;
			}
		}
		return true;
	},
	repaintRowAlternation: function( table )
	{
		var rows = table.find( 'tbody tr' );
		rows.removeClass( 'alternate' );
		var visibleRows = rows.filter( ':visible' );
		for( var i = 0; i < visibleRows.length; i++ )
		{
			if( i % 2 == 0 )
			{
				jQuery( visibleRows[i] ).addClass( 'alternate' );
			}
		}
	},
	addItem: function( block, focus )
	{

		if( focus === undefined )
		{
			focus = true;
		}
		
		var blockName = block.attr('data-name');
		if (!blockName)
		{
			return null;
		}
	  
		var itemClass = blockName.concat('Block');
		var template = jQuery('.fieldTemplates .itemBlock.template.' + itemClass)

		if (template.size() != 1)
		{
			return null;
		}
	  
		// assign free array index to new item
		var largestIndexInBlock = 0;
		block.find('.itemBlock.' + itemClass).each(function()
		{
			var index = jQuery(this).attr('data-index');
			if (!index)
			{
				return;
			}
			index = parseInt(index);
			if (!index)
			{
				return;
			}
			if (index > largestIndexInBlock)
			{
				largestIndexInBlock = index;
			}
		});
		var newIndex = largestIndexInBlock + 1;
	  
		// create new entry
		var newBlock = template.clone( false );
	
		newBlock.show();
		newBlock.removeClass( 'template' );
		
        newBlock.find('input.hasDatepicker').each(function(){
            $( this ).removeClass('hasDatepicker');
            focus = false;
        });
        
		var inputs = newBlock.find('select,input[type=text],input[type=hidden]');
		inputs.val('');
		var checkboxes = newBlock.find('input[type=checkbox]');
		checkboxes.attr( 'checked', false );
		
		newBlock.attr('data-index', newIndex);    
            
		block.children('.itemContainer').first().append( newBlock );
        
        newBlock.trigger('updateIndexes');
	  
		// raise custom event
		newBlock.trigger( 'additem' );
        newBlock.trigger( 'initMultipleItemsBlock' ); // init nested multiple item blocks
        
		// focus
		if( focus )
		{
			if( newBlock.find('.focusOnAdd').length > 0 )
			{
				newBlock.find('.focusOnAdd').focus();
			}
			else
			{
				setTimeout( function()
				{
				  newBlock.find('select,input[type=text]').first().focus();
				}, 0 );
			}
		}
	  
		return newBlock;

	},
	initRichtext: function()
	{
		// richtext config
		var plugins = [ 'inlinepopups', 'iespell', 'insertdatetime', 'preview', 'searchreplace', 'contextmenu', 'safari' ];
		// remove inlinepopups plugin for Opera 10
		if( typeof BrowserDetect != 'undefined' )
		{
			if( BrowserDetect.browser == 'Opera' && BrowserDetect.version == 9.8 )
			{
				for( var i = 0; i < plugins.length; i++ )
				{
					if( plugins[i] == 'inlinepopups' )
					{
						delete plugins[i];
					}
				}
			}
		}
		
		var tinymceConfig = 
		{
			script_url: '../shared/3rdpart/tinymce/tiny_mce.js', 
			
			mode : 'specific_textareas', 
			editor_selector : 'richtextInput', 
			theme : 'advanced', 
			entities : '160,nbsp,38,amp,60,lt,62,gt', 
			body_class : 'content', 
			plugins : plugins.join(','), 
			theme_advanced_buttons1 : 'bold,italic,formatselect,justifyleft,justifycenter,justifyright,justifyfull,|,sub,sup,|,bullist,numlist,|,link,unlink,image,embed,|,code,cleanup,removeformat', 
			theme_advanced_blockformats : 'p,address,pre,h2,h3,h4,h5,h6', 
			theme_advanced_buttons2 : '', 
			theme_advanced_buttons3 : '', 
			theme_advanced_toolbar_location : 'top', 
			theme_advanced_toolbar_align : 'left', 
			theme_advanced_statusbar_location : 'bottom', 
			plugin_insertdate_dateFormat : '%Y-%m-%d', 
			plugin_insertdate_timeFormat : '%H:%M:%S', 
			extended_valid_elements : 'a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]', 
			relative_urls : true, 
			theme_advanced_resizing : true, 
			object_resizing : false,
			content_css: '/styles/textFormat.css',
			init_instance_callback: function( instance )
			{
				jQuery( instance.contentAreaContainer ).trigger( 'tinymceinit', [ instance ] );
			}
		};
		tinymceConfig.setup = function( editor )
		{
			// skip first onBeforeGetContent call because textFormat.css has not loaded yet
			editor.onInit.add( function( editor )
			{
				// richtext focus effect
				tinymce.dom.Event.add
				(
					editor.settings.content_editable ? editor.getBody() : (tinymce.isGecko ? editor.getDoc() : editor.getWin()), 'focus', function() 
					{
						// jQuery's internal selector engine requires colons and periods to be escaped
						jQuery( ( '#' + editor.editorContainer ).replace(/(:|\.)/g, '\\$1') ).children('.mceLayout').addClass('focus');
					}
				);
				tinymce.dom.Event.add
				(
					editor.settings.content_editable ? editor.getBody() : (tinymce.isGecko ? editor.getDoc() : editor.getWin()), 'blur', function() 
					{
						// jQuery's internal selector engine requires colons and periods to be escaped
						jQuery( ( '#' + editor.editorContainer ).replace(/(:|\.)/g, '\\$1') ).children('.mceLayout').removeClass('focus');
						tinyMCE.triggerSave(); // update textarea contents
					}
				);
			});
		}
		
		var newFields = jQuery( '.richtextInput:not(.initialized)' );
		if( newFields.length > 0 )
		{
			newFields.tinymce( tinymceConfig );
			newFields.addClass( 'initialized' );
		}
	}
};

String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ''); };

if( !'foreach' in Array.prototype )
{
	Array.prototype.foreach = Array.prototype.forEach;
}

