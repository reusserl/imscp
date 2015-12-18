<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by i-MSCP Team <team@i-mscp.net>
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
 * Initialize login
 *
 * @param \Zend\EventManager\EventManagerInterface $eventManager Events Manager
 * @return void
 */
function init_login($eventManager)
{
    // Purge expired sessions
    //do_session_timeout();

    //$cfg = \iMSCP\Core\Application::getInstance()->getConfig();

    //if ($cfg['BRUTEFORCE']) {
    //    /** @var \iMSCP\Core\Plugin\PluginManager $pluginManager */
    //    $pluginManager = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('PluginManager');
    //    $bruteforce = new iMSCP\Core\Security\Bruteforce($pluginManager);
    //    $bruteforce->attach($pluginManager->getEventManager());
    //}

    // Attach a listener that is responsible to check domain status and expire date
    $eventManager->attach(\iMSCP\Core\Auth\AuthEvent::onAfterAuthentication, 'login_checkDomainAccount');
}

/**
 * Check domain account state (status and expires date)
 *
 * Note: Listen to the onBeforeSetIdentity event triggered in the iMSCP_Authentication component.
 *
 * @param \iMSCP\Core\Auth\AuthEvent $event
 * @return void
 */
function login_checkDomainAccount(\iMSCP\Core\Auth\AuthEvent $event)
{
    /** @var $identity stdClass */
    $identity = $event->getIdentity();

    if ($identity['admin_type'] === 'user') {
        $stmt = exec_query(
            '
                SELECT
                    domain_expires, domain_status, admin_status
                FROM
                    domain
                INNER JOIN
                    admin ON(domain_admin_id = admin_id)
                WHERE
                    domain_admin_id = ?
            ',
            $identity['admin_id']
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $isAccountStateOk = true;

        if (($row['admin_status'] != 'ok') || ($row['domain_status'] != 'ok')) {
            $isAccountStateOk = false;

            set_page_message(tr('Your account is currently under maintenance or disabled. Please, contact your reseller.'), 'error');
        } else {
            $domainExpireDate = $row['domain_expires'];

            if ($domainExpireDate && $domainExpireDate < time()) {
                $isAccountStateOk = false;
                set_page_message(tr('Your account has expired.'), 'error');
            }
        }

        if (!$isAccountStateOk) {
            redirectTo('index.php');
        }
    }
}

/**
 * Session garbage collector
 *
 * @return void
 */
function do_session_timeout()
{
    $cfg = \iMSCP\Core\Application::getInstance()->getConfig();

    // We must not remove bruteforce plugin data (AND `user_name` IS NOT NULL)
    exec_query('DELETE FROM login WHERE lastaccess < ? AND user_name IS NOT NULL', time() - $cfg['SESSION_TIMEOUT'] * 60);
}

/**
 * Check login
 *
 * @param string $userLevel User level (admin|reseller|user)
 * @param bool $preventExternalLogin If TRUE, external login is disallowed
 */
function check_login($userLevel = '', $preventExternalLogin = false)
{
    do_session_timeout();

    /** @var \iMSCP\Core\Auth\AuthenticationService $authentication */
    $authentication = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('Authentication');

    if (!$authentication->hasIdentity()) {
        $authentication->clearIdentity(); // Ensure deletion of all identity data

        if (is_xhr()) {
            header('Status: 401 Unauthorized');
            exit;
        }

        redirectTo('/index.php');
    }

    $cfg = \iMSCP\Core\Application::getInstance()->getConfig();
    $identity = $authentication->getIdentity();

    // When panel is in maintenance mode, only administrators can access it
    if (
        $cfg['MAINTENANCEMODE'] && $identity['admin_type'] !== 'admin' &&
        (!isset($_SESSION['logged_from_type']) || $_SESSION['logged_from_type'] !== 'admin')
    ) {
        $authentication->clearIdentity();
        redirectTo('/index.php');
    }

    // Check user level
    $userType = $identity['admin_type'];
    if (!empty($userLevel) && $userType !== $userLevel) {
        redirectTo('/index.php');
    }

    // Prevent external login / check from referer
    if ($preventExternalLogin) {
        /** @var \Zend\Http\PhpEnvironment\Request $request */
        $request = \iMSCP\Core\Application::getInstance()->getRequest();
        $httpReferer = $request->getServer('HTTP_REFERER');

        if($httpReferer) {
            // Extracting hostname from referer URL
            // Note2: We remove any braket in referer (ipv6 issue)
            $refererHostname = str_replace(['[', ']'], '', parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST));

            // The URL does contains the host element?
            if ($refererHostname) {
                // Note1: We don't care about the scheme, we only want make parse_url() happy
                // Note2: We remove any braket in hostname (ipv6 issue)
                $hostname = str_replace(['[', ']'], '', parse_url("http://{$_SERVER['HTTP_HOST']}", PHP_URL_HOST));

                // The referer doesn't match the panel hostname?
                if (!in_array($refererHostname, [$hostname, $request->getServer('SERVER_NAME')])) {
                    set_page_message(tr('Request from foreign host was blocked.'), 'info');

                    if (
                        substr(
                            $request->getServer('SCRIPT_FILENAME'),
                            (int)-strlen($request->getServer('REDIRECT_URL')),
                            strlen($request->getServer('REDIRECT_URL'))
                        ) !== $request->getServer('REDIRECT_URL')
                    ) {
                        redirectToUiLevel();
                    }
                }
            }
        }
    }

    // If all goes fine update session and lastaccess
    exec_query('UPDATE login SET lastaccess = ? WHERE session_id = ?', [time(), session_id()]);
}

