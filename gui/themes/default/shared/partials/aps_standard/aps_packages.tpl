
<link rel="stylesheet" href="{THEME_ASSETS_PATH}/css/aps_standard.css?v={THEME_ASSETS_VERSION}">
<script src="{THEME_ASSETS_PATH}/js/vendor/angular.min.js"></script>
<script src="{THEME_ASSETS_PATH}/js/vendor/angular-resource.min.js"></script>
<script src="{THEME_ASSETS_PATH}/js/vendor/ng-table.min.js"></script>
<script src="{THEME_ASSETS_PATH}/js/aps_standard.js?v={THEME_ASSETS_VERSION}"></script>

<div data-ng-app="apsStandard">
	<div class="PackageList" ajax-loader ng-cloak>
		<div ng-controller="PackageController as pkgCtrl">
			<table ng-table="pkgCtrl.tableParams" data-template-header="custom/header" data-template-pagination="custom/pager" ng-show="total_packages">
				<tbody>
				<tr ng-repeat-start="package in $data">
					<td class="Logo" data-filter="{nbpages: 'custom/filters/nbpages'}">
						<a data-ng-click="showDetails()"><img data-ng-src="{{package.icon_url}}" alt=""/></a>
					</td>
					<td class="Description" data-filter="{category: 'select'}" data-filter-data="pkgCtrl.categories">
						<h4><a data-ng-click="showDetails()">{{package.name + ' - ' + package.version}}</a></h4>
						{{package.summary}}
					</td>
					<td class="Details" ng-class="{ 'Locked': package.status == 'disabled' }" data-filter="{search: 'custom/filters/globalsearch'}">
						<a data-ng-click="showDetails()">{TR_DETAILS}</a>
					</td>
				</tr>
				<tr ng-repeat-end>
					<td class="Version" filter="{name: 'paging'}">APS <b>{{package.aps_version}}</b></td>
					<td class="Info">
						<span ng-if="package.package_cert != 'none'">{TR_CERTIFIED}</span>
						{TR_CATEGORY}: <b>{{package.category}}</b>
						{TR_VENDOR}: <a data-ng-href="{{package.vendor_uri}}">{{package.vendor}}</a>
					</td>
					<td class="Details">
						<!-- BDP: adm_btn1 -->
						<jq-button ng-click="changeStatus(package.status == 'ok' ? 'disabled' : 'ok', package.status)" data-ng-value="package.status == 'ok' ? '{TR_LOCK}' : '{TR_UNLOCK}'"></jq-button>
						<!-- EDP: adm_btn1 -->
						<!-- BDP: client_btn1 -->
						<jq-button ng-click="install()" value="{TR_INSTALL}"></jq-button>
						<!-- EDP: client_btn1 -->
					</td>
				</tr>
				<tbody>
				<tfoot>
				<tr>
					<td colspan="3">{TR_TOTAL_PACKAGES}: {{total_packages}}</td>
				</tr>
				</tfoot>
			</table>
			<script type="text/ng-template" id="custom/header">
				<ng-table-filter-row></ng-table-filter-row>
			</script>
			<script type="text/ng-template" id="custom/filters/nbpages">
				<label>
					<select data-ng-model="count" data-ng-change="params.count(count)">
						<option data-ng-bind="count" data-ng-value="count"
						        data-ng-repeat="count in params.settings().counts"></option>
					</select>
				</label>
			</script>
			<script type="text/ng-template" id="custom/filters/globalsearch">
				<label><input type="text" placeholder="{TR_GLOBAL_SEARCH}" ng-model="pkgCtrl.search"></label>
			</script>
			<script type="text/ng-template" id="custom/pager">
				<div class="paginator" ng-if="pages.length">
					<span class="icon" data-ng-class="{ 'i_prev': page.active, 'i_prev_gray': !page.active }" data-ng-repeat="page in pages" ng-if="page.type == 'prev'" data-ng-click="params.page(page.number)"></span>
					<span class="icon" data-ng-class="{ 'i_next': page.active, 'i_next_gray': !page.active }" data-ng-repeat="page in pages" ng-if="page.type == 'next'" data-ng-click="params.page(page.number)"></span>
				</div>
			</script>
			<!-- BDP: adm_btn2 -->
			<button data-ng-click="pkgCtrl.updateIndex()">{TR_UPDATE_PACKAGE_INDEX}</button>
			<!-- EDP: adm_btn2 -->
		</div>
	</div>
	<script type="text/ng-template" id="dialog/package/details">
		<div ng-controller="PackageController as pkgCtrl">
			<div class="Package">
				<div class="Right">
					<ul>
						<li class="Logo" ng-style="{ 'background-image': 'url({{model.icon_url}})'}"></li>
						<li class="Download">
							<a data-ng-href="{{model.url}}">{TR_DOWNLOAD}</a>
							<!--<span>{{model.length}} MB</span>-->
						</li>
					</ul>
				</div>
				<div class="Left">
					<h2 class="Title">{{model.name}} {{model.version}}</h2>
					<p class="Description">{{model.description}}</p>
					<ul class="Info">
						<li><b>{TR_APS_VERSION}:</b> {{model.aps_version}}</li>
						<li><b>{TR_NAME}:</b> {{model.name}}</li>
						<li><b>{TR_VERSION}:</b> {{model.version}}</li>
						<li><b>{TR_VENDOR}:</b> <a data-ng-href="{{model.vendor_uri}}">{{model.vendor}}</a></li>
						<li><b>{TR_PACKAGER}:</b> {{model.packager}}</li>
					</ul>
				</div>
			</div>
		</div>
	</script>
</div>
<div class="loader"><div class="modal"></div></div>
