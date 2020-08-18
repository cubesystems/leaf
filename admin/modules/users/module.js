jQuery(function()
{
    var passwords = jQuery('.module-users.method-edit .passwords');
    
    passwords.find('[name="change_password"]').change(function()
    {
        var passwordFields = passwords.find('input:not([name="change_password"]), button');
        
        if (jQuery(this).attr('checked'))
        {
            passwordFields.removeAttr('disabled');
        }
        else
        {
            setGeneratedPassword('');
            
            passwordFields.not('button').val('');
            passwordFields.attr('disabled', true);
        }
       
    });
    
    
    passwords.find('.generator .generate').click(function()
    {
        var charRange = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        var rangeLength = charRange.length;
        
        var selectedChars = [];
        for (var i = 12; i > 0; i--)
        {
            selectedChars.push( charRange.charAt( Math.floor( Math.random() * rangeLength ) ) );
        }
        
        var password = selectedChars.join('');
        
        passwords.find('[type="password"]').val(password);
        setGeneratedPassword( password );

    });
    
    passwords.find('input[type="password"]').change(function()
    {
        setGeneratedPassword('');
    });
    
    
    var setGeneratedPassword = function( password )
    {
        var container = passwords.find('.password');
        var field = container.find('input');
        
        field.val(password);
        
        if (field.val().length > 0)
        {
            container.show();
            field.focus().select();
            
        }
        else
        {
            container.hide();
        }
        
    }
        
    
});
