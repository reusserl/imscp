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

namespace iMSCP\ApsStandard\Entity;

use iMSCP\ApsStandard\Hydrator;
use iMSCP\ApsStandard\Validation;


/**
 * Class Package
 * @package iMSCP\ApsStandard\Entity
 */
abstract class EntityAbstract implements Hydrator, Validation
{
	/**
	 * @var int Package unique identifier
	 */
	protected $id;

	/**
	 * Get identity
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set identity
	 *
	 * @param int $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Hydrate this object with the provided data
	 *
	 * @param  array $data
	 * @return EntityAbstract
	 */
	public function hydrate(array $data)
	{
		$reflect = new \ReflectionClass($this);
		foreach ($data as $property => $value) {
			if ($reflect->hasProperty($property)) {
				$this->{$property} = $value;
			}
		}

		return $this;
	}

	/**
	 * Extract values from this object
	 *
	 * @return array
	 */
	public function extract()
	{
		$reflect = new \ReflectionClass($this);
		$data = array();
		foreach ($reflect->getProperties(\ReflectionProperty::IS_PROTECTED) as $prop) {
			$propName = $prop->getName();
			$data[$propName] = $this->{$propName};
		}

		return $data;
	}
}
