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
	 * Get settings definition for the given package
	 *
	 * @param ApsPackage $package Package from which instance settings are retrieved
	 * @return array
	 */
	public function getSettingsFromMetadataFile(ApsPackage $package)
	{
		$this->getEventManager()->dispatch('onGetApsInstanceSettingsFromMetadataFile', array(
			'package' => $package, 'context' => $this
		));

		$meta = $this->getPackageMetadataDir() . '/' . $package->getApsVersion() . '/' . $package->getName() . '/APP-META.xml';
		if (!file_exists($meta) || filesize($meta) == 0) {
			throw new \RuntimeException(tr('The %s package META file is missing or invalid.', $meta));
		}

		$doc = new ApsDocument($meta);

		$choices = $this->getDomainList();
		$groupName = tr('Installation target');
		$settings = array(
			'__base_url_host__' => array(
				'name' => '__base_url_host__',
				'value' => $choices[0]['value'],
				'metadata' => array(
					'group' => $groupName,
					'label' => tr('Target domain'),
					'tooltip' => tr('Domain under which the application must be installed.'),
					'choices' => $choices,
					'type' => 'enum',
					'required' => true
				)
			),
			'__base_url_path__' => array(
				'name' => '__base_url_path__',
				'value' => '/',
				'metadata' => array(
					'group' => $groupName,
					'label' => tr('Target folder'),
					'tooltip' => tr('An optional subfolder in which the application must be installed.'),
					'type' => 'string',
					'regexp' => '^\\/[\x21-\x7e\\/]*$',
					'maxlength' => 255,
					'required' => true
				)
			)
		);

		// Database settings (if required)
		if ($doc->getXPathValue('//db:id')) {
			$groupName = tr('Database');
			$settings['__db_name__'] = array(
				'name' => '__db_name__',
				'value' => '',
				'metadata' => array(
					'group' => $groupName,
					'label' => tr('Database name'),
					'tooltip' => tr('The database must exist.'),
					'type' => 'string',
					'minlength' => 1,
					'required' => true
				)
			);

			if ($doc->getXPathValue('//db:can-use-tables-prefix/text()') == 'true') {
				$groupName = tr('Database');
				$settings['__db_table_prefix__'] = array(
					'name' => '__db_table_prefix__',
					'value' => '',
					'metadata' => array(
						'group' => $groupName,
						'label' => tr('Database table prefix'),
						'tooltip' => tr('Optional prefix for database tables. Needed for databases which already contain tables for another application.'),
						'type' => 'string',
						'minlength' => 1,
						'maxlength' => 5,
						'regexp' => '[0-9-a-z_]*',
						'required' => false
					)
				);
			}

			$settings['__db_user__'] = array(
				'name' => '__db_user__',
				'value' => '',
				'metadata' => array(
					'group' => $groupName,
					'label' => tr('Database user'),
					'tooltip' => tr('The user must exist and must have privileges on the database.'),
					'type' => 'string',
					'minlength' => 1,
					'required' => true
				)
			);

			$settings['__db_pwd__'] = array(
				'name' => '__db_pwd__',
				'value' => '',
				'metadata' => array(
					'group' => $groupName,
					'label' => tr('Database password'),
					'type' => 'password',
					'minlength' => 1,
					'required' => true
				)
			);
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
				$setting = array(
					'name' => $settingName,
					'metadata' => array(
						'group' => $settingGroup,
						'label' => ucfirst(str_replace('_', ' ',
							$doc->getXPathValue("root:name[@xml:lang='$intLang']/text()", $item)
								?: $doc->getXPathValue("root:name/text()", $item)
						)),
						'tooltip' => $doc->getXPathValue("root:description[@xml:lang='$intLang']/text()", $item)
							?: $doc->getXPathValue("root:description/text()", $item),
						'type' => $settingType,
						'required' => ($doc->getXPathValue("@optional", $item) !== 'true') ? true : false,
						'hidden' => ($doc->getXPathValue("@visibility", $item) == 'hidden') ? true : false,
						'protected' => ($doc->getXPathValue("@protected", $item) == 'true') ? true : false
					)
				);

				switch ($settingType) {
					case 'boolean':
						$setting['value'] = (bool)$doc->getXPathValue('@default-value', $item) ?: false;
						break;
					case 'string':
					case 'password':
						$setting['value'] = $doc->getXPathValue('@default-value', $item);
						$setting['metadata']['minlength'] = (int)$doc->getXPathValue('@min-length', $item);
						$setting['metadata']['maxlength'] = (int)$doc->getXPathValue('@max-length', $item);
						$setting['metadata']['regexp'] = $doc->getXPathValue('@regex', $item);
						$setting['metadata']['charset'] = $doc->getXPathValue('@charset', $item);
						break;
					case 'integer':
						$setting['value'] = (int)$doc->getXPathValue('@default-value', $item) ?: 0;
						$setting['metadata']['min'] = $doc->getXPathValue('@min', $item) ?: 0;
						$setting['metadata']['max'] = $doc->getXPathValue('@max', $item);
						break;
					case 'float':
						$setting['value'] = (float)$doc->getXPathValue('@default-value', $item) ?: 0.0;
						$setting['metadata']['min'] = $doc->getXPathValue('@min', $item) ?: 0.0;
						$setting['metadata']['max'] = $doc->getXPathValue('@max', $item);
						$setting['metadata']['step'] = 0.1;
						break;
					case 'date':
					case 'time':
						$setting['value'] = $doc->getXPathValue('@default-value', $item);
						$setting['metadata']['min'] = $doc->getXPathValue('@min', $item) ?: 0;
						$setting['metadata']['max'] = $doc->getXPathValue('@max', $item);
						break;
					case 'email':
					case 'domain-name':
					case 'host-name':
						$setting['value'] = $doc->getXPathValue('@default-value', $item);
						break;
					case 'enum':
						$choices = array();
						foreach ($doc->getXPathValue('root:choice', $item, false) as $choice) {
							$choices[] = array(
								'name' => $doc->getXPathValue('root:name', $choice),
								'value' => $doc->getXPathValue('@id', $choice)
							);
						}
						$setting['value'] = $doc->getXPathValue('@default-value', $item) ?: $choices[0]['value'];
						$setting['metadata']['choices'] = $choices;
						break;
					case 'list':
						//$settingValue = '';
						break;
					default:
						throw new \DomainException(sprintf("Unknown APS '%s' setting type.", $settingType));
				}

				$settings[$settingName] = $setting;
			}
		}

		// License aggrement (TODO > APS 1.2)
		if (floatval($package->getApsVersion()) < 1.2 && $doc->getXPathValue("//root:license/@must-accept") == 'true') {
			$settings['license_agreement'] = array(
				'name' => 'license_agreement',
				'value' => false,
				'metadata' => array(
					'group' => tr('License agreement'),
					'label' => tr("Do you agree with the software's license?"),
					'aps_type' => 'boolean',
					'type' => 'boolean',
					'tooltip' => tr('See package details for the license.')
				)
			);
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

		$settingObjects = array();
		foreach ($settings as $setting) {
			/** @var ApsInstanceSetting $settingObject */
			$settingObject = $serializer->fromArray($setting, self::APS_INSTANCE_SETTING_ENTITY_CLASS);
			$settingName = $settingObject->getName();

			// Ignore unknown settings
			if (in_array($settingName, $expectedSettings)) {
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
			$mainDmnProps = get_domain_default_props($this->getAuth()->getIdentity()->admin_id);
			$domainsList = array(array(
				'name' => $mainDmnProps['domain_name'],
				'value' => $mainDmnProps['domain_id']
			));

			$stmt = $this->entityManager->getConnection()->prepare(
				"
				SELECT
					CONCAT(`t1`.`subdomain_name`, '.', `t2`.`domain_name`) AS `name`,
					CONCAT(`t1`.`subdomain_id`, '_sub') AS `value`
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
					`alias_name`,
					CONCAT(`alias_id`, '_als')
				FROM
					`domain_aliasses`
				WHERE
					`domain_id` = :domain_id
				AND
					`alias_status` = :status
				UNION
				SELECT
					CONCAT(`t1`.`subdomain_alias_name`, '.', `t2`.`alias_name`),
					CONCAT(`t1`.`subdomain_alias_id`, '_alssub')
				FROM
					`subdomain_alias` AS `t1`
				INNER JOIN
					`domain_aliasses` AS `t2` USING(`alias_id`)
				WHERE
					`t2`.`domain_id` = :domain_id
				AND
					`subdomain_alias_status` = :status
		");

			$stmt->execute(array('domain_id' => $mainDmnProps['domain_id'], 'status' => 'ok'));
			if ($stmt->rowCount()) {
				$domainsList = array_merge($domainsList, $stmt->fetchAll(\PDO::FETCH_ASSOC));
				usort($domainsList, function ($a, $b) {
					return strnatcmp(decode_idna($a['name']), decode_idna($b['name']));
				});
			}

			$this->domainList = $domainsList;
		}

		return $this->domainList;
	}
}
