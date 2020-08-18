{alias_context code=admin}
<div id="loginFormContainer">
    <form id="loginForm" method="post" action="">
	    <img id="logo" src="images/logo.jpg" alt="Leaf logo" />
	    <div class="inputs">
	        <label id="usernameLabel" for="username">
	            <span>{alias code=login}:</span>
	            <input class="text" type="text" id="username" name="leafAuthUsername" value="" />
	        </label>
	        <label id="passwordLabel" for="password">
	            <span>{alias code=password}:</span>
	            <input class="text" type="password" id="password" name="leafAuthPassword" value="" />
	        </label>
			<button type="submit" id="submit">OK</button>
			{if $redirect_url}
				<input type="hidden" name="redirect_url" value="{request_url}" />
			{/if}
	    </div>
    </form>
    <div id="copyNote">
        {if $smarty.const.DEVELOPED_BY == "cubesystems"}
            &copy; 2006-{'Y'|date} Cube | 
            <a href="http://www.cubesystems.lv/">www.cubesystems.lv</a> 
        {else}
            &copy; 2006-{'Y'|date} Cube-Media | 
            <a href="http://cube.lv/">www.cube.lv</a> 
        {/if}
	</div>

   
    <!--[if lt IE 9]>
        
	<div class="browser-notice">
		{alias code=worksBestWith}: 
        <ul class="browsers">
		<li>
            <a href="http://www.mozilla.org/en-US/" title="Mozilla Firefox">
			<img src="images/icons/firefox.png" alt="Mozilla Firefox" />
            </a>
        </li>
        <li>
		<a href="https://www.google.com/chrome?hl=lv" title="Google Chrome">
			<img src="images/icons/chrome.png" alt="Google Chrome" />
		</a>
        </li>
        </ul>
	</div>

    <![endif]-->                
        
</div>
