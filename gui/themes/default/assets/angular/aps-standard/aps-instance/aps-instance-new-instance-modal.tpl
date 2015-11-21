<form name="NewApsInstanceForm" id="NewApsInstanceForm" role="form" novalidate class="json-form form-horizontal"
      ng-controller="ApsInstanceController as ApsInstance"
      ng-submit="NewApsInstanceForm.$valid  && ApsInstance.newInstance(model)" autocomplete="off">
	<div class="static_error" ng-repeat="error in model.errors">{{error}}</div>
	<div class="static_warning" ng-show="NewApsInstanceForm.$submitted && NewApsInstanceForm.$invalid" translate>
		You must fill out all required fields.
	</div>
	<div ng-repeat="(group, fields) in model.settings|groupBy:'metadata.group'">
		<fieldset>
			<legend>{{group}}</legend>
			<json-form-fields fields="fields"></json-form-fields>
		</fieldset>
	</div>
</form>
