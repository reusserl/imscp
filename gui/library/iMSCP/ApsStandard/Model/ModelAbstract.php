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

namespace iMSCP\ApsStandard\Model;

use iMSCP\ApsStandard\Hydrator;
use iMSCP\ApsStandard\Validation;

/**
 * Class ModelAbstract
 * @package iMSCP\ApsStandard\Model
 */
abstract class ModelAbstract implements Hydrator, Validation
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
	 * {@inheritDoc}
	 */
	public function hydrate(array $values)
	{
		// Hydrate using class methods (setters)
		foreach ($values as $property => $value) {
			$setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));
			if (is_callable(array($this, $setter))) {
				$this->{$setter}($value);
			}
		}

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function extract()
	{
		// Extract using class methods (getters)
		$reflect = new \ReflectionClass($this);

		$values = array();
		foreach ($reflect->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
			$attribute = $method->getName();

			if (strlen($attribute) < 3 || !in_array(substr($attribute, 0, 3), array('get', 'has', 'is'))) {
				continue;
			}

			if (strpos($attribute, 'get') === 0) {
				$attribute = substr($attribute, 3);
				if (!property_exists($this, $attribute)) {
					$attribute = lcfirst($attribute);
				}
			}

			// CamelCase to underscore
			$attribute = strtolower(preg_replace(
				array('#(?<=(?:\p{Lu}))(\p{Lu}\p{Ll})#', '#(?<=(?:\p{Ll}|\p{Nd}))(\p{Lu})#'),
				array('_' . '\1', '_' . '\1'),
				$attribute
			));

			$values[$attribute] = $method->invoke($this);
		}

		return $values;
	}
}
