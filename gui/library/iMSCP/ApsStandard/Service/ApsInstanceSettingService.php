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
use iMSCP_Registry as Registry;
use JMS\Serializer\Serializer;

/**
 * Class ApsInstanceSettingService
 * @package iMSCP\ApsStandard\Service
 */
class ApsInstanceSettingService extends ApsAbstractService
{
	const INSTANCE_SETTING_ENTITY_CLASS = 'iMSCP\\ApsStandard\\Entity\\InstanceSetting';

	/**
	 * Get settings definition for the given package
	 *
	 * @param ApsPackage $package Package from which instance settings
	 * @return array
	 */
	public function getSettingsFromMetadataFile(ApsPackage $package)
	{
		$this->getEventManager()->dispatch('onGetApsInstanceSettingsFromMetadataFile', array(
			'package' => $package, 'context' => $this
		));

		$meta = $this->getMetadataDir() . '/' . $package->getApsVersion() . '/' . $package->getName() . '/APP-META.xml';
		if (!file_exists($meta) || filesize($meta) == 0) {
			throw new \RuntimeException(tr('The %s package META file is missing or invalid.', $meta));
		}

		$doc = new ApsDocument($meta);

		$choices = $this->getDomainList();
		$groupName = tr('Installation target');
		$settings = array(
			array(
				'name' => 'base_url_host',
				'value' => strval($choices[0]['value']),
				'metadata' => array(
					'group' => $groupName,
					'label' => tr('Target domain'),
					'tooltip' => tr('Domain under which the application must be installed.'),
					'choices' => $choices,
					'type' => 'enum'
				)
			),
			array(
				'name' => 'base_url_path',
				'value' => '/',
				'metadata' => array(
					'group' => $groupName,
					'label' => tr('Target folder'),
					'tooltip' => tr('An optional subfolder in which the application must be installed.'),
					'type' => 'text',
					'regexp' => '^[\x21-\x7e\\/]+$',
					'max_length' => 255
				)
			)
		);

		// Database settings (if required)
		if ($doc->getXPathValue('//db:id')) {
			$config = Registry::get('config');
			$groupName = tr('Database');
			$settings[] = array(
				'metadata' => array(
					'group' => $groupName,
					'label' => tr('Password'),
					'regexp' => '^[\x21-\x7e]+$',
					'type' => 'password',
					'min_length' => 6,
					'max_length' => $config['PASSWD_CHARS']
				),
				'name' => 'db_pwd'
			);
		}

		$intLang = str_replace('_', '-', $_SESSION['user_def_lang']);

		// Retrieve all setting groups from the package metadata file
		foreach ($doc->getXPathValue('//root:settings/root:group', null, false) as $group) {
			// Group name
			$groupName = $doc->getXPathValue("root:name[@xml:lang='$intLang']/text()", $group) ?:
				$doc->getXPathValue("root:name/text()", $group) ?: tr('Other settings');

			// Retrieve all settings in current setting group (or subgroup)
			foreach ($doc->getXPathValue('(root:group/root:setting|root:setting)', $group, false) as $item) {
				$type = $doc->getXPathValue('@type', $item);
				$setting = array();
				$setting['name'] = $doc->getXPathValue('@id', $item);
				$setting['metadata']['group'] = $groupName;
				$setting['metadata']['label'] = ucfirst(str_replace(
					'_', ' ', $doc->getXPathValue("root:name[@xml:lang='$intLang']/text()", $item) ?:
					$doc->getXPathValue("root:name/text()", $item)
				));
				$setting['metadata']['aps_type'] = $type;

				if (in_array($type, array('domain-name', 'email', 'float', 'integer', 'string', 'password'))) {
					$setting['value'] = strval($doc->getXPathValue('@default-value', $item));
					$setting['metadata']['type'] = 'text';
					$setting['metadata']['regexp'] = $doc->getXPathValue('@regex', $item);
					$setting['metadata']['min_length'] = $doc->getXPathValue('@min-length', $item);
					$setting['metadata']['max_length'] = $doc->getXPathValue('@max-length', $item);
				} elseif ('type' == 'email') {
					$setting['type'] = 'email';
				} elseif ($type == 'password') {
					$setting['metadata']['type'] = 'password';
					$setting['metadata']['regexp'] = $doc->getXPathValue('@regex', $item);
					$setting['metadata']['min_length'] = $doc->getXPathValue('@min-length', $item);
					$setting['metadata']['max_length'] = $doc->getXPathValue('@max-length', $item);
				} elseif ($type == 'enum') {
					$setting['value'] = strval($doc->getXPathValue('@default-value', $item) ?: $choices[0]['value']);
					$choices = array();
					foreach ($doc->getXPathValue('root:choice', $item, false) as $choice) {
						$choices[] = array(
							'name' => $doc->getXPathValue('root:name', $choice),
							'value' => strval($doc->getXPathValue('@id', $choice))
						);
					}
					$setting['metadata']['type'] = 'enum';
					$setting['metadata']['choices'] = $choices;
				} elseif ($type == 'boolean') {
					$setting['value'] = strval($doc->getXPathValue('@default-value', $item));
					$setting['metadata']['type'] = 'boolean';
				} else {
					throw new \DomainException(sprintf("Unknown APS '%s' setting type.", $type));
				}

				$settings[] = $setting;
			}
		}

		return $settings;
	}

	/**
	 * Get settings from the given JSON payload
	 *
	 * @throws \Exception
	 * @param ApsPackage $package Package from which instance setting must be retrieved
	 * @param string $payload JSON payload
	 * @return ApsInstanceSetting[]
	 */
	public function getSettingsFromPayload($package, $payload)
	{
		$settings = $this->getSettingsFromMetadataFile($package);
		$expectedSettings = array_map(function ($setting) {
			return $setting['name'];
		}, $settings);

		/** @var Serializer $serializer */
		$serializer = $this->getServiceLocator()->get('Serializer');

		/** @var ApsInstanceSetting[] $settings */
		$inputSettings = $serializer->deserialize($payload, self::INSTANCE_SETTING_ENTITY_CLASS, 'json');

		/** @var ApsInstanceSetting $setting */
		foreach ($inputSettings as $setting) {
			if (!in_array($setting->getName(), $expectedSettings)) {
				throw new \Exception('Invalid payload.', 400);
			}

			$setting->setMetadata($setting['metadata']);
		}

		if (count($inputSettings) != count($expectedSettings)) {
			throw new \Exception('Invalid payload.', 400);
		}

		return $inputSettings;
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
		$mainDmnProps = get_domain_default_props($this->getAuth()->getIdentity()->admin_id);
		$domainsList = array(array(
			'name' => $mainDmnProps['domain_name'],
			'value' => $mainDmnProps['domain_id'] . '_' . 'dmn'
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

		return $domainsList;
	}
}
