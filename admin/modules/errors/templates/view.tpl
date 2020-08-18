<div class="errors">
	{include file="_toolbar.tpl"}
	{simpleForm module="errors" do="deleteError" id=$item->getMessageHash() button="images/icons/bin_empty.png"}{/simpleForm}
	<div class="content view">
	    <table class="leafTable errorDetails labelFirstColumn" cellspacing="0" cellpadding="0">
			{if $item->request_method == "GET"}
	        <tr class="alternate">
				<th>Link to error:</th>
				<td><a href="http://{$item->http_host|escape}{$item->request_uri|escape}"><img src="images/icons/world_go.png" alt="" /> http://{$item->http_host|escape}{$item->request_uri|escape}</a></td>
			</tr>
			{/if}

	        <tr>
				<th>Message:</th>
				<td>{$item->message} in "{$item->file}" on line {$item->line}.</td>
			</tr>
			<tr class="alternate">
				<th>Date:</th>
				<td>{$item->add_date}</td>
			</tr>
			<tr>
				<th>Level:</th>
				<td>{$item->getLevelName()}</td>
			</tr>
			<tr class="alternate">
				<th>User IP:</th>
				<td>
					{$item->user_ip|escape}
					{if !empty($item->user_forwarded_ip)} 
						({$item->user_forwarded_ip|escape})
					{/if}
				</td>
			</tr>
	        <tr>
				<th>HTTP Host:</th>
				<td>{$item->http_host|escape}</td>
			</tr>
	        <tr class="alternate">
				<th>Request URI:</th>
				<td>{$item->request_uri|escape}</td>
			</tr>
	        <tr>
				<th>Query String:</th>
				<td>{$item->query_string|escape}</td>
			</tr>
	        <tr class="alternate">
				<th>Request Method:</th>
				<td>{$item->request_method|escape}</td>
			</tr>
	        <tr>
				<th>HTTP Referer:</th>
				<td>{$item->http_referer|escape}</td>
			</tr>
	        <tr class="alternate">
				<th>User Agent:</th>
				<td>{$item->user_agent|escape}</td>
			</tr>
	        <tr>
				<th>HTTP Content Type:</th>
				<td>{$item->http_content_type|escape}</td>
			</tr>
	        <tr class="alternate">
				<th>HTTP Cookie:</th>
				<td>{$item->http_cookie|escape}</td>
			</tr>
	        <tr>
				<th>$_GET:</th>
		        <td>
		            <div class="preWrap">
						<pre>{$item->data_get|escape}</pre>
					</div>
					
		        </td>
			</tr>
	        <tr class="alternate">
				<th>$_POST:</th>
		        <td>
		            <div class="preWrap">
						<pre>{$item->data_post|escape}</pre>
					</div>
					
		        </td>
			</tr>
	        <tr>
				<th>$_COOKIE:</th>
		        <td>
		            <div class="preWrap">
						<pre>{$item->data_cookie|escape}</pre>
					</div>
					
		        </td>
			</tr>
	        <tr class="alternate">
				<th>$_FILES:</th>
		        <td>
		            <div class="preWrap">
						<pre>{$item->data_files|escape}</pre>
					</div>
					
		        </td>
			</tr>
	        <tr>
				<th>$_SESSION:</th>
		        <td>
		            <div class="preWrap">
						<pre>{$item->data_session|escape}</pre>
					</div>
					
		        </td>
			</tr>
	        <tr class="alternate">
				<th>argv:</th>
		        <td>
		            <div class="preWrap">
						<pre>{$item->argv|escape}</pre>
					</div>
					
		        </td>
			</tr>
	        <tr>
				<th>Context:</th>
		        <td>
		            <div class="preWrap">
						<pre>{$item->context|escape}</pre>
					</div>
					
		        </td>
			</tr>
			<tr class="alternate">
				<th>Stack Trace:</th>
		        <td class="stackTraceOutput">
					<pre>{$item->stackTrace|escape}</pre>
		        </td>
			</tr>
			
	    </table>
	</div>
	{include file="_toolbar.tpl"}
</div>
