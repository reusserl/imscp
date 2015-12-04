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
 * Generate administrator personal data
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl
 * @param int $user_id
 */
function gen_admin_personal_data(&$tpl, $user_id)
{
	$cfg = \iMSCP\Core\Application::getInstance()->getConfig();
	$query = "
		SELECT
			`fname`, `lname`, `gender`, `firm`, `zip`, `city`, `state`, `country`,
			`street1`, `street2`, `email`, `phone`, `fax`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
	";
	$rs = exec_query($query, $user_id);
	$row = $rs->fetch(PDO::FETCH_ASSOC);
	$tpl->assign([
		'FIRST_NAME' => empty($row['fname']) ? '' : tohtml($row['fname']),
		'LAST_NAME' => empty($row['lname']) ? '' : tohtml($row['lname']),
		'FIRM' => empty($row['firm']) ? '' : tohtml($row['firm']),
		'ZIP' => empty($row['zip']) ? '' : tohtml($row['zip']),
		'CITY' => empty($row['city']) ? '' : tohtml($row['city']),
		'STATE' => empty($row['state']) ? '' : tohtml($row['state']),
		'COUNTRY' => empty($row['country']) ? '' : tohtml($row['country']),
		'STREET_1' => empty($row['street1']) ? '' : tohtml($row['street1']),
		'STREET_2' => empty($row['street2']) ? '' : tohtml($row['street2']),
		'EMAIL' => empty($row['email']) ? '' : tohtml($row['email']),
		'PHONE' => empty($row['phone']) ? '' : tohtml($row['phone']),
		'FAX' => empty($row['fax']) ? '' : tohtml($row['fax']),
		'VL_MALE' => (($row['gender'] == 'M') ? $cfg['HTML_SELECTED'] : ''),
		'VL_FEMALE' => (($row['gender'] == 'F') ? $cfg['HTML_SELECTED'] : ''),
		'VL_UNKNOWN' => ((($row['gender'] == 'U') || (empty($row['gender']))) ? $cfg['HTML_SELECTED'] : '')
	]);
}

/**
 * Update administrator personal data
 *
 * @param int $user_id
 */
function update_admin_personal_data($user_id)
{
	\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onBeforeEditUser, [
		'userId' => $user_id
	]);

	$fname = clean_input($_POST['fname']);
	$lname = clean_input($_POST['lname']);
	$gender = $_POST['gender'];
	$firm = clean_input($_POST['firm']);
	$zip = clean_input($_POST['zip']);
	$city = clean_input($_POST['city']);
	$state = clean_input($_POST['state']);
	$country = clean_input($_POST['country']);
	$street1 = clean_input($_POST['street1']);
	$street2 = clean_input($_POST['street2']);
	$email = clean_input($_POST['email']);
	$phone = clean_input($_POST['phone']);
	$fax = clean_input($_POST['fax']);

	$query = "
		UPDATE
			`admin`
		SET
			`fname` = ?, `lname` = ?, `firm` = ?, `zip` = ?, `city` = ?, `state` = ?,
		    `country` = ?, `street1` = ?, `street2` = ?, `email` = ?, `phone` = ?,
			`fax` = ?, `gender` = ?
		WHERE
			`admin_id` = ?
	";
	exec_query($query, [
		$fname, $lname, $firm, $zip, $city, $state, $country, $street1, $street2, $email, $phone, $fax, $gender, $user_id
	]);

	\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAfterEditUser, [
		'userId' => $user_id
	]);

	set_page_message(tr('Personal data successfully updated.'), 'success');
	redirectTo('profile.php');
}

/***********************************************************************************************************************
 * Main
 */

require '../../application.php';

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAdminScriptStart);

check_login('admin');

if (isset($_POST['uaction']) && $_POST['uaction'] === 'updt_data') {
	update_admin_personal_data($_SESSION['user_id']);
}

$tpl = new \iMSCP\Core\Template\TemplateEngine();
$tpl->define_dynamic([
	'layout' => 'shared/layouts/ui.tpl',
	'page' => 'admin/personal_change.tpl',
	'page_message' => 'layout'
]);
$tpl->assign([
	'TR_PAGE_TITLE', tr('Admin / Profile / Personal Data'),
	'TR_PERSONAL_DATA' => tr('Personal data'),
	'TR_FIRST_NAME' => tr('First name'),
	'TR_LAST_NAME' => tr('Last name'),
	'TR_COMPANY' => tr('Company'),
	'TR_ZIP_POSTAL_CODE' => tr('Zip/Postal code'),
	'TR_CITY' => tr('City'),
	'TR_STATE' => tr('State/Province'),
	'TR_COUNTRY' => tr('Country'),
	'TR_STREET_1' => tr('Street 1'),
	'TR_STREET_2' => tr('Street 2'),
	'TR_EMAIL' => tr('Email'),
	'TR_PHONE' => tr('Phone'),
	'TR_FAX' => tr('Fax'),
	'TR_GENDER' => tr('Gender'),
	'TR_MALE' => tr('Male'),
	'TR_FEMALE' => tr('Female'),
	'TR_UNKNOWN' => tr('Unknown'),
	'TR_UPDATE_DATA' => tr('Update data')
]);

generateNavigation($tpl);
generatePageMessage($tpl);
gen_admin_personal_data($tpl, $_SESSION['user_id']);

$tpl->parse('LAYOUT_CONTENT', 'page');
\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAdminScriptEnd, [
	'templateEngine' => $tpl
]);
$tpl->prnt();

unsetMessages();
