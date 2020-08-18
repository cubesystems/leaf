<form id="profileForm" method="get" action="./">
	<input type="hidden" name="module" value="profile" />
	<input type="hidden" name="do" value="edit" />
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
		<label>{alias code=email}:</label>
		<span>{$obj->email|escape}</span>
	</div>
	<div class="entry btnDiv">
		<button type="submit">{alias context=admin code=edit}</button>
	</div>
</form>