<div class="NewInstanceDialog">
	<h2><span><?= tohtml(tr('Application parameters'))?></span></h2>
	<div class="static_error" ng-show="model.errors.length">
		<div ng-repeat="error in model.errors">{{error}}</div>
	</div>
	<imscp-form form="model"></imscp-form>
</div>
