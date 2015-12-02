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

namespace iMSCP\DoctrineIntegration;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\DependencyIndicatorInterface;
use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\Stdlib\ArrayUtils;

/**
 * Class Module
 * @package iMSCP\Core
 */
class Module implements InitProviderInterface, ConfigProviderInterface, DependencyIndicatorInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function init(ModuleManagerInterface $manager)
	{
		// Registers an autoloading callable for annotations
		AnnotationRegistry::registerLoader(function ($className) {
			return class_exists($className);
		});
	}

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
	public function getModuleDependencies()
	{
		return ['iMSCP\Core'];
	}
}
