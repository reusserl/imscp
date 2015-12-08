<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generates page
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl Template engine
 * @return void
 */
function client_generatePage($tpl)
{
    _client_generateIpsList($tpl);
    _client_generateNetcardsList($tpl);

    if (isset($_POST['ip_number'])) {
        $tpl->assign('VALUE_IP', tohtml($_POST['ip_number']));
    } else {
        $tpl->assign('VALUE_IP', '');
    }
}

/**
 * Generates IPs list
 *
 * @access private
 * @param iMSCP\Core\Template\TemplateEngine $tpl Template engine
 * @return void
 */
function _client_generateIpsList($tpl)
{
    $cfg = \iMSCP\Core\Application::getInstance()->getConfig();
    $query = "SELECT * FROM `server_ips`";
    $stmt = execute_query($query);

    if ($stmt->rowCount()) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            list($actionName, $actionUrl) = _client_generateIpAction($row['ip_id'], $row['ip_status']);

            $tpl->assign([
                'IP' => $row['ip_number'],
                'NETWORK_CARD' => ($row['ip_card'] === NULL) ? '' : tohtml($row['ip_card'])
            ]);

            $tpl->assign([
                'ACTION_NAME' => ($cfg['BASE_SERVER_IP'] == $row['ip_number']) ? tr('Protected') : $actionName,
                'ACTION_URL' => ($cfg['BASE_SERVER_IP'] == $row['ip_number']) ? '#' : $actionUrl
            ]);

            $tpl->parse('IP_ADDRESS_BLOCK', '.ip_address_block');
        }
    } else { // Should never occur but who knows.
        $tpl->assign('IP_ADDRESSES_BLOCK', '');
        set_page_message(tr('No IP address found.'), 'info');
    }
}

/**
 * Generates Ips action
 *
 * @access private
 * @param int $ipId Ip address unique identifier
 * @param string $status
 * @return array
 */
function _client_generateIpAction($ipId, $status)
{
    if ($status == 'ok') {
        return [tr('Remove IP'), 'ip_delete.php?delete_id=' . $ipId];
    }

    if ($status == 'todelete') {
        return [translate_dmn_status('todelete'), '#'];
    }

    if ($status == 'toadd') {
        return [translate_dmn_status('toadd'), '#'];
    }

    if (!in_array($status, ['toadd', 'tochange', 'ok', 'todelete'])) {
        return [tr('Unknown Error'), '#'];
    }

    return [tr('N/A'), '#'];
}

/**
 * Generates network cards list
 *
 * @access private
 * @param iMSCP\Core\Template\TemplateEngine $tpl Template engine
 * @return void
 */
function _client_generateNetcardsList($tpl)
{
    global $networkCardObject;

    if ($networkCardObject->getErrors() != '') {
        set_page_message($networkCardObject->getErrors(), 'error');
    }

    $networkCards = $networkCardObject->getAvailableInterface();
    sort($networkCards);

    if (!empty($networkCards)) {
        foreach ($networkCards as $networkCard) {
            $tpl->assign('NETWORK_CARD', $networkCard);
            $tpl->parse('NETWORK_CARD_BLOCK', '.network_card_block');
        }
    } else { // Should never occur but who knows.
        set_page_message(tr('Unable to find any network interface. You cannot add new IP address.'), 'error');
        $tpl->assign('IP_ADDRESS_FORM_BLOCK', '');
    }
}

/**
 * Checks IP data
 *
 * @param string $ipNumber IP number
 * @param string $netcard Network card
 * @return bool TRUE if data are valid, FALSE otherwise
 */
