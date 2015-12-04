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

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAdminScriptStart);

check_login('admin');

$cfg = \iMSCP\Core\Application::getInstance()->getConfig();

$tpl = new \iMSCP\Core\Template\TemplateEngine();
$tpl->define_dynamic([
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'admin/settings_maintenance_mode.tpl',
    'page_message' => 'layout'
]);

if (isset($_POST['uaction']) and $_POST['uaction'] == 'apply') {
    $maintenancemode = $_POST['maintenancemode'];
    $maintenancemode_message = clean_input($_POST['maintenancemode_message']);

    /** @var \iMSCP\Core\Config\DbConfigHandler $dbConfig */
    $dbConfig = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('DbConfig');
    $dbConfig['MAINTENANCEMODE'] = $maintenancemode;
    $dbConfig['MAINTENANCEMODE_MESSAGE'] = $maintenancemode_message;
    set_page_message(tr('Settings saved.'), 'success');
    redirectTo('settings_maintenance_mode.php');
}

$selectedOn = '';
$selectedOff = '';

if ($cfg['MAINTENANCEMODE']) {
    $selectedOn = $cfg['HTML_SELECTED'];
    set_page_message(tr('Maintenance mode is activated. In this mode, only administrators can login.'), 'static_info');
} else {
    $selectedOff = $cfg['HTML_SELECTED'];
    set_page_message(tr('In maintenance mode, only administrators can login.'), 'static_info');
}

$tpl->assign([
    'TR_PAGE_TITLE' => tr('Admin / System Tools / Maintenance Settings'),
    'TR_MAINTENANCEMODE' => tr('Maintenance mode'),
    'TR_MESSAGE' => tr('Message'),
    'MESSAGE_VALUE' => (isset($cfg['MAINTENANCEMODE_MESSAGE']))
        ? tohtml($cfg['MAINTENANCEMODE_MESSAGE'])
        : tr("We are sorry, but the system is currently under maintenance.\nPlease try again later."),
    'SELECTED_ON' => $selectedOn,
    'SELECTED_OFF' => $selectedOff,
    'TR_ENABLED' => tr('Enabled'),
    'TR_DISABLED' => tr('Disabled'),
    'TR_APPLY' => tr('Apply'),
    'TR_MAINTENANCE_MESSAGE' => tr('Maintenance message')
]);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAdminScriptEnd, [
    'templateEngine' => $tpl
]);
$tpl->prnt();

unsetMessages();
