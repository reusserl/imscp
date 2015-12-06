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
 * Adds Htaccess group
 *
 * @param int $domainId Domain unique identifier
 * @çeturn void
 */
function client_addHtaccessGroup($domainId)
{
    if (isset($_POST['uaction']) && $_POST['uaction'] == 'add_group') {
        if (isset($_POST['groupname'])) {
            if (!validates_username($_POST['groupname'])) {
                set_page_message(tr('Invalid group name.'), 'error');
                return;
            }

            $groupname = clean_input($_POST['groupname']);
            $stmt = exec_query('SELECT `id` FROM`htaccess_groups` WHERE `ugroup` = ? AND `dmn_id` = ?', [
                $groupname, $domainId
            ]);

            if (!$stmt->rowCount()) {
                exec_query('INSERT INTO `htaccess_groups` (`dmn_id`, `ugroup`, `status`) VALUES (?, ?, ?)', [
                    $domainId, $groupname, 'toadd'
                ]);
                send_request();
                set_page_message(tr('Htaccess group successfully scheduled for addition.'), 'success');
                write_log("{$_SESSION['user_logged']}: added htaccess group: $groupname", E_USER_NOTICE);
                redirectTo('protected_user_manage.php');
            }

            set_page_message(tr('This htaccess group already exists.'), 'error');
            return;
        }

        set_page_message(tr('Invalid htaccess group name.'), 'error');
        return;
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
    'page' => 'client/puser_gadd.tpl',
    'page_message' => 'layout',
    'usr_msg' => 'page',
    'grp_msg' => 'page',
    'pusres' => 'page',
    'pgroups' => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE' => tr('Client / Webtools / Protected Areas / Manage Users and Groups / Add Group'),
    'TR_HTACCESS_GROUP' => tr('Htaccess group'),
    'TR_GROUPNAME' => tr('Group name'),
    'TR_ADD_GROUP' => tr('Add'),
    'TR_CANCEL' => tr('Cancel')
]);

generateNavigation($tpl);
client_addHtaccessGroup(get_user_domain_id($_SESSION['user_id']));
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onClientScriptEnd, null, [
    'templateEngine' => $tpl
]);
$tpl->prnt();

unsetMessages();
