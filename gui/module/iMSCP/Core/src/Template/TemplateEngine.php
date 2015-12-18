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

namespace iMSCP\Core\Template;

use iMSCP\Core\Application;
use iMSCP\Core\Events;
use Zend\EventManager\EventManager;

/**
 * Class TemplateEngine
 * @package iMSCP\Core
 */
class TemplateEngine
{
    /**
     * @var array Template names
     */
    protected $templateNames = [];

    /**
     * @var array Template data
     */
    protected $templateData = [];

    /**
     * @var array Template options
     */
    protected $templateOptions = [];

    /**
     * @var array Dynamic template names
     */
    protected $dynamicTemplateNames = [];

    /**
     * @var array Dynamic template data
     */
    protected $dynamicTemplateData = [];

    /**
     * @var array Dynamic template options
     */
    protected $dynamicTemplateOptions = [];

    /**
     * @var array Dynamic template values
     */
    protected $dynamicTemplateValues = [];

    /**
     * @var array Namespaces
     */
    protected $namespaces = [];

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * Templates root directory
     *
     * @var string
     */
    protected $templateRootDir = '.';

    /**
     * @var string Template start tag
     */
    protected $templateStartTag = '<!-- ';

    /**
     * @var string Template end tag
     */
    protected $templateEndTag = ' -->';

    /**
     * @var string Template start tag name
     */
    protected $TemplateStartTagName = 'BDP: ';

    /**
     * @var string Template end tag name
     */
    protected $templateEndTagName = 'EDP: ';

    /**
     * @var string Template name regexp
     */
    protected $templateNameRegexp = '([a-z0-9][a-z0-9\_]*)';

    /**
     * @var string Template start regexp
     */
    protected $templateStartRegexp;

    /**
     * @var string Template end regexp
     */
    protected $templateEndRegexp;

    /**
     * @var string
     */
    protected $templateIncludeRegexp;

    /**
     * @var string
     */
    protected $lastParsed = '';

    /**
     * @var array
     */
    protected $stack = [];

    /**
     * @var int
     */
    protected $sp = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        $application = Application::getInstance();
        $config = $application->getConfig();
        $this->eventManager = $application->getEventManager();

        $this->setTemplateRootDir($config['ROOT_TEMPLATE_PATH']);

        $this->templateStartRegexp = '/';
        $this->templateStartRegexp .= $this->templateStartTag;
        $this->templateStartRegexp .= $this->TemplateStartTagName;
        $this->templateStartRegexp .= $this->templateNameRegexp;
        $this->templateStartRegexp .= $this->templateEndTag . '/';

        $this->templateEndRegexp = '/';
        $this->templateEndRegexp .= $this->templateStartTag;
        $this->templateEndRegexp .= $this->templateEndTagName;
        $this->templateEndRegexp .= $this->templateNameRegexp;
        $this->templateEndRegexp .= $this->templateEndTag . '/';

