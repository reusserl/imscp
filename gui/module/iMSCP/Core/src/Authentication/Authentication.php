<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

namespace iMSCP\Core\Authentication;

use iMSCP\Core\Utils\Crypt;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;

/**
 * Class Authentication
 *
 * This component is responsible to authenticate users through authentication handlers. An authentication handler is a
 * listener that listen on the iMSCP\Core\Plugin\PluginEvent::onAuthenticate event which is triggered during
 * authentication process.
 *
 * Any authentication handlers must return an iMSCP\Core\Authentication\AuthenticationResult object that specify whether
 * or not the authentication is successfull. In case of a successfull authentication, all other authentication handlers
 * are skipped.
 *
 * @package iMSCP\Core\Authentication
 */
class Authentication implements EventManagerAwareInterface
{
    /**
     * @var EventManager
     */
    protected $events;

    /**
     * @var AuthenticationEvent
     */
    protected $event;

    /**
     * @var \StdClass identity
     */
    protected $identity;

    /**
     * Constructor
     *
     * @param EventManagerInterface $eventManager
     */
    public function __construct(EventManagerInterface $eventManager = null)
    {
        if ($eventManager instanceof EventManagerInterface) {
            $this->setEventManager($eventManager);
        }
    }

    /**
     * Handle authentication
     *
     * This is default authentication handler which handle login credential (user/password) authentication.
     *
     * @param AuthenticationEvent $event
     * @return void
     */
    public function onAuthentication(AuthenticationEvent $event)
    {
        $messages = [];
        $username = (!empty($_POST['uname'])) ? encode_idna(clean_input($_POST['uname'])) : '';
        $password = (!empty($_POST['upass'])) ? clean_input($_POST['upass']) : '';

        if (empty($username)) {
            $messages[] = tr('The username field is empty.');
        }

        if (empty($password)) {
            $messages[] = tr('The password field is empty.');
        }

        if (empty($messages)) {
            $stmt = exec_query(
                'SELECT admin_id, admin_name, admin_pass, admin_type, email, created_by FROM admin WHERE admin_name = ?',
                $username
            );

            if (!$stmt->rowCount()) {
                $authResult = new AuthenticationResult(
                    AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND, [], tr('Supplied credential is invalid.')
                );
            } else {
                $identity = $stmt->fetch(\PDO::FETCH_ASSOC);
                $passwordHash = $identity['admin_pass'];

                if (!Crypt::verify($password, $passwordHash)) {
                    $authResult = new AuthenticationResult(
                        AuthenticationResult::FAILURE_CREDENTIAL_INVALID, [], tr('Supplied credential is invalid.')
                    );
                } else {
                    if (strpos($passwordHash, '$2a$') !== 0) { # Not a password encrypted with Bcrypt, then re-encrypt it
                        exec_query('UPDATE admin SET admin_pass = ? WHERE admin_id = ?', [
                            Crypt::bcrypt($password), $identity['admin_id']
                        ]);
                    }

                    $authResult = new AuthenticationResult(
                        AuthenticationResult::SUCCESS, $identity, 'Authentication successful.'
                    );
                }
            }
        } else {
            $authResult = new AuthenticationResult(
                (count($messages) == 2)
                    ? AuthenticationResult::FAILURE_CREDENTIAL_EMPTY : AuthenticationResult::FAILURE_CREDENTIAL_INVALID,
                [],
                $messages
            );
        }

        $event->setAuthResult($authResult);
    }

    /**
     * Set the authentication event
     *
     * @param  AuthenticationEvent $event
     * @return AuthenticationEvent
     */
    public function setEvent(AuthenticationEvent $event)
    {
        $this->event = $event;
        return $this;
    }

    /**
     * Get the authentication event
     *
     * @return AuthenticationEvent
     */
    public function getEvent()
    {
        if (!$this->event instanceof AuthenticationEvent) {
            $this->setEvent(new AuthenticationEvent());
        }

        return $this->event;
    }

    /**
     * Inject an EventManager instance
     *
     * @param  EventManagerInterface $events
     * @return self
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers([
            __CLASS__,
            get_class($this),
            'authentication_service',
        ]);
        $this->events = $events;
        $this->attachDefaultListeners();
        return $this;
    }

    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (!$this->events instanceof EventManagerInterface) {
            $this->setEventManager(new EventManager());
        }

        return $this->events;
    }

    /**
     * Process authentication
     *
     * @trigger AuthenticationEvent::onBeforeAuthentication
     * @trigger AuthenticationEvent::onAuthentication
     * @trigger AuthenticationEvent::onAfterAuthentication
     * @return AuthenticationResult
     */
    public function authenticate()
    {
        $events = $this->getEventManager();
        $response = $events->trigger(AuthenticationEvent::onBeforeAuthentication, $this, $this->getEvent());

        if (!$response->stopped()) {
            $result = $events->trigger(AuthenticationEvent::onAuthentication, $this, $this->getEvent(), function ($r) {
                return $r instanceof AuthenticationResult && $r->isValid();
            });

            $authResult = $result->last();

            if (!$authResult instanceof AuthenticationResult) {
                $authResult = new AuthenticationResult(
                    AuthenticationResult::FAILURE_UNCATEGORIZED, tr('Could not authenticate.')
                );
            }

            if ($authResult->isValid()) {
                $this->unsetIdentity(); // Prevent multiple successive calls from storing inconsistent results
                $this->setIdentity($authResult->getIdentity());
            }

            $this->getEvent()->setAuthResult($authResult);
        } else {
            $authResult = new AuthenticationResult(
                AuthenticationResult::FAILURE_UNCATEGORIZED, null, tr('Authentication process has been interrupted.')
            );
            $this->getEvent()->setAuthResult($authResult);
        }

        $events->trigger(AuthenticationEvent::onAfterAuthentication, $this, $this->getEvent());
        return $authResult;
    }

