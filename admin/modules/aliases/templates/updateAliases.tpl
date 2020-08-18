	<div class="panelLayout module-aliases method-edit">
		<div class="secondaryPanel">
			{include file=list.tpl}
		</div>

		<div class="primaryPanel">


        <div id="editForm">
            <h1>{alias code=updateAliasesHeading}</h1>

            <table class="updateResults">
            <tr>
                <th>{alias code=numberOfNewGroups}:</th>
                <td>{$newGroups|escape}</td>
            </tr>

            <tr>
                <th>{alias code=numberOfNewTranslations}:</th>
                <td>{$newTranslations|escape}</td>
            </tr>

            <tr>
                <th>{alias code=numberOfUpdatedTranslations}:</th>
                <td>{$updatedTranslations|escape}</td>
            </tr>

            </table>

        </div>

    </div>
</div>