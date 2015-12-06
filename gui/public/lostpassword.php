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

require '../application.php';
require 'module/iMSCP/Core/src/Functions/LostPassword.php';

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onLostPasswordScriptStart);

do_session_timeout();

$cfg = \iMSCP\Core\Application::getInstance()->getConfig();

if (!$cfg['LOSTPASSWORD']) {
    redirectTo('/index.php');
}

if (!check_gd()) {
    throw new RuntimeException(tr("PHP GD extension not loaded."));
}

removeOldKeys($cfg['LOSTPASSWORD_TIMEOUT']);

$tpl = new \iMSCP\Core\Template\TemplateEngine();
$tpl->defineDynamic([
    'layout' => 'shared/layouts/simple.tpl',
    'page' => 'lostpassword.tpl',
    'page_message' => 'layout'
]);
$tpl->assign([
    'TR_PAGE_TITLE' => tr('i-MSCP - Multi Server Control Panel / Lost Password'),
    'CONTEXT_CLASS' => '',
    'productLongName' => tr('internet Multi Server Control Panel'),
    'productLink' => 'http://www.i-mscp.net',
    'productCopyright' => tr('© 2010-2015 i-MSCP Team<br/>All Rights Reserved'),
    'TR_CAPCODE' => tr('Security code'),
    'GET_NEW_IMAGE' => tr('Generate new code'),
    'TR_IMGCAPCODE' => '<img id="captcha" src="imagecode.php" width="' . $cfg['LOSTPASSWORD_CAPTCHA_WIDTH'] .
        '" height="' . $cfg['LOSTPASSWORD_CAPTCHA_HEIGHT'] . '" alt="captcha image" />',
    'TR_USERNAME' => tr('Username'),
    'TR_SEND' => tr('Send'),
    'TR_CANCEL' => tr('Cancel')
]);

/** @var \Zend\Http\PhpEnvironment\Request $request */
$request = \iMSCP\Core\Application::getInstance()->getRequest();

// A request for new password was validated (User clicked on the link he has received by mail)
if (($key = $request->getQuery('key'))) {
    clean_input($key);

    // Sending new password
    if (sendPassword($key)) {
        set_page_message(tr('Your new password has been sent. Check your email.'), 'success');
        redirectTo('index.php');
    } else {
        set_page_message(tr('New password has not been sent. Ask your administrator.'), 'error');
    }
} elseif ($request->isPost()) { // Request for new password
    /** @var \iMSCP\Core\Plugin\PluginManager $pluginManager */
    $pluginManager = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('pluginManager');
    $bruteForce = new \iMSCP\Core\Plugin\Bruteforce($pluginManager, 'captcha');

    if ($bruteForce->isWaiting() || $bruteForce->isBlocked()) {
        set_page_message($bruteForce->getLastMessage(), 'error');
        redirectTo('lostpassword.php');
    } else {
        $bruteForce->recordAttempt();
    }

    if (($uname = $request->getPost('uname')) && isset($_SESSION['image']) && ($capcode = $request->getPost('capcode'))) {
        $uname = clean_input($uname);
        $capcode = clean_input($capcode);

        if ($_SESSION['image'] !== $capcode) {
            set_page_message(tr('Wrong security code'), 'error');
        } elseif (!requestPassword($uname)) {
            set_page_message(tr('Wrong username.'), 'error');
        } else {
            set_page_message(tr('Your request for new password has been registered. You will receive an email with instructions to complete the process.'), 'success');
        }
    } else {
        set_page_message(tr('All fields are required.'), 'error');
    }
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onLostPasswordScriptEnd, null, [
    'templateEngine' => $tpl
]);
$tpl->prnt();
