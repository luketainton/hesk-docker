<?php
/**
 *
 * This file is part of HESK - PHP Help Desk Software.
 *
 * (c) Copyright Klemen Stirn. All rights reserved.
 * https://www.hesk.com
 *
 * For the full copyright and license agreement information visit
 * https://www.hesk.com/eula.php
 *
 */

/* Check if this is a valid include */
if (!defined('IN_SCRIPT')) {die('Invalid attempt');}

// Override sensitive settings in the demo mode
$hesk_settings['db_host']               = $hesklang['hdemo'];
$hesk_settings['db_name']               = $hesklang['hdemo'];
$hesk_settings['db_user']               = $hesklang['hdemo'];
$hesk_settings['db_pass']               = $hesklang['hdemo'];
$hesk_settings['db_pfix']               = $hesklang['hdemo'];
$hesk_settings['url_key']               = $hesklang['hdemo'];
$hesk_settings['smtp_host_name']        = $hesklang['hdemo'];
$hesk_settings['smtp_user']             = $hesklang['hdemo'];
$hesk_settings['smtp_password']         = $hesklang['hdemo'];
$hesk_settings['pop3_host_name']        = $hesklang['hdemo'];
$hesk_settings['pop3_user']             = $hesklang['hdemo'];
$hesk_settings['pop3_password']         = $hesklang['hdemo'];
$hesk_settings['imap_host_name']        = $hesklang['hdemo'];
$hesk_settings['imap_user']             = $hesklang['hdemo'];
$hesk_settings['imap_password']         = $hesklang['hdemo'];
$hesk_settings['recaptcha_public_key']  = $hesklang['hdemo'];
$hesk_settings['recaptcha_private_key'] = $hesklang['hdemo'];
