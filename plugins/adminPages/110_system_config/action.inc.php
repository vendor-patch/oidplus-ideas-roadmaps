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

if ($_POST["action"] == "config_update") {
	$handled = true;

	if (!OIDplus::authUtils()::isAdminLoggedIn()) {
		die('You need to log in as administrator.');
	}

	$name = $_POST['name'];
	$value = $_POST['value'];

	if (!OIDplus::db()->query("update ".OIDPLUS_TABLENAME_PREFIX."config set value = '".OIDplus::db()->real_escape_string($value)."' where name = '".OIDplus::db()->real_escape_string($name)."'")) {
		die(OIDplus::db()->error());
	}

	echo "OK";
}
