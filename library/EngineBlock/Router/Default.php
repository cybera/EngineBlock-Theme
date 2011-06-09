<?php
/**
 * SURFconext EngineBlock
 *
 * LICENSE
 *
 * Copyright 2011 SURFnet bv, The Netherlands
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and limitations under the License.
 *
 * @category  SURFconext EngineBlock
 * @package
 * @copyright Copyright © 2010-2011 SURFnet SURFnet bv, The Netherlands (http://www.surfnet.nl)
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */

/**
 * Default router, expects a format like /module/controller/action and routes it.
 */
class EngineBlock_Router_Default extends EngineBlock_Router_Abstract
{
    const DEFAULT_MODULE_NAME     = "Default";
    const DEFAULT_CONTROLLER_NAME = "Index";
    const DEFAULT_ACTION_NAME     = "Index";

    /**
     * Note that this router interprets ////tekno as /tekno, NOT as /default/index/index/tekno
     *
     * @param  $uri
     * @return bool
     */
    public function route($uri)
    {
        $urlParts = preg_split('/\//', $uri, 0, PREG_SPLIT_NO_EMPTY);
        $urlPartsCount = count($urlParts);

        $module     = static::DEFAULT_MODULE_NAME;
        $controller = static::DEFAULT_CONTROLLER_NAME;
        $action     = static::DEFAULT_ACTION_NAME;
        $arguments  = array();

        // Note how we actually use the fall-through
        switch($urlPartsCount)
        {
            case 3:
                // /module/controller/action
                if ($urlParts[2]) {
                    $action     = $this->_convertHyphenatedToCamelCase($urlParts[2]);
                }

            case 2:
                // /module/controller => /module/controller/index
                if ($urlParts[1]) {
                    $controller = $this->_convertHyphenatedToCamelCase($urlParts[1]);
                }

                // /module => /module/index/index
            case 1:
                if ($urlParts[0]) {
                    $module     = $this->_convertHyphenatedToCamelCase($urlParts[0]);
                }

            case 0:
                break;

            default: // URL: /authentication/idp/single-sign-on/myidp/other/arguments/in/url
                if ($urlParts[2]) {
                    $action     = $this->_convertHyphenatedToCamelCase($urlParts[2]);
                }
                if ($urlParts[1]) {
                    $controller = $this->_convertHyphenatedToCamelCase($urlParts[1]);
                }
                if ($urlParts[0]) {
                    $module     = $this->_convertHyphenatedToCamelCase($urlParts[0]);
                }
                $arguments = array_slice($urlParts, 3);
        }

        $this->_moduleName      = $module;
        $this->_controllerName  = $controller;
        $this->_actionName      = $action;
 
        $this->setActionArguments($arguments);

        return true;
    }

    /**
     * Convert a-hyphenated-string to AHyphenatedString
     *
     * @param string $name
     * @return string
     */
    protected function _convertHyphenatedToCamelCase($name)
    {
        return implode(array_map('ucfirst', explode('-', $name)));
    }
}
