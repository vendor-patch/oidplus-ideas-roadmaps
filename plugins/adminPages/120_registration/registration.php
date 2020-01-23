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

require_once __DIR__ . '/../../../includes/oidplus.inc.php';

ob_start(); // allow cookie headers to be sent

header('Content-Type:text/html; charset=UTF-8');

OIDplus::init(true);

ob_start();

$step = 1;
$errors_happened = false;
$edits_possible = true;

?><!DOCTYPE html>
<html lang="en">

<head>
	<title>OIDplus Setup</title>
	<meta name="robots" content="noindex">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../../../setup/setup.css">
	<?php
	if (RECAPTCHA_ENABLED) {
	?>
	<script src="https://www.google.com/recaptcha/api.js"></script>
	<?php
	}
	?>
</head>

<body>

<h1>OIDplus Setup - Initial Settings</h1>

<p>Your database settings are correct.</p>

<p>The following settings need to be configured once.<br>
After setup is complete, you can change all these settings if required.</p>

<form method="POST" action="registration.php">
<input type="hidden" name="sent" value="1">

<?php
if (RECAPTCHA_ENABLED) {
	echo '<p><u>Step '.($step++).': Solve CAPTCHA</u></p>';
	echo '<noscript>';
	echo '<p><font color="red">You need to enable JavaScript to solve the CAPTCHA.</font></p>';
	echo '</noscript>';
	echo '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.RECAPTCHA_PUBLIC.'" }); </script>';
	echo '<p>Before logging in, please solve the following CAPTCHA</p>';
	echo '<p>If the CAPTCHA does not work (e.g. because of wrong keys, please run <a href="<?php echo OIDplus::system_url(); ?>setup/">setup part 1</a> again or edit includes/config.inc.php).</p>';
	echo '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.RECAPTCHA_PUBLIC.'"></div>';

	if (isset($_REQUEST['sent'])) {
		$secret=RECAPTCHA_PRIVATE;
		$response=$_POST["g-recaptcha-response"];
		$verify=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$response}");
		$captcha_success=json_decode($verify);
		if ($captcha_success->success==false) {
			echo '<p><font color="red"><b>CAPTCHA not sucessfully verified</b></font></p>';
			$errors_happened = true;
			$edits_possible = false;
		}
	}
}
?>

<p><u>Step <?php echo $step++; ?>: Authentificate</u></p>

<p>Please enter the administrator password you have entered before.</p>

<p><input type="password" name="admin_password" value=""> (<a href="<?php echo OIDplus::system_url(); ?>setup/">Forgot?</a>) <?php

if (isset($_REQUEST['sent'])) {
	if (!OIDplusAuthUtils::adminCheckPassword($_REQUEST['admin_password'])) {
		$errors_happened = true;
		$edits_possible = false;
		echo '<font color="red"><b>Wrong password</b></font>';
	}
}

?></p>

<?php
#------------------------
$do_edits = isset($_REQUEST['sent']) && $edits_possible;;
#------------------------
?>

<p><u>Step <?php echo $step++; ?>: Please enter the email address of the system administrator</u></p>

<input type="text" name="admin_email" value="<?php

$msg = '';
if (isset($_REQUEST['sent'])) {
	echo htmlentities($_REQUEST['admin_email']);
	if ($do_edits) {
		try {
			OIDplus::config()->setValue('admin_email', $_REQUEST['admin_email']);
		} catch (Exception $e) {
			$msg = $e->getMessage();
			$errors_happened = true;
		}
	}
} else {
	echo htmlentities(OIDplus::config()->getValue('admin_email'));
}

?>" size="25"><?php echo ' <font color="red"><b>'.$msg.'</b></font>'; ?>

<p><u>Step <?php echo $step++; ?>: What title should your Registration Authority / OIDplus instance have?</u></p>

<input type="text" name="system_title" value="<?php

$msg = '';
if (isset($_REQUEST['sent'])) {
	echo htmlentities($_REQUEST['system_title']);
	if ($do_edits) {
		try {
			OIDplus::config()->setValue('system_title', $_REQUEST['system_title']);
		} catch (Exception $e) {
			$msg = $e->getMessage();
			$errors_happened = true;
		}
	}
} else {
	echo htmlentities(OIDplus::config()->getValue('system_title'));
}

?>" size="50"><?php echo ' <font color="red"><b>'.$msg.'</b></font>'; ?>

<p><u>Step <?php echo $step++; ?>: Enable/Disable object type plugins</u></p>

<p>Which object types do you want to manage using OIDplus?</p>

<?php

$enabled_ary = array();

