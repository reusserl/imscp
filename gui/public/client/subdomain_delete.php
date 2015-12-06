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

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onClientScriptStart);

check_login('user');

if (customerHasFeature('subdomains') && isset($_GET['id'])) {
    $subId = clean_input($_GET['id']);
    $dmnId = get_user_domain_id($_SESSION['user_id']);

    $query = "
        SELECT
            `t1`.`subdomain_id`, CONCAT(`t1`.`subdomain_name`, '.', `t2`.`domain_name`) AS `subdomain_name`
        FROM
            `subdomain` AS `t1`
        LEFT JOIN
            `domain` AS `t2` ON(`t2`.`domain_id` = `t1`.`domain_id`)
        WHERE
            `t2`.`domain_id` = ?
        AND
            `t1`.`subdomain_id` = ?
    ";
    $stmt = exec_query($query, [$dmnId, $subId]);

    if ($stmt->rowCount()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $subName = $row['subdomain_name'];
        $ret = false;

        // Check for mail accounts
        $query = "
            SELECT
                COUNT(`mail_id`) AS `cnt`
            FROM
                `mail_users`
            WHERE
                (`mail_type` LIKE ? OR `mail_type` = ?)
            AND
                `sub_id` = ?
        ";
        $stmt = exec_query($query, [$subId, MT_SUBDOM_MAIL . '%', MT_SUBDOM_FORWARD]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row['cnt'] > 0) {
            set_page_message(tr('Subdomain you are trying to remove has email accounts. Remove them first.'), 'error');
            $ret = true;
        }

        // Check for FTP accounts
        $query = "SELECT count(`userid`) AS `cnt` FROM `ftp_users` WHERE `userid` LIKE ?";
        $stmt = exec_query($query, "%@$subName");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row['cnt'] > 0) {
            set_page_message(tr('Subdomain you are trying to remove has FTP accounts. Remove them first.'), 'error');
            $ret = true;
        }

        if (!$ret) {
            \iMSCP\Core\Application::getInstance()->getEventManager()->trigger(
                \iMSCP\Core\Events::onBeforeDeleteSubdomain, null, ['subdomainId' => $subId, 'type' => 'sub']
            );

            $cfg = \iMSCP\Core\Application::getInstance()->getConfig();

            /** @var \Doctrine\DBAL\Connection $db */
            $db = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('Database');

            try {
                $db->beginTransaction();
                $query = "UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?";
                $stmt = exec_query($query, ['todelete', $subId]);
                $query = "UPDATE ssl_certs SET status = ? WHERE domain_id = ? AND domain_type = ?";
                $stmt = exec_query($query, ['todelete', $subId, 'sub']);
                $db->commit();
            } catch (PDOException $e) {
                $db->rollBack();
                throw $e;
            }

            \iMSCP\Core\Application::getInstance()->getEventManager()->trigger(
                \iMSCP\Core\Events::onAfterDeleteSubdomain, null, [
                'subdomainId' => $subId,
                'type' => 'sub'
            ]);

            send_request();
            write_log("{$_SESSION['user_logged']} scheduled deletion of subdomain: $subName", E_USER_NOTICE);
            set_page_message(tr('Subdomain successfully scheduled for deletion.'), 'success');
        }

        redirectTo('domains_manage.php');
    }
}

showBadRequestErrorPage();
