<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Authentication_AuthEvent as AuthEvent;
use iMSCP_Authentication_Result as AuthResult;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Events_Manager_Interface as EventManagerInterface;

/**
 * Authentication class
 *
 * This service authenticate users by triggering the AuthEvent event. Listeners of that event are
 * authentication handlers which are responsible to implement real authentication logic.
 *
 * Any authentication handler should set the appropriate AuthResult on the AuthEvent.
 */
class iMSCP_Authentication
{
    /**
     * Singleton instance
     *
     * @var iMSCP_Authentication
     */
    protected static $instance = NULL;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager = NULL;

    /**
     * Singleton pattern implementation -  makes "new" unavailable
     */
    protected function __construct()
    {

    }

    /**
     * Singleton pattern implementation -  makes "clone" unavailable
     *
     * @return void
     */
    protected function __clone()
    {

    }

    /**
     * Implements singleton design pattern
     *
     * @return iMSCP_Authentication Provides a fluent interface, returns self
     */
    public static function getInstance()
    {
        if (NULL === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Return an iMSCP_Events_Manager instance
     *
     * @param EventManagerInterface $events
     * @return EventManagerInterface
     */
    public function getEventManager(EventManagerInterface $events = NULL)
    {
        if (NULL !== $events) {
            $this->eventManager = $events;
            return $events;
        }

        if (NULL === $this->eventManager) {
            $this->eventManager = EventManager::getInstance();
        }

        return $this->eventManager;
    }

    /**
     * Process authentication
     *
     * @trigger onBeforeAuthentication
     * @trigger onAuthentication
     * @trigger onAfterAuthentication
     * @return iMSCP_Authentication_Result
     */
    public function authenticate()
    {
        $em = $this->getEventManager();
        $response = $em->dispatch(Events::onBeforeAuthentication, array('context' => $this));

        if (!$response->isStopped()) {
            $authEvent = new AuthEvent();

            // Process authentication through registered handlers
            // Note: In versions pre1.3.9, the auth result was pulled from the response object. To stay compatible with
            // plugins that were developed for versions pre1.3.9, we first try to pull the auth result from the response
            // object and if it is not defined, we pull it from the new auth event that has been introduced in version
            // 1.3.9. Plugin that make use of the new auth event must requires the i-MSCP API 1.0.7.
            $response = $em->dispatch($authEvent, array('context' => $this));
            $authResult = $response->last() ?: $authEvent->getAuthenticationResult();

            // Covers case where no one of authentication handlers has set an authentication result
            if (!$authResult instanceof AuthResult) {
                $authResult = new AuthResult(AuthResult::FAILURE_UNCATEGORIZED, tr('Unknown reason.'));
            }

            if ($authResult->isValid()) {
                $this->unsetIdentity(); // Prevent multiple successive calls from storing inconsistent results
                $this->setIdentity($authResult->getIdentity());
            }
        } else {
            $authResult = new AuthResult(AuthResult::FAILURE_UNCATEGORIZED, NULL, $response->last());
        }

        $em->dispatch(Events::onAfterAuthentication, array('context' => $this, 'authResult' => $authResult));
        return $authResult;
    }

    /**
     * Returns true if and only if an identity is available from storage
     *
     * @return boolean
     */
    public function hasIdentity()
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        $stmt = exec_query('SELECT COUNT(session_id) AS cnt FROM login WHERE session_id = ? AND ipaddr = ?', array(
            session_id(), getipaddr()
        ));

        return (bool)$stmt->fetchRow(PDO::FETCH_COLUMN);
    }

    /**
     * Returns the identity from storage or null if no identity is available
     *
     * @return stdClass|null
     */
    public function getIdentity()
    {
        $identity = NULL;

        if (!$this->hasIdentity()) {
            return $identity;
        }

        $identity = new stdClass();
        $identity->admin_id = $_SESSION['user_id'];
        $identity->admin_name = $_SESSION['user_logged'];
        $identity->admin_type = $_SESSION['user_type'];
        $identity->email = $_SESSION['user_email'];
        $identity->created_by = $_SESSION['user_created_by'];
        return $identity;
    }

    /**
     * Set the given identity
     *
     * @trigger onBeforeSetIdentity
     * @trigger onAfterSetIdentify
     * @param stdClass $identity Identity data
     */
    public function setIdentity($identity)
    {
        $this->getEventManager()->dispatch(
            Events::onBeforeSetIdentity, array('context' => $this, 'identity' => $identity)
        );

        session_regenerate_id();
        $lastAccess = time();

        exec_query('INSERT INTO login (session_id, ipaddr, lastaccess, user_name) VALUES (?, ?, ?, ?)', array(
            session_id(), getIpAddr(), $lastAccess, $identity->admin_name
        ));

        $_SESSION['user_logged'] = $identity->admin_name;
        $_SESSION['user_type'] = $identity->admin_type;
        $_SESSION['user_id'] = $identity->admin_id;
        $_SESSION['user_email'] = $identity->email;
        $_SESSION['user_created_by'] = $identity->created_by;
        $_SESSION['user_login_time'] = $lastAccess;
        $_SESSION['user_identity'] = $identity;

        $this->getEventManager()->dispatch(Events::onAfterSetIdentity, array('context' => $this));
    }

    /**
     * Unset the current identity
     *
     * @trigger onBeforeUnsetIdentity
     * @trigger onAfterUnserIdentity
     * @return void
     */
    public function unsetIdentity()
    {
        $this->getEventManager()->dispatch(Events::onBeforeUnsetIdentity, array('context' => $this));

        exec_query('DELETE FROM login WHERE session_id = ?', session_id());

        $preserveList = array(
            'user_def_lang', 'user_theme', 'user_theme_color', 'show_main_menu_labels', 'pageMessages'
        );

        foreach (array_keys($_SESSION) as $sessionVariable) {
            if (!in_array($sessionVariable, $preserveList)) {
                unset($_SESSION[$sessionVariable]);
            }
        }

        $this->getEventManager()->dispatch(Events::onAfterUnsetIdentity, array('context' => $this));
    }
}