foreach (OIDplus::getRegisteredObjectTypes() as $ot) {
	echo '<input type="checkbox" name="enable_ot_'.$ot::ns().'" id="enable_ot_'.$ot::ns().'"';
	if (isset($_REQUEST['sent'])) {
	        if (isset($_REQUEST['enable_ot_'.$ot::ns()])) {
			echo ' checked';
			$enabled_ary[] = $ot::ns();
		}
	} else {
	        echo ' checked';
	}
	echo '> <label for="enable_ot_'.$ot::ns().'">'.htmlentities($ot::objectTypeTitle()).'</label><br>';
}

foreach (OIDplus::getDisabledObjectTypes() as $ot) {
	echo '<input type="checkbox" name="enable_ot_'.$ot::ns().'" id="enable_ot_'.$ot::ns().'"';
	if (isset($_REQUEST['sent'])) {
	        if (isset($_REQUEST['enable_ot_'.$ot::ns()])) {
			echo ' checked';
			$enabled_ary[] = $ot::ns();
		}
	} else {
	        echo ''; // <-- difference
	}
	echo '> <label for="enable_ot_'.$ot::ns().'">'.htmlentities($ot::objectTypeTitle()).'</label><br>';
}

$msg = '';
if ($do_edits) {
	try {
		OIDplus::config()->setValue('objecttypes_enabled', implode(';', $enabled_ary));
	} catch (Exception $e) {
		$msg = $e->getMessage();
		$errors_happened = true;
	}
}

echo ' <font color="red"><b>'.$msg.'</b></font>';

echo '<p><u>Step '.($step++).': System registration and automatic Publishing</u></p>';

echo file_get_contents(__DIR__ . '/info.tpl');

if (!function_exists('openssl_sign')) {
	echo '<p>OpenSSL plugin is missing in PHP. You cannot register your OIDplus instance.</p>';
} else {

	echo '<p>Privacy level:</p><select name="reg_privacy" id="reg_privacy">';

	# ---

	echo '<option value="0"';
	if (isset($_REQUEST['sent'])) {
		if (isset($_REQUEST['reg_privacy']) && ($_REQUEST['reg_privacy'] == 0)) echo ' selected';
	} else {
		if ((OIDplus::config()->getValue('reg_privacy') == 0) || !OIDplus::config()->getValue('reg_wizard_done')) {
			echo ' selected';
		} else {
			echo '';
		}
	}
	echo '>0 = Register to directory service and automatically publish RA/OID data at oid-info.com</option>';

	# ---

	echo '<option value="1"';
	if (isset($_REQUEST['sent'])) {
		if (isset($_REQUEST['reg_privacy']) && ($_REQUEST['reg_privacy'] == 1)) echo ' selected';
	} else {
		if ((OIDplus::config()->getValue('reg_privacy') == 1)) {
			echo ' selected';
		} else {
			echo '';
		}
	}
	echo '>1 = Only register to directory service</option>';

	# ---

	echo '<option value="2"';
	if (isset($_REQUEST['sent'])) {
		if (isset($_REQUEST['reg_privacy']) && ($_REQUEST['reg_privacy'] == 2)) echo ' selected';
	} else {
		if ((OIDplus::config()->getValue('reg_privacy') == 2)) {
			echo ' selected';
		} else {
			echo '';
		}
	}
	echo '>2 = Hide system</option>';

	# ---

	echo '</select>';

	$msg = '';
	if ($do_edits) {
		try {
			OIDplus::config()->setValue('reg_privacy', $_REQUEST['reg_privacy']);
		} catch (Exception $e) {
			$msg = $e->getMessage();
			$errors_happened = true;
		}
	}
	echo ' <font color="red"><b>'.$msg.'</b></font>';

	echo '<p><i>Privacy information:</i> This setting can always be changed in the administrator login / control panel.</p>';
	// TODO: describe what data is transmitted or link to a privacy statement
}

?>

<p><u>Submit</u></p>

<input type="submit" value="Save and start OIDplus!">

</form>

<?php

if (function_exists('openssl_sign')) {

?>

<p><u>Your OIDplus system ID (derived from the public key) is:</u></p>

1.3.6.1.4.1.37476.30.9.<b><?php
echo htmlentities(OIDplus::system_id());
?></b>

<p><u>Your public key is</u></p>

<?php

echo '<pre>'.htmlentities(OIDplus::config()->getValue('oidplus_public_key')).'</pre>';

}

?>

</body>

</html>

<?php

$cont = ob_get_contents();
ob_end_clean();

if ($do_edits && !$errors_happened)  {
	OIDplus::config()->setValue('reg_wizard_done', '1');
	header('Location:../../../');
} else {
	echo $cont;
}
