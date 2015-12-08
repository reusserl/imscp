<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by i-MSCP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Schedule deletion of the given mail account
 * /**
 * @param $mailId
 * @param $dmnProps
 * @throws Exception
 */
function client_deleteMailAccount($mailId, $dmnProps)
{
    $stmt = exec_query(
        'SELECT `mail_addr` FROM `mail_users` WHERE `mail_id` = ? AND `domain_id` = ?',
        [$mailId, $dmnProps['domain_id']]
    );

    if ($stmt->rowCount()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $mailAddr = $row['mail_addr'];
        $toDeleteStatus = 'todelete';

        \iMSCP\Core\Application::getInstance()->getEventManager()->trigger(
            \iMSCP\Core\Events::onBeforeDeleteMail, null, ['mailId' => $mailId]
        );

        exec_query('UPDATE `mail_users` SET `status` = ? WHERE `mail_id` = ?', [$toDeleteStatus, $mailId]);
        exec_query(
            '
                UPDATE
                    `mail_users`
                SET
                    `status` = ?
                WHERE
                    `mail_acc` = ? OR `mail_acc` LIKE ? OR `mail_acc` LIKE ? OR `mail_acc` LIKE ?
            ',
            [$toDeleteStatus, $mailAddr, "$mailAddr,%", "%,$mailAddr,%", "%,$mailAddr"]
        );
        delete_autoreplies_log_entries();

        \iMSCP\Core\Application::getInstance()->getEventManager()->trigger(
            \iMSCP\Core\Events::onAfterDeleteMail, null, ['mailId' => $mailId]
        );

        set_page_message(tr('Mail account %s successfully scheduled for deletion.', '<strong>' . decode_idna($mailAddr) . '</strong>'), 'success');
    } else {
        showBadRequestErrorPage();
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

if (customerHasFeature('mail') && isset($_REQUEST['id'])) {
    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);
    $nbDeletedMails = 0;
    $mailIds = (array)$_REQUEST['id'];
    $mailId = null;

    if (!empty($mailIds)) {
        /** @var \Doctrine\DBAL\Connection $db */
        $db = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('Database');

        try {
            $db->beginTransaction();

            foreach ($mailIds as $mailId) {
                $mailId = clean_input($mailId);
                client_deleteMailAccount($mailId, $mainDmnProps);
                $nbDeletedMails++;
            }

            $db->commit();
            send_request();
            write_log(sprintf("{$_SESSION['user_logged']} deleted %d mail account(s)", $nbDeletedMails), E_USER_NOTICE);
        } catch (PDOException $e) {
            $db->rollBack();

            if (isset($_SESSION['pageMessages'])) {
                unset($_SESSION['pageMessages']);
            }

            $errorMessage = $e->getMessage();
            $code = $e->getCode();

            write_log(
                sprintf(
                    'An unexpected error occurred while attempting to delete mail account with ID %s: %s',
                    $mailId,
                    $errorMessage
                ),
                E_USER_ERROR
            );

            if ($code == 403) {
                set_page_message(tr('Operation canceled: %s', $errorMessage), 'warning');
            } elseif ($e->getCode() == 400) {
                showBadRequestErrorPage();
            } else {
                set_page_message(tr('An unexpected error occurred. Please contact your reseller.'), 'error');
            }
        }
    } else {
        set_page_message(tr('You must select a least one mail account to delete.'), 'error');
    }

    redirectTo('mail_accounts.php');
} else {
    showBadRequestErrorPage();
}
