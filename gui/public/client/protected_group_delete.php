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
    \iMSCP\Core\Events::onClientScriptStart, \iMSCP\Core\Application::getInstance()->getApplicationEvent()
);

check_login('user');
customerHasFeature('protected_areas') or showBadRequestErrorPage();

$dmn_id = get_user_domain_id($_SESSION['user_id']);

if (isset($_GET['gname']) && $_GET['gname'] !== '' && is_numeric($_GET['gname'])) {
    $group_id = $_GET['gname'];
} else {
    redirectTo('protected_areas.php');
    exit;
}

$change_status = 'todelete';
$query = "
    UPDATE
        `htaccess_groups`
    SET
        `status` = ?
    WHERE
        `id` = ?
    AND
        `dmn_id` = ?
    AND
        `ugroup` != ?
";
$rs = exec_query($query, [$change_status, $group_id, $dmn_id, 'statistics']);

$query = "SELECT *  FROM `htaccess` WHERE `dmn_id` = ?";
$stmt = exec_query($query, $dmn_id);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ht_id = $row['id'];
    $grp_id = $row['group_id'];

    $grp_id_splited = explode(',', $grp_id);

    $key = array_search($group_id, $grp_id_splited);
    if ($key !== false) {
        unset($grp_id_splited[$key]);
        if (count($grp_id_splited) == 0) {
            $status = 'todelete';
        } else {
            $grp_id = implode(",", $grp_id_splited);
            $status = 'tochange';
        }
        $update_query = "
            UPDATE
                `htaccess`
            SET
                `group_id` = ?, `status` = ?
            WHERE
                `id` = ?
        ";
        $rs_update = exec_query($update_query, [$grp_id, $status, $ht_id]);
    }
}

set_page_message(tr('Htaccess group successfully scheduled for deletion.'), 'success');
send_request();
write_log($_SESSION['user_logged'] . ": deleted Htaccess group ID: $group_id", E_USER_NOTICE);
redirectTo('protected_user_manage.php');
