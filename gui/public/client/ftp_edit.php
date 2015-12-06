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

require '../../application.php';

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page data
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl Template engine instance
 * @param string $ftpUserId FTP userid
 * @param string $mainDomainName Main domain name
 * @return void
 */
function generatePageData($tpl, $ftpUserId, $mainDomainName)
{
    $cfg = \iMSCP\Core\Application::getInstance()->getConfig();
    $query = "SELECT `homedir` FROM `ftp_users` WHERE `userid` = ?";
    $stmt = exec_query($query, $ftpUserId);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $ftpHomeDir = $row['homedir'];
    $customerHomeDir = $cfg['USER_WEB_DIR'] . '/' . $mainDomainName;

    if ($ftpHomeDir == $customerHomeDir) {
        $customFtpHomeDir = '/';
    } else {
        $customFtpHomeDir = substr($ftpHomeDir, strlen($customerHomeDir));
    }

    $tpl->assign(
        [
            'USERNAME' => tohtml($ftpUserId),
            'HOME_DIR' => (isset($_POST['home_dir'])) ? tohtml($_POST['home_dir']) : tohtml($customFtpHomeDir),
            'ID' => tohtml($ftpUserId),
        ]
    );
}

/**
 * Update FTP account
 *
 * @param string $userid FTP userid
 * @param string $mainDomainName Main domain name
 * @return bool TRUE on success, FALSE on failure
 */
function updateFtpAccount($userid, $mainDomainName)
{
    $ret = true;

    if (!empty($_POST['password'])) {
        if (empty($_POST['password_repeat']) || $_POST['password'] !== $_POST['password_repeat']) {
            set_page_message(tr("Passwords do not match."), 'error');
            $ret = false;
        }

        if (!checkPasswordSyntax($_POST['password'])) {
            $ret = false;
        }

        $passwd = $_POST['password'];
        $encPasswd = \iMSCP\Core\Utils\Crypt::sha512($passwd);
    } else {
        $passwd = null;
        $encPasswd = null;
    }

    if (isset($_POST['home_dir'])) {
        $homeDir = clean_input($_POST['home_dir']);

        if ($homeDir != '/' && $homeDir != '') {
            // Strip possible double-slashes
            $homeDir = str_replace('//', '/', $homeDir);

            // Check for updirs '..'
            if (strpos($homeDir, '..') !== false) {
                set_page_message(tr('Invalid home directory.'), 'error');
                $ret = false;
            }

            if ($ret) {
                $vfs = new \iMSCP\Core\VirtualFileSystem($mainDomainName);

                // Check for directory existence
                if (!$vfs->exists($homeDir)) {
                    set_page_message(tr("Home directory '%s' doesn't exist", $homeDir), 'error');
                    $ret = false;
                }
            }
        }
    } else {
        showBadRequestErrorPage();
        exit;
    }

    if ($ret) {
        \iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onBeforeEditFtp, null, [
            'ftpUserId' => $userid,
            'ftpPassword' => $passwd
        ]);

        $cfg = \iMSCP\Core\Application::getInstance()->getConfig();
        $homeDir = rtrim(str_replace('//', '/', $cfg['USER_WEB_DIR'] . '/' . $mainDomainName . '/' . $homeDir), '/');

        if ($cfg['FTPD_SERVER'] == 'vsftpd') {
            if (isset($encPasswd) && isset($homeDir)) {
                $query = "UPDATE `ftp_users` SET `passwd` = ?, `homedir` = ?, `status` = ? WHERE `userid` = ?";
                exec_query($query, [$encPasswd, $homeDir, 'tochange', $userid]);
            } else {
                $query = "UPDATE `ftp_users` SET `homedir` = ?, `status` = ? WHERE `userid` = ?";
                exec_query($query, [$homeDir, 'tochange', $userid]);
            }
        } else {
            if (isset($encPasswd) && isset($homeDir)) {
                $query = "UPDATE `ftp_users` SET `passwd` = ?, `homedir` = ? WHERE `userid` = ?";
                exec_query($query, [$encPasswd, $homeDir, $userid]);
            } else {
                $query = "UPDATE `ftp_users` SET `homedir` = ? WHERE `userid` = ?";
                exec_query($query, [$homeDir, $userid]);
            }
        }

        \iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAfterEditFtp, null, [
            'ftpUserId' => $userid,
            'ftpPassword' => $passwd
        ]);

        if ($cfg['FTPD_SERVER'] == 'vsftpd') {
            send_request();
        }

        write_log(sprintf("%s updated FTP account: %s", $_SESSION['user_logged'], $userid), E_USER_NOTICE);
        set_page_message(tr('FTP account successfully updated.'), 'success');
    }

    return $ret;
}

/***********************************************************************************************************************
 * Main
 */

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onClientScriptStart);

check_login('user');
customerHasFeature('ftp') or showBadRequestErrorPage();

if (isset($_GET['id'])) {
    $userid = clean_input($_GET['id']);

    if (who_owns_this($userid, 'ftpuser') != $_SESSION['user_id']) {
        showBadRequestErrorPage();
    }

    $stmt = exec_query("SELECT `domain_name` FROM `domain` WHERE`domain_admin_id` = ?", $_SESSION['user_id']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $mainDomainName = $row['domain_name'];

    if (!empty($_POST)) {
        if (updateFtpAccount($userid, $mainDomainName)) {
            redirectTo('ftp_accounts.php');
        }
    }

    $tpl = new \iMSCP\Core\Template\TemplateEngine();
    $tpl->define_dynamic([
        'layout' => 'shared/layouts/ui.tpl',
        'page' => 'client/ftp_edit.tpl',
        'page_message' => 'layout'
    ]);
    $tpl->assign([
        'TR_PAGE_TITLE' => tr('Client / FTP / Overview / Edit FTP Account'),
        'TR_FTP_DIRECTORIES' => tojs(('FTP directories')),
        'TR_CLOSE' => tojs(tr('Close')),
        'TR_FTP_USER_DATA' => tr('FTP account data'),
        'TR_USERNAME' => tr('Username'),
        'TR_PASSWORD' => tr('Password'),
        'TR_PASSWORD_REPEAT' => tr('Repeat password'),
        'TR_HOME_DIR' => tr('Home directory'),
        'CHOOSE_DIR' => tr('Choose dir'),
        'TR_CHANGE' => tr('Update'),
        'TR_CANCEL' => tr('Cancel')
    ]);

    generatePageData($tpl, $userid, $mainDomainName);
    generateNavigation($tpl);
    generatePageMessage($tpl);

    $tpl->parse('LAYOUT_CONTENT', 'page');
    \iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onClientScriptEnd, null, [
        'templateEngine' => $tpl
    ]);
    $tpl->prnt();

    unsetMessages();
} else {
    showBadRequestErrorPage();
}
