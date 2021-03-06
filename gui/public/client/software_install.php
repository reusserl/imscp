<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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

use iMSCP\VirtualFileSystem as VirtualFileSystem;

/***********************************************************************************************************************
 *  Script functions
 */

/**
 * Set FTP root dir
 *
 * @param null|iMSCP_pTemplate $tpl
 * @return void
 */
function setFtpRootDir($tpl = null)
{
    $domainProps = get_domain_default_props($_SESSION['user_id']);

    if (!is_xhr()) {
        list($mountPoint, $documentRoot) = getDomainMountpoint($domainProps['domain_id'], 'dmn', $_SESSION['user_id']);

        $tpl->assign('DOCUMENT_ROOT', tohtml(utils_normalizePath($documentRoot)));

        # Set parameters for the FTP chooser
        $_SESSION['ftp_chooser_domain_id'] = $domainProps['domain_id'];
        $_SESSION['ftp_chooser_user'] = $_SESSION['user_logged'];
        $_SESSION['ftp_chooser_root_dir'] = utils_normalizePath($mountPoint . '/' . $documentRoot);
        $_SESSION['ftp_chooser_hidden_dirs'] = array();
        $_SESSION['ftp_chooser_unselectable_dirs'] = array();
        return;
    }

    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/json');

    $data = array();

    if (!isset($_POST['domain_id']) || !isset($_POST['domain_type'])) {
        header('Status: 400 Bad Request');
        $data['message'] = tr('Bad request.');
    } else {
        try {
            list($mountPoint, $documentRoot) = getDomainMountpoint(
                intval($_POST['domain_id']), clean_input($_POST['domain_type']), $_SESSION['user_id']
            );

            # Update parameters for the FTP chooser
            $_SESSION['ftp_chooser_domain_id'] = $domainProps['domain_id'];
            $_SESSION['ftp_chooser_user'] = $_SESSION['user_logged'];
            $_SESSION['ftp_chooser_root_dir'] = utils_normalizePath($mountPoint . '/' . $documentRoot);
            $_SESSION['ftp_chooser_hidden_dirs'] = array();
            $_SESSION['ftp_chooser_unselectable_dirs'] = array();

            header('Status: 200 OK');
            $data['document_root'] = utils_normalizePath($documentRoot);
        } catch (iMSCP_Exception $e) {
            header('Status: 400 Bad Request');
            $data['message'] = tr('Bad request.') . ' ' . $e->getMessage();
        }
    }

    echo json_encode($data);
    exit;
}

/**
 * Generate Page
 *
 * @throws iMSCP_Exception
 * @param iMSCP_pTemplate $tpl
 * @param int $softwareId Software unique identifier
 * @return void
 */
function client_generatePage($tpl, $softwareId)
{
    $domainProperties = get_domain_default_props($_SESSION['user_id']);
    $stmt = exec_query('SELECT created_by FROM admin WHERE admin_id = ?', $_SESSION['user_id']);

    if (!$stmt->rowCount()) {
        throw new iMSCP_Exception('An unexpected error occurred. Please contact your reseller.');
    }

    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
    get_software_props_install(
        $tpl, $domainProperties['domain_id'], $softwareId, $row['created_by'], $domainProperties['domain_sqld_limit']
    );
}

