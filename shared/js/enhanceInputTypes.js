if( 'addEventListener' in document && 'querySelectorAll' in document )
{
	document.addEventListener( 'DOMContentLoaded', function()
	{
		// emails
		var emails = document.querySelectorAll( 'input.inputType-email, input[name="email"][type="text"]' );
		for( var i = 0; i < emails.length; i++ )
		{
			emails[i].type = 'email';
			emails[i].autocapitalize = false;
		}
		// urls
		var urls = document.querySelectorAll( 'input.inputType-url, input[name="url"][type="text"]' );
		for( var i = 0; i < urls.length; i++ )
		{
			urls[i].type = 'url';
			urls[i].autocapitalize = false;
		}
		// numbers
		var numbers = document.querySelectorAll( 'input.inputType-number, input[name="number"][type="text"]' );
		for( var i = 0; i < numbers.length; i++ )
		{
			numbers[i].type = 'number';
		}
	},false);
}