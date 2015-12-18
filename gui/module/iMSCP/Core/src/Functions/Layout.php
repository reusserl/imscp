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

/**
 * Return user GUI properties
 *
 * @param int $userId User identifier
 * @return array
 * @todo must be removed
 */
function get_user_gui_props($userId)
{
    $cfg = \iMSCP\Core\Application::getInstance()->getConfig();
    $stmt = exec_query('SELECT `lang`, `layout` FROM `user_gui_props` WHERE `user_id` = ?', $userId);

    if ($stmt->rowCount()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($row['lang']) || !empty($row['layout'])) {
            if (empty($row['lang'])) {
                return [$cfg['USER_INITIAL_LANG'], $row['layout']];
            }

            if (empty($row['layout'])) {
                return [$row['lang'], $cfg['USER_INITIAL_THEME']];
            }

            return [$row['lang'], $row['layout']];
        }
    }

    return [$cfg['USER_INITIAL_LANG'], $cfg['USER_INITIAL_THEME']];
}

/**
 * Generates the page messages to display on client browser
 *
 * @param iMSCP\Core\Template\TemplateEngine $tpl TemplateEngine instance
 * @return void
 */
function generatePageMessage($tpl)
{
    if (isset($_SESSION['pageMessages'])) {
        foreach (['success', 'error', 'warning', 'info', 'static_success', 'static_error', 'static_warning', 'static_info'] as $level) {
            if (isset($_SESSION['pageMessages'][$level])) {
                $tpl->assign([
                    'MESSAGE_CLS' => $level,
                    'MESSAGE' => $_SESSION['pageMessages'][$level]
                ]);
                $tpl->parse('PAGE_MESSAGE', '.page_message');
            }
        }

        unset($_SESSION['pageMessages']);
        return;
    }

    $tpl->assign('PAGE_MESSAGE', '');
}

/**
 * Sets a page message to display on client browser
 *
 * @param string $message $message Message to display
 * @param string $level Message level (INFO, WARNING, ERROR, SUCCESS)
 * @return void
 */
function set_page_message($message, $level = 'info')
{
    $level = strtolower($level);

    if (!is_string($message)) {
        throw new InvalidArgumentException('set_page_message() expects a string for $message');
    }

    if (!in_array($level, ['info', 'warning', 'error', 'success', 'static_success', 'static_error', 'static_warning', 'static_info'])) {
        throw new InvalidArgumentException(sprintf('Wrong level %s for page message.', $level));
    }

    if (isset($_SESSION['pageMessages'][$level])) {
        $_SESSION['pageMessages'][$level] .= "\n<br />$message";
        return;
    }

    $_SESSION['pageMessages'][$level] = $message;
}

/**
 * format message(s) to be displayed on client browser as page message.
 *
 * @param  string|array $messages Message or stack of messages to be concatenated
 * @return string Concatenated messages
 */
function format_message($messages)
{
    $string = '';

    if (is_array($messages)) {
        foreach ($messages as $message) {
            $string .= $message . "<br />\n";
        }
    } elseif (is_string($messages)) {
        $string = $messages;
    } else {
        throw new InvalidArgumentException('set_page_message() expects a string or an array for $messages.');
    }

    return $string;
}

/**
 * Gets menu variables
 *
 * @param  string $menuLink Menu link
 * @return mixed
 */
