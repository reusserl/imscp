<!DOCTYPE html>
<html ng-app="imscp" ng-strict-di>
<head>
	<title>{TR_PAGE_TITLE}</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="nofollow, noindex">
	<meta name="description" content="i-MSCP: internet Multi Server Control Panel">
	<meta name="author" content="http://i-mscp.net/">
	<link rel="icon" href="../../assets/images/favicon.ico">

	<!-- build:css(assets) /dist/assets/css/imscp.min.css -->
	<link rel="stylesheet" href="../../assets/css/jquery-ui-black.css">
	<link rel="stylesheet" href="../../assets/css/jquery-ui-blue.css">
	<link rel="stylesheet" href="../../assets/css/jquery-ui-green.css">
	<link rel="stylesheet" href="../../assets/css/jquery-ui-red.css">
	<link rel="stylesheet" href="../../assets/css/jquery-ui-yellow.css">
	<link rel="stylesheet" href="../../assets/css/ui.css">

	<link rel="stylesheet" href="../../assets/css/black.css">
	<link rel="stylesheet" href="../../assets/css/blue.css">
	<link rel="stylesheet" href="../../assets/css/green.css">
	<link rel="stylesheet" href="../../assets/css/red.css">
	<link rel="stylesheet" href="../../assets/css/yellow.css">

	<link rel="stylesheet" href="../../assets/angular/xhr-loader/xhr-loader.css">
	<link rel="stylesheet" href="../../assets/angular/json-form/json-form.css">
	<link rel="stylesheet" href="../../assets/angular/aps-standard/aps-standard.css">
	<!-- endbuild -->

	<script>
		var imscp_i18n = {JS_TRANSLATIONS};
		var iMSCP = {
			i18n: imscp_i18n,
			locale: {LOCALE},
			userIdentity: {USER_IDENTITY}
		}
	</script>

	<!-- build:js(assets) /dist/assets/js/imscp.min.js -->
	<script src="../../assets/js/vendor/jQuery/jquery.js"></script>
	<script src="../../assets/js/vendor/jQuery/jquery-ui.js"></script>
	<script src="../../assets/js/vendor/jQuery/datatables/datatables.js"></script>
	<script src="../../assets/js/vendor/jQuery/datatables/natural.js"></script>
	<script src="../../assets/js/vendor/jQuery/pgenerator.jquery.js"></script>
	<script src="../../assets/angular/vendor/angular.js"></script>
	<script src="../../assets/angular/vendor/angular-gettext.js"></script>
	<script src="../../assets/angular/vendor/angular-filter.js"></script>
	<script src="../../assets/angular/vendor/angular-resource.js"></script>
	<script src="../../assets/angular/vendor/angular-sanitize.js"></script>
	<script src="../../assets/angular/vendor/ng-table.js"></script>

	<script src="../../assets/js/main.js"></script>

	<script src="../../assets/angular/application.module.js"></script>
	<script src="../../assets/angular/application.config.js"></script>
	<script src="../../assets/angular/application.controller.js"></script>

	<script src="../../assets/angular/core/core.module.js"></script>
	<script src="../../assets/angular/core/error500-response-interceptor.factory.js"></script>

	<script src="../../assets/angular/authentication/authentication.module.js"></script>
	<script src="../../assets/angular/authentication/authentication.constant.js"></script>
	<script src="../../assets/angular/authentication/authentication-data.value.js"></script>
	<script src="../../assets/angular/authentication/authentication.factory.js"></script>
	<script src="../../assets/angular/authentication/authentication-alert.directive.js"></script>
	<script src="../../assets/angular/authentication/authentication-response-interceptor.factory.js"></script>

	<script src="../../assets/angular/notification/notification.module.js"></script>
	<script src="../../assets/angular/notification/notification.provider.js"></script>
	<script src="../../assets/angular/notification/notification.directive.js"></script>

	<script src="../../assets/angular/modal/modal.module.js"></script>
	<script src="../../assets/angular/modal/modal.service.js"></script>

	<script src="../../assets/angular/jq-ui/jq-ui.module.js"></script>
	<script src="../../assets/angular/jq-ui/jq-button.directive.js"></script>
	<script src="../../assets/angular/jq-ui/jq-confirm.directive.js"></script>
	<script src="../../assets/angular/jq-ui/jq-tabs.directive.js"></script>
	<script src="../../assets/angular/jq-ui/jq-tooltip.directive.js"></script>

	<script src="../../assets/angular/json-form/json-form.module.js"></script>
	<script src="../../assets/angular/json-form/json-form-fields.directive.js"></script>
	<script src="../../assets/angular/json-form/json-form-field.directive.js"></script>

	<script src="../../assets/angular/filter/filter.module.js"></script>
	<script src="../../assets/angular/filter/escape-html.filter.js"></script>
	<script src="../../assets/angular/filter/str-to-regexp.filter.js"></script>
	<script src="../../assets/angular/filter/trusted-html.filter.js"></script>

	<script src="../../assets/angular/xhr-loader/xhr-loader.module.js"></script>
	<script src="../../assets/angular/xhr-loader/xhr-loader.directive.js"></script>

	<script src="../../assets/angular/aps-standard/aps-standard.module.js"></script>
	<script src="../../assets/angular/aps-standard/aps-package/aps-package.module.js"></script>
	<script src="../../assets/angular/aps-standard/aps-package/aps-package.resource.js"></script>
	<script src="../../assets/angular/aps-standard/aps-package/aps-package.controller.js"></script>
	<script src="../../assets/angular/aps-standard/aps-instance/aps-instance.module.js"></script>
	<script src="../../assets/angular/aps-standard/aps-status-translator.filter.js"></script>
	<script src="../../assets/angular/aps-standard/aps-instance/aps-instance.resource.js"></script>
	<script src="../../assets/angular/aps-standard/aps-instance/aps-instance.controller.js"></script>

	<!--<script src="../../assets/angular/templates.js"></script>-->
	<!-- endbuild -->
</head>
<body class="{THEME_COLOR}Layout" ng-controller="ApplicationController" xhr-loader authentication-alert ng-cloak>
<div id="wrapper">
	<div class="header">
		<!-- INCLUDE "../partials/navigation/main_menu.tpl" -->
		<div class="logo"><img src="{ISP_LOGO}" width="212" height="89" alt="i-MSCP logo"/></div>
	</div>
	<div class="location">
		<div class="location-area">
			<h1 class="{SECTION_TITLE_CLASS}">{TR_SECTION_TITLE}</h1>
		</div>
		<ul class="location-menu">
			<!-- BDP: logged_from -->
			<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
			<!-- EDP: logged_from -->
			<li><a class="logout" href="/index.php?action=logout">{TR_MENU_LOGOUT}</a></li>
		</ul>
		<!-- INCLUDE "../partials/navigation/breadcrumbs.tpl" -->
	</div>
	<!-- INCLUDE "../partials/navigation/left_menu.tpl" -->
	<div class="body">
		<h2 class="{TITLE_CLASS}"><span>{TR_TITLE}</span></h2>
		<notification></notification>
		<!-- BDP: page_message -->
		<div class="{MESSAGE_CLS}">{MESSAGE}</div>
		<!-- EDP: page_message -->
		{LAYOUT_CONTENT}
	</div>
	<div class="footer">
		i-MSCP {iMSCP_VERSION}<br>
		Build: {iMSCP_BUILDDATE}<br>
		Codename: {iMSCP_CODENAME}
	</div>
</div>
</body>
</html>
