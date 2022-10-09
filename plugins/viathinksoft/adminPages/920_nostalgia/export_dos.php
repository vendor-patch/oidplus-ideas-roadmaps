<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

header('Content-Type:text/html; charset=UTF-8');

require_once __DIR__ . '/../../../../includes/oidplus.inc.php';

set_exception_handler(array('OIDplusGui', 'html_exception_handler'));

@set_time_limit(0);

OIDplus::init(true);

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_OIDplusPageAdminNostalgia', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
}

if (!OIDplus::authUtils()->isAdminLoggedIn()) {
	throw new OIDplusException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')));
}

if (!class_exists('ZipArchive')) {
	throw new OIDplusException(_L('The PHP extension "ZipArchive" needs to be installed to create a ZIP archive with an included database. Otherwise, you can just download the plain program without data.'));
}

$dos_ids = array();
$parent_oids = array();
$i = 0;
$dos_ids[''] = '00000000';
$parent_oids[''] = '';

$dos_ids[''] = str_pad(strval($i++), 8, '0', STR_PAD_LEFT);
$res = OIDplus::db()->query("select * from ###objects where id like 'oid:%' order by ".OIDplus::db()->natOrder('id'));
while ($row = $res->fetch_object()) {
	$oid = substr($row->id, strlen('oid:'));
	$parent_oid = substr($row->parent, strlen('oid:'));
	$dos_ids[$oid] = str_pad(strval($i++), 8, '0', STR_PAD_LEFT);
	if ($parent_oid == '') {
		$parent_oids[$oid] = '';
	} else {
		$parent_oids[$oid] = $parent_oid;
	}
}

$tmp_file = OIDplus::localpath().'userdata/dos_export.zip';

$zip = new ZipArchive();
if ($zip->open($tmp_file, ZipArchive::CREATE)!== true) {
	throw new OIDplusException("cannot open <$tmp_file>");
}

function make_line($command, $data) {
	return $command.$data."\r\n";
}

// https://github.com/danielmarschall/oidplus_dos/blob/master/OIDFILE.PAS
define('CMD_VERSION',         'VERS');
define('CMD_OWN_ID',          'SELF');
define('CMD_PARENT',          'SUPR');
define('CMD_CHILD',           'CHLD');
define('CMD_ASN1_IDENTIFIER', 'ASN1');
define('CMD_UNICODE_LABEL',   'UNIL');
define('CMD_DESCRIPTION',     'DESC');

foreach ($dos_ids as $oid => $dos_id) {
	$cont = '';

	$cont .= make_line(CMD_VERSION, 2022);

	$cont .= make_line(CMD_OWN_ID, $dos_id.$oid);

	$parent_oid = $parent_oids[$oid];
	$parent_id = $dos_ids[$parent_oid];
	$cont .= make_line(CMD_PARENT, $parent_id.$parent_oid);

	foreach ($parent_oids as $child_oid => $parent_oid) {
		if ($child_oid == '') continue;
		if ($parent_oid == $oid) {
			$child_id = $dos_ids[$child_oid];
			$cont .= make_line(CMD_CHILD, $child_id.$child_oid);
		}
	}

	$res = OIDplus::db()->query("select * from ###asn1id where oid = 'oid:$oid'");
	while ($row = $res->fetch_object()) {
		$asn1 = $row->name;
		$cont .= make_line(CMD_ASN1_IDENTIFIER, $asn1);
	}

	$res = OIDplus::db()->query("select * from ###iri where oid = 'oid:$oid'");
	while ($row = $res->fetch_object()) {
		$iri = $row->name;
		$cont .= make_line(CMD_UNICODE_LABEL, $iri);
	}

	if ($oid == '') {
		// TODO: Split our OIDplus root OIDs into the real OID tree (1, 1.3, 1.3.6, ...)
		$cont .= make_line(CMD_DESCRIPTION, 'Here, you can find the root OIDs');
	} else {
		$res = OIDplus::db()->query("select * from ###objects where id = 'oid:$oid';");
		$row = $res->fetch_object();
		$desc_ary1 = handleDesc($row->title);
		$desc_ary2 = handleDesc($row->description);
		$desc_ary = array_merge($desc_ary1, $desc_ary2);
		$prev_line = '';
		foreach ($desc_ary as $line_idx => $line) {
			if ($line == $prev_line) continue;
			if ($line_idx >= 10/*DESCEDIT_LINES*/) break;
			$cont .= make_line(CMD_DESCRIPTION, $line);
			$prev_line = $line;
		}
	}

	//echo "****$dos_id.OID\r\n";
	//echo "$cont\r\n";

	$zip->addFromString("$dos_id.OID", $cont);
}

$exe_url = 'https://github.com/danielmarschall/oidplus_dos/raw/master/OIDPLUS.EXE';
$exe = url_get_contents($exe_url);
if (!$exe) {
	throw new OIDplusException(_L("Cannot download the binary file from GitHub (%1)", $exe_url));
}
$zip->addFromString('OIDPLUS.EXE', $exe);

$zip->close();

if (!headers_sent()) {
	header('Content-Type: application/zip');
	header('Content-Disposition: attachment; filename=oidplus_dos.zip');
	readfile($tmp_file);
}

unlink($tmp_file);

OIDplus::invoke_shutdown();

# ---

function handleDesc($desc) {
	$desc = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $desc); // br2nl
	$desc = strip_tags($desc);
	$desc = str_replace('&nbsp;', ' ', $desc);
	$desc = html_entity_decode($desc);
	$desc = str_replace("\r", "", $desc);
	$desc = str_replace("\n", "  ", $desc);
	$desc = str_replace("\t", "  ", $desc);
	$desc = trim($desc);
	$desc_ary = explode("\r\n", wordwrap($desc, 75, "\r\n", true));
	if (implode('',$desc_ary) == '') $desc_ary = array();
	return $desc_ary;
}