function get_menu_vars($menuLink)
{
    if (strpos($menuLink, '}') === false || strpos($menuLink, '}') === false) {
        return $menuLink;
    }

    $stmt = exec_query(
        '
            SELECT
                `customer_id`, `fname`, `lname`, `firm`, `zip`, `city`, `state`, `country`, `email`, `phone`, `fax`,
                `street1`, `street2`
            FROM
                `admin`
            WHERE
                `admin_id` = ?
        '
        , $_SESSION['user_id']
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $search = [];
    $replace = [];
    $search [] = '{uid}';
    $replace[] = $_SESSION['user_id'];
    $search [] = '{uname}';
    $replace[] = tohtml($_SESSION['user_logged']);
    $search [] = '{cid}';
    $replace[] = tohtml($row['customer_id']);
    $search [] = '{fname}';
    $replace[] = tohtml($row['fname']);
    $search [] = '{lname}';
    $replace[] = tohtml($row['lname']);
    $search [] = '{company}';
    $replace[] = tohtml($row['firm']);
    $search [] = '{zip}';
    $replace[] = tohtml($row['zip']);
    $search [] = '{city}';
    $replace[] = tohtml($row['city']);
    $search [] = '{state}';
    $replace[] = $row['state'];
    $search [] = '{country}';
    $replace[] = tohtml($row['country']);
    $search [] = '{email}';
    $replace[] = tohtml($row['email']);
    $search [] = '{phone}';
    $replace[] = tohtml($row['phone']);
    $search [] = '{fax}';
    $replace[] = tohtml($row['fax']);
    $search [] = '{street1}';
    $replace[] = tohtml($row['street1']);
    $search [] = '{street2}';
    $replace[] = tohtml($row['street2']);
    $stmt = exec_query(
        'SELECT `domain_name`, `domain_admin_id` FROM `domain` WHERE `domain_admin_id` = ?', $_SESSION['user_id']
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $search [] = '{domain_name}';
    $replace[] = $row['domain_name'];
    return str_replace($search, $replace, $menuLink);
}

/**
 * Returns available color set for current layout
 *
 * @return array
 */
function layout_getAvailableColorSet()
{
    static $colorSet = [];

    if (!$colorSet) {
        $cfg = \iMSCP\Core\Application::getInstance()->getConfig();

        if (!file_exists($cfg['ROOT_TEMPLATE_PATH'] . '/info.php')) {
            throw new RuntimeException("Layout info.php file is missing or not readable");
        }

        $themeInfo = include_once $cfg['ROOT_TEMPLATE_PATH'] . '/info.php';

        if (!is_array($themeInfo) || !isset($themeInfo['theme_color_set'])) {
            throw new RuntimeException("Missing 'theme_color_set' parameter in layout info file.");
        }

        $colorSet = (array)$themeInfo['theme_color_set'];
    }

    return $colorSet;
}

/**
 * Returns layout color for given user
 *
 * @param int $userId user unique identifier
 * @return string User layout color
 */
function layout_getUserLayoutColor($userId)
{
    static $color = null;

    if (null === $color) {
        if (isset($_SESSION['user_theme_color'])) {
            $color = $_SESSION['user_theme_color'];
        } else {
            $allowedColors = layout_getAvailableColorSet();
            $stmt = exec_query('SELECT `layout_color` FROM `user_gui_props` WHERE `user_id` = ?', $userId);

            if ($stmt->rowCount()) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $color = $row['layout_color'];

                if (!$color || !in_array($color, $allowedColors)) {
                    $color = array_shift($allowedColors);
                }
            } else {
                $color = array_shift($allowedColors);
            }
        }
    }

    return $color;
}

/**
 * Init layout
 *
 * @param \Zend\EventManager\Event $event
 * @return void
 * @todo Use cookies to store user UI properties (Remember me implementation?)
 */
function layout_init($event)
{
    $cfg = \iMSCP\Core\Application::getInstance()->getConfig();
    ini_set('default_charset', 'UTF-8');

    // Get user identity
    $identity = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('Authentication')->getIdentity();

    if (isset($_SESSION['user_theme_color'])) {
        $color = $_SESSION['user_theme_color'];
    } elseif (isset($_SESSION['user_id'])) {
        $userId = isset($_SESSION['logged_from_id']) ? $_SESSION['logged_from_id'] : $_SESSION['user_id'];
        $color = layout_getUserLayoutColor($userId);
        $_SESSION['user_theme_color'] = $color;
    } else {
        $colors = layout_getAvailableColorSet();
        $color = array_shift($colors);
    }

    // Get user locale and language
    /** @var \Zend\I18n\Translator\Translator $translator */
    $translator = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('Translator');
    $locale = $translator->getLocale();
    $localeParts = explode('_', $locale);
    $lang = $localeParts[0];
    unset($localeParts);

    /** @var $tpl iMSCP\Core\Template\TemplateEngine */
    $tpl = $event->getParam('templateEngine');
    $tpl->assign([
        'THEME_COLOR' => $color,
        'ASSETS_PATH' => $cfg['ASSETS_PATH'],
        'ISP_LOGO' => ($identity['admin_type'] !== 'guest') ? layout_getUserLogo() : '',
        'JS_TRANSLATIONS' => i18n_getJsTranslations(),
        'USER_IDENTITY' => json_encode([
            'userId' => $identity['admin_id'],
            'userRole' => $identity['admin_type']
        ]),
        'LANG' => $lang,
        'LOCALE' => $locale,
    ]);
    $tpl->parse('LAYOUT', 'layout');
}

/**
 * Sets given layout color for given user
 *
 * @param int $userId User unique identifier
 * @param string $color Layout color
 * @return bool TRUE on success false otherwise
 */
function layout_setUserLayoutColor($userId, $color)
{
    if (in_array($color, layout_getAvailableColorSet())) {
        exec_query('UPDATE `user_gui_props` SET `layout_color` = ? WHERE `user_id` = ?', [$color, (int)$userId]);

        // Dealing with sessions across multiple browsers for same user identifier - Begin
        $sessionId = session_id();
        $stmt = exec_query('SELECT `session_id` FROM `login` WHERE `user_name` = ?  AND `session_id` <> ?', [
            $_SESSION['user_logged'], $sessionId
        ]);

        if ($stmt->rowCount()) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                session_write_close();
                session_id($row['session_id']);
                session_start();
                $_SESSION['user_theme_color'] = $color; // Update user layout color
            }

            // Return back to the previous session
            session_write_close();
            session_id($sessionId);
            session_start();
        }

        return true;
    }

    return false;
}

