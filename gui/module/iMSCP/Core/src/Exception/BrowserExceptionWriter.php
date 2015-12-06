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

namespace iMSCP\Core\Exception;
use iMSCP\Core\Application;
use iMSCP\Core\Template\TemplateEngine;

/**
 * Class BrowserExceptionWriter
 * @package iMSCP\Core\Exception
 */
class BrowserExceptionWriter extends AbstractExceptionWriter
{
    /**
     * @var TemplateEngine
     */
    protected $templateEngine;

    /**
     * @var string Template file path
     */
    protected $templateFile;

    /** @var  string message */
    protected $message;

    /**
     * Constructor
     *
     * @param string $templateFile Template file path
     */
    public function __construct($templateFile = 'message.tpl')
    {
        $this->templateFile = (string)$templateFile;
    }

    /**
     * onUncaughtException event listener
     *
     * @param ExceptionEvent $event
     * @return void
     */
    public function onUncaughtException(ExceptionEvent $event)
    {
        $e = $event->getException();

        $config = Application::getInstance()->getConfig();
        $debug = $config['DEBUG'];

        if ($debug) {
            $this->message .= sprintf(
                "An exception has been thrown in file %s at line %s:\n\n", $e->getFile(), $e->getLine()
            );

            $this->message .= preg_replace('#([\t\n]+|<br \/>)#', ' ', $e->getMessage());
            $this->message .= "\n\nTrace:\n\n" . $e->getTraceAsString();
        } else {
            $this->message = 'An unexpected error occured. Please contact your administrator';
        }

        try {
            if ($this->templateFile) {
                $this->render();
            }
        } catch (\Exception $event) {
        }

        # Fallback to inline template in case something goes wrong with template engine
        if (!($tpl = $this->templateEngine)) {
            echo <<<HTML
<!DOCTYPE html>
<html>
	<head>
	<title>i-MSCP - internet Multi Server Control Panel - Fatal Error</title>
	<meta charset="UTF-8">
	<meta name="robots" content="nofollow, noindex">
	<link rel="icon" href="/themes/default/assets/images/favicon.ico">
	<link rel="stylesheet" href="/themes/default/assets/css/jquery-ui-black.css">
	<link rel="stylesheet" href="/themes/default/assets/css/simple.css">
	<!--[if (IE 7)|(IE 8)]>
		<link href="/themes/default/assets/css/ie78overrides.css?v=1425280612" rel="stylesheet">
	<![endif]-->
	<script src="/themes/default/assets/js/jquery/jquery.js"></script>
	<script src="/themes/default/assets/js/jquery/jquery-ui.js"></script>
	<script src="/themes/default/assets/js/imscp.js"></script>
	<script>
		$(function () { iMSCP.initApplication('simple'); });
	</script>
	</head>
	<body class="black">
		<div class="wrapper">
			<div id="content">
				<div id="message_container">
					<h1>An unexpected error occurred</h1>
					<pre>{$this->message}</pre>
					<div class="buttons">
						<a class="link_as_button" href="javascript:history.go(-1)" target="_self">Back</a>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
HTML;
        } else {
            $event->setParam('templateEngine', $tpl);
            layout_init($event);
            $tpl->parse('LAYOUT', 'layout');
            $tpl->prnt();
        }
    }

    /**
     * Render exception template file
     *
     * @return void
     */
    protected function render()
    {
        $tpl = new TemplateEngine();
        $tpl->defineDynamic([
            'layout' => 'shared/layouts/simple.tpl',
            'page' => $this->templateFile,
            'page_message' => 'layout',
            'backlink_block' => 'page'
        ]);
        $tpl->assign([
            'TR_PAGE_TITLE' => 'i-MSCP - internet Multi Server Control Panel - Fatal Error',
            'HEADER_BLOCK' => '',
            'BOX_MESSAGE_TITLE' => 'An unexpected error occurred',
            'PAGE_MESSAGE' => '',
            'BOX_MESSAGE' => $this->message,
            'BACK_BUTTON_DESTINATION' => 'javascript:history.go(-1)',
            'TR_BACK' => 'Back'
        ]);
        $tpl->parse('LAYOUT_CONTENT', 'page');
        $this->templateEngine = $tpl;
    }
}
