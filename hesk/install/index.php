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
define('HESK_PATH','../');

require(HESK_PATH . 'install/install_functions.inc.php');

// Reset installation steps
hesk_session_stop();

hesk_iHeader();
?>

<table border="0" width="100%">
<tr>
<td><a href="https://www.hesk.com"><img src="hesk.png" width="166" height="60" alt="Visit HESK.com" border="0" /></a></td>
<td align="center"><h3>Thank you for downloading HESK. Please choose an option below:</h3></td>
</tr>
</table>

<hr />

<form method="get" action="install.php">
<p align="center"><input type="submit" value="Click here to INSTALL HESK &raquo;" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /></p>
<p align="center">Install a new copy of HESK</p>
</form>

<hr />

<form method="get" action="update.php">
<p align="center"><input type="submit" value="Click here to UPDATE HESK &raquo;" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /></p>
<p align="center">Update existing HESK to version <?php echo HESK_NEW_VERSION; ?></p>
</form>

<?php
hesk_iFooter();
exit();
?>
