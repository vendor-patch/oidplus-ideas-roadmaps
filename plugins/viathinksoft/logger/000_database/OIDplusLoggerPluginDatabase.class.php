<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus\Plugins\Logger\Database;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusLogEvent;
use ViaThinkSoft\OIDplus\Core\OIDplusLoggerPlugin;
use ViaThinkSoft\OIDplus\Core\OIDplusLogTargetObject;
use ViaThinkSoft\OIDplus\Core\OIDplusLogTargetUser;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusLoggerPluginDatabase extends OIDplusLoggerPlugin {

	/**
	 * @param string $reason
	 * @return bool
	 */
	public function available(string &$reason): bool {
		$reason = '';
		return true;
	}

	/**
	 * @param OIDplusLogEvent $event
	 * @return bool
	 * @throws OIDplusException
	 */
	public function log(OIDplusLogEvent $event): bool {
		$addr = OIDplus::getClientIpAddress() ?: '';

		if (OIDplus::db()->transaction_supported() && (OIDplus::db()->transaction_level() > 0)) {
			// If a transaction is open, we must use it, otherwise we might get a deadlock if the isolated connection is waiting for the main connection which is in the transaction
			// (Happens with Backup Restore plugin)
			$db = OIDplus::db();
		} else {
			// Isolated connection is good for detecting the last insert ID...
			$db = OIDplus::dbIsolated();
		}

		$db->query("insert into ###log (addr, unix_ts, event) values (?, ?, ?)", array($addr, time(), $event->getMessage())); // TODO: why unix_ts? Why not a database DATETIME field?!
		$log_id = $db->insert_id();
		if ($log_id === 0) {
			$log_id = $db->getScalar("select max(id) as last_id from ###log");
			if (!$log_id) throw new OIDplusException(_L('Could not log event'));
		}

		$object_dupe_check = array();
		$user_dupe_check = array();
		foreach ($event->getTargets() as $target) {
			$severity = $target->getSeverity();
			if ($target instanceof OIDplusLogTargetObject) {
				$object = $target->getObject();
				if (in_array($object, $object_dupe_check)) continue;
				$object_dupe_check[] = $object;
				$db->query("insert into ###log_object (log_id, severity, object) values (?, ?, ?)", array((int)$log_id, (int)$severity, $object));
			} else if ($target instanceof OIDplusLogTargetUser) {
				$username = $target->getUsername();
				if (in_array($username, $user_dupe_check)) continue;
				$user_dupe_check[] = $username;
				$db->query("insert into ###log_user (log_id, severity, username) values (?, ?, ?)", array((int)$log_id, (int)$severity, $username));
			} else {
				assert(false);
			}
		}

		return true;
	}

}
