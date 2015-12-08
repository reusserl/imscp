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
 *
 * @param int $domainId Domain unique identifier
 * @return mixed
 */
function protect_area($domainId)
{
    if (!isset($_POST['uaction']) || $_POST['uaction'] != 'protect_it') {
        return;
    }

    if (!isset($_POST['users']) && !isset($_POST['groups'])) {
        set_page_message(tr('Please choose htaccess user or htaccess group.'), 'error');
        return;
    }

    if (empty($_POST['paname'])) {
        set_page_message(tr('Please enter a name for the protected area.'), 'error');
        return;
    }

    if (empty($_POST['other_dir'])) {
        set_page_message(tr('Please enter protected area path'), 'error');
        return;
    }

    $path = clean_input($_POST['other_dir'], false);

    // Cleanup path:
    // Adds a slash as a first char of the path if it doesn't exist
    // Removes the double slashes
    // Remove the trailing slash if it exists
    if ($path != '/') {
        $clean_path = [];

        foreach (explode(DIRECTORY_SEPARATOR, $path) as $dir) {
            if ($dir != '') {
                $clean_path[] = $dir;
            }
        }

        $path = '/' . implode(DIRECTORY_SEPARATOR, $clean_path);
    }

    $domain = $_SESSION['user_logged'];

    // Check for existing directory. We need to use the virtual file system
    $vfs = new \iMSCP\Core\VirtualFileSystem($domain);
    $res = $vfs->exists($path);

    if (!$res) {
        set_page_message(tr("%s doesn't exist", $path), 'error');
        return;
    }

    $ptype = $_POST['ptype'];

    $users = [];
    if (isset($_POST['users'])) {
        $users = $_POST['users'];
    }

    $groups = [];
    if (isset($_POST['groups'])) {
        $groups = $_POST['groups'];
    }

    $areaname = $_POST['paname'];
    $userid = '';
    $groupid = '';

    if ($ptype == 'user') {
        for ($i = 0, $cnt_users = count($users); $i < $cnt_users; $i++) {
            if ($cnt_users == 1 || $cnt_users == $i + 1) {
                $userid .= $users[$i];
                if ($userid == '-1' || $userid == '') {
                    set_page_message(tr('You cannot protect an area without selected htaccess user(s).'), 'error');
                    return;
                }
            } else {
                $userid .= $users[$i] . ',';
            }
        }

        $groupid = 0;
    } else {
        for ($i = 0, $cnt_groups = count($groups); $i < $cnt_groups; $i++) {
            if ($cnt_groups == 1 || $cnt_groups == $i + 1) {
                $groupid .= $groups[$i];
                if ($groupid == '-1' || $groupid == '') {
                    set_page_message(tr('You cannot protect an area without selected htaccess group(s).'), 'error');
                    return;
                }
            } else {
                $groupid .= $groups[$i] . ',';
            }
        }

        $userid = 0;
    }

    // let's check if we have to update or to make new enrie
    $alt_path = $path . "/";
    $query = "
        SELECT
            `id`
        FROM
            `htaccess`
        WHERE
            `dmn_id` = ?
        AND
            (`path` = ? OR `path` = ?)
    ";

    $stmt = exec_query($query, [$domainId, $path, $alt_path]);
    $toadd_status = 'toadd';
    $tochange_status = 'tochange';

    if ($stmt->rowCount()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $query = "
            UPDATE
                `htaccess`
            SET
                `user_id` = ?, `group_id` = ?, `auth_name` = ?, `path` = ?,
                `status` = ?
            WHERE
                `id` = ?;
        ";
        exec_query($query, [$userid, $groupid, $areaname, $path, $tochange_status, $row['id']]);
        send_request();
        set_page_message(tr('Protected area successfully scheduled for update.'), 'success');
    } else {
        $query = "
            INSERT INTO `htaccess` (
                `dmn_id`, `user_id`, `group_id`, `auth_type`, `auth_name`, `path`,
                `status`
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?
            )
        ";
        exec_query($query, [$domainId, $userid, $groupid, 'Basic', $areaname, $path, $toadd_status]);
        send_request();
        set_page_message(tr('Protected area successfully scheduled for addition.'), 'success');
    }

    redirectTo('protected_areas.php');
}

/**
 * Generates page.
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl Template engine instance
 * @param int $domainId Domain unique identifier
 * @return void
 */
