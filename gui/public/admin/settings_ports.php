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
 * Get protocol data from $_POST array
 *
 * @param integer $index
 * @return string protocol
 */
function getProtocol($index)
{
    /** @var \iMSCP\Core\Config\DbConfigHandler $dbConfig */
    $dbConfig = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('DbConfig');

    if (isset($_POST['port_type'][$index])) {
        $protocol = $_POST['port_type'][$index];
    } else {
        try {
            $sData = $dbConfig[$_POST['var_name'][$index]];
            $sData = explode(';', $sData);
            $protocol = $sData[1];
        } catch (Exception $e) {
            $protocol = 'notexistingone';
        }
    }

    return $protocol;
}

/**
 * Prepare and put data in session on error(s)
 *
 * @param bool $mode TRUE on add, FALSE otherwise
 * @return void
 */
function toSession($mode)
{
    global $errorFieldsIds;

    // Create a json object that will be used by client browser for fields highlighting
    $_SESSION['errorFieldsIds'] = json_encode($errorFieldsIds);

    if ($mode == 'add') { // Data for error on add
        $values = [
            'name_new' => $_POST['name_new'],
            'ip_new' => $_POST['ip_new'],
            'port_new' => $_POST['port_new'],
            'port_type_new' => $_POST['port_type_new'],
            'show_val_new' => $_POST['show_val_new']
        ];

        $_SESSION['error_on_add'] = $values;
    } else { // Data for error on update
        foreach ($_POST['var_name'] as $index => $service) {
            $port = $_POST['port'][$index];
            $protocol = getProtocol($index);
            $name = $_POST['name'][$index];
            $show = $_POST['show_val'][$index];
            $ip = $_POST['ip'][$index];
            $values[$service] = "$port;$protocol;$name;$show;$ip";
            $_SESSION['error_on_updt'] = $values;
        }
    }
}

/**
 * Validates a service port and sets an appropriate message on error
 *
 * @param string $name Service name
 * @param string $ip Ip address
 * @param int $port Port
 * @param string $protocol Protocle
 * @param bool $show Tell whether or not service must be show on status page
 * @param string $index Item index on update, empty value otherwise
 * @return bool TRUE if valid, FALSE otherwise
 */
function admin_validatesService($name, $ip, $port, $protocol, $show, $index = '')
{
    global $errorFieldsIds;

    /** @var \iMSCP\Core\Config\DbConfigHandler $dbConfig */
    $dbConfig = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('DbConfig');

    $dbServiceName = "PORT_$name";
    $ip = ($ip == 'localhost') ? '127.0.0.1' : $ip;

    // Check for service name syntax
    if (!is_basicString($name)) {
        set_page_message(tr("Error with '$name': Only letters, numbers, dash and underscore are allowed for services names."), 'error');
        $errorFieldsIds[] = "name$index";
    }

    // Check for IP syntax
    if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
        set_page_message(tr(' Wrong IP address.'), 'error');
        $errorFieldsIds[] = "ip$index";
    }

    // Check for port syntax
    if (!is_number($port) || $port < 1 || $port > 65535) {
        set_page_message(tr('Only numbers in range from 0 to 65535 are allowed.'), 'error');
        $errorFieldsIds[] = "port$index";
    }

    // Check for service port existences
    if (!is_int($index) && isset($dbConfig[$dbServiceName])) {
        set_page_message(tr('Service name already exists.'), 'error');
        $errorFieldsIds[] = "name$index";
    }

    // Check for protocol and show option
    if (($protocol != 'tcp' && $protocol != 'udp') || ($show != '0' && $show != '1')) {
        showBadRequestErrorPage();
    }

    return !isset($_SESSION['pageMessages']);
}

/**
 * Adds or updates services ports
 *
 * @param string $mode Mode in witch act (add or update)
 * @return void
 */
