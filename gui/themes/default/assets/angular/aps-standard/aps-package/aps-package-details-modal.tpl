<jq-tabs id="tabs" ng-transclude class="ApsPackage">
	<ul>
		<li><a href="#tabs-1" translate>General</a></li>
		<li ng-show="model.license"><a href="#tabs-2" translate>License</a></li>
	</ul>
	<div id="tabs-1">
		<div>
			<div class="Right">
				<ul>
					<li class="Logo" ng-style="{ 'background-image': 'url({{model.icon_url}})'}"></li>
					<li class="Download"><a ng-href="{{model.url}}" translate>Download</a></li>
				</ul>
			</div>
			<div class="Left">
				<h2 class="Title">{{model.name}} {{model.version}}</h2>
				{{package.name}}
				<p class="Description">{{model.description}}</p>
				<ul class="Info">
					<li><b translate>APS version:</b> {{model.aps_version}}</li>
					<li><b translate>Name:</b> {{model.name}}</li>
					<li><b translate>Version:</b> {{model.version}}</li>
					<li><b translate>Vendor:</b> <a target="_blank" ng-href="{{model.vendor_uri}}">{{model.vendor}}</a></li>
					<li><b translate>Packager:</b> {{model.packager}}</li>
				</ul>
			</div>
		</div>
	</div>
	<div id="tabs-2" ng-bind-html="model.license|trustedHtml" class="License"></div>
</jq-tabs>
