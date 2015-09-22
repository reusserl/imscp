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
use iMSCP\ApsStandard\Entity\Package as PackageEntity;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Events as Events;
use iMSCP_pTemplate as TemplateEngine;

// Include core library
require 'imscp-lib.php';

$eventManager = EventManager::getInstance();
$eventManager->dispatch(Events::onAdminScriptStart);
check_login('admin');

if (is_xhr()) { // Dispatches the XHR request based on HTTP verbs
	$controller = new PackageController($eventManager);

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET': // Return packages list
			$controller->indexAction();
			break;
		case 'PUT': // Change (lock/unlock) package status
			$payload = json_decode(file_get_contents('php://input'), JSON_OBJECT_AS_ARRAY);
			if (is_array($payload)) {
				$controller->changeStatus(new PackageEntity($payload));
			}
			break;
		case 'POST':
			// Trigger package index update
			$controller->updateIndexAction();
	}

	$controller->sendJsonResponse(400);
}

$tpl = new TemplateEngine();
$tpl->define_dynamic(array(
	'layout' => 'shared/layouts/ui.tpl',
	'page' => 'shared/partials/aps_standard/aps_packages.tpl',
	'page_message' => 'layout',
	'aps_package_entry' => 'page'
));

$tpl->assign(array(
	'TR_PAGE_TITLE' => tohtml(tr('Admin / APS Standard / Packages'), 'htmlAttr'),
	'TR_DETAILS' => tohtml(tr('Details')),
	'TR_CATEGORY' => tohtml(tr('Category')),
	'TR_VENDOR' => tohtml(tr('Vendor')),
	'TR_CERTIFIED' => tohtml(tr('Certified')),
	'TR_LOCK' => tohtml(tr('Lock'), 'htmlAttr'),
	'TR_UNLOCK' => tohtml(tr('Unlock'), 'htmlAttr'),
	'TR_UPDATE_PACKAGE_INDEX' => tohtml(tr('Update package index'))
));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
$eventManager->dispatch(Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
