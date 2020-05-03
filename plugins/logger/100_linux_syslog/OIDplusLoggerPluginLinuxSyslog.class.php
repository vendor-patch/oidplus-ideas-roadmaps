<?php

/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class OIDplusLoggerPluginLinuxSyslog extends OIDplusLoggerPlugin {

	public static function available(&$reason)/*: bool*/ {
		if (substr($_SERVER['OS'],0,7) === 'Windows') {
			$reason = 'Functionality not available on Windows';
			return false;
		}

		if (!file_exists('/var/log/syslog')) {
			$reason = "File /var/log/syslog not existing";
			return false;
		}

		if (@file_put_contents('/var/log/syslog', '') === false) {
			$reason = "File /var/log/syslog not writeable";
			return false;
		}

		$reason = '';
		return true;
	}

	public static function log($event, $users, $objects)/*: bool*/ {
		if (substr($_SERVER['OS'],0,7) === 'Windows') return false;

		if (!file_exists('/var/log/syslog')) return false;

		return @file_put_contents('/var/log/syslog', "$line\n") !== false;
	}
}
