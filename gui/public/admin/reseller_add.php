<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Returns reseller data
 *
 * @return array Reference to array of data
 */
function &admin_getData()
{
	static $data = null;

	if (null === $data) {
		// Fetch server ip list
		$query = "SELECT `ip_id`, `ip_number` FROM `server_ips`  ORDER BY `ip_number`";
		$stmt = exec_query($query);

		if ($stmt->rowCount()) {
			$data['server_ips'] = $stmt->fetchAll();
		} else {
			set_page_message(tr('Unable to get the IP address list. Please fix this problem.'), 'error');
			redirectTo('manage_users.php');
		}

		$phpEditor = \iMSCP\Core\Php\PhpEditor::getInstance();

		foreach (
			[
				'admin_name' => '',
				'password' => '',
				'password_confirmation' => '',
				'fname' => '',
				'lname' => '',
				'gender' => 'U',
				'firm' => '',
				'zip' => '',
				'city' => '',
				'state' => '',
				'country' => '',
				'email' => '',
				'phone' => '',
				'fax' => '',
				'street1' => '',
				'street2' => '',
				'max_dmn_cnt' => '0',
				'max_sub_cnt' => '0',
				'max_als_cnt' => '0',
				'max_mail_cnt' => '0',
				'max_ftp_cnt' => '0',
				'max_sql_db_cnt' => '0',
				'max_sql_user_cnt' => '0',
				'max_traff_amnt' => '0',
				'max_disk_amnt' => '0',
				'aps_standard' => 'no',
				'support_system' => 'no',
				'customer_id' => '',
				'php_ini_system' => 'no',
				'php_ini_al_disable_functions' => $phpEditor->getRePermVal('phpiniDisableFunctions'),
				'php_ini_al_allow_url_fopen' => $phpEditor->getRePermVal('phpiniAllowUrlFopen'),
				'php_ini_al_display_errors' => $phpEditor->getRePermVal('phpiniDisplayErrors'),
				'php_ini_max_post_max_size' => $phpEditor->getRePermVal('phpiniPostMaxSize'),
				'php_ini_max_upload_max_filesize' => $phpEditor->getRePermVal('phpiniUploadMaxFileSize'),
				'php_ini_max_max_execution_time' => $phpEditor->getRePermVal('phpiniMaxExecutionTime'),
				'php_ini_max_max_input_time' => $phpEditor->getRePermVal('phpiniMaxInputTime'),
				'php_ini_max_memory_limit' => $phpEditor->getRePermVal('phpiniMemoryLimit')
			] as $key => $value
		) {
			if (isset($_POST[$key])) {
				$data[$key] = clean_input($_POST[$key]);
			} else {
				$data[$key] = $value;
			}
		}

		if (isset($_POST['reseller_ips']) && is_array($_POST['reseller_ips'])) {
			foreach ($_POST['reseller_ips'] as $key => $value) {
				$_POST['reseller_ips'][$key] = clean_input($value);
			}

			$data['reseller_ips'] = $_POST['reseller_ips'];
		} else { // We are safe here
			$data['reseller_ips'] = [];
		}
	}

	return $data;
}

/**
 * Generates account form
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl Template engine instance
 * @param array &$data Reseller data
 * @return void
 */
function _admin_generateAccountForm($tpl, &$data)
{
	$tpl->assign([
		'TR_ACCOUNT_DATA' => tr('Account data'),
		'TR_RESELLER_NAME' => tr('Name'),
		'RESELLER_NAME' => tohtml($data['admin_name']),
		'TR_PASSWORD' => tr('Password'),
		'TR_PASSWORD_CONFIRMATION' => tr('Password confirmation'),
		'PASSWORD_CONFIRMATION' => tohtml($data['password_confirmation']),
		'TR_EMAIL' => tr('Email'),
		'EMAIL' => tohtml($data['email'])
	]);
}

/**
 * Generates IP list form
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl Template engine instance
 * @param array &$data Reseller data
 * @return void
 */
