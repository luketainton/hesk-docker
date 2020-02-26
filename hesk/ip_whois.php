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

// The purpose of this file is to hide ticket tracking ID in HTTP_REFERER when querying the IP WHOIS service

define('IN_SCRIPT',1);
define('HESK_PATH','./');

// Get all the required files and functions
require(HESK_PATH . 'hesk_settings.inc.php');

// Set correct Content-Type header
header('Content-Type: text/html; charset=utf-8');

// Most people will never see this text, so it is not included in text.php
// (saves resources as we don't need to call common.inc.php and load language)
$hesklang['1']='Page Redirection';
$hesklang['2']='If you are not redirected automatically, follow <a href="%s">this link</a>'; // %s will be replaced with URL

// Don't bother validating IP address format, just sure no invalid chars are sent
if ( isset($_GET['ip']) && preg_match('/^[0-9A-Fa-f\:\.]+$/', $_GET['ip']) )
{
	// Create redirect URL
	$url = str_replace('{IP}', str_replace(':', '%3A', $_GET['ip']), $hesk_settings['ip_whois']);

	// Redirect to the IP whois
	?>
	<!DOCTYPE HTML>
	<html lang="en-US">
		<head>
			<meta charset="UTF-8">
			<meta http-equiv="refresh" content="1;url=<?php echo $url; ?>">
			<script type="text/javascript">
			window.location.href = "<?php echo $url; ?>"
			</script>
			<title><?php echo $hesklang['1']; ?></title>
		</head>
		<body>
			<?php echo sprintf($hesklang['2'], $url); ?>
		</body>
	</html>
	<?php
}

// Exit
exit;
