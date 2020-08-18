function initLeafFileFields( domNode, show )
{
	if( domNode == undefined )
	{
		domNode = '.leafFile-field-wrap';
	}

	jQuery( domNode ).each(function()
	{
        var wrapQ = jQuery( this );

        var fileIdInput  = wrapQ.find('input.leafFile-id-field:first');
        var removeButton = wrapQ.find('.removeFileButton');
        var removeConfirmationField = wrapQ.find('.leafFile-remove-confirmation-field:first')

        removeButton.each(function()
        {
            jQuery( this ).click(function()
            {
                 if (confirm(removeConfirmationField.val()))
                 {
                    fileIdInput.val( -1 );
                    wrapQ.removeClass('field-has-leafFile');
                 }
            });

        });
	});
}
jQuery( document ).ready( function()
{
	initLeafFileFields();
});

