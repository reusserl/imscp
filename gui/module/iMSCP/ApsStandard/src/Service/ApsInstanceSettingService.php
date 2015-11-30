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

namespace iMSCP\ApsStandard\Service;

use iMSCP\ApsStandard\ApsDocument;
use iMSCP\ApsStandard\Entity\ApsInstanceSetting;
use iMSCP\ApsStandard\Entity\ApsPackage;
use JMS\Serializer\Serializer;

/**
 * Class ApsInstanceSettingService
 * @package iMSCP\ApsStandard\Service
 */
class ApsInstanceSettingService extends ApsAbstractService
{
	const APS_INSTANCE_SETTING_ENTITY_CLASS = 'iMSCP\\ApsStandard\\Entity\\ApsInstanceSetting';

	/**
	 * @var array Domain list
	 */
	protected $domainList;

	/**
	 * Returns settings from the metadata file that belongs to the given package
	 *
	 * @param ApsPackage $package Package from which instance settings are retrieved
	 * @return ApsInstanceSetting[]
	 */
	public function getSettingsFromMetadataFile(ApsPackage $package)
	{
		$meta = $this->getPackageMetadataDir() . '/' . $package->getApsVersion() . '/' . $package->getName() . '/APP-META.xml';
		if (!file_exists($meta) || filesize($meta) == 0) {
			throw new \RuntimeException(tr('The %s package META file is missing or invalid.', $meta));
		}

		$settings = [];
		$doc = new ApsDocument($meta);

		$groupName = tr('Installation target');
		$choices = $this->getDomainList();
		$setting = (new ApsInstanceSetting())
			->setGroup($groupName)
			->setLabel(tr('Target domain'))
			->setDescription(tr('Domain under which the application must be installed.'))
			->setName('__base_url_host__')
			->setValue(current($choices))
			->setMetadata([
				'choices' => $choices,
				'type' => 'enum',
				'required' => true
			]);
		$settings['__base_url_host__'] = $setting;

		$setting = (new ApsInstanceSetting())
			->setGroup($groupName)
			->setLabel(tr('Target folder'))
			->setDescription(tr('An optional subfolder in which the application must be installed.'))
			->setName('__base_url_path__')
			->setValue('/')
			->setMetadata([
				'type' => 'string',
				'regexp' => '^\\/[\x21-\x7e\\/]*$',
				'maxlength' => 255,
				'required' => true
			]);
		$settings['__base_url_path__'] = $setting;

		// Add database settings (if required)
		if ($doc->getXPathValue('//db:id')) {
			$settings += $this->getDatabaseSettings($doc);
		}

		// Get user locale
		$intLang = str_replace('_', '-', $this->getServiceLocator()->get('translator')->getLocale());

		// Retrieve all setting group
		foreach ($doc->getXPathValue('//root:settings/root:group', null, false) as $group) {
			$settingGroup = $doc->getXPathValue("root:name[@xml:lang='$intLang']/text()", $group) ?:
				$doc->getXPathValue("root:name/text()", $group) ?: tr('Other settings');

			// Retrieve all settings in current setting group (or subgroup)
			// Based on APS 1.2. specifications
			foreach ($doc->getXPathValue('(root:group/root:setting|root:setting)', $group, false) as $item) {
				$settingName = $doc->getXPathValue('@id', $item);
				$settingType = $doc->getXPathValue('@type', $item);

				$setting = (new ApsInstanceSetting())
					->setGroup($settingGroup)
					->setLabel(
						$doc->getXPathValue("root:name[@xml:lang='$intLang']/text()", $item)
							?: $doc->getXPathValue("root:name/text()", $item)
					)->setDescription(
						$doc->getXPathValue("root:description[@xml:lang='$intLang']/text()", $item)
							?: $doc->getXPathValue("root:description/text()", $item)
					)->setName($settingName);

				$metadata = [
					'type' => $settingType,
					'required' => ($doc->getXPathValue("@optional", $item) !== 'true') ? true : false,
					//'hidden' => ($doc->getXPathValue("@visibility", $item) == 'hidden') ? true : false,
					//'protected' => ($doc->getXPathValue("@protected", $item) == 'true') ? true : false
				];

				switch ($settingType) {
					case 'boolean':
						$setting->setValue((bool)$doc->getXPathValue('@default-value', $item) ?: false);
						break;
					case 'string':
					case 'password':
						$setting->setValue($doc->getXPathValue('@default-value', $item));
						$metadata['minlength'] = $doc->getXPathValue('@min-length', $item);
						$metadata['maxlength'] = $doc->getXPathValue('@max-length', $item);
						$metadata['regexp'] = $doc->getXPathValue('@regex', $item);
						$metadata['charset'] = $doc->getXPathValue('@charset', $item);
						break;
					case 'integer':
						$setting->setValue((int)$doc->getXPathValue('@default-value', $item) ?: 0);
						$metadata['min'] = $doc->getXPathValue('@min', $item) ?: 0;
						$metadata['max'] = $doc->getXPathValue('@max', $item);
						break;
					case 'float':
						$setting->setValue((float)$doc->getXPathValue('@default-value', $item) ?: 0.0);
						$metadata['min'] = $doc->getXPathValue('@min', $item) ?: 0.0;
						$metadata['max'] = $doc->getXPathValue('@max', $item);
						$metadata['step'] = 0.1;
						break;
					case 'date':
					case 'time':
						$setting->setValue($doc->getXPathValue('@default-value', $item));
						$metadata['min'] = $doc->getXPathValue('@min', $item) ?: 0;
						$metadata['max'] = $doc->getXPathValue('@max', $item);
						break;
					case 'email':
					case 'domain-name':
					case 'host-name':
						$setting->setValue($doc->getXPathValue('@default-value', $item));
						break;
					case 'enum':
						$choices = [];
						foreach ($doc->getXPathValue('root:choice', $item, false) as $choice) {
							$choices[$doc->getXPathValue('root:name', $choice)] = $doc->getXPathValue('@id', $choice);
						}
						$setting->setValue($doc->getXPathValue('@default-value', $item) ?: current($choices));
						$metadata['choices'] = $choices;
						break;
					default:
						throw new \DomainException(sprintf("Unknown or not supported APS '%s' setting type.", $settingType));
				}

				$setting->setMetadata($metadata);
				$settings[$settingName] = $setting;
			}
		}

		// License aggrement (TODO > APS 1.2)
		if (floatval($package->getApsVersion()) < 1.2 && $doc->getXPathValue("//root:license/@must-accept") == 'true') {
			$setting = (new ApsInstanceSetting())
				->setGroup(tr('License agreement'))
				->setLabel(tr("Do you agree with the software's license?"))
				->setDescription(tr('See package details for the license.'))
				->setName('license_agreement')
				->setValue(true)
				->setMetadata(['type' => 'boolean']);
			$settings['license_agreement'] = $setting;
		}

		return $settings;
	}

