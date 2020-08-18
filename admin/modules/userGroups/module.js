jQuery(function()
{
    $('input[name=setAll][type=radio]').live('change', function (){
        $('input[value="' + $(this).val() + '"][type=radio]').each(function(){
            $(this).prop('checked', true);
        });
    });
});

