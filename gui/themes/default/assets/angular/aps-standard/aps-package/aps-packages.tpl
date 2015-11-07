<div class="ApsPackageList" data-xhr-loader data-ng-cloak>
	<div data-ng-controller="ApsPackage as PkgCtrl">
		<table data-ng-table="PkgCtrl.tableParams" data-template-header="custom/header"
		       data-template-pagination="custom/pager" data-ng-show="total_packages">
			<tbody>
			<tr data-ng-repeat-start="package in $data">
				<td class="Logo" data-filter="{nbpages: 'custom/filters/nbpages'}">
					<a data-ng-click="showAction()"><img data-ng-src="{{package.icon_url}}" alt=""/></a>
				</td>
				<td class="Description" data-filter="{category: 'select'}" data-filter-data="PkgCtrl.categories">
					<h4><a data-ng-click="showAction()">{{package.name + ' - ' + package.version}}</a></h4>
					{{package.summary}}
				</td>
				<td class="Details" data-ng-class="{ 'Locked': package.status == 'locked' }"
				    data-filter="{search: 'custom/filters/globalsearch'}">
					<a data-ng-click="showAction()"><?= tohtml(tr('Details'))?></a>
				</td>
			</tr>
			<tr data-ng-repeat-end>
				<td class="Version" data-filter="{name: 'paging'}">APS <b>{{package.aps_version}}</b></td>
				<td class="Info">
					<span ng-if="package.package_cert != 'none'"><?= tohtml(tr('Certified'))?></span>
					<?= tohtml(tr('Category'))?>: <b>{{package.category}}</b>
					<?= tohtml(tr('Vendor'))?>: <a target="_blank"
					                               data-ng-href="{{package.vendor_uri}}">{{package.vendor}}</a>
				</td>
				<td class="Details">
					<!-- BDP: adm_btn1 -->
					<jq-button
							data-ng-click="updateAction(package.status == 'unlocked' ? 'locked' : 'unlocked', package.status)"
							data-ng-value="package.status == 'unlocked' ? '<?= tohtml(tr('Lock'), 'htmlAttr')?>' : '<?= tohtml(tr('Unlock'), 'htmlAttr')?>'"></jq-button>
					<!-- EDP: adm_btn1 -->
					<!-- BDP: client_btn1 -->
					<div ng-controller="WriteInstanceController as WrtInstCtrl">
						<jq-button data-ng-click="WrtInstCtrl.newAction(package)"
						           value="<?= tohtml(tr('Install'))?>"></jq-button>
					</div>
					<!-- EDP: client_btn1 -->
				</td>
			</tr>
			<tbody>
			<tfoot>
			<tr>
				<td colspan="3"><?= tohtml(tr('Total packages'))?>: {{total_packages}}</td>
			</tr>
			</tfoot>
		</table>
		<script type="text/ng-template" id="custom/header">
			<ng-table-filter-row></ng-table-filter-row>
		</script>
		<script type="text/ng-template" id="custom/filters/nbpages">
			<label>
				<select data-ng-model="count" data-ng-change="params.count(count)">
					<option data-ng-bind="count" data-ng-value="count" data-ng-repeat="count in params.settings().counts"></option>
				</select>
			</label>
		</script>
		<script type="text/ng-template" id="custom/filters/globalsearch">
			<label><input type="text" placeholder="<?= tohtml(tr('Global search'))?>" data-ng-model="PkgCtrl.search"></label>
		</script>
		<script type="text/ng-template" id="custom/pager">
			<div class="paginator" data-ng-if="pages.length">
				<span class="icon" data-ng-class="{ 'i_prev': page.active, 'i_prev_gray': !page.active }"
				      data-ng-repeat="page in pages" ng-if="page.type == 'prev'"
				      data-ng-click="params.page(page.number)"></span>
				<span class="icon" data-ng-class="{ 'i_next': page.active, 'i_next_gray': !page.active }"
				      data-ng-repeat="page in pages" ng-if="page.type == 'next'"
				      data-ng-click="params.page(page.number)"></span>
			</div>
		</script>
		<!-- BDP: adm_btn2 -->
		<button data-ng-click="updateIndexAction()"><?= tohtml(tr('Update package index'))?></button>
		<!-- EDP: adm_btn2 -->
	</div>
</div>
<div class="loader">
	<div class="modal"></div>
</div>
