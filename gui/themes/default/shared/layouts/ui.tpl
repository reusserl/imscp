<!DOCTYPE html>
<html>
<head>
	<title>{TR_PAGE_TITLE}</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="nofollow, noindex">
	<meta name="description" content="i-MSCP: internet Multi Server Control Panel">
	<meta name="author" content="http://i-mscp.net/">
	<link rel="icon" href="{ASSETS_PATH}/images/favicon.ico">
	<!-- build:css /dist/assets/css/ui.css -->
	<link rel="stylesheet" href="/assets/css/jquery-ui-black.css">
	<link rel="stylesheet" href="/assets/css/jquery-ui-blue.css">
	<link rel="stylesheet" href="/assets/css/jquery-ui-green.css">
	<link rel="stylesheet" href="/assets/css/jquery-ui-red.css">
	<link rel="stylesheet" href="/assets/css/jquery-ui-yellow.css">
	<link rel="stylesheet" href="/assets/css/ui.css">
	<link rel="stylesheet" href="/assets/css/aps_standard.css">
	<link rel="stylesheet" href="/assets/css/black.css">
	<link rel="stylesheet" href="/assets/css/blue.css">
	<link rel="stylesheet" href="/assets/css/green.css">
	<link rel="stylesheet" href="/assets/css/red.css">
	<link rel="stylesheet" href="/assets/css/yellow.css">
	<!-- endbuild -->
	<script>
		imscp_i18n = {JS_TRANSLATIONS};
	</script>
	<!-- build:js /dist/assets/js/ui.js -->
	<script src="/assets/js/vendor/jQuery/jquery.js"></script>
	<script src="/assets/js/vendor/jQuery/jquery-ui.js"></script>
	<script src="/assets/js/vendor/jQuery/datatables/datatables.js"></script>
	<script src="/assets/js/vendor/jQuery/datatables/natural.js"></script>
	<script src="/assets/js/vendor/jQuery/pgenerator.jquery.js"></script>
	<script src="/assets/js/vendor/AngularJs/angular.js"></script>
	<script src="/assets/js/vendor/AngularJs/angular-filter.js"></script>
	<script src="/assets/js/vendor/AngularJs/angular-resource.js"></script>
	<script src="/assets/js/vendor/AngularJs/angular-sanitize.js"></script>
	<script src="/assets/js/vendor/AngularJs/ng-table.js"></script>
	<script src="/assets/js/main.js"></script>
	<script src="/assets/js/aps-standard.js"></script>
	<!-- endbuild -->
</head>
<body class="{THEME_COLOR}Layout">
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
