$(function(){
    $('.ui-tabs').tabs();
	$('.ui-tabs :input').focus(function(e){
		parent = $(this).parents('.ui-tabs-panel');
		if (parent.is('.ui-tabs-hide'))
		{
			$('.ui-tabs').tabs('select', parent.attr('id'));
		}
	});
});
