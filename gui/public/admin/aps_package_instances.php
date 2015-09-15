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

use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Events as Events;
use iMSCP_pTemplate as TemplateEngine;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 */
function generatePage($tpl)
{

}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

EventManager::getInstance()->dispatch(Events::onAdminScriptStart);
check_login('admin');

$tpl = new TemplateEngine();
$tpl->define_dynamic(array(
	'layout' => 'shared/layouts/ui.tpl',
	'page' => 'shared/partials/aps_standard/aps_package_instances.tpl',
	'page_message' => 'layout',
	'aps_package_instances' => 'page'
));

$tpl->assign(array(
	'TR_PAGE_TITLE' => tohtml(tr('Admin / APS Standard / Package Instances'), 'htmlAttr'),
	'PACKAGE_LIST' => tr('Package list'),
	'TR_PACKAGE_NAME' => tr('Package name'),
	'TR_PACKAGE_STATUS' => tr('Package status'),
	'TR_LOADING_IN_PROGRSS' => tr('Loading in progress...'),
	'TR_ID' => tr('Id'),
	'TR_INSTALL_LOCATION' => tr('Install location'),
	'TR_STATUS' => tr('Status'),
	'TR_ACTIONS' => tr('Actions')
));

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventManager::getInstance()->dispatch(Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