	/**
	 * Get settings objects from the given array
	 *
	 * @param ApsPackage $package Package for which instance settings must be retrieved
	 * @param array $settings payload
	 * @return ApsInstanceSetting[]
	 */
	public function getSettingObjectsFromArray(ApsPackage $package, array $settings)
	{
		$settingsFromMetadataFile = $this->getSettingsFromMetadataFile($package);
		$expectedSettings = array_keys($settingsFromMetadataFile);

		/** @var Serializer $serializer */
		$serializer = $this->getServiceLocator()->get('Serializer');

		$settingObjects = [];
		foreach ($settings as $setting) {
			/** @var ApsInstanceSetting $settingObject */
			$settingObject = $serializer->fromArray($setting, self::APS_INSTANCE_SETTING_ENTITY_CLASS);
			$settingName = $settingObject->getName();

			// Ignore unknown settings
			if (in_array($settingName, $expectedSettings)) {
				// We don't trust to metadata that are sent back because they can have been modified.
				// We rely on local metadata only.
				$settingObject->setMetadata($settingsFromMetadataFile[$settingName]['metadata']);
				$settingObjects[$settingName] = $settingObject;
			}
		}

		if (count($settingObjects) < count($expectedSettings)) {
			throw new \DomainException('Invalid payload: Missing setting(s).', 400);
		}

		unset($settingObjects['license_agreement']); // We do want store license agreement
		return $settingObjects;
	}