function admin_addUpdateServices($mode = 'add')
{
    global $errorFieldsIds;

    /** @var \iMSCP\Core\Config\DbConfigHandler $dbConfig */
    $dbConfig = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('DbConfig');

    if ($mode == 'add') { // Adds a service port
        $port = $_POST['port_new'];
        $protocol = $_POST['port_type_new'];
        $name = strtoupper($_POST['name_new']);
        $show = $_POST['show_val_new'];
        $ip = $_POST['ip_new'];

        if (admin_validatesService($name, $ip, $port, $protocol, $show)) {
            $dbServiceName = "PORT_$name";
            $dbConfig[$dbServiceName] = "$port;$protocol;$name;$show;$ip";
            write_log($_SESSION['user_logged'] . ": Added service port $name ($port)!", E_USER_NOTICE);
        }
    } elseif ($mode == 'update') { // Updates one or more services ports
        // Reset counter of update queries
        $dbConfig->resetQueriesCounter('update');

        foreach ($_POST['name'] as $index => $name) {
            $port = $_POST['port'][$index];
            $protocol = getProtocol($index);
            $name = strtoupper($name);
            $show = $_POST['show_val'][$index];
            $ip = $_POST['ip'][$index];

            if (admin_validatesService($name, $ip, $port, $protocol, $show, $index)) {
                $dbServiceName = $_POST['var_name'][$index];
                $dbConfig[$dbServiceName] = "$port;$protocol;$name;$show;$ip";
            }
        }
    } else {
        throw new InvalidArgumentException(sprintf('admin_addUpdateServices(): Wrong argument for %s', $mode));
    }

    if (!empty($errorFieldsIds)) {
        toSession($mode);
    } elseif ($mode == 'add') {
        set_page_message(tr('Service port successfully addeds'), 'success');
    } else {
        $updateCount = $dbConfig->countQueries('update');

        if ($updateCount > 0) {
            set_page_message(tr('%d Service(s) port successfully updateds', $updateCount), 'success');
        } else {
            set_page_message(tr('Nothing has been changed.'), 'info');
        }
    }
}

/**
 * Gets and prepares the template part for services ports
 *
 * This function is used for generation of both pages (show page and error page)
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl TemplateEngine instance
 * @return void;
 */
function admin_showServices($tpl)
{
    $cfg = \iMSCP\Core\Application::getInstance()->getConfig();

    if (isset($_SESSION['error_on_updt'])) {
        $values = new \iMSCP\Core\Config\ArrayConfigHandler($_SESSION['error_on_updt']);
        unset($_SESSION['error_on_updt']);
        $services = array_keys($values->toArray());
    } else {
        /** @var \iMSCP\Core\Config\DbConfigHandler $values */
        $values = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('DbConfig');

        // Gets list of services port names
        $services = array_filter(array_keys($values->toArray()), function ($name) {
            return strlen($name) > 5 && substr($name, 0, 5) == 'PORT_';
        });

        if (isset($_SESSION['errorOnAdd'])) {
            $errorOnAdd = new \iMSCP\Core\Config\ArrayConfigHandler($_SESSION['errorOnAdd']);
            unset($_SESSION['errorOnAdd']);
        }
    }

    if (empty($services)) {
        $tpl->assign('SERVICE_PORTS', '');
        set_page_message(tr('You have not service ports defined.'), 'static_info');
    } else {
        sort($services);

        foreach ($services as $index => $service) {
            list($port, $protocol, $name, $status, $ip) = explode(';', $values[$service]);
            $htmlSelected = $cfg['HTML_SELECTED'];
            $selectedUdp = $protocol == 'udp' ? $htmlSelected : '';
            $selectedTcp = $protocol == 'udp' ? '' : $htmlSelected;
            $selectedOn = $status == '1' ? $htmlSelected : '';
            $selectedOff = $status == '1' ? '' : $htmlSelected;
            $tpl->assign([
                'SERVICE' => '<input name="name[]" type="text" id="name' . $index .
                    '" value="' . tohtml($name) . '" class="textinput" maxlength="25" />',
                'NAME' => tohtml($name),
                'DISABLED' => '',
                'TR_DELETE' => tr('Delete'),
                'URL_DELETE' => "?delete=$service",
                'NUM' => $index
            ]);
            $tpl->parse('PORT_DELETE_LINK', 'port_delete_link');
            $tpl->assign([
                'VAR_NAME' => tohtml($service),
                'IP' => ($ip == 'localhost') ? '127.0.0.1' : (!$ip ? '0.0.0.0' : tohtml($ip)),
                'PORT' => tohtml($port),
                'SELECTED_UDP' => $selectedUdp,
                'SELECTED_TCP' => $selectedTcp,
                'SELECTED_ON' => $selectedOn,
                'SELECTED_OFF' => $selectedOff
            ]);
            $tpl->parse('SERVICE_PORTS', '.service_ports');
        }

        // Add fields
        $tpl->assign(
            isset($errorOnAdd) ? [
                'VAL_FOR_NAME_NEW' => $errorOnAdd['name_new'],
                'VAL_FOR_IP_NEW' => $errorOnAdd['ip_new'],
                'VAL_FOR_PORT_NEW' => $errorOnAdd['port_new']
            ] : [
                'VAL_FOR_NAME_NEW' => '',
                'VAL_FOR_IP_NEW' => '',
                'VAL_FOR_PORT_NEW' => ''
            ]
        );

        // Error fields ids
        $tpl->assign('ERROR_FIELDS_IDS', isset($_SESSION['errorFieldsIds']) ? $_SESSION['errorFieldsIds'] : '[]');
        unset($_SESSION['errorFieldsIds']);
    }
}