        $this->templateIncludeRegexp = '~' . $this->templateStartTag . 'INCLUDE "([^\"]+)"' . $this->templateEndTag . '~m';
    }

    /**
     * Sets templates root directory
     *
     * @param string $templateRootDir Template root directory
     * @return void
     */
    public function setTemplateRootDir($templateRootDir)
    {
        if (!is_dir($templateRootDir)) {
            throw new \InvalidArgumentException('TemplateEngine::setRootDir expects a valid directory.');
        }

        $this->templateRootDir = $templateRootDir;
    }

    /**
     *
     * Defines one or more static templates
     *
     * @param string|array $template A template name or an array where the keys are template names and values, the template files
     * @param string $templateFile template value (only relevant when $templateName is a string)
     * @return void
     */
    public function define($template, $templateFile = '')
    {
        if (is_array($template)) {
            foreach ($template as $k => $v) {
                $this->templateNames[$k] = $v;
                $this->templateData[$k] = '';
                $this->templateOptions[$k] = '';
            }
        } else {
            $this->templateNames[$template] = $templateFile;
            $this->templateData[$template] = '';
            $this->templateOptions[$template] = '';
        }
    }

    /**
     * Defines one or more dynamic templates
     *
     * @param string|array $template A template name or an array where the keys are template names and values, the template files
     * @param string $templateFile template value (only relevant when $template is a string)
     * @return void
     */
    public function defineDynamic($template, $templateFile = '')
    {
        if (is_array($template)) {
            foreach ($template as $k => $v) {
                $this->dynamicTemplateNames[$k] = $v;
                $this->dynamicTemplateData[$k] = '';
                $this->dynamicTemplateOptions[$k] = '';
            }
        } else {
            $this->dynamicTemplateNames[$template] = $templateFile;
            $this->dynamicTemplateData[$template] = '';
            $this->dynamicTemplateOptions[$template] = '';
        }
    }

    /**
     * Define one or more static inline templates
     *
     * @param string|array $template A template name or an array where the keys are template names and values, the template content
     * @param string $templateContent template content (only relevant when $template is a string)
     * @return void
     */
    public function defineNoFile($template, $templateContent = '')
    {
        if (is_array($template)) {
            foreach ($template as $k => $v) {
                $this->templateNames[$k] = '_no_file_';
                $this->templateData[$k] = $v;
                $this->templateOptions[$k] = '';
            }
        } else {
            $this->templateNames[$template] = '_no_file_';
            $this->templateData[$template] = $templateContent;
            $this->templateOptions[$template] = '';
        }
    }

    /**
     * Define one or more dynamic inline template
     *
     * @param string|array $template A template name or an array where the keys are template names and values, the template content
     * @param string $templateContent template content (only relevant when $template is a string)
     * @return void
     */
    public function defineNoFileDynamic($template, $templateContent = '')
    {
        if (is_array($template)) {
            foreach ($template as $k => $v) {
                $this->dynamicTemplateNames[$k] = '_no_file_';
                $this->dynamicTemplateData[$k] = $v;
                $this->dynamicTemplateData[strtoupper($k)] = $v;
                $this->dynamicTemplateOptions[$k] = '';
            }
        } else {
            $this->dynamicTemplateNames[$template] = '_no_file_';
            $this->dynamicTemplateData[$template] = $templateContent;
            $this->dynamicTemplateData[strtoupper($template)] = $templateContent;
            $this->dynamicTemplateOptions[$template] = '';
        }
    }

    /**
     * Assign value(s) to the given namespace(s)
     *
     * @param string|array $namespace A namespace or an array where the keysare namespaces and values, the namespace values
     * @param string $value Namespace value (only relevant when $namespace is a string)
     * @return void
     */
    public function assign($namespace, $value = '')
    {
        if (is_array($namespace)) {
            foreach ($namespace as $k => $v) {
                $this->namespaces[$k] = $v;
            }
        } else {
            $this->namespaces[$namespace] = $value;
        }
    }

    /**
     * Unassign data for the given namespace(s)
     *
     * @param @param string|array $namespace A namespace or an array where the keys are namespaces and values, the
     *                                       namespace values
     * @return void
     */
    public function unsign($namespace)
    {
        if (is_array($namespace)) {
            foreach ($namespace as $key => $value) {
                unset($this->namespaces[$key]);
            }
        } else {
            unset($this->namespaces[$namespace]);
        }
    }

    /**
     * Is the given namespace defined?
     *
     * @param string $namespace namespace
     * @return boolean TRUE if the namespace was define, FALSE otherwise
     */
    public function isNamespace($namespace)
    {
        return isset($this->namespaces[$namespace]);
    }

    /**
     * Parse the given template namespace
     *
     * @param string $parentTemplateName
     * @param string $templateName
     */
    public function parse($parentTemplateName, $templateName)
    {
        if (!preg_match('/[A-Z0-9][A-Z0-9\_]*/', $parentTemplateName)) {
            return;
        }

        if (!preg_match('/[A-Za-z0-9][A-Za-z0-9\_]*/', $templateName)) {
            return;
        }

        $addFlag = false;

        if (preg_match('/^\./', $templateName)) {
            $templateName = substr($templateName, 1);
            $addFlag = true;
        }

        if (
            isset($this->templateNames[$templateName]) &&
            (
                $this->templateNames[$templateName] == '_no_file_' ||
                stripos($this->templateNames[$templateName], '.tpl') !== false
            )
        ) { // static NO FILE - static FILE
            if ($this->templateData[$templateName] == '') {
                $this->templateData[$templateName] = $this->getFile($this->templateNames[$templateName]);
            }

            if ($addFlag && isset($this->namespaces[$parentTemplateName])) {
                $this->namespaces[$parentTemplateName] .= $this->substituteDynamic($this->templateData[$templateName]);
            } else {
                $this->namespaces[$parentTemplateName] = $this->substituteDynamic($this->templateData[$templateName]);
            }

            $this->lastParsed = $this->namespaces[$parentTemplateName];
        } elseif (
            @$this->dynamicTemplateNames[$templateName] == '_no_file_' ||
            stripos(@$this->dynamicTemplateNames[$templateName], 'tpl') !== false ||
            $this->findOrigin($templateName)
        ) { // dynamic NO FILE - dynamic FILE
            if (!$this->parseDynamic($parentTemplateName, $templateName, $addFlag)) {
                return;
            }

            $this->lastParsed = $this->namespaces[$parentTemplateName];
        } else {
            if ($addFlag && isset($this->namespaces[$parentTemplateName])) {
                $this->namespaces[$parentTemplateName] .= $this->namespaces[$templateName];
            } else {
                $this->namespaces[$parentTemplateName] = @$this->namespaces[$templateName];
            }
        }
    }

    /**
     *
     * @param string $pname
     * @param string $tname
     * @param bool $addFlag
     * @return bool
     */
    public function parseDynamic($pname, $tname, $addFlag)
    {
        $child = false;
        $parent = '';

        if (
            stripos($this->dynamicTemplateNames[$tname], 'tpl') === false &&
            strpos($this->dynamicTemplateNames[$tname], '_no_file_') === false
        ) {
            $child = true;
            $parent = $this->findOrigin($tname);

            if (!$parent) {
                return false;
            }
        }

        if ($child) {
            $swap = $parent;
            $parent = $tname;
            $tname = $swap;
        }

        if (empty($this->dynamicTemplateData[$tname])) {
            $this->dynamicTemplateData[$tname] = $this->getFile($this->dynamicTemplateNames[$tname]);
        }

        if (!preg_match('/d\_/', $this->dynamicTemplateOptions[$tname])) {
            $this->dynamicTemplateOptions[$tname] .= 'd_';
            $tpl_origin = $this->dynamicTemplateData[$tname];
            $this->dynamicTemplateData[$tname] = $this->devideDynamic($tpl_origin);
        }

        if ($child) {
            $swap = $parent;
            $tname = $swap;
        }

        if ($addFlag) {
            $safe = isset($this->namespaces[$pname]) ? $this->namespaces[$pname] : '';
            $this->namespaces[$pname] = $safe . ($this->substituteDynamic($this->dynamicTemplateData[$tname]));
        } else {
            $this->namespaces[$pname] = $this->substituteDynamic($this->dynamicTemplateData[$tname]);
        }

        return true;
    }

    /**
     *
     * @param string $pname
     * @return void
     */
    public function fastPrint($pname = '')
    {
        if ($pname) {
            $this->prnt($pname);
            return;
        }

        $this->prnt();
    }

    /**
     *
     * @param null|string $parentTemplateName
     * @return void
     */
    public function prnt($parentTemplateName = null)
    {
        if ($parentTemplateName) {
            echo $this->namespaces[$parentTemplateName];
            return;
        }

        echo $this->lastParsed;
    }

    /**
     * Returns last parse result
     *
     * @return string
     */
    public function getLastParseResult()
    {
        return $this->lastParsed;
    }

    /**
     * Replaces last parse result with given content.
     *
     * @param string $newContent New content
     * @param string $namespace Namespace
     * @return TemplateEngine Provides fluent interface, returns self
     */
    public function replaceLastParseResult($newContent, $namespace = null)
    {
        $this->lastParsed = (string)$newContent;
        if (isset($this->namespaces[$namespace])) {
            $this->namespaces[$namespace] = $newContent;
        }

        return $this;
    }

    /**
     * Load the given template file
     *
     * @param string|array $templateFile Template file path or an array where the second item contain the template file path
     * @return mixed|string
     */
    protected function getFile($templateFile)
    {
        static $parentTplDir = null;

        if (!is_array($templateFile)) {
            $this->eventManager->trigger(Events::onBeforeAssembleTemplateFiles, $this, [
                'templatePath' => $this->templateRootDir . '/' . $templateFile
            ]);
        } else { // INCLUDED file
            $templateFile = ($parentTplDir !== null) ? $parentTplDir . '/' . $templateFile[1] : $templateFile[1];
        }

        if (!$this->isSafe($templateFile)) {
            throw new \RuntimeException(sprintf(
                'Could not find the %s template file', $this->templateRootDir . '/' . $templateFile), 404
            );
        }

        $prevParentTplDir = $parentTplDir;
        $parentTplDir = dirname($templateFile);

        $this->eventManager->trigger(Events::onBeforeLoadTemplateFile, $this, [
            'templatePath' => $this->templateRootDir . '/' . $templateFile
        ]);

        // Evaluate template (since 1.3.x)
        //$fileContent = file_get_contents($this->root_dir . '/' . $fname);
        ob_start();
        include($this->templateRootDir . '/' . $templateFile);
        $fileContent = ob_get_clean();

        $this->eventManager->trigger(Events::onAfterLoadTemplateFile, $this, ['templateContent' => $fileContent]);

        $fileContent = preg_replace_callback($this->templateIncludeRegexp, [$this, 'getFile'], $fileContent);
        $parentTplDir = $prevParentTplDir;

        $this->eventManager->trigger(Events::onAfterAssembleTemplateFiles, $this, ['templateContent' => $fileContent]);
        return $fileContent;
    }

    /**
     * @param string $templateFile
     * @return bool
     */
    protected function isSafe($templateFile)
    {
        return (file_exists($this->templateRootDir . '/' . $templateFile)) ? true : false;
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function substituteDynamic($data)
    {
        $this->sp = 0;
        $startFrom = -1;
        $curlBegin = substr($data, (int)'{', $startFrom);

        if ($curlBegin) {
            $this->stack[$this->sp++] = ['{', $curlBegin];
            $curl = $this->findNextCurl($data, $startFrom);

            while ($curl) {
                if ($curl[0] == '{') {
                    $this->stack[$this->sp++] = $curl;
                    $startFrom = $curl[1];
                } else {
                    $curlEnding = $curl[1];

                    if ($this->sp > 0) {
                        $curl = $this->stack [--$this->sp];
                        // check for empty stack must be done HERE !
                        $curlBegin = $curl[1];

                        if ($curlBegin < $curlEnding + 1) {
                            $varName = substr($data, $curlBegin + 1, $curlEnding - $curlBegin - 1);

                            // The whole WORK goes here :)
                            if (preg_match('/[A-Z0-9][A-Z0-9\_]*/', $varName)) {
                                if (isset($this->namespaces[$varName])) {
                                    $data = substr_replace(
                                        $data, $this->namespaces[$varName], $curlBegin, $curlEnding - $curlBegin + 1
                                    );
                                    $startFrom = $curlBegin - 1;
                                    // new value may also begin with '{'
                                } elseif (isset($this->dynamicTemplateData[$varName])) {
                                    $data = substr_replace(
                                        $data, $this->dynamicTemplateData[$varName], $curlBegin, $curlEnding - $curlBegin + 1
                                    );
                                    $startFrom = $curlBegin - 1;
                                    // new value may also begin with '{'
                                } else {
                                    $startFrom = $curlBegin;
                                    // no suitable value found -> go forward
                                }
                            } else {
                                $startFrom = $curlBegin;
                                // go forward, we have {no variable} here.
                            }
                        } else {
                            $startFrom = $curlEnding;
                            // go forward, we have {} here.
                        }
                    } else {
                        $startFrom = $curlEnding;
                    }
                }

                $curl = $this->findNextCurl($data, $startFrom);
            }

            return $data;
        }

        return $data;
    }

    /**
     * Finds the next pair of curly brakets
     *
     * @param string $data
     * @param int $spos
     * @return array|bool
     */
    protected function findNextCurl($data, $spos)
    {
        $curlBegin = strpos($data, '{', $spos + 1);
        $curlEnding = strpos($data, '}', $spos + 1);

        if ($curlBegin) {
            if ($curlEnding) {
                if ($curlBegin < $curlEnding) {
                    return ['{', $curlBegin];
                }

                return ['}', $curlEnding];
            }

            return ['{', $curlBegin];
        }

        if ($curlEnding) {
            return ['}', $curlEnding];
        }

        return false;
    }

    /**
     *
     * @param string $tname
     * @return bool
     */
    protected function findOrigin($tname)
    {
        if (!@$this->dynamicTemplateNames[$tname]) {
            return false;
        }

        while (
            stripos($this->dynamicTemplateNames[$tname], 'tpl') === false &&
            strpos($this->dynamicTemplateNames[$tname], '_no_file_') === false
        ) {
            $tname = $this->dynamicTemplateNames[$tname];
        }

        return $tname;
    }


    /**
     *
     * @param string $data
     * @return mixed
     */
    protected function devideDynamic($data)
    {
        $startFrom = -1;
        $tag = $this->findNext($data, $startFrom);

        while ($tag) {
            if ($tag[1] == 'b') {
                $this->stack[$this->sp++] = $tag;
                $startFrom = $tag[3];
            } else {
                $templateName = $tag[0];
                $tpl_eb_pos = $tag[2];
                $tpl_ee_pos = $tag[3];
                $tag = $this->stack [--$this->sp];
                $tpl_bb_pos = $tag[2];
                $tpl_be_pos = $tag[3];

                $this->dynamicTemplateData[strtoupper($templateName)] = substr($data, $tpl_be_pos + 1, $tpl_eb_pos - $tpl_be_pos - 1);
                $this->dynamicTemplateData[$templateName] = substr($data, $tpl_be_pos + 1, $tpl_eb_pos - $tpl_be_pos - 1);
                $data = substr_replace($data, '{' . strtoupper($templateName) . '}', $tpl_bb_pos, $tpl_ee_pos - $tpl_bb_pos + 1);
                $startFrom = $tpl_bb_pos + strlen("{" . $templateName . "}") - 1;
            }

            $tag = $this->findNext($data, $startFrom);
        }

        return $data;
    }

    /**
     * Find next dynamic block
     *
     * @param string $data Data in which search is made
     * @param int $spos Position from which starting to search
     * @return array|bool
     */
    protected function findNext($data, $spos)
    {
        do {
            if (false === ($tagStartPos = strpos($data, $this->templateStartTag, $spos + 1))) {
                return false;
            }

            if (false === ($tagEndPos = strpos($data, $this->templateEndTag, $tagStartPos + 1))) {
                return false;
            }

            $length = $tagEndPos + strlen($this->templateEndTag) - $tagStartPos;
            $tag = substr($data, $tagStartPos, $length);

            if ($tag) {
                if (preg_match($this->templateStartRegexp, $tag, $matches)) {
                    return [$matches[1], 'b', $tagStartPos, $tagEndPos + strlen($this->templateEndTag) - 1];
                } elseif (preg_match($this->templateEndRegexp, $tag, $matches)) {
                    return [$matches[1], 'e', $tagStartPos, $tagEndPos + strlen($this->templateEndTag) - 1];
                } else {
                    $spos = $tagEndPos;
                }
            } else {
                return false;
            }
        } while (true);

        return false;
    }
}
