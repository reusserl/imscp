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

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class Package
 * @package iMSCP\ApsStandard\Entity
 */
class Package implements EntityHydrator, EntityValidation
{
	/**
	 * @var int Package unique identifier
	 */
	public $id;

	/**
	 * @var string Package name
	 */
	public $name;

	/**
	 * @var string Package summary
	 */
	public $summary;

	/**
	 * @var string Package version
	 */
	public $version;

	/**
	 * @var int Package release number
	 */
	public $release;

	/**
	 * @var string Package APS version
	 */
	public $aps_version;

	/**
	 * @var string Package category
	 */
	public $category;

	/**
	 * @var string Package vendor
	 */
	public $vendor;

	/**
	 * @var string Package vendor URI
	 */
	public $vendor_uri;

	/**
	 * @var string Package path
	 */
	public $path;

	/**
	 * @var string Package URL
	 */
	public $url;

	/**
	 * @var string Package icon URL
	 */
	public $icon_url;

	/**
	 * @var string Package certification
	 */
	public $cert;

	/**
	 * @var string Package status
	 */
	public $status;

	/**
	 * Constructor
	 * @param array $data Optional
	 */
	public function __construct(array $data = array())
	{
		if (!empty($data)) {
			$this->hydrate($data);
		}
	}

	/**
	 * Get validation metadata
	 *
	 * @param ClassMetadata $metadata
	 */
	public static function loadValidationMetadata(ClassMetadata $metadata)
	{
		// Right now, only the status filed is mutable. Thus, we process validation only for that field
		$metadata->addPropertyConstraints('status', array(
			new NotBlank(),
			new Choice(array('choices' => array('ok', 'disabled')))
		));
	}

	/**
	 * Hydrate this object with the provided data
	 *
	 * @param  array $data
	 * @return self
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

		foreach ($reflect->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
			$propName = $prop->getName();
			$data[$propName] = $this->{$propName};
		}

		return $data;
	}
}
