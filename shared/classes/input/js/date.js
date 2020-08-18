function initDatepicker( domNode, show )
{
	if( domNode == undefined )
	{
		domNode = '.date-field-wrap:not(.no-auto-init) input:not(.normalized)';
	}
    
	jQuery( domNode ).each(function()
	{
		var self = jQuery( this );
		var settings = 
		{
			showAnim: 		'slide', 
			showOptions: 	{ direction: 'up' },
			duration: 		'fast',
			changeFirstDay: false,
			changeMonth: 	true,
			changeYear: 	true
		}
        
		if( self.parents( '.ui-dialog:first' ).length > 0 )
		{
			var dialog = self.parents( '.ui-dialog:first' );
			settings.beforeShow = function()
			{
				setTimeout(function()
				{
					var instance = jQuery.datepicker._curInst;
					if( !instance )
					{
						return;
					}
					instance.dpDiv.css( 'z-index', dialog.css( 'z-index' ) * 1 + 1 );
				});
			}
		}
		var format = self.siblings( '.format' ).html();
		if( format !== null )
		{
			settings.dateFormat = format;
		}
		var normalizedInput = self.siblings( '.normalized' );
		if( normalizedInput.length > 0 )
		{
			settings.onSelect = function( dateText, picker )
			{
				var selectedMonth =  picker.selectedMonth + 1;
				var normalizedMonth = (selectedMonth < 10 ? '0' : '' ) + selectedMonth;

				var normalizedDay = (picker.selectedDay < 10 ? '0' : '' ) + picker.selectedDay;

				normalizedInput.val( picker.selectedYear + '-' + normalizedMonth + '-' + normalizedDay );
				self.change();
			}
			var lastInstance;
			var updateFromLocalized = function( item )
			{
				if( !lastInstance )
				{
					return;
				}
				var dateFormat = jQuery.datepicker._get( lastInstance, 'dateFormat' );
				var date = jQuery.datepicker.parseDate( dateFormat, item.val() );
				normalizedInput.val( jQuery.datepicker.formatDate( jQuery.datepicker.W3C, date ) );
				item.datepicker( 'setDate', date );
			}
            var updateFromNormalized = function ( item, supressChange )
            {
                var parsedDate = jQuery.datepicker.parseDate( jQuery.datepicker.W3C, normalizedInput.val() );
                item.datepicker( 'setDate', parsedDate );
                if(!supressChange)
                {
                    self.change();
                }
            }
			self.keydown(function( event )
			{
				lastInstance = jQuery.datepicker._curInst || lastInstance;
				// circumvent default ENTER key handler
				if( event.keyCode == 13 )
				{
					updateFromLocalized( self );
				}
			});
			self.change(function()
			{
				updateFromLocalized( self );
			});
            
            normalizedInput.change(function()
            {
                updateFromNormalized( self );
            });
		}
		// init
		self.datepicker( settings );
		self.trigger( 'datepickerinit' );
        
		if( normalizedInput.length > 0 )
		{
			// this is a hack - without settimeout datepicker does not have initialized localization and the wrong format is used
			setTimeout(function(){ updateFromNormalized( self, true ); },0);
		}
	});
	
	if( show != undefined && show == true )
	{
		setTimeout(function()
		{
			jQuery( domNode ).datepicker( 'show' );
		},0);
	}
	// fix for smaller pages - datepicker creates scroller if there wasn't one
	jQuery( '.ui-datepicker.ui-helper-hidden-accessible' ).hide();
}
jQuery( document ).ready( function()
{
	// set default date format
	$.datepicker.setDefaults
	({
		dateFormat: 'dd/mm/yy'
	});
	//datepicker
	initDatepicker();
	// add class to body
	jQuery( 'body' ).addClass( 'javascriptOn' );
	// field focus
	jQuery( '.date-field-wrap button' ).live( 'click', function()
	{
		jQuery( this ).parents( '.special-field-wrap' ).find( 'input' ).focus();
	});
});
