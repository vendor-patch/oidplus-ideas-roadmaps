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

plugin_combobox_change_callbacks.push(function(strPlugin) {
	$("#DBPLUGIN_PARAMS_SQLSRV")[0].style.display = (strPlugin == 'SQLSRV') ? "Block" : "None";
});

rebuild_callbacks.push(function() {
	var e = $("#db_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'SQLSRV') return true;

	$("#struct_cli_1")[0].innerHTML = '';
	$("#struct_cli_2")[0].innerHTML = '';
	$("#struct_1")[0].href = 'struct_empty.sql.php';
	$("#struct_2")[0].href = 'struct_with_examples.sql.php';

	error = false;

	// Check 1: host must not be empty
	if ($("#sqlsrv_host")[0].value.length == 0)
	{
		$("#sqlsrv_host_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a host name!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a host name!</i>'; // do not translate
		error = true;
	} else {
		$("#sqlsrv_host_warn")[0].innerHTML = '';
	}

	// Check 2: Database name must not be empty
	if ($("#sqlsrv_database")[0].value.length == 0)
	{
		$("#sqlsrv_database_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a database name!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a database name!</i>'; // do not translate
		error = true;
	} else {
		$("#sqlsrv_database_warn")[0].innerHTML = '';
	}

	$("#struct_1")[0].href = setupdir+'struct_empty.sql.php?prefix='+encodeURI($("#tablename_prefix")[0].value)+'&database='+encodeURI($("#sqlsrv_database")[0].value)+'&slang=mssql';
	$("#struct_2")[0].href = setupdir+'struct_with_examples.sql.php?prefix='+encodeURI($("#tablename_prefix")[0].value)+'&database='+encodeURI($("#sqlsrv_database")[0].value)+'&slang=mssql';
	$("#struct_cli_1")[0].innerHTML = '';
	$("#struct_cli_2")[0].innerHTML = '';

	return !error;
});

rebuild_config_callbacks.push(function() {
	var e = $("#db_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'SQLSRV') return '';

	return 'OIDplus::baseConfig()->setValue(\'SQLSRV_SERVER\',     ' + jsString($("#sqlsrv_host")[0].value) + ');<br>' +
	       'OIDplus::baseConfig()->setValue(\'SQLSRV_USERNAME\',   ' + jsString($("#sqlsrv_username")[0].value) + ');<br>' +
	       'OIDplus::baseConfig()->setValue(\'SQLSRV_PASSWORD\',   ' + _b64EncodeUnicode($("#sqlsrv_password")[0].value) + ');<br>' +
	       'OIDplus::baseConfig()->setValue(\'SQLSRV_DATABASE\',   ' + jsString($("#sqlsrv_database")[0].value) + ');<br>';
});
