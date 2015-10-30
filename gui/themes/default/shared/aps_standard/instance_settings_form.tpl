<form class="form-horizontal" role="form" novalidate>
	<div ng-repeat="(group, fields) in model | groupBy: 'metadata.group'">
		<fieldset form="{{group | escapeHtml}}">
			<legend>{{group}}</legend>
			<div ng-repeat="field in fields">
				<imscp-form-field field="field"></imscp-form-field>
			</div>
		</fieldset>
	</div>
</form>
<script type="text/ng-template" id="template/form/fields/string.tpl">
	<div class="form-group">
		<label for="{{field.name | escapeHtml}}" class="col-sm-2 control-label">
			{{field.metadata.label}}
			<jq-tooltip ng-show="field.metadata.tooltip" class="tips icon i_help"
			            ng-attr-title="{{field.metadata.tooltip | escapeHtml}}"></jq-tooltip>
		</label>
		<input type="{{field.metadata.type}}" ng-model="field.value" id="{{field.name | escapeHtml}}"
		       name="{{field.name | escapeHtml}}" ng-value="field.value"
		       ng-pattern="field.metadata.regexp | strToRegexp"
		       ng-minlength="{{field.metadata.min_length}}" ng-maxlength="{{field.metadata.max_length}}" required>
	</div>
	<div ng-show="{{field.metadata.type == 'password'}}" class="form-group">
		<label for="{{field.name | escapeHtml}}_repeat" class="col-sm-2 control-label">
			<?= tohtml(tr('Password Confirmation'))?>
		</label>
		<input identicalTo="{{field.name}}" type="{{field.metadata.type}}" ng-model="field.value_confirmation"
		       id="{{field.name | escapeHtml}}_repeat" name="{{field.name | escapeHtml}}_repeat"
		       ng-pattern="field.metadata.regexp | strToRegexp" ng-minlength="{{field.metadata.min_length}}"
		       ng-maxlength="{{field.metadata.max_length}}" required>
	</div>
</script>
<script type="text/ng-template" id="template/form/fields/boolean.tpl">
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<div class="checkbox">
				<label>
					<input type="checkbox" ng-model="field.value" id="{{field.name | escapeHtml}}"
					       name="{{field.name | escapeHtml}}" ng-value="field.value">
					{{field.metadata.label}}
					<jq-tooltip ng-show="field.metadata.tooltip" class="tips icon i_help"
					            ng-attr-title="{{field.metadata.tooltip | escapeHtml}}"></jq-tooltip>
				</label>
			</div>
		</div>
	</div>
</script>
<script type="text/ng-template" id="template/form/fields/enum.tpl">
	<div class="form-group">
		<label for="{{field.name | escapeHtml}}" class="col-sm-2 control-label">
			{{field.metadata.label}}
			<jq-tooltip ng-show="field.metadata.tooltip" class="tips icon i_help"
			            ng-attr-title="{{field.metadata.tooltip | escapeHtml}}"></jq-tooltip>
		</label>
		<select name="{{field.name | escapeHtml}}" ng-model="field.value">
			<option ng-repeat="opt in field.metadata.choices" ng-selected="opt.value == field.value"
			        value="{{opt.value | escapeHtml}}" required>{{opt.name}}</option>
		</select>
	</div>
</script>