/**
 * Remove a service port from the database
 *
 * @param string $serviceName Service name
 * @return bool TRUE on success, FALSE otherwise
 */
function deleteService($serviceName)
{
    /** @var \iMSCP\Core\Config\DbConfigHandler $dbConfig */
    $dbConfig = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('DbConfig');

    if (!isset($dbConfig[$serviceName])) {
        set_page_message(tr("Unknown service name '%s'.", $serviceName), 'error');
        return false;
    }

    // Remove service port from the database
    unset($dbConfig[$serviceName]);
    write_log($_SESSION['user_logged'] . ": Removed port for '$serviceName'.", E_USER_NOTICE);
    set_page_message(tr('Service port successfully removed.'), 'success');
    return true;
}

/***********************************************************************************************************************
 * Main
 */

require '../../application.php';

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAdminScriptStart);

check_login('admin');

if (isset($_POST['uaction']) && $_POST['uaction'] != 'reset') {
    admin_addUpdateServices((clean_input($_POST['uaction'])));
} elseif (isset($_GET['delete'])) {
    deleteService(clean_input($_GET['delete']));
}

$tpl = new \iMSCP\Core\Template\TemplateEngine();
$tpl->defineDynamic([
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'admin/settings_ports.tpl',
    'page_message' => 'layout',
    'service_ports' => 'page',
    'port_delete_link' => 'service_ports'
]);
$tpl->assign([
    'TR_PAGE_TITLE' => tr('Admin / Settings / Service Ports'),
    'TR_ACTION' => tr('Action'),
    'TR_UDP' => tr('udp'),
    'TR_TCP' => tr('tcp'),
    'TR_ENABLED' => tr('Yes'),
    'TR_DISABLED' => tr('No'),
    'TR_SERVERPORTS' => tr('Server ports'),
    'TR_SERVICE' => tr('Service Name'),
    'TR_IP' => tr('IP address'),
    'TR_PORT' => tr('Port'),
    'TR_PROTOCOL' => tr('Protocol'),
    'TR_SHOW' => tr('Show'),
    'TR_DELETE' => tr('Delete'),
    'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s service port ?', '%s'),
    'TR_ADD_NEW_SERVICE_PORT' => tr('Add new service port'),
    'VAL_FOR_SUBMIT_ON_UPDATE' => tr('Update'),
    'VAL_FOR_SUBMIT_ON_ADD' => tr('Add'),
    'VAL_FOR_SUBMIT_ON_RESET' => tr('Reset')
]);

\iMSCP\Core\Application::getInstance()->getEventManager()->attach('onGetJsTranslations', function ($e) {
    /** @var $e \Zend\EventManager\Event */
    $e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
});

generateNavigation($tpl);
admin_showServices($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onAdminScriptEnd, null, [
    'templateEngine' => $tpl
]);
$tpl->prnt();

unsetMessages();
