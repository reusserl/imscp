<div class="ApsInstanceList" ng-controller="ApsInstanceController as ApsInstance" ng-init="ApsInstance.listInstances()">
	<table ng-show="ApsInstance.instances.length">
		<thead>
		<tr>
			<th translate>ID</th>
			<th translate>Name</th>
			<th translate>Location</th>
			<th translate>Status</th>
			<th translate>Action</th>
		</tr>
		</thead>
		<tbody>
		<tr ng-repeat="instance in ApsInstance.instances">
			<td>{{instance.id}}</td>
			<td>{{instance.package.name}}</td>
			<td>
				<a ng-if="instance.status == 'ok'" ng-href="{{instance.location}}">{{instance.location}}</a>
				<span ng-if="instance.status != 'ok'" ng-href="{{instance.location}}">{{instance.location}}</span>
			</td>
			<td>
				{{instance.status|apsTranslateStatus}}
			</td>
			<td ng-switch="instance.status">
				<div ng-switch-when="ok">
						<span ng-if="instance.package.status == 'unlocked' || instance.package.status == 'outdated'">
							<a href="#" class="icon i_reload" ng-click="ApsInstance.reinstallInstance(instance)"
							   jq-confirm="{{'Are you sure you want to reinstall this application?'|translate}}"
							   translate>Reinstall</a>
						</span>
					<a href="#" class="icon i_delete" ng-click="ApsInstance.deleteInstance(instance)"
					   jq-confirm="{{'Are you sure you want to delete this application?'|translate}}"
					   translate>Install</a>
				</div>
				<span ng-switch-default translate>N/A</span>
			</td>
		</tr>
		</tbody>
	</table>
</div>
