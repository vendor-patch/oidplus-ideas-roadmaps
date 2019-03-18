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

function raChangeContactDataFormOnSubmit() {
    $.ajax({
      url: "action.php",
      type: "POST",
      data: {
        action: "change_ra_data",
        email: $("#email").val(),
        ra_name: $("#ra_name").val(),
        organization: $("#organization").val(),
        office: $("#office").val(),
        personal_name: $("#personal_name").val(),
        privacy: $("#privacy").is(":checked") ? 1 : 0,
        street: $("#street").val(),
        zip_town: $("#zip_town").val(),
        country: $("#country").val(),
        phone: $("#phone").val(),
        mobile: $("#mobile").val(),
        fax: $("#fax").val()
      },
      success: function(data) {
                        if (data != "OK") {
                                alert("Error: " + data);
                        } else {
				alert("Done");
                                //document.location = '?goto=oidplus:system';
                                //reloadContent();
                        }
      }
  });
  return false;
}