	/**
	 * Get domain list
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Exception
	 * @return array
	 */
	protected function getDomainList()
	{
		if (null === $this->domainList) {
			$adminId = $this->getAuth()->getIdentity()->admin_id;
			$mainDmnProps = get_domain_default_props($adminId);
			$domainsList = [$mainDmnProps['domain_name']];
			//$qcp = new QueryCacheProfile(3600, 'aps_domain_list_' . $adminId);
			//$stmt = $this->entityManager->getConnection()->executeCacheQuery(
			$stmt = $this->entityManager->getConnection()->executeQuery(
				"
					SELECT
						CONCAT(`t1`.`subdomain_name`, '.', `t2`.`domain_name`) AS `name`
					FROM
						`subdomain` AS `t1`
					INNER JOIN
						`domain` AS `t2` USING(`domain_id`)
					WHERE
						`t1`.`domain_id` = :domain_id
					AND
						`t1`.`subdomain_status` = :status
					UNION
					SELECT
						`alias_name`
					FROM
						`domain_aliasses`
					WHERE
						`domain_id` = :domain_id
					AND
						`alias_status` = :status
					UNION
					SELECT
						CONCAT(`t1`.`subdomain_alias_name`, '.', `t2`.`alias_name`)
					FROM
						`subdomain_alias` AS `t1`
					INNER JOIN
						`domain_aliasses` AS `t2` USING(`alias_id`)
					WHERE
						`t2`.`domain_id` = :domain_id
					AND
						`subdomain_alias_status` = :status
				",
				[':domain_id' => $mainDmnProps['domain_id'], ':status' => 'ok']
				//[],
				//$qcp
			);
			$domainsList = array_merge($domainsList, $stmt->fetchAll(\PDO::FETCH_COLUMN));
			//$stmt->closeCursor(); // at this point the result is cached
			$this->domainList = array_combine(array_map('decode_idna', $domainsList), $domainsList);
		}

		return $this->domainList;
	}

	/**
	 * Get database settings
	 *
	 * @param ApsDocument $doc APS document
	 * @return ApsInstanceSetting[]
	 */
	protected function getDatabaseSettings($doc)
	{
		$settings = [];
		$groupName = tr('Database');

		$setting = (new ApsInstanceSetting())
			->setGroup($groupName)
			->setLabel(tr('Database name'))
			->setDescription(tr('The database must exist.'))
			->setName('__db_name__')
			->setValue('')
			->setMetadata([
				'type' => 'string',
				'minlength' => 1,
				'required' => true
			]);
		$settings['__db_name__'] = $setting;

		if ($doc->getXPathValue('//db:can-use-tables-prefix/text()') == 'true') {
			$setting = (new ApsInstanceSetting())
				->setGroup($groupName)
				->setLabel(tr('Database table prefix'))
				->setDescription(tr('Optional prefix for database tables.'))
				->setName('__db_table_prefix__')
				->setValue('')
				->setMetadata([
					'type' => 'string',
					'minlength' => 1,
					'maxlength' => 5,
					'regexp' => '[0-9-a-z_]*',
					'required' => false
				]);
			$settings['__db_table_prefix__'] = $setting;
		}

		$setting = (new ApsInstanceSetting())
			->setGroup($groupName)
			->setLabel(tr('Database user'))
			->setDescription(tr('The user must exist and must have privileges on the database.'))
			->setName('__db_user__')
			->setValue('')
			->setMetadata([
				'type' => 'string',
				'minlength' => 1,
				'required' => true
			]);
		$settings['__db_user__'] = $setting;

		$setting = (new ApsInstanceSetting())
			->setGroup($groupName)
			->setLabel(tr('Database password'))
			->setName('__db_pwd__')
			->setValue('')
			->setMetadata([
				'type' => 'password',
				'minlength' => 1,
				'required' => true
			]);
		$settings['__db_pwd__'] = $setting;

		return $settings;
	}
}