/**
 * Get user logo path
 *
 * Note: Only administrators and resellers can have their own logo. Search is done in the following order:
 * user logo -> user's creator logo -> theme logo --> isp logo.
 *
 * @param bool $searchForCreator Tell whether or not search must be done for user's creator in case no logo is found for user
 * @param bool $returnDefault Tell whether or not default logo must be returned
 * @return string User logo path.
 * @todo cache issues
 */
function layout_getUserLogo($searchForCreator = true, $returnDefault = true)
{
    $cfg = \iMSCP\Core\Application::getInstance()->getConfig();

    // On switched level, we want show logo from logged user
    if (isset($_SESSION['logged_from_id']) && $searchForCreator) {
        $userId = $_SESSION['logged_from_id'];
        // Customers inherit the logo of their reseller
    } elseif ($_SESSION['user_type'] === 'user') {
        $userId = $_SESSION['user_created_by'];
    } else {
        $userId = $_SESSION['user_id'];
    }

    $logo = null;
    $stmt = exec_query('SELECT `logo` FROM `user_gui_props` WHERE `user_id`= ?', $userId);

    if ($stmt->rowCount()) {
        $logo = $stmt->fetch(PDO::FETCH_ASSOC)['logo'];
    }

    if ($logo === null && $searchForCreator && $userId != 1) {
        $stmt = exec_query(
            '
                SELECT
                    `b`.`logo`
                FROM
                    `admin` `a`
                LEFT JOIN
                    `user_gui_props` `b` ON (`b`.`user_id` = `a`.`created_by`)
                WHERE
                    `a`.`admin_id`= ?
            ',
            $userId
        );

        if ($stmt->rowCount()) {
            $logo = $stmt->fetch(PDO::FETCH_ASSOC)['logo'];
        }
    }

    // No user logo found
    if ($logo === null || !file_exists($cfg['GUI_ROOT_DIR'] . '/data/persistent/ispLogos/' . $logo)) {
        if (!$returnDefault) {
            return '';
        }

        if (file_exists($cfg['ROOT_TEMPLATE_PATH'] . '/assets/images/imscp_logo.png')) {
            return $cfg['ASSETS_PATH'] . '/images/imscp_logo.png';
        }

        // no logo available, we use default
        return $cfg['ISP_LOGO_PATH'] . '/' . 'isp_logo.gif';

    }

    return $cfg['ISP_LOGO_PATH'] . '/' . $logo;
}