/***********************************************************************************************************************
 * Main program
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');
customerHasFeature('aps') or showBadRequestErrorPage();

if (!isset($_GET['id']) || !is_number($_GET['id'])) {
    showBadRequestErrorPage();
}

$softwareId = intval($_GET['id']);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout'                 => 'shared/layouts/ui.tpl',
    'page'                   => 'client/software_install.tpl',
    'page_message'           => 'layout',
    'software_item'          => 'page',
    'show_domain_list'       => 'page',
    'software_install'       => 'page',
    'no_software'            => 'page',
    'installdb_item'         => 'page',
    'select_installdb'       => 'page',
    'require_installdb'      => 'page',
    'select_installdbuser'   => 'page',
    'installdbuser_item'     => 'page',
    'softwaredbuser_message' => 'page',
    'create_db'              => 'page',
    'create_message_db'      => 'page'
));

if (!empty($_POST)) {
    if (is_xhr()) {
        setFtpRootDir();
    }

    if (!isset($_POST['selected_domain']) || !isset($_POST['other_dir']) || !isset($_POST['install_username'])
        || !isset($_POST['install_password']) || !isset($_POST['install_email'])
    ) {
        showBadRequestErrorPage();
    }

    # Required data
    $otherDir = utils_normalizePath(clean_input($_POST['other_dir']));
    $appLoginName = clean_input($_POST['install_username']);
    $appPassword = clean_input($_POST['install_password']);
    $appEmail = clean_input($_POST['install_email']);
    $stmt = exec_query(
        '
          SELECT software_master_id, software_db, software_name, software_version, software_language,
            software_prefix, software_depot
          FROM web_software
          WHERE software_id = ?
        ',
        $softwareId
    );

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $softwareData = $stmt->fetchRow(PDO::FETCH_ASSOC);
    $postData = explode(';', $_POST['selected_domain']);

    if (sizeof($postData) != 2) {
        showBadRequestErrorPage();
    }

    $domainId = intval($postData[0]);
    $domainType = clean_input($postData[1]);
    $domainProps = get_domain_default_props($_SESSION['user_id']);
    $aliasId = $subId = $aliasSubId = 0;

    switch ($domainType) {
        case 'dmn':
            $stmt = exec_query(
                "
                  SELECT '/' AS mpoint, document_root
                  FROM domain
                  WHERE domain_id = ?
                  AND domain_admin_id = ?
                  AND domain_status = ?
                  AND url_forward = ?
                ",
                array($domainId, $_SESSION['user_id'], 'ok', 'no')
            );
            break;
        case 'sub':
            $subId = $domainId;
            $stmt = exec_query(
                '
                  SELECT subdomain_mount AS mpoint, subdomain_document_root AS document_root
                  FROM subdomain
                  WHERE subdomain_id = ?
                  AND domain_id = ?
                  AND subdomain_url_forward = ?
                  AND subdomain_status = ?
                ',
                array($domainId, $domainProps['domain_id'], 'no', 'ok')
            );
            break;
        case 'als':
            $aliasId = $domainId;
            $stmt = exec_query(
                '
                  SELECT alias_mount AS mpoint, alias_document_root AS document_root
                  FROM domain_aliasses
                  WHERE alias_id = ?
                  AND domain_id = ?
                  AND alias_status = ?
                  AND url_forward = ?
                ',
                array($domainId, $domainProps['domain_id'], 'ok', 'no')
            );
            break;
        case 'alssub':
            $aliasSubId = $domainId;
            $stmt = exec_query(
                '
                  SELECT subdomain_alias_mount AS mpoint, subdomain_alias_document_root AS document_root
                  FROM subdomain_alias
                  INNER JOIN domain_aliasses USING(alias_id)
                  WHERE subdomain_alias_id = ?
                  AND subdomain_alias_url_forward = ?
                  AND domain_id = ?
                  AND subdomain_alias_status =?
                ',
                array($domainId, 'no', $domainProps['domain_id'], 'ok')
            );
            break;
        default:
            showBadRequestErrorPage();
            exit;
    }

    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
    $installPath = utils_normalizePath($row['mpoint'] . '/htdocs/' . $otherDir);
    $error = false;

    $vfs = new VirtualFileSystem($_SESSION['user_logged']);
    if (!$vfs->exists($installPath, VirtualFileSystem::VFS_TYPE_DIR)) {
        set_page_message(tr("The directory %s doesn't exists. Please create that directory using your file manager.", $otherDir), 'error');
        $error = true;
    } else {
        $stmt = exec_query(
            'SELECT software_name, software_version FROM web_software_inst WHERE domain_id = ? AND path = ?', array(
            $domainId, $installPath
        ));

        if ($stmt->rowCount()) {
            $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
            set_page_message(tr('Please select another directory. %s (%s) is installed there.', $row['software_name'], $row['software_version']), 'error');
            $error = true;
        }
    }

    # Check application username
    if (strpos($appLoginName, ',') !== FALSE || !validates_username($appLoginName)) {
        set_page_message(tr('Invalid username.'), 'error');
        $error = true;
    }

    # Check application password
    if (strpos($appPassword, ',') !== FALSE || !checkPasswordSyntax($appPassword)) {
        $error = true;
    }

    # Check application email
    if (strpos($appEmail, ',') !== FALSE || !chk_email($appEmail)) {
        set_page_message(tr('Invalid email address.'), 'error');
        $error = true;
    }

    # Check application database if required
    if ($softwareData['software_db']) {
        if (!isset($_POST['selected_db']) || !isset($_POST['sql_user'])) {
            showBadRequestErrorPage();
        }

        $appDatabase = clean_input($_POST['selected_db']);
        $appSqlUser = clean_input($_POST['sql_user']);

        # Ensure that both SQL user and database are owned by customer and get SQL password
        $stmt = exec_query(
            '
              SELECT sqlu_pass
              FROM sql_user
              INNER JOIN sql_database USING(sqld_id)
              INNER JOIN domain USING(domain_id)
              WHERE sqlu_name = ?
              AND sqld_name = ?
              AND domain_admin_id = ?
            ',
            array($appSqlUser, $appDatabase, $_SESSION['user_id'])
        );

        if (!$stmt->rowCount()) {
            showBadRequestErrorPage();
        }

        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
        if (check_db_connection($appDatabase, $appSqlUser, $row['sqlu_pass'])) {
            $appSqlPassword = $row['sqlu_pass'];
        } else {
            set_page_message(tr('Unable to connect to the selected database using the selected SQL user.'), 'error');
            $error = true;
        }

        $softwarePrefix = $softwareData['software_prefix'];
    } else {
        $softwarePrefix = $appDatabase = $appSqlUser = $appSqlPassword = 'no_required';
    }

    if (!$error && isset($appSqlPassword)) {
        exec_query(
            '
              INSERT INTO web_software_inst (
                domain_id, alias_id, subdomain_id, subdomain_alias_id, software_id, software_master_id,
                software_name, software_version, software_language, path, software_prefix, db,
                database_user, database_tmp_pwd, install_username, install_password, install_email,
                software_status, software_depot
              ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
              )
            ',
            array(
                $domainProps['domain_id'], $aliasId, $subId, $aliasSubId, $softwareId, $softwareData['software_master_id'],
                $softwareData['software_name'], $softwareData['software_version'], $softwareData['software_language'],
                $installPath, $softwarePrefix, $appDatabase, $appSqlUser, $appSqlPassword, $appLoginName,
                $appPassword, $appEmail, 'toadd', $softwareData['software_depot']
            )
        );

        write_log(sprintf('%s added new software instance: %s', decode_idna($_SESSION['user_logged']), $softwareData['software_name']), E_USER_NOTICE);
        send_request();
        set_page_message(tr('Software instance has been scheduled for installation'), 'success');
        redirectTo('software.php');
    }
} else {
    setFtpRootDir($tpl);
    $otherDir = '';
    $appLoginName = 'admin';
    $appPassword = '';
    $appEmail = $_SESSION['user_email'];
}

$tpl->assign(array(
    'TR_PAGE_TITLE'               => tr('Client / Webtools / Software / Software Installation'),
    'SOFTWARE_ID'                 => tohtml($softwareId),
    'TR_NAME'                     => tr('Software'),
    'TR_TYPE'                     => tr('Type'),
    'TR_DB'                       => tr('Database required'),
    'TR_SELECT_DOMAIN'            => tr('Target domain'),
    'TR_CANCEL'                   => tr('Cancel'),
    'TR_INSTALL'                  => tr('Install'),
    'TR_PATH'                     => tr('Installation path'),
    'TR_CHOOSE_DIR'               => tr('Choose dir'),
    'TR_SELECT_DB'                => tr('Database'),
    'TR_SQL_USER'                 => tr('SQL user'),
    'TR_SQL_PWD'                  => tr('Password'),
    'TR_SOFTWARE_MENU'            => tr('Software installation'),
    'TR_INSTALLATION'             => tr('Installation details'),
    'TR_INSTALLATION_INFORMATION' => tr('Username and password for application login'),
    'TR_INSTALL_USER'             => tr('Login username'),
    'TR_INSTALL_PWD'              => tr('Login password'),
    'TR_INSTALL_EMAIL'            => tr('Email address'),
    'VAL_OTHER_DIR'               => tohtml($otherDir),
    'VAL_INSTALL_USERNAME'        => tohtml($appLoginName),
    'VAL_INSTALL_PASSWORD'        => tohtml($appPassword),
    'VAL_INSTALL_EMAIL'           => tohtml($appEmail)
));

iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
    /** @var $e iMSCP_Events_Event */
    $translations = $e->getParam('translations');
    $translations['core']['close'] = tr('Close');
    $translations['core']['ftp_directories'] = tr('Ftp directories');
});

client_generatePage($tpl, $softwareId);
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
unsetMessages();
