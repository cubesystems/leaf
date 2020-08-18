{if 
	$field.properties.default and 
	( 
		$field.value == '0000-00-00 00:00:00' or 
		!$field.value || 
		$field.properties.default == $field.value 
	)
}
	{assign var=fieldValue value=$field.properties.default|strtotime|date_format:"%Y-%m-%d"}
{elseif $field.value}
	{assign var=fieldValue value=$field.value|date_format:"%Y-%m-%d"}
{/if}
{input type="date" name=$field.input_name id=$field.input_id value=$fieldValue format="yy-mm-dd" }