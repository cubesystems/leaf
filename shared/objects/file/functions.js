$(document).ready(function() {
	$('#file').change(function(){
		var path = this.value;
		var extension = path.substring(path.lastIndexOf(".") + 1);
		extension = extension.toLowerCase();
		var nameObj = $("#name");
		if(nameObj.val() == '')
		{
			nameObj.val(path.substring(path.lastIndexOf("\\") + 1));
		}
		
		if(extension=='jpg' || extension=='jpeg')
		{
			$('#resizediv').show();
		}
		else
		{
			$('#resizediv').hide();
		}
	});
});