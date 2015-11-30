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

namespace iMSCP\Core\Service;

use Zend\ModuleManager\ModuleManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class FrontendConfigFactory
 * @package iMSCP\Core\Service
 */
class FrontendConfigFactory implements FactoryInterface
{
	/**
	 * Create the frontend configuration service
	 *
	 * Retrieves the Module Manager from the service locator, and executes
	 * {@link Zend\ModuleManager\ModuleManager::loadModules()}.
	 *
	 * It then retrieves the config listener from the module manager, and from that the merged configuration.
	 *
	 * @param ServiceLocatorInterface $serviceLocator
	 * @return array|\Traversable
	 */
	public function createService(ServiceLocatorInterface $serviceLocator)
	{
		/** @var ModuleManager $moduleManager */
		$moduleManager = $serviceLocator->get('ModuleManager');
		$moduleManager->loadModules();
		$moduleParams = $moduleManager->getEvent()->getParams();
		$frontendConfig = $moduleParams['configListener']->getMergedConfig(false);
		return $frontendConfig;
	}
}
