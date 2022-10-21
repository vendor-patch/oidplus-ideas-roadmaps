<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusCaptchaPluginNone extends OIDplusCaptchaPlugin {

	public static function id(): string {
		return 'None';
	}

	public function isVisible(): bool {
		return false;
	}

	public function captchaDomHead() {
		// Here you can add styles and scripts to be included into the HTML <head> part
		return '<script>
		function oidplus_captcha_response() {
			return OIDplusCaptchaPluginNone.captchaResponse();
		}
		function oidplus_captcha_reset() {
			return OIDplusCaptchaPluginNone.captchaReset();
		}
		</script>';
	}

	public function captchaGenerate($header_text=null, $footer_text=null) {
		return '';
	}

	public function captchaVerify($params, $fieldname=null) {
		return true;
	}

	public static function setupHTML(): string {
		return '<div id="CAPTCHAPLUGIN_PARAMS_NONE">'.
		       '<p>'._L('No CAPTCHA will be used. Please note that your system will be prone to "Brute force" attacks.').'</p>'.
		       '</div>';
	}

}
