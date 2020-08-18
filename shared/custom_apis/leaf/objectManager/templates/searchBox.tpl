<form id="frmSearch" onsubmit="return searchSuggestForm()" action="?{$baseUrl|escape}">
	<input type="text" id="txtSearch" name="txtSearch" alt="Search Criteria" onkeyup="searchSuggest(this)" autocomplete="off" />
	<button type="submit">Meklēt</button>
	<div id="search_suggest"></div>
</form>