function _admin_generateIpListForm($tpl, &$data)
{
	$cfg = \iMSCP\Core\Application::getInstance()->getConfig();

	$htmlChecked = $cfg['HTML_CHECKED'];

	$tpl->assign([
		'TR_IP_ADDRESS' => tr('IP address'),
		'TR_IP_LABEL' => tr('Label'),
		'TR_ASSIGN' => tr('Assign')
	]);

	\iMSCP\Core\Application::getInstance()->getEventManager()->attach('onGetJsTranslations', function ($e) {
		/** @var $e \Zend\EventManager\Event */
		$e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
	});

	foreach ($data['server_ips'] as $ipData) {
		$tpl->assign([
			'IP_ID' => tohtml($ipData['ip_id']),
			'IP_NUMBER' => tohtml($ipData['ip_number']),
			'IP_ASSIGNED' => in_array($ipData['ip_id'], $data['reseller_ips']) ? $htmlChecked : ''
		]);

		$tpl->parse('IP_BLOCK', '.ip_block');
	}
}

/**
 * Generates features form
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl Template engine instance
 * @param array &$data Reseller data
 * @return void
 */
function _admin_generateLimitsForm($tpl, &$data)
{
	$tpl->assign([
		'TR_ACCOUNT_LIMITS' => tr('Account limits'),
		'TR_MAX_DMN_CNT' => tr('Domain limit') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
		'MAX_DMN_CNT' => tohtml($data['max_dmn_cnt']),
		'TR_MAX_SUB_CNT' => tr('Subdomain limit') . '<br /><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
		'MAX_SUB_CNT' => tohtml($data['max_sub_cnt']),
		'TR_MAX_ALS_CNT' => tr('Domain alias limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
		'MAX_ALS_CNT' => tohtml($data['max_als_cnt']),
		'TR_MAX_MAIL_CNT' => tr('Email account limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
		'MAX_MAIL_CNT' => tohtml($data['max_mail_cnt']),
		'TR_MAX_FTP_CNT' => tr('FTP account limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
		'MAX_FTP_CNT' => tohtml($data['max_ftp_cnt']),
		'TR_MAX_SQL_DB_CNT' => tr('SQL database limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
		'MAX_SQL_DB_CNT' => tohtml($data['max_sql_db_cnt']),
		'TR_MAX_SQL_USER_CNT' => tr('SQL user limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
		'MAX_SQL_USER_CNT' => tohtml($data['max_sql_user_cnt']),
		'TR_MAX_TRAFF_AMNT' => tr('Monthly traffic limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
		'MAX_TRAFF_AMNT' => tohtml($data['max_traff_amnt']),
		'TR_MAX_DISK_AMNT' => tr('Disk space limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
		'MAX_DISK_AMNT' => tohtml($data['max_disk_amnt'])
	]);
}

/**
 * Generates features form
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl Template engine instance
 * @param array &$data Reseller data
 * @return void
 */
function _admin_generateFeaturesForm($tpl, &$data)
{
	$cfg = \iMSCP\Core\Application::getInstance()->getConfig();
	$htmlChecked = $cfg['HTML_CHECKED'];
	$tpl->assign([
		'TR_FEATURES' => tr('Features'),

		'TR_SETTINGS' => tr('Settings'),
		'TR_PHP_EDITOR' => tr('PHP Editor'),
		'TR_PHP_EDITOR_SETTINGS' => tr('PHP Editor Settings'),
		'TR_PERMISSIONS' => tr('Permissions'),
		'TR_DIRECTIVES_VALUES' => tr('PHP directives values'),
		'TR_FIELDS_OK' => tr('All fields seem to be valid.'),
		'TR_VALUE_ERROR' => tr('Value for the PHP <strong>%%s</strong> directive must be between %%d and %%d.'),
		'TR_CLOSE' => tr('Close'),

		'PHP_INI_SYSTEM_YES' => ($data['php_ini_system'] == 'yes') ? $htmlChecked : '',
		'PHP_INI_SYSTEM_NO' => ($data['php_ini_system'] != 'yes') ? $htmlChecked : '',

		'TR_PHP_INI_AL_ALLOW_URL_FOPEN' => tr('Can edit the PHP %s directive', '<b>allow_url_fopen</b>'),
		'PHP_INI_AL_ALLOW_URL_FOPEN_YES' => ($data['php_ini_al_allow_url_fopen'] == 'yes') ? $htmlChecked : '',
		'PHP_INI_AL_ALLOW_URL_FOPEN_NO' => ($data['php_ini_al_allow_url_fopen'] != 'yes') ? $htmlChecked : '',

		'TR_PHP_INI_AL_DISPLAY_ERRORS' => tr('Can edit the PHP %s directive', '<b>display_errors</b>'),
		'PHP_INI_AL_DISPLAY_ERRORS_YES' => ($data['php_ini_al_display_errors'] == 'yes') ? $htmlChecked : '',
		'PHP_INI_AL_DISPLAY_ERRORS_NO' => ($data['php_ini_al_display_errors'] != 'yes') ? $htmlChecked : '',

		'TR_PHP_INI_MAX_MEMORY_LIMIT' => tr('Max value for the %s PHP directive', '<b>memory_limit</b>'),
		'PHP_INI_MAX_MEMORY_LIMIT' => tohtml($data['php_ini_max_memory_limit']),

		'TR_PHP_INI_MAX_UPLOAD_MAX_FILESIZE' => tr('Max value for the %s PHP directive', '<b>upload_max_filesize</b>'),
		'PHP_INI_MAX_UPLOAD_MAX_FILESIZE' => tohtml($data['php_ini_max_upload_max_filesize']),

		'TR_PHP_INI_MAX_POST_MAX_SIZE' => tr('Max value for the %s PHP directive', '<b>post_max_size</b>'),
		'PHP_INI_MAX_POST_MAX_SIZE' => tohtml($data['php_ini_max_post_max_size']),

		'TR_PHP_INI_MAX_MAX_EXECUTION_TIME' => tr('Max value for the %s PHP directive', '<b>max_execution_time</b>'),
		'PHP_INI_MAX_MAX_EXECUTION_TIME' => tohtml($data['php_ini_max_max_execution_time']),

		'TR_PHP_INI_MAX_MAX_INPUT_TIME' => tr('Max value for the %s PHP directive', '<b>max_input_time</b>'),
		'PHP_INI_MAX_MAX_INPUT_TIME' => tohtml($data['php_ini_max_max_input_time']),

		'TR_APS_STANDARD' => tr('APS Standard'),
		'APS_STANDARD_YES' => ($data['aps_standard'] == 'yes') ? $htmlChecked : '',
		'APS_STANDARD_NO' => ($data['aps_standard'] != 'yes') ? $htmlChecked : '',

		'TR_SUPPORT_SYSTEM' => tr('Support system'),
		'SUPPORT_SYSTEM_YES' => ($data['support_system'] == 'yes') ? $htmlChecked : '',
		'SUPPORT_SYSTEM_NO' => ($data['support_system'] != 'yes') ? $htmlChecked : '',

		'TR_PHP_INI_PERMISSION_HELP' => tr('Yes means that the reseller can allow his customers to edit this directive'),
		'TR_YES' => tr('Yes'),
		'TR_NO' => tr('No'),
		'TR_MIB' => tr('MiB'),
		'TR_SEC' => tr('Sec.')
	]);

	if ($cfg['HTTPD_SERVER'] != 'apache_itk') {
		$tpl->assign([
			'TR_PHP_INI_AL_DISABLE_FUNCTIONS' => tr('Can edit the PHP %s directive', '<b>disable_functions</b>'),
			'PHP_INI_AL_DISABLE_FUNCTIONS_YES' => ($data['php_ini_al_disable_functions'] == 'yes') ? $htmlChecked : '',
			'PHP_INI_AL_DISABLE_FUNCTIONS_NO' => ($data['php_ini_al_disable_functions'] != 'yes') ? $htmlChecked : ''
		]);
	} else {
		$tpl->assign('PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK', '');
	}
}

/**
 * Generates features form
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl Template engine instance
 * @param array $data Domain data
 * @return void
 */
function  _admin_generatePersonalDataFrom($tpl, &$data)
{
	$cfg = \iMSCP\Core\Application::getInstance()->getConfig();
	$htmlSelected = $cfg['HTML_SELECTED'];
	$tpl->assign([
		'TR_PERSONAL_DATA' => tr('Personal data'),
		'TR_CUSTOMER_ID' => tr('Customer ID'),
		'CUSTOMER_ID' => tohtml($data['customer_id']),
		'TR_FNAME' => tr('First name'),
		'FNAME' => tohtml($data['fname']),
		'TR_LNAME' => tr('Last name'),
		'LNAME' => tohtml($data['lname']),
		'TR_GENDER' => tr('Gender'),
		'TR_MALE' => tr('Male'),
		'MALE' => ($data['gender'] == 'M') ? $htmlSelected : '',
		'TR_FEMALE' => tr('Female'),
		'FEMALE' => ($data['gender'] == 'F') ? $htmlSelected : '',
		'TR_UNKNOWN' => tr('Unknown'),
		'UNKNOWN' => ($data['gender'] != 'M' && $data['gender'] != 'F') ? $htmlSelected : '',
		'TR_FIRM' => tr('Company'),
		'FIRM' => tohtml($data['firm']),
		'TR_STREET1' => tr('Street 1'),
		'STREET1' => tohtml($data['street1']),
		'TR_STREET2' => tr('Street 2'),
		'STREET2' => tohtml($data['street2']),
		'TR_ZIP' => tr('Zip code'),
		'ZIP' => tohtml($data['zip']),
		'TR_CITY' => tr('City'),
		'CITY' => tohtml($data['city']),
		'TR_STATE' => tr('State'),
		'STATE' => tohtml($data['state']),
		'TR_COUNTRY' => tr('Country'),
		'COUNTRY' => tohtml($data['country']),
		'TR_PHONE' => tr('Phone'),
		'PHONE' => tohtml($data['phone']),
		'TR_FAX' => tr('Fax'),
		'FAX' => tohtml($data['fax'])
	]);
}

/**
 * Generate edit form
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl Template engine instance
 * @param array &$data Reseller data
 * @return void
 */
function admin_generateForm($tpl, &$data)
{
	_admin_generateAccountForm($tpl, $data);
	_admin_generateIpListForm($tpl, $data);
	_admin_generateLimitsForm($tpl, $data);
	_admin_generateFeaturesForm($tpl, $data);
	_admin_generatePersonalDataFrom($tpl, $data);
}

/**
 * Create reseller account
 *
 * @throws Exception
 * @return bool
 */
function admin_checkAndCreateResellerAccount()
{
	global $errFieldsStack;

	\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onBeforeAddUser);

	$cfg = \iMSCP\Core\Application::getInstance()->getConfig();
	$errFieldsStack = [];

	// Get needed data
	$data =& admin_getData();

	/** @var \Doctrine\DBAL\Connection $db */
	$db = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('Database');

	try {
		$db->beginTransaction();

		// Check for reseller name

		$query = "SELECT COUNT(`admin_id`) `usernameExist` FROM `admin` WHERE `admin_name` = ? LIMIT 1";
		$stmt = exec_query($query, $data['admin_name']);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($row['usernameExist']) {
			set_page_message(tr("The username %s is not available.", '<b>' . $data['admin_name'] . '</b>'), 'error');
			$errFieldsStack[] = 'admin_name';
		} elseif (!validates_username($data['admin_name'])) {
			set_page_message(tr('Incorrect username length or syntax.'), 'error');
			$errFieldsStack[] = 'admin_name';
		}

		// check for password

		if (empty($data['password'])) {
			set_page_message(tr('You must provide a password.'), 'error');
			$errFieldsStack[] = 'password';
			$errFieldsStack[] = 'password_confirmation';
		} elseif ($data['password'] != $data['password_confirmation']) {
			set_page_message(tr("Passwords do not match."), 'error');
			$errFieldsStack[] = 'password';
			$errFieldsStack[] = 'password_confirmation';
		} elseif (!checkPasswordSyntax($data['password'])) {
			$errFieldsStack[] = 'password';
			$errFieldsStack[] = 'password_confirmation';
		}

		// Check for email address

		if (!chk_email($data['email'])) {
			set_page_message(tr('Incorrect syntax for email address.'), 'error');
			$errFieldsStack[] = 'email';
		}

		// Check for ip addresses - We are safe here
		$resellerIps = [];
		foreach ($data['server_ips'] as $serverIpData) {
			if (in_array($serverIpData['ip_id'], $data['reseller_ips'])) {
				$resellerIps[] = $serverIpData['ip_id'];
			}
		}

		sort($resellerIps);

		if (empty($resellerIps)) {
			set_page_message(tr('You must assign at least one IP per reseller.'), 'error');
		}

		// Check for max domains limit
		if (!imscp_limit_check($data['max_dmn_cnt'], null)) {
			set_page_message(tr('Incorrect limit for %s.', tr('domain')), 'error');
			$errFieldsStack[] = 'max_dmn_cnt';
		}

		// Check for max subdomains limit
		if (!imscp_limit_check($data['max_sub_cnt'])) {
			set_page_message(tr('Incorrect limit for %s.', tr('subdomains')), 'error');
			$errFieldsStack[] = 'max_sub_cnt';
		}

		// check for max domain aliases limit
		if (!imscp_limit_check($data['max_als_cnt'])) {
			set_page_message(tr('Incorrect limit for %s.', tr('domain aliases')), 'error');
			$errFieldsStack[] = 'max_als_cnt';
		}

		// Check for max mail accounts limit
		if (!imscp_limit_check($data['max_mail_cnt'])) {
			set_page_message(tr('Incorrect limit for %s.', tr('email accounts')), 'error');
			$errFieldsStack[] = 'max_mail_cnt';
		}

		// Check for max ftp accounts limit
		if (!imscp_limit_check($data['max_ftp_cnt'])) {
			set_page_message(tr('Incorrect limit for %s.', tr('FTP accounts')), 'error');
			$errFieldsStack[] = 'max_ftp_cnt';
		}

		// Check for max Sql databases limit
		if (!imscp_limit_check($data['max_sql_db_cnt'])) {
			set_page_message(tr('Incorrect limit for %s.', tr('SQL databases')), 'error');
			$errFieldsStack[] = 'max_sql_db_cnt';
		} elseif ($_POST['max_sql_db_cnt'] == -1 && $_POST['max_sql_user_cnt'] != -1) {
			set_page_message(tr('SQL database limit is disabled but SQL user limit is not.'), 'error');
			$errFieldsStack[] = 'max_sql_db_cnt';
		}

		// Check for max Sql users limit
		if (!imscp_limit_check($data['max_sql_user_cnt'])) {
			set_page_message(tr('Incorrect limit for %s.', tr('SQL users')), 'error');
			$errFieldsStack[] = 'max_sql_user_cnt';
		} elseif ($_POST['max_sql_user_cnt'] == -1 && $_POST['max_sql_db_cnt'] != -1) {
			set_page_message(tr('SQL user limit is disabled but SQL database limit is not.'), 'error');
			$errFieldsStack[] = 'max_sql_user_cnt';
		}

		// Check for max monthly traffic limit
		if (!imscp_limit_check($data['max_traff_amnt'], null)) {
			set_page_message(tr('Incorrect limit for %s.', tr('traffic')), 'error');
			$errFieldsStack[] = 'max_traff_amnt';
		}

		// Check for max disk space limit
		if (!imscp_limit_check($data['max_disk_amnt'], null)) {
			set_page_message(tr('Incorrect limit for %s.', tr('Disk space')), 'error');
			$errFieldsStack[] = 'max_disk_amnt';
		}

		// Check for PHP editor settings
		$phpEditor = \iMSCP\Core\Php\PhpEditor::getInstance();

		if ($data['php_ini_system'] == 'yes') {
			// Check for permissions - We are safe here (If a permissions is wrong, default value is used)
			$phpEditor->setRePerm('phpiniSystem', 'yes');

			if ($cfg['HTTPD_SERVER'] != 'apache_itk') {
				$phpEditor->setRePerm('phpiniDisableFunctions', $data['php_ini_al_disable_functions']);
			} else {
				$phpEditor->setRePerm('phpiniDisableFunctions', 'no');
			}

			$phpEditor->setRePerm('phpiniAllowUrlFopen', $data['php_ini_al_allow_url_fopen']);
			$phpEditor->setRePerm('phpiniDisplayErrors', $data['php_ini_al_display_errors']);

			// Check for max values
			if (!$phpEditor->setRePerm('phpiniPostMaxSize', $data['php_ini_max_post_max_size']) ||
				!$phpEditor->setRePerm('phpiniUploadMaxFileSize', $data['php_ini_max_upload_max_filesize']) ||
				!$phpEditor->setRePerm('phpiniMaxExecutionTime', $data['php_ini_max_max_execution_time']) ||
				!$phpEditor->setRePerm('phpiniMaxInputTime', $data['php_ini_max_max_input_time']) ||
				!$phpEditor->setRePerm('phpiniMemoryLimit', $data['php_ini_max_memory_limit'])
			) {
				set_page_message(tr('Please check the PHP editor settings.'), 'error');
			}
		} else {
			$phpEditor->loadReDefaultPerm();
		}

		if (empty($errFieldsStack) && !isset($_SESSION['pageMessages'])) { // Update process begin here
			// Insert reseller personal data into database

			$query = "
				INSERT INTO `admin` (
					`admin_name`, `admin_pass`, `admin_type`, `domain_created`,
					`created_by`, `fname`, `lname`, `firm`, `zip`, `city`, `state`,
					`country`, `email`, `phone`, `fax`, `street1`, `street2`, `gender`
				) VALUES (
					?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
				)
			";
			exec_query($query, [
				$data['admin_name'], \iMSCP\Core\Utils\Crypt::bcrypt($data['password']), 'reseller', time(),
				$_SESSION['user_id'], $data['fname'], $data['lname'], $data['firm'], $data['zip'], $data['city'],
				$data['state'], $data['country'], $data['email'], $data['phone'], $data['fax'], $data['street1'],
				$data['street2'], $data['gender']
			]);

			// Get new reseller unique identifier
			$resellerId = $db->lastInsertId();

			// Insert reseller GUI properties into database

			$query = 'REPLACE INTO `user_gui_props` (`user_id`, `lang`, `layout`) VALUES (?, ?, ?)';
			exec_query($query, [$resellerId, $cfg['USER_INITIAL_LANG'], $cfg['USER_INITIAL_THEME']]);

			// Insert reseller properties into database
			$query = "
				INSERT INTO `reseller_props` (
					`reseller_id`, `reseller_ips`, `max_dmn_cnt`, `current_dmn_cnt`,
					`max_sub_cnt`, `current_sub_cnt`, `max_als_cnt`, `current_als_cnt`,
					`max_mail_cnt`, `current_mail_cnt`, `max_ftp_cnt`, `current_ftp_cnt`,
					`max_sql_db_cnt`, `current_sql_db_cnt`, `max_sql_user_cnt`,
					`current_sql_user_cnt`, `max_traff_amnt`, `current_traff_amnt`,
					`max_disk_amnt`, `current_disk_amnt`, `support_system`, `customer_id`,
					`php_ini_system`, `php_ini_al_disable_functions`, `php_ini_al_allow_url_fopen`,
					`php_ini_al_display_errors`, `php_ini_max_post_max_size`,
					`php_ini_max_upload_max_filesize`, `php_ini_max_max_execution_time`,
					`php_ini_max_max_input_time`, `php_ini_max_memory_limit`, `aps_standard`
				) VALUES (
					?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
				)
			";
			exec_query($query, [
				$resellerId, implode(';', $resellerIps) . ';', $data['max_dmn_cnt'], '0',
				$data['max_sub_cnt'], '0', $data['max_als_cnt'], '0', $data['max_mail_cnt'], '0',
				$data['max_ftp_cnt'], '0', $data['max_sql_db_cnt'], '0', $data['max_sql_user_cnt'], '0',
				$data['max_traff_amnt'], '0', $data['max_disk_amnt'], '0', $data['support_system'],
				$data['customer_id'], $phpEditor->getRePermVal('phpiniSystem'),
				$phpEditor->getRePermVal('phpiniDisableFunctions'), $phpEditor->getRePermVal('phpiniAllowUrlFopen'),
				$phpEditor->getRePermVal('phpiniDisplayErrors'), $phpEditor->getRePermVal('phpiniPostMaxSize'),
				$phpEditor->getRePermVal('phpiniUploadMaxFileSize'), $phpEditor->getRePermVal('phpiniMaxExecutionTime'),
				$phpEditor->getRePermVal('phpiniMaxInputTime'), $phpEditor->getRePermVal('phpiniMemoryLimit'),
				$data['aps_standard']
			]);

			$db->commit();

			\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAfterAddUser);

			// Send welcome mail to the new reseller
			send_add_user_auto_msg(
				$_SESSION['user_id'], $data['admin_name'], $data['password'], $data['email'], $data['fname'],
				$data['lname'], tr('Reseller')
			);
			write_log("A new reseller account (<b>{$data['admin_name']}</b>) has been created by {$_SESSION['user_logged']}", E_USER_NOTICE);
			set_page_message(tr('Reseller account successfully created.'), 'success');
			return true;
		}
	} catch (PDOException $e) {
		$db->rollBack();
		throw $e;
	}

	return false;
}

/***********************************************************************************************************************
 * Main
 */

require '../../application.php';

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAdminScriptStart);

$cfg = \iMSCP\Core\Application::getInstance()->getConfig();

check_login('admin');

if (!empty($_POST) && admin_checkAndCreateResellerAccount()) {
	redirectTo('manage_users.php');
}

// Initialize field error stack
$errFieldsStack = [];

// Getting domain data
$data =& admin_getData();

$tpl = new \iMSCP\Core\Template\TemplateEngine();
$tpl->define_dynamic([
	'layout' => 'shared/layouts/ui.tpl',
	'page' => 'admin/reseller_add.tpl',
	'page_message' => 'layout',
	'ips_block' => 'page',
	'ip_block' => 'ips_block',
	'php_editor_disable_functions_block' => 'page'
]);
$tpl->assign([
	'TR_PAGE_TITLE' => tr('Admin / Users / Add Reseller'),
	'TR_ADD_RESELLER' => tr('Add reseller'),
	'TR_NOTICE' => tr('i-MSCP Notice'),
	'TR_EVENT_NOTICE' => tojs(tr('The `Enter` key is disabled for performance reasons.')),
	'TR_CREATE' => tr('Create'),
	'TR_CANCEL' => tr('Cancel'),
	'ERR_FIELDS_STACK' => json_encode($errFieldsStack)
]);

generateNavigation($tpl);
admin_generateForm($tpl, $data);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAdminScriptEnd, [
	'templateEngine' => $tpl
]);
$tpl->prnt();

unsetMessages();