    /**
     * Returns true if and only if an identity is available from storage
     *
     * @return boolean
     */
    public function hasIdentity()
    {
        if (isset($_SESSION['user_id'])) {
            $stmt = exec_query('SELECT COUNT(session_id) AS cnt FROM login WHERE session_id = ? AND ipaddr = ?', [
                session_id(),
                getipaddr()
            ]);

            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (bool)$row['cnt'];
        }

        return false;
    }

    /**
     * Returns the identity from storage or null if no identity is available
     *
     * @return \stdClass|null
     */
    public function getIdentity()
    {
        if ($this->identity === null) {
            if ($this->hasIdentity()) {
                $this->identity = new \stdClass();
                $this->identity->admin_id = $_SESSION['user_id'];
                $this->identity->admin_name = $_SESSION['user_logged'];
                $this->identity->admin_type = $_SESSION['user_type'];
                $this->identity->email = $_SESSION['user_email'];
                $this->identity->created_by = $_SESSION['user_created_by'];

                if (isset($_SESSION['logged_from_type'])) {
                    $this->identity->logged_from_admin_id = $_SESSION['logged_from_id'];
                    $this->identity->logged_from_admin_name = $_SESSION['logged_from'];
                    $this->identity->logged_from_admin_type = $_SESSION['logged_from_type'];
                }
            } else {
                $this->identity = new \stdClass();
                $this->identity->admin_id = null;
                $this->identity->admin_name = null;
                $this->identity->admin_type = 'guest';
                $this->identity->email = null;
                $this->identity->created_by = null;
            }
        }

        return $this->identity;
    }

    /**
     * Set the given identity
     *
     * @trigger AuthenticationEvent::onBeforeSetIdentity
     * @trigger AuthenticationEvent::onAfterSetIdentify
     * @param \stdClass $identity Identity data
     */
    public function setIdentity($identity)
    {
        $this->getEventManager()->trigger(AuthenticationEvent::onBeforeSetIdentity, $this, $this->getEvent());

        session_regenerate_id();
        $lastAccess = time();

        exec_query('INSERT INTO login (session_id, ipaddr, lastaccess, user_name) VALUES (?, ?, ?, ?)', [
            session_id(), getIpAddr(), $lastAccess, $identity['admin_name']
        ]);

        $_SESSION['user_logged'] = $identity['admin_name'];
        $_SESSION['user_type'] = $identity['admin_type'];
        $_SESSION['user_id'] = $identity['admin_id'];
        $_SESSION['user_email'] = $identity['email'];
        $_SESSION['user_created_by'] = $identity['created_by'];
        $_SESSION['user_login_time'] = $lastAccess;
        $_SESSION['user_identity'] = $identity;

        $this->getEventManager()->trigger(AuthenticationEvent::onAfterSetIdentity, $this, $this->getEvent());
    }

    /**
     * Unset the current identity
     *
     * @trigger AuthenticationEvent::onBeforeUnsetIdentity
     * @trigger AuthenticationEvent::onAfterUnserIdentity
     * @return void
     */
    public function unsetIdentity()
    {
        $this->getEventManager()->trigger(AuthenticationEvent::onBeforeUnsetIdentity, $this, $this->getEvent());

        exec_query('DELETE FROM login WHERE session_id = ?', session_id());

        $preserveList = ['user_def_lang', 'user_theme', 'user_theme_color', 'show_main_menu_labels', 'pageMessages'];
        foreach (array_keys($_SESSION) as $sessionVariable) {
            if (!in_array($sessionVariable, $preserveList)) {
                unset($_SESSION[$sessionVariable]);
            }
        }

        $this->identity = null;
        $this->getEventManager()->trigger(AuthenticationEvent::onAfterUnsetIdentity, $this, $this->getEvent());
    }

    /**
     * Register the default event listeners
     *
     * @return Authentication
     */
    protected function attachDefaultListeners()
    {
        $events = $this->getEventManager();
        $events->attach(AuthenticationEvent::onAuthentication, [$this, 'onAuthentication'], 99);
    }
}
