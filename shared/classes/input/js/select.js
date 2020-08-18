// TODO: manage results container height (resize to always fully fit in screen) - if search field is not visible (it is 
// scrolled away) it is impossible to select values

// event handler on autocomplete expand icon
jQuery( document ).click( function( event )
{
	var target = jQuery( event.target );
	if( target.hasClass( 'autocompleteExpandIcon' ) )
	{
		var input = target.siblings( '.autocompleteInput' );
		if( !input.attr( 'disabled' ) )
		{
			input.focus();
		}
	}
});
jQuery( document ).focusin( function( event )
{
	var target = jQuery( event.target );
	if( target.is( '.autocompleteInput' ) )
	{
		var wrap = target.parents( '.input:first' );
		if( !wrap.is( '.initiated' ) )
		{
			new ObjectSelect( wrap[0] );
		}
	}
});

jQuery( document ).ready( function()
{
	jQuery( '.input.selectWrap' ).each(function()
	{
		var instance = new ObjectSelect( this );
	});
});

function ObjectSelect( domNode )
{
	var self = this;
	
	self.useSessionStorage = false;
	
	var node = jQuery( domNode );
	
	self.node = node;
	self.domNode = domNode;
	
	self.searchUrl = node.attr( 'data-searchUrl' );
	self.editUrl = node.attr( 'data-editUrl' );
	self.saveUrl = node.attr( 'data-saveUrl' );
	self.selectionModel = node.attr( 'data-selectionModel' );
	self.dialogClass = node.attr( 'data-dialogClass' );
	
	self.storageName = hex_sha1( self.editUrl );
	
	self.extraResponseFields = [];
	var fieldNamesString = node.attr( 'data-extraResponseFields' );
	if( fieldNamesString )
	{
		var fieldNames = fieldNamesString.split(',');
		for( var i = 0; i < fieldNames.length; i++ )
		{
			self.extraResponseFields.push( fieldNames[i] );
		}
	}
	
	self.attachEvents();
	// prevents resultsContainer flickering
	self.node.addClass( 'initiated' );
	// save reference
	self.node.data( 'objectSelectInstance', self );
	// trigger custom event
	self.node.trigger( 'objectselectcreate', [ self ] );
}

/**
 * override this method to dynamically modify data request url
 */
ObjectSelect.prototype.generateRequest = function( query )
{
	return '&search=' + query;
}

