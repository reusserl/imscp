<div class="ApsPackageList" ng-controller="ApsPackageController as ApsPackage" ng-init="ApsPackage.packageTable()"
     ng-cloak>
	<div class="static_info" ng-if="Application.isAuthorized(Application.userRoles.admin) && ApsPackage.isReadyTable"
	     translate>
		Interface from which you can setup the list of packages that must be available for the customers.<br> Be aware
		that i-MSCP doesn't filter the packages by itself. Before unlocking a package, you must ensure that it fits with
		your hosting policy.
	</div>
	<table ng-table="ApsPackage.tableParams" template-header="custom/header" template-pagination="custom/pager"
	       ng-show="ApsPackage.isReadyTable" ng-switch="$data.length > 0">
		<tbody ng-switch-when="true">
		<tr ng-repeat-start="package in $data track by package.id">
			<td class="Logo" filter="{nbpages: 'custom/filters/nbpages'}">
				<a ng-click="ApsPackage.packageDetailsModal(package)"><img ng-src="{{package.icon_url}}" alt=""></a>
			</td>
			<td class="Description" filter="{category: 'select'}" filter-data="ApsPackage.categories">
				<h4><a ng-click="ApsPackage.packageDetailsModal(package)">{{package.name + ' - ' + package.version}}</a>
				</h4>
				{{package.summary}}
			</td>
			<td class="Details" ng-class="{ 'Locked': package.status == 'locked' }"
			    filter="{globalSearch: 'custom/filters/globalsearch'}">
				<a ng-click="ApsPackage.packageDetailsModal(package)" translate>Details</a>
			</td>
		</tr>
		<tr ng-repeat-end>
			<td class="Version">APS <b>{{package.aps_version}}</b></td>
			<td class="Info">
				<span ng-if="package.package_cert != 'none'" translate>Certified</span>
				<translate>Category:</translate>
				<b>{{package.category}}</b>
				<translate>Vendor:</translate>
				<a target="_blank" ng-href="{{package.vendor_uri}}">{{package.vendor}}</a>
			</td>
			<td class="Details">
				<jq-button ng-if="Application.isAuthorized(Application.userRoles.admin)"
				           ng-click="ApsPackage.packageUpdateStatus(package)"
				           ng-value="package.status|apsTranslateStatus"></jq-button>
				<jq-button ng-if="Application.isAuthorized(Application.userRoles.client)"
				           ng-controller="ApsInstanceController as ApsInstance"
				           ng-click="ApsInstance.newInstanceModal(package)" ng-value="'Install'|translate"></jq-button>
			</td>
		</tr>
		<tbody>
		<tbody ng-switch-when="false">
		<tr>
			<td colspan="3"><b translate>No package matching the given criteria.</b></td>
		</tr>
		</tbody>
		<tfoot ng-switch-when="true">
		<tr>
			<td colspan="3" translate>Total packages: {{ApsPackage.tableParams.total()}}</td>
		</tr>
		</tfoot>
	</table>
	<button ng-if="Application.isAuthorized(Application.userRoles.admin)" ng-click="ApsPackage.packageUpdateIndex()"
	        jq-confirm="{{'Are you sure you want to update the package index? Be aware that this task can take up to 5 minutes.'|translate}}"
	        translate>
		Update package index
	</button>
</div>

<script type="text/ng-template" id="custom/header">
	<ng-table-filter-row></ng-table-filter-row>
</script>

<script type="text/ng-template" id="custom/filters/nbpages">
	<label>
		<select ng-init="count = 5" ng-options="count for count in params.settings().counts"
		        ng-model="count" ng-change="params.count(count)"></select>
	</label>
</script>

<script type="text/ng-template" id="custom/filters/globalsearch">
	<label><input type="text" name="globalSearch" placeholder="{{'Global search'|translate}}"
	              ng-model="ApsPackage.filters.globalSearch"></label>
</script>

<script type="text/ng-template" id="custom/pager">
	<div class="paginator" ng-if="pages.length">
		<span class="icon" ng-class="{ 'i_prev': page.active, 'i_prev_gray': !page.active }"
		      ng-repeat="page in pages" ng-if="page.type == 'prev'" ng-click="params.page(page.number)"></span>
		<span class="icon" ng-class="{ 'i_next': page.active, 'i_next_gray': !page.active }"
		      ng-repeat="page in pages" ng-if="page.type == 'next'" ng-click="params.page(page.number)"></span>
	</div>
</script>
