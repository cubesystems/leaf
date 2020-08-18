<div class="header">
	<div class="padding">
		<h2>{$item->getDisplayString()|escape}</h2>
	</div>
</div>
<div class="content view">
	<div class="section">

		{include file=$_module->pathTo('_view.field') name=title 	   onEmpty="-"}
		{include file=$_module->pathTo('_view.field') name=isActive onEmpty=-}

		<div class="section list" data-type="triggers">
            <h3>{alias code=triggers}</h3>
            <table class="leafTable">
                <tr>
                    <th>{alias code=type}</th>
                    <th>{alias code=contextObject}</th>
                    <th>{alias code=operator}</th>
                </tr>
            {foreach from=$item->getTriggers() item=trigger}
                <tr>
                    <td>{alias code="trigger_type_`$trigger->type`"}</td>
                    <td>{$trigger->getContextObject()|escape}</td>
                    <td>{alias code="operator_`$trigger->type`_`$trigger->operator`"}: {$trigger->getFormatedValue()|escape}</td>
                </tr>
            {/foreach}
            </table>
		</div>
		<div class="section list" data-type="actions">
            <h3>{alias code=actions}</h3>
            <table class="leafTable">
                <tr>
                    <th>{alias code=type}</th>
                    <th>{alias code=contextObject}</th>
                    <th>{alias code=action}</th>
                    <th>{alias code=value}</th>
                </tr>
            {foreach from=$item->getActions() item=action}
                <tr>
                    <td>{alias code="action_type_`$action->type`"}</td>
                    <td>{$action->getContextObject()|escape}</td>
                    <td>{alias code="action_`$action->type`_`$action->action`"}</td>
                    <td>{$action->getFormatedValue()|escape}</td>
                </tr>
            {/foreach}
            </table>
		</div>

	</div>
</div>

{include file=$_module->pathTo('_view.footer')}