ObjectSelect.prototype.attachEvents = function()
{
	var self = this;
	// search
	if( self.selectionModel == 'search' )
	{
		// Use an XHRDataSource
	    var dataSource = new YAHOO.util.XHRDataSource( self.searchUrl );
	    // save reference to data source
		self.dataSource = dataSource;
		// Set the responseType
	    dataSource.responseType = YAHOO.util.XHRDataSource.TYPE_JSON;
	    // Define the schema of the delimited results
	    dataSource.responseSchema = 
		{
	        resultsList : 'collection', 
	        fields : [ 'displayString', 'id', 'totalResults' ]
	    };
		// additional fields
		for( var i = 0; i < self.extraResponseFields.length; i++ )
		{
			dataSource.responseSchema.fields.push( self.extraResponseFields[i] );
		}
	    // Enable caching
	    dataSource.maxCacheEntries = 5;
		
	    // Instantiate the AutoComplete
	    var autocomplete = new YAHOO.widget.AutoComplete( self.node.find( '.autocompleteInput' )[0], self.node.find( '.resultsContainer' )[0], dataSource );
	    // save reference
		self.autocomplete = autocomplete;
		// some autocomplete params
		autocomplete.resultTypeList = false;
	    autocomplete.minQueryLength = 0;
	    autocomplete.maxResultsDisplayed = 20;
		// The webservice needs additional parameters 
		autocomplete.generateRequest = function( query ) 
		{
	        return self.generateRequest( query ); 
	    };
        
        var valueUpdateTimeout;
	    var valueField = self.node.find( '.value' )[0];
		// selection event
	    autocomplete.itemSelectEvent.subscribe(function( type, params ) 
		{
            clearTimeout(valueUpdateTimeout);
			var selectedDomNode = params[1]; // reference to the selected LI element
	        var selectedValues  = params[2]; // object literal of selected item's result data
	        
	        // update hidden field with the selected item's ID
	        valueField.value = selectedValues.id;
			
			// fire custom "change" event
			jQuery( valueField ).trigger( 'change', [ selectedValues, selectedDomNode ] );
			jQuery( valueField ).trigger( 'itemselect', [ selectedValues, selectedDomNode ] );
	    });
	    
	    // unmatched even
	    autocomplete.unmatchedItemSelectEvent.subscribe(function( type, params )
	    {
            clearTimeout(valueUpdateTimeout);
			
	    	if( valueField.value != '' )
			{
				valueField.value = '';
				jQuery( valueField ).trigger( 'change' );
			}
	    });
		
		autocomplete.dataRequestEvent.subscribe(function( type, params )
		{
			// add loading class (note that no css is provided for this by default)
			self.node.addClass( 'loading' );
		});
		
		var lastResultsCount = 0;
		autocomplete.dataReturnEvent.subscribe(function( type, params )
		{
			var results = params[2];
			
			// remove loading class (note that no css is provided for this by default)
			self.node.removeClass( 'loading' );
			if( results.length == 0 )
			{
				// ensure container is visible
				autocomplete.alwaysShowContainer = true;
				autocomplete._toggleContainer( true );
				// hide previous results
				self.node.find( '.autocompleteWrap .yui-ac-bd' ).hide();
				// update footer
				lastResultsCount = 0;
			}
			else
			{
				// allow container to collapse 
				autocomplete.alwaysShowContainer = false;
				// show results
				self.node.find( '.autocompleteWrap .yui-ac-bd' ).show();
				lastResultsCount = results[0].totalResults;
			}
			// update footer
			autocomplete.setFooter( lastResultsCount + ' ' + self.node.attr( 'data-objectsFoundText' ) );
			if( lastResultsCount > autocomplete.maxResultsDisplayed )
			{
				self.node.find( '.autocompleteWrap' ).addClass( 'resultsOverflow' );
			}
			else
			{
				self.node.find( '.autocompleteWrap' ).removeClass( 'resultsOverflow' );
			}
			// repaint alternation - this is done thourgh timeout to allow new nodes to be added to container
			setTimeout(function()
			{
				var rows = self.node.find( '.autocompleteWrap .resultsContainer .yui-ac-bd li' );
				rows.removeClass( 'alternate' );
				var visibleRows = rows.filter( ':visible' );
				//var visibleRows = rows;
				for( var i = 0; i < visibleRows.length; i++ )
				{
					if( i % 2 == 0 )
					{
						jQuery( visibleRows[i] ).addClass( 'alternate' );
					}
				}
			},0);
			
		});
		
		// expand results container on focus
		var focusTimeout;
		self.node.find( '.autocompleteInput' ).focus(function()
		{
			if( executeFocusAction )
			{
				autocomplete.sendQuery( self.node.find( '.autocompleteInput' ).val() );
			}
			executeFocusAction = true;
		});
		self.node.find( '.autocompleteInput' ).blur(function(e)
		{
            valueUpdateTimeout = setTimeout
            (
                function() 
                {
                    if (jQuery(e.target).val() === '')
                    {
                        jQuery(valueField).attr('value', '');  
                    }
                }, 
                0 
            );
            
			// if lastResultsCount is 0, container visibility was forsed and it needs to be removed
			if( lastResultsCount == 0 )
			{
				autocomplete.alwaysShowContainer = false;
				autocomplete._toggleContainer( false );
			}
		});
		// skip focus action. this is done because upon selection input field briefly loses focus and "itemSelect" 
		// event is fired after "focus" event - the query is sent with the old input value
		var executeFocusAction = true;
		self.node.find( '.autocompleteWrap .resultsContainer' ).mousedown(function()
		{
			executeFocusAction = false;
		});
	}
	// trigger
	self.node.find( '.trigger' ).click(function()
	{
		// recreate nodes - if they were cloned, references are wrong
		self.node = jQuery( this ).parents( '.selectWrap:first' );
		self.domNode = self.node[0];
		
		var dialog = jQuery( '<div></div>' ).dialog
		({
			dialogClass: 'leafDialog editDialog inputDialog loading ' + self.dialogClass, 
			width:  600,
			height: 400,
			bgiframe: true,
			zIndex: 25000,
			close: function()
			{
				jQuery( this ).remove();
			}
		});
		
		var url = new RequestUrl( self.editUrl );
		if( url.query.module )
		{
			dialog.addClass( 'module-' + url.query.module );
		}
		if( url.query['do'] )
		{
			dialog.addClass( 'method-' + url.query['do'] );
		}
		
		self.getDialogHtml(function( html )
		{
			dialog.parents( '.leafDialog' ).removeClass( 'loading' );
			dialog.html( html );
			dialog.dialog( 'option', 'title', dialog.find( 'h2' ).html().trim() );
			dialog.find( 'h2' ).remove();
			
			var form = dialog.find( 'form' );
			form.find( '.focusOnReady' ).focus();
			// set default object name if selection model is "search"
			if( self.selectionModel == 'search' )
			{
				form.find( '.focusOnReady' ).val( self.node.find( '.autocompleteInput' ).val() );
			}
			
			self.attachDialogEvents( dialog );
			
			if( typeof desktop !== 'undefined' && 'initRichtext' in desktop )
			{
				desktop.initRichtext();
			}
            
            form.find('.input.selectWrap').each( function( )
            {
                new ObjectSelect( this );
            });
		});
	});
}

