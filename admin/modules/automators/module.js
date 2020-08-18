jQuery(function()
{
    // type change
    $('.module-automators select.[name$="[type]"], .module-automators select.[name$="[action]"],').live('change', function (){
        var container = $(this).parent();
        var id = container.find('input[name$="[id]"]').val();
        var type = container.find('select[name$="[type]"]').val();
        var partialType = container.parent().attr('data-type');
        var url = location.href + '&ajax=1&html=1&itemId=' + id + '&type=' + type + '&getPartial=' + partialType;
        url += '&contextObjectId=' + container.find('input[name$="[contextObjectId]"]').val();
        if(partialType == 'actions')
        {
            url += '&action=' + container.find('select[name$="[action]"]').val();
        }
        $.get(url , function(data) {
            container.replaceWith(data);
        });
    });

    // new
    $('.module-automators img.addIcon').live('click', function (){
        var container = $(this).parent().parent();
        $.get(location.href + '&ajax=1&html=1&getPartial=' + container.attr('data-type') , function(data) {
            container.append(data);
        });
    });

    // delete
    $('.module-automators img.deleteIcon').live('click', function (){
        var parent = $(this).parent();
        var id = parent.find('input[name$="[id]"]').val();
        if(id)
        {
            parent.parent().append('<input type="hidden" name="' + parent.parent().attr('data-type') + 'Deleted[]" value="' + id + '" />');
        }
        parent.remove();
    });
});

