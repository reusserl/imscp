<form name="NewApsInstanceForm" role="form" novalidate class="form-horizontal">
	<div data-ng-repeat="(group, fields) in model.settings|groupBy:'metadata.group'">
		<fieldset>
			<legend>{{group}}</legend>
			<json-form-fields fields="fields"></json-form-fields>
		</fieldset>
	</div>
	{{NewApsInstanceForm|json}}
</form>
