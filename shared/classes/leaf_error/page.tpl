<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>{$object->title|default:"leaf error/warning"|escape}</title>
		{foreach from=$object->headers item=header}
			{$header}
		{/foreach}
        {foreach from=$object->css item=css}
        <link rel="stylesheet" type="text/css" href="{$css|escape}" />
        {/foreach}
	</head>
	<body>
		<div id="boxout">
			<div id="boxin">
				<div id="content">
					{foreach from=$object->messages item=message}
						<div class="message">
                            {strip}
							{if $message.header}<h1>{$message.header|escape}</h1>{/if}
                            {if $message.html}{$message.html}{elseif $message.msg}{$message.msg|escape}{/if}
                            {/strip}
						</div>
					{/foreach}
				</div>
				{*<pre>
					{php}xdebug_print_function_stack();{/php}
				</pre>*}
			</div>
		</div>
	</body>
</html>