function gen_protect_it($tpl, $domainId)
{
    $cfg = \iMSCP\Core\Application::getInstance()->getConfig();

    if (!isset($_GET['id'])) {
        $edit = 'no';
        $type = 'user';
        $userid = 0;
        $groupid = 0;
        $tpl->assign([
            'PATH' => '',
            'AREA_NAME' => '',
            'UNPROTECT_IT' => ''
        ]);
    } else {
        $edit = 'yes';
        $htid = $_GET['id'];
        $tpl->assign('CDIR', $htid);
        $tpl->parse('UNPROTECT_IT', 'unprotect_it');
        $query = "SELECT * FROM `htaccess` WHERE `dmn_id` = ? AND `id` = ?";
        $stmt = exec_query($query, [$domainId, $htid]);

        if (!$stmt->rowCount()) {
            redirectTo('protected_areas_add.php');
            exit;
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $userid = $row['user_id'];
        $groupid = $row['group_id'];
        $status = $row['status'];
        $path = $row['path'];
        $authname = $row['auth_name'];
        $okStatus = 'ok';

        if ($status !== $okStatus) {
            set_page_message(tr("Status for protected area must be 'OK' if you want to edit it."), 'error');
            redirectTo('protected_areas.php');
            exit;
        }

        $tpl->assign([
            'PATH' => tohtml($path),
            'AREA_NAME' => tohtml($authname)
        ]);

        // let's get the htaccess management type
        if ($userid !== 0 && $groupid == 0) {
            // we have only user htaccess
            $type = 'user';
        } elseif ($groupid !== 0 && $userid == 0) {
            // we have only groups htaccess
            $type = 'group';
        } else {
            // we have unsr and groups htaccess
            $type = 'both';
        }
    }
    // this area is not secured by htaccess
    if ($edit == 'no' || $type == 'user') {
        $tpl->assign([
            'USER_CHECKED' => $cfg['HTML_CHECKED'],
            'GROUP_CHECKED' => "",
            'USER_FORM_ELEMENS' => "false",
            'GROUP_FORM_ELEMENS' => "true"
        ]);
    }

    if ($type == 'group') {
        $tpl->assign([
            'USER_CHECKED' => "",
            'GROUP_CHECKED' => $cfg['HTML_CHECKED'],
            'USER_FORM_ELEMENS' => "true",
            'GROUP_FORM_ELEMENS' => "false"
        ]);
    }

    $query = "SELECT *  FROM `htaccess_users` WHERE `dmn_id` = ?";
    $stmt = exec_query($query, $domainId);

    if (!$stmt->rowCount()) {
        $tpl->assign([
            'USER_VALUE' => "-1",
            'USER_LABEL' => tr('You do not have customers.'),
            'USER_SELECTED' => ''
        ]);

        $tpl->parse('USER_ITEM', 'user_item');
    } else {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $usrid = explode(',', $userid);
            $usrSelected = '';

            for ($i = 0, $cntUsrid = count($usrid); $i < $cntUsrid; $i++) {
                if ($edit == 'yes' && $usrid[$i] == $row['id']) {
                    $i = $cntUsrid + 1;
                    $usrSelected = $cfg['HTML_SELECTED'];
                } else {
                    $usrSelected = '';
                }
            }

            $tpl->assign([
                'USER_VALUE' => $row['id'],
                'USER_LABEL' => tohtml($row['uname']),
                'USER_SELECTED' => $usrSelected
            ]);
            $tpl->parse('USER_ITEM', '.user_item');
        }
    }

    $query = "SELECT * FROM `htaccess_groups` WHERE `dmn_id` = ?";
    $stmt = exec_query($query, $domainId);

    if (!$stmt->rowCount()) {
        $tpl->assign([
            'GROUP_VALUE' => "-1",
            'GROUP_LABEL' => tr('You have no groups.'),
            'GROUP_SELECTED' => ''
        ]);

        $tpl->parse('GROUP_ITEM', 'group_item');
    } else {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $grp_id = explode(',', $groupid);
            $grpSelected = '';

            for ($i = 0, $cnt_grp_id = count($grp_id); $i < $cnt_grp_id; $i++) {
                if ($edit == 'yes' && $grp_id[$i] == $row['id']) {
                    $i = $cnt_grp_id + 1;
                    $grpSelected = $cfg['HTML_SELECTED'];
                } else {
                    $grpSelected = '';
                }
            }

            $tpl->assign([
                'GROUP_VALUE' => $row['id'],
                'GROUP_LABEL' => tohtml($row['ugroup']),
                'GROUP_SELECTED' => $grpSelected
            ]);
            $tpl->parse('GROUP_ITEM', '.group_item');
        }
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
    'page' => 'client/protect_it.tpl',
    'page_message' => 'layout',
    'group_item' => 'page',
    'user_item' => 'page',
    'unprotect_it' => 'page'
]);

$tpl->assign([
    'TR_PAGE_TITLE' => tr('Client / Webtools / Protected Areas / {TR_DYNAMIC_TITLE}'),
    'TR_FTP_DIRECTORIES' => tojs(('FTP directories')),
    'TR_CLOSE' => tojs(tr('Close')),
    'TR_DYNAMIC_TITLE' => isset($_GET['id']) ? tr('Edit protected area') : tr('Add protected area'),
    'TR_PROTECTED_AREA' => tr('Protected areas'),
    'TR_AREA_NAME' => tr('Area name'),
    'TR_PATH' => tr('Path'),
    'CHOOSE_DIR' => tr('Choose dir'),
    'TR_USER' => tr('Users'),
    'TR_GROUPS' => tr('Groups'),
    'TR_USER_AUTH' => tr('User auth'),
    'TR_GROUP_AUTH' => tr('Group auth'),
    'TR_PROTECT_IT' => tr('Protect it'),
    'TR_UNPROTECT_IT' => tr('Unprotect it'),
    'TR_CANCEL' => tr('Cancel'),
    'TR_MANAGE_USERS_AND_GROUPS' => tr('Users and groups')
]);

generateNavigation($tpl);
protect_area(get_user_domain_id($_SESSION['user_id']));
gen_protect_it($tpl, get_user_domain_id($domainId));
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onClientScriptEnd, null, [
    'templateEngine' => $tpl
]);
$tpl->prnt();

unsetMessages();
