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

namespace iMSCP\Core\Doctrine\Serializer\Construction;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\VisitorInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\DeserializationContext;

/**
 * Class DoctrineObjectConstructors
 *
 * Doctrine object constructor for new (or existing) objects during deserialization.
 *
 * Note: Used in place of JMS DoctrineObjectConstructors as long the bug described by
 * https://github.com/schmittjoh/serializer/pull/299 is not fixed.
 *
 * @package iMSCP\Core\Doctrine\Serializer\Construction
 */
class DoctrineObjectConstructor implements ObjectConstructorInterface
{
	/**
	 * @var ManagerRegistry
	 */
	private $managerRegistry;

	/**
	 * @var ObjectConstructorInterface
	 */
	private $fallbackConstructor;

	/**
	 * Constructor
	 *
	 * @param ManagerRegistry $managerRegistry Manager registry
	 * @param ObjectConstructorInterface $fallbackConstructor Fallback object constructor
	 */
	public function __construct(ManagerRegistry $managerRegistry, ObjectConstructorInterface $fallbackConstructor)
	{
		$this->managerRegistry = $managerRegistry;
		$this->fallbackConstructor = $fallbackConstructor;
	}

	/**
	 * {@inheritdoc}
	 */
	public function construct(VisitorInterface $visitor, ClassMetadata $metadata, $data, array $type, DeserializationContext $context)
	{
		// Locate possible ObjectManager
		/** @var EntityManagerInterface $objectManager */
		$objectManager = $this->managerRegistry->getManagerForClass($metadata->name);

		if (!$objectManager) {
			// No ObjectManager found, proceed with normal deserialization
			return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
		}

		// Locate possible ClassMetadata
		$classMetadataFactory = $objectManager->getMetadataFactory();

		if ($classMetadataFactory->isTransient($metadata->name)) {
			// No ClassMetadata found, proceed with normal deserialization
			return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
		}

		// Managed entity, check for proxy load
		if (!is_array($data)) {
			// Single identifier, load proxy
			return $objectManager->getReference($metadata->name, $data);
		}

		// Fallback to default constructor if missing identifier(s)
		$classMetadata = $objectManager->getClassMetadata($metadata->name);
		$identifierList = array();

		foreach ($classMetadata->getIdentifierFieldNames() as $name) {
			if (!array_key_exists($name, $data)) {
				return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
			}

			$identifierList[$name] = $data[$name];
		}

		// Entity update, load it from database
		$object = $objectManager->find($metadata->name, $identifierList);

		if(!is_object($object)) {
			throw EntityNotFoundException::fromClassNameAndIdentifier($metadata->name, $identifierList);
			//return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
		}

		$objectManager->initializeObject($object);

		return $object;
	}
}
