<?php
/**
 * Copyright [2011] [Mario Mueller]
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */
namespace Prowl\Security;

/**
 * Filter Interface
 * 
 * @author Mario Mueller <mario.mueller.work at gmail.com>
 * @version 1.0.0
 * @package Prowl
 * @subpackage Connector.Security
 */
interface Secureable {

	/**
	 * Filters a string.
	 * @abstract
	 * @param  String $sContentToFilter
	 * @return string
	 */
	public function filter($sContentToFilter);
}
