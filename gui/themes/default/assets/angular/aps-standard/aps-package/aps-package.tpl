<jq-tabs id="tabs" data-ng-transclude>
	<ul>
		<li><a href="#tabs-1"><?= tohtml(tr('General'))?></a></li>
		<li data-ng-show="model.license"><a href="#tabs-2"><?= tohtml(tr('License'))?></a></li>
	</ul>
	<div id="tabs-1">
		<div class="Package">
			<div class="Right">
				<ul>
					<li class="Logo" data-ng-style="{ 'background-image': 'url({{model.icon_url}})'}"></li>
					<li class="Download">
						<a data-ng-href="{{model.url}}"><?= tohtml(tr('Download'))?></a>
					</li>
				</ul>
			</div>
			<div class="Left">
				<h2 class="Title">{{model.name}} {{model.version}}</h2>
				{{package.name}}
				<p class="Description">{{model.description}}</p>
				<ul class="Info">
					<li><b><?= tohtml(tr('APS version'))?>:</b> {{model.aps_version}}</li>
					<li><b><?= tohtml(tr('Name'))?></b> {{model.name}}</li>
					<li><b><?= tohtml(tr('Version'))?>:</b> {{model.version}}</li>
					<li><b><?= tohtml(tr('Vendor'))?>:</b> <a target="_blank" data-ng-href="{{model.vendor_uri}}">{{model.vendor}}</a></li>
					<li><b><?= tohtml(tr('Packager'))?>:</b> {{model.packager}}</li>
				</ul>
			</div>
		</div>
	</div>
	<div id="tabs-2" ng-bind-html="model.license|toTrustedHtml" class="License"></div>
</jq-tabs>
