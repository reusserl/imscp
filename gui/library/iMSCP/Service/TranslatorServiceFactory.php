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

use iMSCP_Registry as Registry;
use Zend_Cache as CacheHandler;
use Zend_Locale as Locale;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend_Translate as Translator;

/**
 * Class SerializerServiceFactory
 * @package iMSCP\Service
 */
class TranslatorServiceFactory implements FactoryInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function createService(ServiceLocatorInterface $serviceLocator)
	{
		$config = Registry::get('config');
		$trFilePathPattern = $config['GUI_ROOT_DIR'] . '/i18n/locales/%s/LC_MESSAGES/%s.mo';

		if (PHP_SAPI != 'cli') {
			$lang = Registry::set('user_def_lang', isset($_SESSION['user_def_lang'])
				? $_SESSION['user_def_lang']
				: (isset($config['USER_INITIAL_LANG']) ? $config['USER_INITIAL_LANG'] : 'auto')
			);

			if (Locale::isLocale($lang)) {
				$locale = new Locale($lang);

				if ($lang == 'auto') {
					$locale->setLocale('en_GB');
					$browser = $locale->getBrowser();

					arsort($browser);
					foreach ($browser as $language => $quality) {
						if (file_exists(sprintf($trFilePathPattern, $language, $language))) {
							$locale->setLocale($language);
							break;
						}
					}
				} elseif (!file_exists(sprintf($trFilePathPattern, $locale, $locale))) {
					$locale->setLocale('en_GB');
				}
			} else {
				$locale = new Locale('en_GB');
			}
		} else {
			$locale = new Locale('en_GB');
		}

		// Setup cache object for translations
		$cache = CacheHandler::factory(
			'Core',
			'File',
			array(
				'caching' => true,
				'lifetime' => null, // Translation cache is never flushed automatically
				'automatic_serialization' => true,
				'automatic_cleaning_factor' => 0,
				'ignore_user_abort' => true,
				'cache_id_prefix' => 'iMSCP_Translate'
			),
			array(
				'hashed_directory_level' => 0,
				'cache_dir' => CACHE_PATH . '/translations'
			)
		);

		if ($config['DEBUG']) {
			$cache->clean(CacheHandler::CLEANING_MODE_ALL);
		} else {
			Translator::setCache($cache);
		}

		// Setup primary translator for iMSCP core translations
		return new Translator(array(
			'adapter' => 'gettext',
			'content' => sprintf($trFilePathPattern, $locale, $locale),
			'locale' => $locale,
			'disableNotices' => true,
			'tag' => 'iMSCP'
		));
	}
}
