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
 * Generate htaccess entries
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl
 * @param int $domainId Customer main domain identifier
 * @return void
 */
function gen_htaccess_entries($tpl, $domainId)
{
    $stmt = exec_query('SELECT * FROM `htaccess` WHERE `dmn_id` = ?', $domainId);

    if (!$stmt->rowCount()) {
        $tpl->assign('PROTECTED_AREAS', '');
        set_page_message(tr('You do not have protected areas.'), 'static_info');
        return;
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tpl->assign([
            'AREA_NAME' => tohtml($row['auth_name']),
            'JS_AREA_NAME' => addslashes($row['auth_name']),
            'AREA_PATH' => tohtml($row['path']),
            'PID' => $row['id'],
            'STATUS' => translate_dmn_status($row['status'])
        ]);
        $tpl->parse('DIR_ITEM', '.dir_item');
    }
}

/***********************************************************************************************************************
 * Main
 */

require '../../application.php';

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onClientScriptStart);

check_login('user');
customerHasFeature('protected_areas') or showBadRequestErrorPage();

$tpl = new \iMSCP\Core\Template\TemplateEngine();
$tpl->define_dynamic([
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'client/protected_areas.tpl',
    'page_message' => 'layout',
    'dir_item' => 'page',
    'action_link' => 'page',
    'protected_areas' => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE' => tr('Client / Webtools / Protected Areas'),
    'TR_HTACCESS' => tr('Protected areas'),
    'TR_DIRECTORY_TREE' => tr('Directory tree'),
    'TR_DIRS' => tr('Name'),
    'TR__ACTION' => tr('Action'),
    'TR_MANAGE_USRES' => tr('Manage users and groups'),
    'TR_USERS' => tr('User'),
    'TR_USERNAME' => tr('Username'),
    'TR_ADD_USER' => tr('Add user'),
    'TR_GROUPNAME' => tr('Group name'),
    'TR_GROUP_MEMBERS' => tr('Group members'),
    'TR_ADD_GROUP' => tr('Add group'),
    'TR_EDIT' => tr('Edit'),
    'TR_GROUP' => tr('Group'),
    'TR_DELETE' => tr('Delete'),
    'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', '%s'),
    'TR_STATUS' => tr('Status'),
    'TR_ADD_AREA' => tr('Add new protected area')]);

generateNavigation($tpl);
gen_htaccess_entries($tpl, get_user_domain_id($_SESSION['user_id']));
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onClientScriptEnd, null, [
    'templateEngine' => $tpl
]);
$tpl->prnt();

unsetMessages();
