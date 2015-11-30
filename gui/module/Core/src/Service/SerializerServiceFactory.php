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

namespace iMSCP\Service;

use iMSCP\Core\Doctrine\Persistence\ManagerRegistry;
use iMSCP\Core\Doctrine\Serializer\Construction\DoctrineObjectConstructor;
use JMS\Serializer\Construction\UnserializeObjectConstructor;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Naming\CamelCaseNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializerBuilder;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class SerializerServiceFactory
 * @package iMSCP\Service
 */
class SerializerServiceFactory implements FactoryInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function createService(ServiceLocatorInterface $serviceLocator)
	{
		/** @var ManagerRegistry $managerRegistry */
		$managerRegistry = $serviceLocator->get('ManagerRegistry');
		$objectConstructor = new DoctrineObjectConstructor($managerRegistry, new UnserializeObjectConstructor());

		$systemConfig = $serviceLocator->get('SystemConfig');

		$serializer = SerializerBuilder::create()
			->setObjectConstructor($objectConstructor)
			->setCacheDir(CACHE_PATH . '/serializer')
			->setDebug($systemConfig['DEVMODE']);

		if ($systemConfig['DEVMODE']) {
			$jsonSerializerVisitor = new JsonSerializationVisitor(
				new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy())
			);
			$jsonSerializerVisitor->setOptions(JSON_PRETTY_PRINT);
			$jsonDeserializerVisitor = new JsonDeserializationVisitor(
				new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy())
			);
			$serializer->setSerializationVisitor('json', $jsonSerializerVisitor);
			$serializer->setDeserializationVisitor('json', $jsonDeserializerVisitor);
		}

		return $serializer->build();
	}
}
