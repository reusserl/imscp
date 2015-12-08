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

require '../application.php';

\iMSCP\Core\Application::getInstance()->getEventManager()->trigger(
    \iMSCP\Core\Events::onTemplatesScriptStart, \iMSCP\Core\Application::getInstance()->getApplicationEvent()
);

$request = \iMSCP\Core\Application::getInstance()->getRequest();
$response = \iMSCP\Core\Application::getInstance()->getResponse();

if (!$request->isXmlHttpRequest() || !($tpl = clean_input($request->getQuery('tpl', '')))) {
    $response->setStatusCode(400);
    $response->send();
    return;
}

try {
    $tpl = new \iMSCP\Core\Template\TemplateEngine();
    $tpl->define('template', $tpl);
    $tpl->parse('TEMPLATE', 'template');

    \iMSCP\Core\Application::getInstance()->getEventManager()->trigger(\iMSCP\Core\Events::onTemplatesScriptEnd, null, [
        'templateEngine' => $tpl
    ]);

    $response->setContent($tpl->getLastParseResult());
} catch (\Exception $e) {
    write_log(sprintf('Could not load the %s template file: %s', $tpl, $e->getMessage()));
    $response->setStatusCode($e->getCode() == 404 ? 404 : 500);
}

$response->send();
