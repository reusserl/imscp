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
    \iMSCP\Core\Events::onAdminScriptStart, \iMSCP\Core\Application::getInstance()->getApplicationEvent()
);

check_login('admin');

$cfg = \iMSCP\Core\Application::getInstance()->getConfig();

if (isset($_GET['id']) && $cfg['HOSTING_PLANS_LEVEL'] === 'admin') {
    $stmt = exec_query('DELETE FROM `hosting_plans` WHERE `id` = ?', intval($_GET['id']));
    if ($stmt->rowCount()) {
        set_page_message(tr('Hosting plan has been successfully deleted.'), 'success');
        redirectTo('hosting_plan.php');
    }
}

showBadRequestErrorPage();
