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
 * Return htaccess username
 *
 * @param int $htuserId Htaccess user unique identifier
 * @param int $domainId Domain unique identifier
 * @return string
 */
function client_getHtaccessUsername($htuserId, $domainId)
{
    $stmt = exec_query('SELECT `uname` FROM `htaccess_users` WHERE `dmn_id` = ? AND `id` = ?', [$domainId, $htuserId]);

    if (!$stmt->rowCount()) {
        redirectTo('protected_user_manage.php');
    }

    return $stmt->fetch(PDO::FETCH_ASSOC)['uname'];
}

/**
 * Generates page
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl Template engine instance
 * @param int $dmn_id Domain unique identifier
 * @return void
 */
function client_generatePage($tpl, &$dmn_id)
{
    if (isset($_GET['uname']) && $_GET['uname'] !== '' && is_numeric($_GET['uname'])) {
        $uuserId = $_GET['uname'];
        $tpl->assign('UNAME', tohtml(client_getHtaccessUsername($uuserId, $dmn_id)));
        $tpl->assign('UID', $uuserId);
    } else if (isset($_POST['nadmin_name']) && !empty($_POST['nadmin_name']) && is_numeric($_POST['nadmin_name'])) {
        $uuserId = $_POST['nadmin_name'];
        $tpl->assign('UNAME', tohtml(client_getHtaccessUsername($uuserId, $dmn_id)));
        $tpl->assign('UID', $uuserId);
    } else {
        redirectTo('protected_user_manage.php');
        exit; // Useless but avoid stupid IDE warning about possibled undefined variable
    }

    // Get groups
    $stmt = exec_query('SELECT * FROM `htaccess_groups` WHERE `dmn_id` = ?', $dmn_id);

    if (!$stmt->rowCount()) {
        set_page_message(tr('You have no groups.'), 'error');
        redirectTo('protected_user_manage.php');
    }

    $addedIn = 0;
    $notAddedIn = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $groupId = $row['id'];
        $groupName = $row['ugroup'];
        $members = $row['members'];
        $members = explode(",", $members);
        $grpIn = 0;

        // let's generete all groups wher the user is assigned
        for ($i = 0, $cnt_members = count($members); $i < $cnt_members; $i++) {
            if ($uuserId == $members[$i]) {
                $tpl->assign([
                    'GRP_IN' => tohtml($groupName),
                    'GRP_IN_ID' => $groupId,
                ]);

                $tpl->parse('ALREADY_IN', '.already_in');
                $grpIn = $groupId;
                $addedIn++;
            }
        }
        if ($grpIn !== $groupId) {
            $tpl->assign([
                'GRP_NAME' => tohtml($groupName),
                'GRP_ID' => $groupId
            ]);

            $tpl->parse('GRP_AVLB', '.grp_avlb');
            $notAddedIn++;
        }
    }

    // generate add/remove buttons
    if ($addedIn < 1) {
        $tpl->assign('IN_GROUP', '');
    }

    if ($notAddedIn < 1) {
        $tpl->assign('NOT_IN_GROUP', '');
    }
}

/**
 * Assign a specific htaccess user to a specific htaccess group
 *
 * @param $dmn_id
 */
function client_addHtaccessUserToHtaccessGroup(&$dmn_id)
{
    if (isset($_POST['uaction']) && $_POST['uaction'] == 'add' &&
        isset($_POST['groups']) && !empty($_POST['groups']) &&
        isset($_POST['nadmin_name']) && is_numeric($_POST['groups']) &&
        is_numeric($_POST['nadmin_name'])
    ) {
        $uuser_id = clean_input($_POST['nadmin_name']);
        $group_id = clean_input($_POST['groups']);
        $stmt = exec_query('SELECT `id`, `ugroup`, `members` FROM `htaccess_groups` WHERE `dmn_id` = ? AND `id` = ?', [
            $dmn_id, $group_id
        ]);

        if (!$stmt->rowCount()) {
            $members = $uuser_id;
            $ugroup = '';
        } else {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $ugroup = $row['ugroup'];
            $members = $row['members'] . "," . $uuser_id;
        }

        exec_query('UPDATE `htaccess_groups` SET `members` = ?, `status` = ? WHERE `id` = ? AND `dmn_id` = ?', [
            $members, 'tochange', $group_id, $dmn_id
        ]);
        send_request();
        set_page_message(tr('Htaccess user successfully assigned to the %s htaccess group', $ugroup), 'success');
    } else {
        return;
    }
}

/**
 * Remove user from a specific group.
 *
 * @param int $dmn_id Domain unique identifier
 * @return void
 */
function client_removeHtaccessUserFromHtaccessGroup(&$dmn_id)
{
    if (
        isset($_POST['uaction']) && $_POST['uaction'] == 'remove' && isset($_POST['groups_in']) &&
        !empty($_POST['groups_in']) && isset($_POST['nadmin_name']) && is_numeric($_POST['groups_in']) &&
        is_numeric($_POST['nadmin_name'])
    ) {
        $groupId = $_POST['groups_in'];
        $uuserId = clean_input($_POST['nadmin_name']);

        $stmt = exec_query('SELECT `id`, `ugroup`, `members` FROM `htaccess_groups` WHERE `dmn_id` = ? AND `id` = ?', [
            $dmn_id, $groupId
        ]);

        if ($stmt->rowCount()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $ugroup = $row['ugroup'];
            $members = explode(',', $row['members']);
        } else {
            $members = [];
            $ugroup = '';
        }

        $key = array_search($uuserId, $members);
        if ($key !== false) {
            unset($members[$key]);
            $members = implode(",", $members);
            exec_query('UPDATE `htaccess_groups` SET `members` = ?, `status` = ? WHERE `id` = ? AND `dmn_id` = ?', [
                $members, 'tochange', $groupId, $dmn_id
            ]);
            send_request();
            set_page_message(tr('Htaccess user successfully deleted from the %s htaccess group ', $ugroup), 'success');
        } else {
            return;
        }
    } else {
        return;
    }
}

/***********************************************************************************************************************
 * Main
 */

require '../../application.php';

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(
    \iMSCP\Core\Events::onClientScriptStart, \iMSCP\Core\Application::getInstance()->getApplicationEvent()
);

check_login('user');
customerHasFeature('protected_areas') or showBadRequestErrorPage();

$tpl = new \iMSCP\Core\Template\TemplateEngine();
$tpl->defineDynamic([
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'client/puser_assign.tpl',
    'page_message' => 'layout',
    'already_in' => 'page',
    'grp_avlb' => 'page',
    'add_button' => 'page',
    'remove_button' => 'page',
    'in_group' => 'page',
    'not_in_group' => 'page'
]);
$tpl->assign(
    [
        'TR_PAGE_TITLE' => 'Client / Webtools / Protected Areas / Manage Users and Groups / Assign Group',
        'TR_SELECT_GROUP' => tr('Select group'),
        'TR_MEMBER_OF_GROUP' => tr('Member of group'),
        'TR_ADD' => tr('Add'),
        'TR_REMOVE' => tr('Remove'),
        'TR_CANCEL' => tr('Cancel')
    ]);

generateNavigation($tpl);
client_addHtaccessUserToHtaccessGroup(get_user_domain_id($_SESSION['user_id']));
client_removeHtaccessUserFromHtaccessGroup(get_user_domain_id($_SESSION['user_id']));
client_generatePage($tpl, get_user_domain_id($_SESSION['user_id']));
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onClientScriptEnd, null, [
    'templateEngine' => $tpl
]);
$tpl->prnt();

unsetMessages();
