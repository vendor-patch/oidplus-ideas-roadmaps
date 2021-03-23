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

function doUpdateOIDplus() {
	show_waiting_anim();
	$.ajax({
		url: "ajax.php",
		type: "POST",
		data: {
			csrf_token:csrf_token,
			plugin:"1.3.6.1.4.1.37476.2.5.2.4.3.900",
			action: "update_now",
		},
		error:function(jqXHR, textStatus, errorThrown) {
			hide_waiting_anim();
			alert(_L("Error: %1",errorThrown));
		},
		success: function(data) {
			hide_waiting_anim();
			if ("error" in data) {
				alert(_L("Error: %1",data.error));
			} else if (data.status >= 0) {
				alert(_L("Update OK"));
				reloadContent();
				return;
			} else {
				alert(_L("Error: %1",data));
			}
			if ("content" in data) {
				$("#update_infobox").text(data.content);
			}
			$("#update_header").text(_L("Result of update"));
		},
		timeout:0 // infinite
	});
	return false;
}

