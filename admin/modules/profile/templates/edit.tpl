<form id="profileForm" class="validatable" method="post" action="{$_module->getModuleUrl()|escape}">
	<input type="hidden" name="action" value="save" />
	<div class="entry">
		<label>{alias code=login}:</label>
		<span>{$obj->login|escape}</span>
	</div>
	<div class="entry">
		<label>{alias code=name}:</label>
		<span>{$obj->name|escape}</span>
	</div>
	<div class="entry">
		<label>{alias code=surname}:</label>
		<span>{$obj->surname|escape}</span>
	</div>
	<div class="entry">
		<label for="email">{alias code=email}:</label>
		<span><input type="text" value="{$obj->email|escape}" id="email" name="email" /></span>
	</div>
	<div class="entry">
		<label for="oldpassword">{alias code=current_password}:</label>
		<span><input type="password" value="" id="oldpassword" name="oldpassword" /></span>
	</div>
	<div class="entry">
		<label for="password1">{alias code=new_password}:</label>
		<span><input type="password" value="" id="password1" name="password1" /></span>
	</div>
	<div class="entry">
		<label for="password2">{alias code=new_password_repeat}:</label>
		<span><input type="password" value="" id="password2" name="password2" /></span>
	</div>
	<div class="entry btnDiv">
		<button type="submit">{alias context=admin code=save}</button>
	</div>
</form>