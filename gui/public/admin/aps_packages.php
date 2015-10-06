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

namespace iMSCP\ApsStandard;

use iMSCP\ApsStandard\Controller\Package as PackageController;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Events as Events;
use iMSCP_pTemplate as TemplateEngine;

require 'imscp-lib.php';

$eventManager = EventManager::getInstance();
$eventManager->dispatch(Events::onAdminScriptStart);
check_login('admin');

if (is_xhr()) {
	$controller = new PackageController($eventManager);
	$controller->handleRequest();
}

$tpl = new TemplateEngine();
$tpl->define_dynamic(array(
	'layout' => 'shared/layouts/ui.tpl',
	'page' => 'shared/partials/aps_standard/aps_packages.tpl',
	'page_message' => 'layout',
	'adm_btn1' => 'page',
	'adm_btn2' => 'page',
	'client_btn1' => 'page'
));

$tpl->assign(array(
	'TR_PAGE_TITLE' => tohtml(tr('Admin / APS Standard / Packages'), 'htmlAttr'),
	'TR_GLOBAL_SEARCH' => tohtml(tr('Global search', 'htmlAttr')),
	'TR_DETAILS' => tohtml(tr('Details')),
	'TR_CATEGORY' => tohtml(tr('Category')),
	'TR_VENDOR' => tohtml(tr('Vendor')),
	'TR_CERTIFIED' => tohtml(tr('Certified')),
	'TR_LOCK' => tohtml(tr('Lock'), 'htmlAttr'),
	'TR_UNLOCK' => tohtml(tr('Unlock'), 'htmlAttr'),
	'TR_TOTAL_PACKAGES' => tohtml(tr('Total packages')),
	'TR_UPDATE_PACKAGE_INDEX' => tohtml(tr('Update package index')),
	'TR_APS_VERSION' => tohtml(tr('%s version', 'APS')),
	'TR_NAME' => tohtml(tr('Name')),
	'TR_VERSION' => tohtml(tr('Version')),
	'TR_PACKAGER' => tohtml(tr('Packager')),
	'TR_DOWNLOAD' => tohtml(tr('Download')),
	'PAGE_MESSAGE' => '',
	'CLIENT_BTN1' => ''
));

$eventManager->registerListener('onGetJsTranslations', function ($e) {
	$e->getParam('translations')->core['aps_standard'] = array(
		'no_package_available' => tr('No package available. You should update package index.'),
		'update_in_progress' => tr('Update of package index is in progress. This task can take several minutes.'),
		'package_destails' => tr('Package details')
	);
});

generateNavigation($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
$eventManager->dispatch(Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
