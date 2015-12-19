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
namespace iMSCP\Auth;

use iMSCP\Core\Application;
use iMSCP\Core\ApplicationEvent;
use Zend\Console\Console;
use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ServiceManager\ServiceManager;

/**
 * Class Module
 * @package iMSCP\Auth
 */
class Module implements ConfigProviderInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * {@inheritdoc}
     */
    public function onBootstrap(EventInterface $appEvent)
    {
        if (Console::isConsole()) {
            return;
        }

        /** @var ApplicationEvent $appEvent */
        /** @var Application $application */
        $application = $appEvent->getApplication();

        //$events = $application->getEventManager();
        $this->serviceManager = $application->getServiceManager();

        // Setup authentication/authorization layer

        /** @var \Zend\Authentication\AuthenticationServiceInterface $authenticationService */
//        $authenticationService = $this->serviceManager->get('authentication');

        /** @var \iMSCP\Auth\Authorization\AuthorizationInterface $authorizationService */
//        $authorizationService = $this->serviceManager->get('authorization');

        // Add both authentication and authorization service to the authentication event
//        $authEvent = new AuthEvent($appEvent, $authenticationService, $authorizationService);

        // To be replaced by MvcRoute listener in v2.0.0
        // Attach listener responsible to trigger authentication events when a i-MSCP action script start
//        $scriptStartListener = new ScriptStartListener($authEvent, $events);
//        $events->attach($scriptStartListener);

        // Attach default listener for authentication tasks
//        $events->attach(AuthEvent::onAuthentication, $this->serviceManager->get(
//            'iMSCP\Auth\Authentication\AuthenticationListener'
//        ));

        // Attach default listener for post authentication tasks
//        $events->attach(AuthEvent::onAuthentication, $this->serviceManager->get(
//            'iMSCP\Auth\Authentication\AuthenticationPostListener'
//        ));

        // Attach listener which is responsible to setup Identity service. This allows to retrieve
        // current Identity as a service.
//        $events->attach(AuthEvent::onAfterAuthentication, [$this, 'onAfterAuthentication'], -1);

        // TODO

        // Attach default listener to resolve authorization resources
        //$events->attach(
        //    AuthEvent::onAuthorization,
        //    $this->services->get('iMSCP\Auth\Authorization\ResourceResolverListener'),
        //    1000
        //);

        // Attach listener for authorization tasks
        //$events->attach(AuthEvent::onAuthorization, $this->services->get(
        //    'iMSCP\Auth\Authorization\AuthorizationListener'
        //));

        // Attach default listener for post authorization tasks
        //$events->attach(AuthEvent::onAfterAuthentication, $this->services->get(
        //    'iMSCP\Auth\Authorization\AuthorizationPostListener'
        //));

        //$events->attach(AuthEvent::onAfterAuthentication, [$this, 'onAuthenticationPost'], -1);
    }

    /**
     * @listen AuthenticationEvent::onAfterAuthentication
     * @param AuthEvent $e
     * @return void
     */
    public function onAfterAuthentication(AuthEvent $e)
    {
        if ($this->serviceManager->has('Identity')) {
            return;
        }

        $this->serviceManager->setService('Identity', $e->getIdentity());
    }
}
