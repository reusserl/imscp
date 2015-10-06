
<div>
	<div class="Package">
		<div class="Right">
			<ul>
				<li class="Logo" ng-style="{ 'background-image': 'url({{model.icon_url}})'}"></li>
				<li class="Download">
					<a data-ng-href="{{model.url}}"><?= tohtml(tr('Download'))?></a>
					<!--<span>{{model.length}} MB</span>-->
				</li>
			</ul>
		</div>
		<div class="Left">
			<h2 class="Title">{{model.name}} {{model.version}}</h2>
			<p class="Description">{{model.description}}</p>
			<ul class="Info">
				<li><b><?= tohtml(tr('APS version'))?>:</b> {{model.aps_version}}</li>
				<li><b><?= tohtml(tr('Name'))?></b> {{model.name}}</li>
				<li><b><?= tohtml(tr('Version'))?>:</b> {{model.version}}</li>
				<li><b><?= tohtml(tr('Vendor'))?>:</b> <a data-ng-href="{{model.vendor_uri}}">{{model.vendor}}</a></li>
				<li><b><?= tohtml(tr('Packager'))?>:</b> {{model.packager}}</li>
			</ul>
		</div>
	</div>
</div>
