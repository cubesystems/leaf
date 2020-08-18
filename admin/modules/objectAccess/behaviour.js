jQuery(document).ready(function()
{

    jQuery('.accessRules .rule .remove button').click(function()
    {
        jQuery(this).parents('.rule').remove();
        updateAccessRuleTable();
    });

    updateAccessRuleTable();


    jQuery('.accessForm').submit(function()
    {
        jQuery(this).css('visibility', 'hidden');

        jQuery(this).find('tbody input[type=checkbox]').each(function()
        {
            if (!this.checked)
            {
                this.value = 0;
                this.checked = true;
            }

        });

    });

    jQuery('button.switchSubjects').click(function()
    {
        var isOn = jQuery('.subjectsWrap').attr('isOn');

        if (!isOn)
        {
            jQuery('.subjectsWrap').show();
            jQuery('.subjectsWrap').attr('isOn', true);
        }
        else
        {
            jQuery('.subjectsWrap').hide();
            jQuery('.subjectsWrap').removeAttr('isOn');
        }


        this.blur();

    });


    jQuery('button.addSubject').click(function()
    {
        var type = jQuery(this).parent().find('input.targetType').val();
        var id   = jQuery(this).parent().find('input.targetId').val();
        var nameHtml = jQuery(this).find('.name').html();

        addSubject( type, id, nameHtml );
        this.blur();
    })

})

function updateAccessRuleTable()
{

    var totalNumberOfRules = 0;

    var types = ['General', 'Group', 'User'];
    var table = jQuery('table.accessRules');

    for (var i=0; i<types.length; i++)
    {
        var numberOfRules = jQuery('.accessRules tbody tr.rule' + types[i]).size();
        var className = 'has' + types[i] + 'Rules';
        if (numberOfRules > 0)
        {
            table.addClass(className);
        }
        else
        {
            table.removeClass(className);
        }
        totalNumberOfRules += numberOfRules;
    }

    if (totalNumberOfRules > 0)
    {
        table.addClass('hasRules');
    }
    else
    {
        table.removeClass('hasRules');
    }

}




function addSubject( type, id, nameHtml )
{
    var inputId = 'allow'.concat(type,id);
    if (jQuery('#' + inputId).size() > 0)
    {
        return;
    }


    var newRow = jQuery('table.accessRules thead .template').clone(true);
    newRow.removeClass('template').addClass('rule' + type);

    newRow.find('.name label').attr('for', inputId).html(nameHtml);
    newRow.find('.value input').attr('id', inputId).attr('name', inputId).attr('checked', true);


    if (type == 'General')
    {
        jQuery('table.accessRules tbody tr.headingGroup').before( newRow );
    }
    else if (type == 'Group')
    {
        jQuery('table.accessRules tbody tr.headingUser').before( newRow );
    }
    else if (type == 'User')
    {
        jQuery('table.accessRules tbody').append( newRow );
    }

    updateAccessRuleTable();

}