/**
 * Updates user logo
 *
 * Note: Only administrators and resellers can have their own logo.
 *
 * @return bool TRUE on success, FALSE otherwise
 */
function layout_updateUserLogo()
{
    $cfg = \iMSCP\Core\Application::getInstance()->getConfig();

    // closure that is run before move_uploaded_file() function - See the
    // Utils_UploadFile() function for further information about implementation
    // details
    $beforeMove = function ($cfg) {
        $tmpFilePath = $_FILES['logoFile']['tmp_name'];

        // Checking file mime type
        if (!($fileMimeType = checkMimeType($tmpFilePath, ['image/gif', 'image/jpeg', 'image/pjpeg', 'image/png']))) {
            set_page_message(tr('You can only upload images.'), 'error');
            return false;
        }

        // Retrieving file extension (gif|jpeg|png)
        if ($fileMimeType == 'image/pjpeg' || $fileMimeType == 'image/jpeg') {
            $fileExtension = 'jpeg';
        } else {
            $fileExtension = substr($fileMimeType, -3);
        }

        // Getting the image size
        list($imageWidth, $imageHeigth) = getimagesize($tmpFilePath);

        // Checking image size
        if ($imageWidth > 500 || $imageHeigth > 90) {
            set_page_message(tr('Images have to be smaller than 500 x 90 pixels.'), 'error');
            return false;
        }

        // Building an unique file name
        $fileName = sha1(utils_randomString(15) . '-' . $_SESSION['user_id']) . '.' . $fileExtension;

        // Return destination file path
        return $cfg['GUI_ROOT_DIR'] . '/data/persistent/ispLogos/' . $fileName;
    };

    if (($logoPath = utils_uploadFile('logoFile', [$beforeMove, $cfg])) === false) {
        return false;
    } else {
        if ($_SESSION['user_type'] == 'admin') {
            $userId = 1;
        } else {
            $userId = $_SESSION['user_id'];
        }

        // We must catch old logo before update
        $oldLogoFile = layout_getUserLogo(false, false);

        exec_query('UPDATE `user_gui_props` SET `logo` = ? WHERE `user_id` = ?', [basename($logoPath), $userId]);

        // Deleting old logo (we are safe here) - We don't return FALSE on failure.
        // The administrator will be warned through logs.
        layout_deleteUserLogo($oldLogoFile, true);
    }

    return true;
}

/**
 * Deletes user logo
 *
 * @param string $logoFilePath OPTIONAL Logo file path
 * @param bool $onlyFile OPTIONAL Tell whether or not only logo file must be deleted
 * @return bool TRUE on success, FALSE otherwise
 */
function layout_deleteUserLogo($logoFilePath = null, $onlyFile = false)
{
    $cfg = \iMSCP\Core\Application::getInstance()->getConfig();

    if (null === $logoFilePath) {
        if ($_SESSION['user_type'] == 'admin') {
            $logoFilePath = layout_getUserLogo(true);
        } else {
            $logoFilePath = layout_getUserLogo(false);
        }
    }

    if ($_SESSION['user_type'] == 'admin') {
        $userId = 1;
    } else {
        $userId = $_SESSION['user_id'];
    }

    if (!$onlyFile) {
        exec_query('UPDATE `user_gui_props` SET `logo` = ? WHERE `user_id` = ?', [null, $userId]);
    }

    if (strpos($logoFilePath, $cfg['ISP_LOGO_PATH']) !== false) {
        $logoFilePath = $cfg['GUI_ROOT_DIR'] . '/data/persistent/ispLogos/' . basename($logoFilePath);

        if (file_exists($logoFilePath) && @unlink($logoFilePath)) {
            return true;
        }

        write_log(tr("Could not delete '%s' user logo.", $logoFilePath), E_USER_WARNING);
        return false;
    }

    return true;
}

/**
 * Is user logo?
 *
 * @param string $logoPath Logo path to match against
 * @return bool TRUE if $logoPath is a user's logo, FALSE otherwise
 */