function client_checkIpData($ipNumber, $netcard)
{
    global $networkCardObject, $errFieldsStack;

    if (filter_var($ipNumber, FILTER_VALIDATE_IP) === false) {
        set_page_message(tr('Wrong IP address.'), 'error');
        $errFieldsStack[] = 'ip_number';
    } else {
        $query = "SELECT COUNT(IF(`ip_number` = ?, 1, NULL)) `isRegisteredIp` FROM `server_ips`";
        $stmt = exec_query($query, $ipNumber);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row['isRegisteredIp']) {
            set_page_message(tr('IP address already under the control of i-MSCP.'), 'error');
            $errFieldsStack[] = 'ip_number';
        }
    }

    if (!in_array($netcard, $networkCardObject->getAvailableInterface())) {
        set_page_message(tr('You must select a network interface.'), 'error');
    }

    if (isset($_SESSION['pageMessages'])) {
        return false;
    }

    return true;
}

/**
 * Register new IP
 *
 * @param string $ipNumber IP number (dot notation)
 * @param string $netcard Network card
 * @return void
 */
function client_registerIp($ipNumber, $netcard)
{
    $query = "INSERT INTO `server_ips` (`ip_number`, `ip_card`, `ip_status`) VALUES (?, ?, ?)";
    exec_query($query, [$ipNumber, $netcard, 'toadd']);
    send_request();
    set_page_message(tr('IP address successfully scheduled for addition.'), 'success');
    write_log("{$_SESSION['user_logged']} added new IP address: $ipNumber", E_USER_NOTICE);
    redirectTo('ip_manage.php');
}

/***********************************************************************************************************************
 * Main
 */

require '../../application.php';

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(
    \iMSCP\Core\Events::onAdminScriptStart, \iMSCP\Core\Application::getInstance()->getApplicationEvent()
);

check_login('admin');

// Initialize network card object
$networkCardObject = new \iMSCP\Core\NetworkCard();

// Initialize field error stack
$errFieldsStack = [];

if (!empty($_POST)) {
    $ipNumber = isset($_POST['ip_number']) ? trim($_POST['ip_number']) : '';
    $netCard = isset($_POST['ip_card']) ? clean_input($_POST['ip_card']) : '';

    if (client_checkIpData($ipNumber, $netCard)) {
        client_registerIp($ipNumber, $netCard);
    }
}

$tpl = new \iMSCP\Core\Template\TemplateEngine();
$tpl->defineDynamic([
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'admin/ip_manage.tpl',
    'page_message' => 'layout',
    'ip_addresses_block' => 'page',
    'ip_address_block' => 'ip_addresses_block',
    'ip_address_form_block' => 'page',
    'network_card_block' => 'ip_address_form_block'
]);
$tpl->assign([
    'TR_PAGE_TITLE' => tr('Admin / Settings / IP Addresses Management'),
    'TR_IP' => tr('IP Address'),
    'TR_ACTION' => tr('Action'),
    'TR_NETWORK_CARD' => tr('Network interface'),
    'TR_ADD' => tr('Add'),
    'TR_CANCEL' => tr('Cancel'),
    'TR_CONFIGURED_IPS' => tr('IP addresses under control of i-MSCP'),
    'TR_ADD_NEW_IP' => tr('Add new IP address'),
    'TR_IP_DATA' => tr('IP address data'),
    'TR_MESSAGE_DELETE' => json_encode(tr('Are you sure you want to delete this IP: %s?', '%s')),
    'TR_MESSAGE_DENY_DELETE' => json_encode(tr('You cannot remove the %s IP address.', '%s')),
    'ERR_FIELDS_STACK' => json_encode($errFieldsStack),
    'TR_TIP' => tr('This interface allow to add or remove IP addresses. IP addresses listed below are already under the control of i-MSCP. IP addresses which are added through this interface will be automatically added into the i-MSCP database, and will be available for assignment to one or many of your resellers. If an IP address is not already configured on the system, it will be attached to the selected network interface.')
]);

\iMSCP\Core\Application::getInstance()->getEventManager()->attach('onGetJsTranslations', function ($e) {
    /** @var $e \Zend\EventManager\Event */
    $e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
});

generateNavigation($tpl);
client_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAdminScriptEnd, null, [
    'templateEngine' => $tpl
]);
$tpl->prnt();

unsetMessages();
