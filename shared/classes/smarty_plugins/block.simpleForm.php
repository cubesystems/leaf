<?php
/*
    available attributes:
        button    -> 3 cases supported:
                        image mode: button=<url>  -> use input type image with URL as src
                        alias mode: button=<alias_code> -> use input type submit with alias for text
                        custom mode: button=false -> do not output submit button automatically

        buttonAliasContext -> custom context for button in alias mode
        buttonText -> exact button text (omit button attribute to use this)

        fields    -> array containing hidden field name/value pairs
        field_*   -> hidden field names and values in the form field_<field_name>=<field_value>
        id        -> shorthand for field_id attribute
        do        -> shorthand for field_action attribute

        confirmation -> alias mode: confirmation=<alias_code> -> use alias for confirmation text
                        custom mode: confirmation=false -> do not output confirmation

        confirmationContext -> custom context for confirmation in alias mode
        confirmationText -> exact text of confirmation

        params -> all/any of the above params as an array
                  specific attributes will override these values
*/

simpleForm::preload();

function smarty_block_simpleForm (array $params, $content, &$smarty, &$repeat)
{
    if (is_null($content)) // opening tag
    {
        return;
    }
	$form = getObject('simpleForm');
	return $form->getContent($params, $content);
}
?>