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
 * Generate page
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl
 * @param int $sqlUserId Sql user id
 * @return array
 */
function client_generatePage($tpl, $sqlUserId)
{
    $stmt = exec_query('SELECT sqlu_name, sqlu_host FROM sql_user WHERE sqlu_id = ?', $sqlUserId);

    if ($stmt->rowCount()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $tpl->assign([
            'USER_NAME' => tohtml($row['sqlu_name']),
            'ID' => $sqlUserId
        ]);

        return [$row['sqlu_name'], $row['sqlu_host']];
    }

    showBadRequestErrorPage();
    exit;
}

/**
 * Update SQL user password
 *
 * @param int $sqlUserId
 * @param string $sqlUserName
 * @param string $sqlUserHost
 * @throws Exception
 */
function client_updateSqlUserPassword($sqlUserId, $sqlUserName, $sqlUserHost)
{

    if (!isset($_POST['uaction'])) {
        return;
    }

    if (empty($_POST['password'])) {
        set_page_message(tr('Please enter a password.'), 'error');
        return;
    }

    if (empty($_POST['password_confirmation'])) {
        set_page_message(tr('Please confirm the password.'), 'error');
        return;
    }

    $password = clean_input($_POST['password']);
    $passwordConfirmation = clean_input($_POST['password_confirmation']);

    if ($password === '') {
        set_page_message(tr("Password cannot be empty."), 'error');
        return;
    }

    if ($password !== $passwordConfirmation) {
        set_page_message(tr("Passwords do not match."), 'error');
        return;
    }

    if (!checkPasswordSyntax($password)) {
        return;
    }

    \iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onBeforeEditSqlUser, null, [
        'sqlUserId' => $sqlUserId
    ]);

    exec_query('SET PASSWORD FOR ?@? = PASSWORD(?)', [$sqlUserName, $sqlUserHost, $password]);
    set_page_message(tr('SQL user password successfully updated.'), 'success');
    write_log(sprintf("%s updated %s@%s SQL user password.", $_SESSION['user_logged'], $sqlUserName, $sqlUserHost), E_USER_NOTICE);

    \iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAfterEditSqlUser, null, [
        'sqlUserId' => $sqlUserId
    ]);
    redirectTo('sql_manage.php');
}

/***********************************************************************************************************************
 * Main
 */

require '../../application.php';

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onClientScriptStart);

check_login('user');
customerHasFeature('sql') or showBadRequestErrorPage();

if (isset($_REQUEST['id'])) {
    $sqlUserId = intval($_REQUEST['id']);

    if (!check_user_sql_perms($sqlUserId)) {
        showBadRequestErrorPage();
    }
} else {
    showBadRequestErrorPage();
    exit;
}

$tpl = new \iMSCP\Core\Template\TemplateEngine();
$tpl->define_dynamic([
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'client/sql_change_password.tpl',
    'page_message' => 'layout'
]);
$tpl->assign([
    'TR_PAGE_TITLE' => tr('Client / Databases / Overview / Update SQL User Password'),
    'TR_DB_USER' => tr('User'),
    'TR_PASSWORD' => tr('Password'),
    'TR_PASSWORD_CONFIRMATION' => tr('Password confirmation'),
    'TR_CHANGE' => tr('Update'),
    'TR_CANCEL' => tr('Cancel')
]);

list($sqlUserName, $sqlUserhost) = client_generatePage($tpl, $sqlUserId);

client_updateSqlUserPassword($sqlUserId, $sqlUserName, $sqlUserhost);
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onClientScriptEnd, null, [
    'templateEngine' => $tpl
]);
$tpl->prnt();

unsetMessages();
