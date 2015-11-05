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
	<link rel="icon" href="/assets/images/favicon.ico">
	<!-- build:css /dist/assets/css/simple.css -->
	<link rel="stylesheet" href="/assets/css/jquery-ui-black.css">
	<link rel="stylesheet" href="/assets/css/jquery-ui-blue.css">
	<link rel="stylesheet" href="/assets/css/jquery-ui-green.css">
	<link rel="stylesheet" href="/assets/css/jquery-ui-red.css">
	<link rel="stylesheet" href="/assets/css/jquery-ui-yellow.css">
	<link rel="stylesheet" href="/assets/css/simple.css">
	<!-- endbuild -->
	<!-- build:css /dist/assets/css/ie78overrides.css -->
	<!--[if (IE 7)|(IE 8)]>
	<link href="/assets/css/ie78overrides.css" rel="stylesheet">
	<![endif]-->
	<!-- endbuild -->
	<script>
		imscp_i18n = {JS_TRANSLATIONS};
	</script>
	<!-- build:js /dist/assets/js/simple.js -->
	<script src="/assets/js/vendor/jQuery/jquery.js"></script>
	<script src="/assets/js/vendor/jQuery/jquery-ui.js"></script>
	<script src="/assets/js/main.js"></script>
	<!-- endbuild -->
</head>
<body class="{THEME_COLOR}Layout simple">
<div class="wrapper">
	<!-- BDP: header_block -->
	<div id="header">
		<div id="logo"><span>{productLongName}</span></div>
		<div id="copyright"><span><a href="{productLink}" target="blank">{productCopyright}</a></span></div>
	</div>
	<!-- EDP: header_block -->
	<div id="content">
		<!-- BDP: page_message -->
		<div id="notice" class="{MESSAGE_CLS}">{MESSAGE}</div>
		<!-- EDP: page_message -->
		{LAYOUT_CONTENT}
	</div>
</div>
</body>
</html>
