<form id="{{model.id | escapeHtml}}" class="form-horizontal" role="form">
	<div ng-repeat="(key, value) in model | groupBy: 'group'">
		<fieldset form="{{key | escapeHtml}}">
			<legend>{{key}}</legend>
			<div ng-repeat="field in value">
				<imscp-form-field field="field"></imscp-form-field>
			</div>
		</fieldset>
	</div>
	{{model |json}}
</form>

<script type="text/ng-template" id="template/form/field/string.html">
	<div class="form-group">
		<label for="{{field.id | escapeHtml}}" class="col-sm-2 control-label" style="vertical-align: middle;">
			{{field.label}}
			<jq-tooltip ng-show="field.tooltip" class="tips icon i_help" ng-attr-title="{{field.tooltip | escapeHtml}}"></jq-tooltip>
		</label>
		<input type="{{field.type}}" ng-model="field.value" id="{{field.id | escapeHtml}}" name="{{field.id | escapeHtml}}" ng-value="field.value">
	</div>
	<div ng-show="{{field.type == 'password'}}" class="form-group">
		<label for="{{field.id | escapeHtml}}_repeat" class="col-sm-2 control-label" style="vertical-align: middle;">
			<?= tohtml(tr('Password Confirmation'))?>
		</label>
		<input identicalTo="{{field.id}}" type="{{field.type}}" ng-model="field.value_confirmation" id="{{field.id | escapeHtml}}_repeat" name="{{field.id | escapeHtml}}_repeat">
	</<div>
</script>
<script type="text/ng-template" id="template/form/field/boolean.html">
	<div class="form-group">
	<div class="col-sm-offset-2 col-sm-10">
		<div class="checkbox">
			<label>
				<input type="checkbox" ng-model="field.value" id="{{field.id | escapeHtml}}" name="{{field.id | escapeHtml}}" ng-value="field.value">
				{{field.label}}
				<jq-tooltip ng-show="field.tooltip" class="tips icon i_help" ng-attr-title="{{field.tooltip | escapeHtml}}"></jq-tooltip>
			</label>
		</div>
	</div>
	</div>
</script>
<script type="text/ng-template" id="template/form/field/enum.html">
	<div class="form-group">
	<label for="{{field.id | escapeHtml}}" class="col-sm-2 control-label">
		{{field.label}}
		<jq-tooltip ng-show="field.tooltip" class="tips icon i_help" ng-attr-title="{{field.tooltip | escapeHtml}}"></jq-tooltip>
	</label>
	<select name="{{field.id | escapeHtml}}" ng-model="field.value">
		<option ng-repeat="opt in field.choices" ng-selected="opt.value == field.value" value="{{opt.value | escapeHtml}}">
			{{opt.name}}
		</option>
	</select>
	</div>
</script>
