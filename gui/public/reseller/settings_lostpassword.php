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
 * Main
 */


require '../../application.php';

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(
    \iMSCP\Core\Events::onResellerScriptStart, \iMSCP\Core\Application::getInstance()->getApplicationEvent()
);

check_login('reseller');

$data1 = get_lostpassword_activation_email($_SESSION['user_id']);
$data2 = get_lostpassword_password_email($_SESSION['user_id']);

if (isset($_POST['uaction']) && $_POST['uaction'] === 'apply') {
    $errMessages = '';
    $data1['subject'] = clean_input($_POST['subject1'], false);
    $data1['message'] = clean_input($_POST['message1'], false);
    $data2['subject'] = clean_input($_POST['subject2'], false);
    $data2['message'] = clean_input($_POST['message2'], false);

    if (empty($data1['subject']) || empty($data2['subject'])) {
        set_page_message(tr('You must specify a subject.'), 'error');
    }

    if (empty($data1['message']) || empty($data2['message'])) {
        set_page_message(tr('You must specify a message.'), 'error');
    }

    if (isset($_SESSION['pageMessages'])) {
        return false;
    } else {
        set_lostpassword_activation_email($_SESSION['user_id'], $data1);
        set_lostpassword_password_email($_SESSION['user_id'], $data2);
        set_page_message(tr('Template for Auto email successfully updated.'), 'success');
    }
}

$tpl = new \iMSCP\Core\Template\TemplateEngine();
$tpl->defineDynamic([
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'reseller/settings_lostpassword.tpl',
    'page_message' => 'layout',
    'custom_buttons' => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE', tr('Reseller / Customers / Lost Password Email'),
    'TR_MESSAGE_TEMPLATE_INFO' => tr('Message template info'),
    'TR_MESSAGE_TEMPLATE' => tr('Message template'),
    'SUBJECT_VALUE1' => tohtml($data1['subject']),
    'MESSAGE_VALUE1' => tohtml($data1['message']),
    'SUBJECT_VALUE2' => tohtml($data2['subject']),
    'MESSAGE_VALUE2' => tohtml($data2['message']),
    'SENDER_EMAIL_VALUE' => tohtml($data1['sender_email']),
    'SENDER_NAME_VALUE' => tohtml($data1['sender_name']),
    'TR_ACTIVATION_EMAIL' => tr('Activation email'),
    'TR_PASSWORD_EMAIL' => tr('Password email'),
    'TR_USER_LOGIN_NAME' => tr('User login (system) name'),
    'TR_USER_PASSWORD' => tr('User password'),
    'TR_USER_REAL_NAME' => tr('User (first and last) name'),
    'TR_LOSTPW_LINK' => tr('Lostpw link'),
    'TR_SUBJECT' => tr('Subject'),
    'TR_MESSAGE' => tr('Message'),
    'TR_SENDER_EMAIL' => tr('Sender email'),
    'TR_SENDER_NAME' => tr('Sender name'),
    'TR_UPDATE' => tr('Update'),
    'TR_BASE_SERVER_VHOST_PREFIX' => tr('URL protocol'),
    'TR_BASE_SERVER_VHOST' => tr('URL to this admin panel'),
    'TR_BASE_SERVER_VHOST_PORT' => tr('URL port'),
    'TR_CANCEL' => tr('Cancel')
]);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onResellerScriptEnd, null, [
    'templateEngine' => $tpl
]);
$tpl->prnt();

unsetMessages();
