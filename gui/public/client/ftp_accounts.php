<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by i-MSCP team
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

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generates page data
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl
 * @return void
 */
function ftp_generatePageData($tpl)
{
    $query = "SELECT userid, status FROM ftp_users WHERE admin_id = ? ORDER BY LENGTH(userid) DESC";
    $stmt = exec_query($query, $_SESSION['user_id']);

    if (!$stmt->rowCount()) {
        set_page_message(tr('You do not have FTP accounts.'), 'static_info');
        $tpl->assign('FTP_ACCOUNTS', '');
    } else {
        $cfg = \iMSCP\Core\Application::getInstance()->getConfig();

        if (!(isset($cfg['FILEMANAGER_PACKAGE']) && $cfg['FILEMANAGER_PACKAGE'] == 'Pydio')) {
            $tpl->assign('FTP_EASY_LOGIN', '');
        }

        $nbFtpAccounts = 0;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $userid = $row['userid'];

            $tpl->assign([
                'FTP_ACCOUNT' => tohtml($userid),
                'UID' => urlencode($userid),
                'FTP_ACCOUNT_STATUS' => translate_dmn_status($row['status'])
            ]);

            if ($row['status'] != 'ok') {
                $tpl->assign('FTP_ACTIONS', '');
            }


            $tpl->parse('FTP_ITEM', '.ftp_item');

            if ($row['status'] != 'todelete') {
                $nbFtpAccounts++;
            }
        }

        $tpl->assign('TOTAL_FTP_ACCOUNTS', $nbFtpAccounts);
    }
}

/***********************************************************************************************************************
 * Main
 */

require '../../application.php';

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onClientScriptStart);

check_login('user');

customerHasFeature('ftp') or showBadRequestErrorPage();

$cfg = \iMSCP\Core\Application::getInstance()->getConfig();

$tpl = new \iMSCP\Core\Template\TemplateEngine();
$tpl->define_dynamic([
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'client/ftp_accounts.tpl',
    'page_message' => 'layout',
    'ftp_message' => 'page',
    'ftp_accounts' => 'page',
    'ftp_item' => 'ftp_accounts',
    'ftp_actions' => 'ftp_item'
]);

$tpl->assign([
    'TR_PAGE_TITLE' => tr('Client / FTP / Overview'),
    'TR_TOTAL_FTP_ACCOUNTS' => tr('FTPs total'),
    'TR_FTP_USERS' => tr('FTP Users'),
    'TR_FTP_ACCOUNT' => tr('FTP account'),
    'TR_FTP_ACTION' => tr('Actions'),
    'TR_FTP_ACCOUNT_STATUS' => tr('Status'),
    'TR_EDIT' => tr('Edit'),
    'TR_DELETE' => tr('Delete'),
    'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete the %s FTP user?', '%s'),
]);

generateNavigation($tpl);
ftp_generatePageData($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onClientScriptEnd, null, [
    'templateEngine' => $tpl
]);
$tpl->prnt();

unsetMessages();
