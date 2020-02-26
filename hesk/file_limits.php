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

define('IN_SCRIPT',1);
define('HESK_PATH','./');

// Get all the required files and functions
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML; 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<title><?php echo $hesklang['ful']; ?></title>
<meta http-equiv="Content-Type" content="text/html;charset=<?php echo $hesklang['ENCODING']; ?>" />
<style type="text/css">
body
{
	margin:5px 5px;
	padding:0;
	background:#fff;
	color: black;
	font : 68.8%/1.5 Verdana, Geneva, Arial, Helvetica, sans-serif;
	text-align:left;
}

p
{
	color : black;
	font-family : Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 1.0em;
}
h3
{
	color : #AF0000;
	font-family : Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	font-size: 1.0em;
	text-align:left;
}
</style>
</head>
<body>

<h3><?php echo $hesklang['ful']; ?></h3>

<table border="0" cellspacing="1" cellpadding="3">
<tr>
<td valign="top">&raquo;</td>
<td valign="top"><?php echo $hesklang['nat']; ?> <b><?php echo $hesk_settings['attachments']['max_number']; ?></b></td>
</tr>
<tr>
<td valign="top">&raquo;</td>
<td valign="top"><?php echo $hesklang['mfs']; ?> <b><?php echo hesk_formatBytes($hesk_settings['attachments']['max_size']); ?></b></td>
</tr>
<tr>
<td valign="top">&raquo;</td>
<td valign="top"><?php echo $hesklang['ufl']; ?>
<p><?php echo implode(', ', $hesk_settings['attachments']['allowed_types']); ?></p>
</td>
</tr>
</table>

<p align="center"><a href="#" onclick="Javascript:window.close()"><?php echo $hesklang['cwin']; ?></a></p>

<p>&nbsp;</p>

</body>

</html>
