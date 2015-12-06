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
 * Add user
 *
 * @param  $tpl iMSCP\Core\Template\TemplateEngine
 * @return void
 */
function add_user($tpl)
{
    $cfg = \iMSCP\Core\Application::getInstance()->getConfig();

    if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_user') {
        \iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onBeforeAddUser);

        if (check_user_data()) {
            $upass = \iMSCP\Core\Utils\Crypt::bcrypt(clean_input($_POST['password']));
            $user_id = $_SESSION['user_id'];
            $username = clean_input($_POST['username']);
            $fname = clean_input($_POST['fname']);
            $lname = clean_input($_POST['lname']);
            $gender = clean_input($_POST['gender']);
            $firm = clean_input($_POST['firm']);
            $zip = clean_input($_POST['zip']);
            $city = clean_input($_POST['city']);
            $state = clean_input($_POST['state']);
            $country = clean_input($_POST['country']);
            $email = clean_input($_POST['email']);
            $phone = clean_input($_POST['phone']);
            $fax = clean_input($_POST['fax']);
            $street1 = clean_input($_POST['street1']);
            $street2 = clean_input($_POST['street2']);

            if (get_gender_by_code($gender, true) === null) {
                $gender = '';
            }

            $query = "
                INSERT INTO `admin` (
                    `admin_name`, `admin_pass`, `admin_type`, `domain_created`, `created_by`, `fname`, `lname`, `firm`,
                    `zip`, `city`, `state`, `country`, `email`, `phone`, `fax`, `street1`, `street2`, `gender`
                ) VALUES (
                    ?, ?, 'admin', unix_timestamp(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ";

            exec_query($query, [
                $username, $upass, $user_id, $fname, $lname, $firm, $zip, $city, $state, $country, $email,
                $phone, $fax, $street1, $street2, $gender
            ]);

            /** @var $db \Doctrine\DBAL\Connection */
            $db = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('Database');
            $new_admin_id = $db->lastInsertId();
            $user_logged = $_SESSION['user_logged'];

            write_log("$user_logged: add admin: $username", E_USER_WARNING);

            $user_def_lang = $cfg['USER_INITIAL_LANG'];
            $user_theme_color = $cfg['USER_INITIAL_THEME'];

            $query = "REPLACE INTO `user_gui_props` (`user_id`, `lang`, `layout`) VALUES (?, ?, ?)";
            exec_query($query, [$new_admin_id, $user_def_lang, $user_theme_color]);

            \iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAfterAddUser);

            send_add_user_auto_msg(
                $user_id,
                clean_input($_POST['username']),
                clean_input($_POST['password']),
                clean_input($_POST['email']),
                clean_input($_POST['fname']),
                clean_input($_POST['lname']),
                tr('Administrator')
            );

            //$_SESSION['user_added'] = 1;
            set_page_message(tr('Admin account successfully created.'), 'success');

            redirectTo('manage_users.php');
        } else { // check user data
            $tpl->assign([
                'EMAIL' => clean_input($_POST['email'], true),
                'USERNAME' => clean_input($_POST['username'], true),
                'FIRST_NAME' => clean_input($_POST['fname'], true),
                'LAST_NAME' => clean_input($_POST['lname'], true),
                'FIRM' => clean_input($_POST['firm'], true),
                'ZIP' => clean_input($_POST['zip'], true),
                'CITY' => clean_input($_POST['city'], true),
                'STATE' => clean_input($_POST['state'], true),
                'COUNTRY' => clean_input($_POST['country'], true),
                'STREET_1' => clean_input($_POST['street1'], true),
                'STREET_2' => clean_input($_POST['street2'], true),
                'PHONE' => clean_input($_POST['phone'], true),
                'FAX' => clean_input($_POST['fax'], true),
                'VL_MALE' => ($_POST['gender'] == 'M') ? $cfg['HTML_SELECTED'] : '',
                'VL_FEMALE' => ($_POST['gender'] == 'F') ? $cfg['HTML_SELECTED'] : '',
                'VL_UNKNOWN' => (($_POST['gender'] == 'U') || empty($_POST['gender'])) ? $cfg['HTML_SELECTED'] : ''
            ]);
        }
    } else {
        $tpl->assign([
            'EMAIL' => '',
            'USERNAME' => '',
            'FIRST_NAME' => '',
            'LAST_NAME' => '',
            'FIRM' => '',
            'ZIP' => '',
            'CITY' => '',
            'STATE' => '',
            'COUNTRY' => '',
            'STREET_1' => '',
            'STREET_2' => '',
            'PHONE' => '',
            'FAX' => '',
            'VL_MALE' => '',
            'VL_FEMALE' => '',
            'VL_UNKNOWN' => $cfg['HTML_SELECTED']
        ]);
    }
}

/**
 * Check user data
 *
 * @return bool
 */
function check_user_data()
{
    if (!validates_username($_POST['username'])) {
        set_page_message(tr('Incorrect username length or syntax.'), 'error');
        return false;
    }

    if ($_POST['password'] != $_POST['password_confirmation']) {
        set_page_message(tr("Passwords do not match."), 'error');
        return false;
    }

    if (!checkPasswordSyntax($_POST['password'])) {
        return false;
    }

    if (!chk_email($_POST['email'])) {
        set_page_message(tr("Incorrect email length or syntax."), 'error');
        return false;
    }

    $query = "SELECT `admin_id` FROM `admin` WHERE `admin_name` = ?";

    $username = clean_input($_POST['username']);
    $rs = exec_query($query, $username);

    if ($rs->rowCount()) {
        set_page_message(tr('This user name already exist.'), 'warning');
        return false;
    }

    return true;
}

/***********************************************************************************************************************
 * Main
 */

require '../../application.php';

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAdminScriptStart);

check_login('admin');

$tpl = new \iMSCP\Core\Template\TemplateEngine();
$tpl->defineDynamic([
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'admin/admin_add.tpl',
    'page_message' => 'layout'
]);

$tpl->assign([
    'TR_PAGE_TITLE', tr('Admin / Users / Add Admin'),
    'TR_EMPTY_OR_WORNG_DATA' => tr('Empty data or wrong field.'),
    'TR_PASSWORD_NOT_MATCH' => tr("Passwords do not match."),
    'TR_ADD_ADMIN' => tr('Add admin'),
    'TR_CORE_DATA' => tr('Core data'),
    'TR_USERNAME' => tr('Username'),
    'TR_PASSWORD' => tr('Password'),
    'TR_PASSWORD_REPEAT' => tr('Password confirmation'),
    'TR_EMAIL' => tr('Email'),
    'TR_ADDITIONAL_DATA' => tr('Additional data'),
    'TR_FIRST_NAME' => tr('First name'),
    'TR_LAST_NAME' => tr('Last name'),
    'TR_GENDER' => tr('Gender'),
    'TR_MALE' => tr('Male'),
    'TR_FEMALE' => tr('Female'),
    'TR_UNKNOWN' => tr('Unknown'),
    'TR_COMPANY' => tr('Company'),
    'TR_ZIP_POSTAL_CODE' => tr('Zip/Postal code'),
    'TR_CITY' => tr('City'),
    'TR_STATE' => tr('State/Province'),
    'TR_COUNTRY' => tr('Country'),
    'TR_STREET_1' => tr('Street 1'),
    'TR_STREET_2' => tr('Street 2'),
    'TR_PHONE' => tr('Phone'),
    'TR_FAX' => tr('Fax'),
    'TR_ADD' => tr('Add')
]);

generateNavigation($tpl);
add_user($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAdminScriptEnd, null, [
    'templateEngine' => $tpl
]);
$tpl->prnt();

unsetMessages();
