<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 * Generates page
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl Template engine instance
 * @return void
 */
function reseller_generatePage($tpl)
{
    $cfg = \iMSCP\Core\Application::getInstance()->getConfig();
    $stmt = exec_query('SELECT domain_created from admin where admin_id = ?', $_SESSION['user_id']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $tpl->assign([
        'TR_ACCOUNT_SUMMARY' => tr('Account summary'),
        'TR_USERNAME' => tr('Username'),
        'USERNAME' => tohtml($_SESSION['user_logged']),
        'TR_ACCOUNT_TYPE' => tr('Account type'),
        'ACCOUNT_TYPE' => $_SESSION['user_type'],
        'TR_REGISTRATION_DATE' => tr('Registration date'),
        'REGISTRATION_DATE' => ($row['domain_created'] != 0) ? date($cfg['DATE_FORMAT'], $row['domain_created']) : tr('Unknown')
    ]);
}

/***********************************************************************************************************************
 * Main
 */

require '../../application.php';

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onResellerScriptStart);

check_login('reseller');

$tpl = new \iMSCP\Core\Template\TemplateEngine();
$tpl->defineDynamic([
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'reseller/profile.tpl',
    'page_message' => 'layout'
]);
$tpl->assign('TR_PAGE_TITLE', tr('Reseller / Profile / Account Summary'));

generateNavigation($tpl);
reseller_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onResellerScriptEnd, null, [
    'templateEngine' => $tpl
]);
$tpl->prnt();

unsetMessages();