ObjectSelect.prototype.getDialogHtml = function( callback )
{
	var self = this;
	
	var storedHtml = false;
	try
	{
		if( self.useSessionStorage && typeof sessionStorage !== 'undefined' && sessionStorage[ self.storageName ] )
		{
			storedHtml = sessionStorage[ self.storageName ];
		}
	}
	catch( error ){}
	if( self.dialogHtml )
	{
		callback( self.dialogHtml );
	}
	else if ( storedHtml )
	{
		self.dialogHtml = storedHtml;
		callback( self.dialogHtml );
	}
	else
	{
		jQuery.ajax
		({
			url: self.editUrl,
			dataType: 'json',
			success: function( json )
			{
				self.dialogHtml = json.html;
				try
				{
					if( typeof sessionStorage !== 'undefined' )
					{
						sessionStorage[ self.storageName ] = self.dialogHtml;
					}
				}
				catch( error ){}
				callback( self.dialogHtml );
			}
		});
	}
}

ObjectSelect.prototype.attachDialogEvents = function( dialog )
{
	var self = this;
	var node = self.node;
	
	// attach submit listener
	var form = dialog.find( 'form' );
	form.find( '.cancelButton' ).click(function( event )
	{
		event.preventDefault();
		dialog.dialog( 'close' ).remove();
		if( self.selectionModel == 'search' )
		{
			node.find( '.autocompleteInput' ).focus();
		}
		else
		{
			node.find( 'select' ).focus();
		}
	});
	var v = new Validation( form );
	form.bind( 'ok', function( event )
	{
		event.preventDefault();
		
		dialog.find( '.header' ).html( '' );
		
		jQuery.ajax
		({
			url: 	  self.saveUrl,
			type:     'post',
			dataType: 'json',
			data: 	  form.serializeArray(),
			success:  function( json )
			{
				if( json.result == 'ok' )
				{
					// update value
					if( self.selectionModel == 'search' )
					{
						// flush cache
						self.dataSource.flushCache();
						// update field values
						var displayField = node.find( '.autocompleteInput' );
						var valueField   = node.find( '.autocompleteWrap .value' );
						displayField.val( json.name );
						valueField.val( json.id );
						displayField.focus();
					}
					else
					{
						var select = node.find( 'select' );
						var newOption = new Option;
						newOption.value = json.id;
						newOption.innerHTML = json.name;
						select.append( newOption );
						select.val( json.id );
						select.focus();
					}
					
					dialog.dialog( 'close' ).remove();
				}
				// Validation should take that this never happens
				else 
				{
					// focus
					form.find( '[name="' + json.errorFields[0].name + '"]' ).focus();
				}
			}
		});
	});
}