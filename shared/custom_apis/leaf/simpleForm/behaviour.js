$(function() {
	$('form.simpleForm').live("submit", function() {
		var result = true;
		var confirmationText = $(this).children('.confirmation').html();
		if (confirmationText !== null)
		{
			result = confirm(confirmationText);
		}
		return result;	
	})
});
