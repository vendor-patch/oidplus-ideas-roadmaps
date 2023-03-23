<?php

/*
 * OIDplus 2.0
 * Copyright 2023 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusAuthPluginVtsMcf extends OIDplusAuthPlugin {

	/**
	 * @param OIDplusRAAuthInfo $authInfo
	 * @param string $check_password
	 * @return bool
	 */
	public function verify(OIDplusRAAuthInfo $authInfo, string $check_password): bool {
		$authKey = $authInfo->getAuthKey();

		if (vts_crypt_version($authKey) != '0') {
			return vts_password_verify($check_password, $authKey);
		} else {
			return false;
		}
	}

	/**
	 * @param string $password
	 * @return OIDplusRAAuthInfo
	 * @throws OIDplusException
	 */
	public function generate(string $password): OIDplusRAAuthInfo {
		$calc_authkey = vts_password_hash($password, PASSWORD_VTS_MCF1, array(
			'algo' => 'sha3-512', // we can safely use it, because we have a pure-PHP implementation shipped with OIDplus
			'mode' => 'hmac'
		));
		return new OIDplusRAAuthInfo($calc_authkey);
	}

	/**
	 * @param string $reason
	 * @return bool
	 */
	public function availableForHash(string &$reason): bool {
		return function_exists('vts_password_hash');
	}

	/**
	 * @param string $reason
	 * @return bool
	 */
	public function availableForVerify(string &$reason): bool {
		return function_exists('vts_password_verify');
	}

}
