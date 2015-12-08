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

$domainId = get_user_domain_id($_SESSION['user_id']);

if (isset($_GET['uname']) && $_GET['uname'] !== '' && is_numeric($_GET['uname'])) {
    $htuserId = $_GET['uname'];
} else {
    redirectTo('protected_areas.php');
    exit;
}

$stmt = exec_query('SELECT `uname` FROM `htaccess_users` WHERE `dmn_id` = ? AND `id` = ?', [$domainId, $htuserId]);

if (!$stmt->rowCount()) {
    showBadRequestErrorPage();
}

$row = $stmt->fetch(PDO::FETCH_ASSOC);
$uname = $row['uname'];

/** @var \Doctrine\DBAL\Connection $db */
$db = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('Database');

try {
    $db->beginTransaction();

    // Let's delete the user from the SQL
    exec_query('UPDATE `htaccess_users` SET `status` = ? WHERE `id` = ? AND `dmn_id` = ?', [
        'todelete', $htuserId, $domainId
    ]);

    // Let's delete this user if assigned to a group
    $stmt = exec_query('SELECT `id`, `members` FROM `htaccess_groups` WHERE `dmn_id` = ?', $domainId);

    if ($stmt->rowCount()) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $members = explode(',', $row['members']);
            $group_id = $row['id'];
            $key = array_search($htuserId, $members);

            if ($key !== false) {
                unset($members[$key]);
                $members = implode(",", $members);
                $rs_update = exec_query('UPDATE `htaccess_groups` SET `members` = ?, `status` = ? WHERE `id` = ?', [
                    $members, 'tochange', $group_id
                ]);
            }
        }
    }

    // Let's delete or update htaccess files if this user is assigned
    $stmt = exec_query('SELECT * FROM `htaccess` WHERE `dmn_id` = ?', $domainId);

    if ($stmt->rowCount()) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $htid = $row['id'];
            $usrid = $row['user_id'];
            $usridSplited = explode(',', $usrid);
            $key = array_search($htuserId, $usridSplited);

            if ($key !== false) {
                unset($usridSplited[$key]);

                if (count($usridSplited) == 0) {
                    $status = 'todelete';
                } else {
                    $usrid = implode(",", $usridSplited);
                    $status = 'tochange';
                }

                $rs_update = exec_query('UPDATE `htaccess` SET `user_id` = ?, `status` = ? WHERE `id` = ?', [
                        $usrid, $status, $htid]
                );
            }
        }
    }

    set_page_message(tr('User scheduled for deletion.'), 'success');
    send_request();
    write_log("{$_SESSION['user_logged']}: deletes user ID (protected areas): $uname", E_USER_NOTICE);
    redirectTo('protected_user_manage.php');
} catch (PDOException $e) {
    $db->rollBack();
    throw $e;
}
