<form id="settingsFrm" name="SettingForm" class="form-horizontal" role="form" ng-controller="WriteInstanceController as SettingCtrl"
      ng-submit="SettingCtrl.createAction(model)" novalidate>
	<div ng-repeat="(group, fields) in model.settings | groupBy: 'metadata.group'">
		<fieldset>
			<legend>{{group}}</legend>
			<div ng-repeat="field in fields">
				<div class="form-group">
					<imscp-form-field field="field"></imscp-form-field>
				</div>
			</div>
		</fieldset>
	</div>
</form>
<script type="text/ng-template" id="template/form/fields/string.tpl">
	<label for="{{field.name|escapeHtml}}" class="col-sm-2 control-label">
		{{field.metadata.label}}
		<jq-tooltip ng-show="field.metadata.tooltip" class="tips icon i_help"
		            ng-attr-title="{{field.metadata.tooltip|escapeHtml}}"></jq-tooltip>
	</label>
	<input type="{{field.metadata.type}}" ng-model="field.value" id="{{field.name|escapeHtml}}"
	       name="{{field.name|escapeHtml}}" ng-value="field.value"
	       ng-pattern="field.metadata.regexp | strToRegexp"
	       ng-minlength="{{field.metadata.min_length}}" ng-maxlength="{{field.metadata.max_length}}" required>
</script>
<script type="text/ng-template" id="template/form/fields/boolean.tpl">
	<div class="col-sm-offset-2">
		<label>
			<jq-tooltip ng-show="field.metadata.tooltip" class="tips icon i_help"
			            ng-attr-title="{{field.metadata.tooltip|escapeHtml}}"></jq-tooltip>
			<input type="checkbox" ng-model="field.value" id="{{field.name|escapeHtml}}"
			       name="{{field.name|escapeHtml}}" ng-value="field.value">
			{{field.metadata.label}}
		</label>
	</div>
</script>
<script type="text/ng-template" id="template/form/fields/enum.tpl">
	<label for="{{field.name|escapeHtml}}" class="col-sm-2 control-label">
		{{field.metadata.label}}
		<jq-tooltip ng-show="field.metadata.tooltip" class="tips icon i_help"
		            ng-attr-title="{{field.metadata.tooltip|escapeHtml}}"></jq-tooltip>
	</label>
	<select name="{{field.name|escapeHtml}}" ng-model="field.value" required>
		<option ng-repeat="opt in field.metadata.choices" ng-selected="opt.value == field.value"
		        value="{{opt.value|escapeHtml}}">{{opt.name}}</option>
	</select>
</script>