/**
 * Switch between user's interfaces
 *
 * @param int $fromId User ID to switch from
 * @param int $toId User ID to switch on
 * @return void
 */
function change_user_interface($fromId, $toId)
{
    $toActionScript = false;

    while (1) { // We loop over nothing here, it's just a way to avoid code repetition
        $query = '
            SELECT
                admin_id, admin_name, admin_type, email, created_by
            FROM
                admin
            WHERE
                admin_id IN(?, ?)
            ORDER BY
                FIELD(admin_id, ?, ?)
            LIMIT
                2
        ';
        $stmt = exec_query($query, [$fromId, $toId, $fromId, $toId]);

        if ($stmt->rowCount() < 2) {
            set_page_message(tr('Wrong request.'), 'error');
        }

        list($from, $to) = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $fromToMap = [];
        $fromToMap['admin']['BACK'] = 'manage_users.php';
        $fromToMap['admin']['reseller'] = 'index.php';
        $fromToMap['admin']['user'] = 'index.php';
        $fromToMap['reseller']['user'] = 'index.php';
        $fromToMap['reseller']['BACK'] = 'users.php';

        if (!isset($fromToMap[$from['admin_type']][$to['admin_type']]) || ($from['admin_type'] == $to['admin_type'])) {
            if (isset($_SESSION['logged_from_id']) && $_SESSION['logged_from_id'] == $to['admin_id']) {
                $toActionScript = $fromToMap[$to['admin_type']]['BACK'];
            } else {
                set_page_message(tr('Wrong request.'), 'error');
                write_log(
                    sprintf("%s tried to switch onto %s's interface", $from['admin_name'], decode_idna($to['admin_name'])),
                    E_USER_WARNING
                );
                break;
            }
        }

        $toActionScript = ($toActionScript) ? $toActionScript : $fromToMap[$from['admin_type']][$to['admin_type']];

        // Set new identity
        /** @var \Zend\Authentication\AuthenticationService $authentication */
        $authentication = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('Authentication');

        $identity =  new \iMSCP\Core\Auth\Identity\AuthenticatedIdentity($to);
        $realIdentity = $authentication->getIdentity();


        $authentication->clearIdentity();

        class SuAdapter extends \Zend\Authentication\Adapter\AbstractAdapter
        {
            protected $identtiy;
            protected $realIdentity;

            public function __construct($identity, $realIdentity)
            {
                $this->identity = $identity;
                $this->$realIdentity = $realIdentity;
            }

            /**
             * Performs an authentication attempt
             *
             * @return \Zend\Authentication\Result
             * @throws \Zend\Authentication\Adapter\Exception\ExceptionInterface If authentication cannot be performed
             */
            public function authenticate()
            {
                return new \Zend\Authentication\Result(
                    \Zend\Authentication\Result::SUCCESS, new \iMSCP\Core\Auth\Identity\SuidIdentity(
                    $this->identity, $this->realIdentity
                ));
            }
        }

        $authentication->authenticate(new SuAdapter($identity, $realIdentity));

        if ($from['admin_type'] != 'user' && $to['admin_type'] != 'admin') {
            // Set additional data about user from wich we are logged from
            $_SESSION['logged_from_type'] = $from['admin_type'];
            $_SESSION['logged_from'] = $from['admin_name'];
            $_SESSION['logged_from_id'] = $from['admin_id'];

            write_log(sprintf("%s switched onto %s's interface", $from['admin_name'], decode_idna($to['admin_name'])), E_USER_NOTICE);
        } else {
            write_log(sprintf("%s switched back from %s's interface", $to['admin_name'], decode_idna($from['admin_name'])), E_USER_NOTICE);
        }

        break;
    }

    redirectToUiLevel($toActionScript);
}

/**
 * Redirects to user ui level
 *
 * @param string $redirect Script on which user should be redirected
 * @return void
 */
function redirectToUiLevel($redirect = 'index.php')
{
    /** @var \iMSCP\Core\Auth\AuthenticationService $authentication */
    $authentication = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('Authentication');

    if ($authentication->hasIdentity()) {
        $userType = $authentication->getIdentity()['admin_type'];

        switch ($userType) {
            case 'user':
            case 'admin':
            case 'reseller':
                // Prevents display of any old message when switching to another user level
                unset($_SESSION['pageMessages']);
                redirectTo('/' . (($userType === 'user') ? 'client' : $userType . '/' . $redirect));
                exit;
            default:
                throw new RuntimeException('Unknown UI level');
        }
    }
}