function layout_isUserLogo($logoPath)
{
    $cfg = \iMSCP\Core\Application::getInstance()->getConfig();

    if (
        $logoPath == '/themes/' . $_SESSION['user_theme'] . '/assets/images/imscp_logo.png'
        || $logoPath == $cfg['ISP_LOGO_PATH'] . '/' . 'isp_logo.gif'
    ) {
        return false;
    }

    return true;
}

/**
 * Load navigation file for current UI level
 *
 * @return void
 */
function layout_LoadNavigation()
{
    if (isset($_SESSION['user_type'])) {

        //$cfg = \iMSCP\Core\Application::getInstance()->getConfig();

        /** @var \Zend\I18n\Translator\Translator $translator */
        //$translator = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('Translator');
        //$locale = $translator->getLocale();

        //switch ($_SESSION['user_type']) {
        //    case 'admin':
        //        $userLevel = 'admin';
        //       $filepath = 'data/cache/translations/navigation/admin_' . $locale . '.php';
        //        break;
        //    case 'reseller':
        //        $userLevel = 'reseller';
        //        $filepath = 'data/cache/translations/navigation/reseller_' . $locale . '.php';
        //        break;
        //    default:
        //        $userLevel = 'client';
        //        $filepath = 'data/cache/translations/navigation/client_' . $locale . '.php';
        //}

        //if (!file_exists($filepath)) {
        //    layout_createNavigationFile($cfg['ROOT_TEMPLATE_PATH'] . "/$userLevel/navigation.php", $locale, $userLevel);
        //}

        //iMSCP_Registry::set('navigation', new Zend\Navigation\Navigation(include($filepath)));

        // Set main menu labels visibility for the current environment
        \iMSCP\Core\Application::getInstance()->getEventManager()->trigger(
            \iMSCP\Core\Events::onBeforeGenerateNavigation, null, 'layout_setMainMenuLabelsVisibilityEvt'
        );
    }
}

/**
 * Create cached version of navigation translations file for the give file and locale
 *
 * @param string $filepath Navigation translation file path Filepath
 * @param string $locale Locale
 * @param string $userLevel User level for which the file is created
 */
/*
function layout_createNavigationFile($filepath, $locale, $userLevel)
{
    $translationsCacheDir = 'data/cache/translations/navigation';

    if (!is_dir($translationsCacheDir)) {
        if (!@mkdir($translationsCacheDir)) {
            throw new RuntimeException('Unable to create cache directory for navigation translations');
        }
    }

    (new \Zend\Config\Writer\PhpArray())
        ->setUseBracketArraySyntax(true)
        ->toFile($translationsCacheDir . '/' . $userLevel . '_' . $locale . '.php', include($filepath));
}
*/

/**
 * Tells whether or not main menu labels are visible for the given user.
 *
 * @param int $userId User unique identifier
 * @return bool
 */
function layout_isMainMenuLabelsVisible($userId)
{
    $stmt = exec_query('SELECT `show_main_menu_labels` FROM `user_gui_props` WHERE `user_id` = ?', $userId);

    if ($stmt->rowCount()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (bool)$row['show_main_menu_labels'];
    }

    return true;
}

/**
 * Sets main menu label visibility for the given user
 *
 * @param int $userId User unique identifier
 * @param int $visibility (0|1)
 * @return void
 */
function layout_setMainMenuLabelsVisibility($userId, $visibility)
{
    exec_query('UPDATE `user_gui_props` SET `show_main_menu_labels` = ? WHERE `user_id` = ?', [$visibility, $userId]);

    if (!isset($_SESSION['logged_from_id'])) {
        $_SESSION['show_main_menu_labels'] = $visibility;
    }
}

/**
 * Sets main menu visibility for current environment
 *
 * @return void
 */
function layout_setMainMenuLabelsVisibilityEvt()
{
    if (!isset($_SESSION['show_main_menu_labels']) && isset($_SESSION['user_type'])) {
        $userId = isset($_SESSION['logged_from_id']) ? $_SESSION['logged_from_id'] : $_SESSION['user_id'];
        $_SESSION['show_main_menu_labels'] = layout_isMainMenuLabelsVisible($userId);
    }
}
