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

define('LOAD_TABS',1);

// Make sure the install folder is deleted
if (is_dir(HESK_PATH . 'install')) {die('Please delete the <b>install</b> folder from your server for security reasons then refresh this page!');}

// Get all the required files and functions
require(HESK_PATH . 'hesk_settings.inc.php');

// Save the default language for the settings page before choosing user's preferred one
$hesk_settings['language_default'] = $hesk_settings['language'];
require(HESK_PATH . 'inc/common.inc.php');
$hesk_settings['language'] = $hesk_settings['language_default'];
require(HESK_PATH . 'inc/admin_functions.inc.php');
require(HESK_PATH . 'inc/setup_functions.inc.php');
hesk_load_database_functions();

hesk_session_start();
hesk_dbConnect();
hesk_isLoggedIn();

// Check permissions for this feature
hesk_checkPermission('can_man_settings');

// Load custom fields
require_once(HESK_PATH . 'inc/custom_fields.inc.php');

$help_folder = '../language/' . $hesk_settings['languages'][$hesk_settings['language']]['folder'] . '/help_files/';

$enable_save_settings   = 0;
$enable_use_attachments = 0;

// Print header
require_once(HESK_PATH . 'inc/header.inc.php');

// Print main manage users page
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');

// Demo mode? Hide values of sensitive settings
if ( defined('HESK_DEMO') )
{
	$hesk_settings['db_host']			= $hesklang['hdemo'];
	$hesk_settings['db_name']			= $hesklang['hdemo'];
	$hesk_settings['db_user']			= $hesklang['hdemo'];
	$hesk_settings['db_pass']			= $hesklang['hdemo'];
	$hesk_settings['db_pfix']			= $hesklang['hdemo'];
	$hesk_settings['smtp_host_name']	= $hesklang['hdemo'];
	$hesk_settings['smtp_user']			= $hesklang['hdemo'];
	$hesk_settings['smtp_password']		= $hesklang['hdemo'];
	$hesk_settings['pop3_host_name']	= $hesklang['hdemo'];
	$hesk_settings['pop3_user']			= $hesklang['hdemo'];
	$hesk_settings['pop3_password']		= $hesklang['hdemo'];
	$hesk_settings['imap_host_name']	= $hesklang['hdemo'];
	$hesk_settings['imap_user']			= $hesklang['hdemo'];
	$hesk_settings['imap_password']		= $hesklang['hdemo'];
	$hesk_settings['recaptcha_public_key']	= $hesklang['hdemo'];
	$hesk_settings['recaptcha_private_key']	= $hesklang['hdemo'];
}

/* This will handle error, success and notice messages */
hesk_handle_messages();


// Check file attachment limits
if ($hesk_settings['attachments']['use'] && ! defined('HESK_DEMO') )
{
    // If SMTP server is used, "From email" should match SMTP username
    if ($hesk_settings['smtp'] && strtolower($hesk_settings['smtp_user']) != strtolower($hesk_settings['noreply_mail']) && hesk_validateEmail($hesk_settings['smtp_user'], 'ERR', 0))
    {
        hesk_show_notice(sprintf($hesklang['from_warning'], $hesklang['email_noreply'], $hesklang['tab_1'], $hesk_settings['smtp_user']));
    }

    // If POP3 fetching is active, no user should have the same email address
    if ($hesk_settings['pop3'] && hesk_validateEmail($hesk_settings['pop3_user'], 'ERR', 0))
    {
        $res = hesk_dbQuery("SELECT `name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` WHERE `email` LIKE '".hesk_dbEscape($hesk_settings['pop3_user'])."'");

        if (hesk_dbNumRows($res) > 0)
        {
            hesk_show_notice(sprintf($hesklang['pop3_warning'], hesk_dbResult($res,0,0), $hesk_settings['pop3_user']) . "<br /><br />" . $hesklang['fetch_warning'], $hesklang['warn']);
        }
    }

    // If IMAP fetching is active, no user should have the same email address
    if ($hesk_settings['imap'] && hesk_validateEmail($hesk_settings['imap_user'], 'ERR', 0))
    {
        $res = hesk_dbQuery("SELECT `name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` WHERE `email` LIKE '".hesk_dbEscape($hesk_settings['imap_user'])."'");

        if (hesk_dbNumRows($res) > 0)
        {
            hesk_show_notice(sprintf($hesklang['imap_warning'], hesk_dbResult($res,0,0), $hesk_settings['imap_user']) . "<br /><br />" . $hesklang['fetch_warning'], $hesklang['warn']);
        }
    }
}
?>
<div class="main__content settings">
    <div class="settings__status">
        <h3><?php echo $hesklang['check_status']; ?></h3>
        <ul class="settings__status_list">
            <li>
                <div class="list--name"><?php echo $hesklang['v']; ?></div>
                <div class="list--status">
                    <?php echo $hesk_settings['hesk_version']; ?>
                    <?php
                    if ($hesk_settings['check_updates']) {
                        $latest = hesk_checkVersion();

                        if ($latest === true) {
                            echo ' - <span style="color:green">' . $hesklang['hud'] . '</span> ';
                        } elseif ($latest != -1) {
                            // Is this a beta/dev version?
                            if (strpos($hesk_settings['hesk_version'], 'beta') || strpos($hesk_settings['hesk_version'], 'dev') || strpos($hesk_settings['hesk_version'], 'RC')) {
                                echo ' <span style="color:darkorange">' . $hesklang['beta'] . '</span> '; ?><br><a href="https://www.hesk.com/update.php?v=<?php echo $hesk_settings['hesk_version']; ?>" target="_blank"><?php echo $hesklang['check4updates']; ?></a><?php
                            } else {
                                echo ' - <span style="color:darkorange;font-weight:bold">' . $hesklang['hnw'] . '</span> '; ?><br><a href="https://www.hesk.com/update.php?v=<?php echo $hesk_settings['hesk_version']; ?>" target="_blank"><?php echo $hesklang['getup']; ?></a><?php
                            }
                        } else {
                            ?> - <a href="https://www.hesk.com/update.php?v=<?php echo $hesk_settings['hesk_version']; ?>" target="_blank"><?php echo $hesklang['check4updates']; ?></a><?php
                        }
                    } else {
                        ?> - <a href="https://www.hesk.com/update.php?v=<?php echo $hesk_settings['hesk_version']; ?>" target="_blank"><?php echo $hesklang['check4updates']; ?></a><?php
                    }
                    ?>
                </div>
            </li>
            <li>
                <div class="list--name"><?php echo $hesklang['phpv']; ?></div>
                <div class="list--status"><?php echo defined('HESK_DEMO') ? $hesklang['hdemo'] : PHP_VERSION . ' ' . (function_exists('mysqli_connect') ? '(MySQLi)' : '(MySQL)'); ?></div>
            </li>
            <li>
                <div class="list--name"><?php echo $hesklang['mysqlv']; ?></div>
                <div class="list--status"><?php echo defined('HESK_DEMO') ? $hesklang['hdemo'] : hesk_dbResult( hesk_dbQuery('SELECT VERSION() AS version') ); ?></div>
            </li>
            <li>
                <div class="list--name">/hesk_settings.inc.php</div>
                <div class="list--status">
                    <?php
                    if (is_writable(HESK_PATH . 'hesk_settings.inc.php')) {
                        $enable_save_settings = 1;
                        echo '<span class="success">'.$hesklang['exists'].'</span>, <span class="success">'.$hesklang['writable'].'</span>';
                    } else {
                        echo '<span class="success">'.$hesklang['exists'].'</span>, <span class="error">'.$hesklang['not_writable'].'</span><br>'.$hesklang['e_settings'];
                    }
                    ?>
                </div>
            </li>
            <li>
                <div class="list--name">/<?php echo $hesk_settings['attach_dir']; ?></div>
                <div class="list--status">
                    <?php
                    if (is_dir(HESK_PATH . $hesk_settings['attach_dir'])) {
                        echo '<span class="success">'.$hesklang['exists'].'</span>, ';
                        if (is_writable(HESK_PATH . $hesk_settings['attach_dir'])) {
                            $enable_use_attachments = 1;
                            echo '<span class="success">'.$hesklang['writable'].'</span>';
                        } else {
                            echo '<span class="error">'.$hesklang['not_writable'].'</span><br>'.$hesklang['e_attdir'];
                        }
                    } else {
                        echo '<span class="error">'.$hesklang['no_exists'].'</span>, <span class="error">'.$hesklang['not_writable'].'</span><br>'.$hesklang['e_attdir'];
                    }
                    ?>
                </div>
            </li>
            <li>
                <div class="list--name">/<?php echo $hesk_settings['cache_dir']; ?></div>
                <div class="list--status">
                    <?php
                    if (is_dir(HESK_PATH . $hesk_settings['cache_dir'])) {
                        echo '<span class="success">'.$hesklang['exists'].'</span>, ';
                        if (is_writable(HESK_PATH . $hesk_settings['cache_dir'])) {
                            $enable_use_attachments = 1;
                            echo '<span class="success">'.$hesklang['writable'].'</span>';
                        } else {
                            echo '<span class="error">'.$hesklang['not_writable'].'</span><br>'.$hesklang['e_cdir'];
                        }
                    } else {
                        echo '<span class="error">'.$hesklang['no_exists'].'</span>, <span class="error">'.$hesklang['not_writable'].'</span><br>'.$hesklang['e_cdir'];
                    }
                    ?>
                </div>
            </li>
        </ul>
    </div>
    <script language="javascript" type="text/javascript"><!--
        function hesk_checkFields() {
            var d = document.form1;

            // DISABLE SUBMIT BUTTON
            d.submitbutton.disabled=true;

            return true;
        }

        function hesk_toggleLayer(nr,setto) {
            if (document.all)
                document.all[nr].style.display = setto;
            else if (document.getElementById)
                document.getElementById(nr).style.display = setto;
        }

        function checkRequiredEmail(field) {
            if (document.getElementById('s_require_email_0').checked && document.getElementById('s_email_view_ticket').checked)
            {
                if (field == 's_require_email_0' && confirm('<?php echo addslashes($hesklang['re_confirm1']); ?>'))
                {
                    document.getElementById('s_email_view_ticket').checked = false;
                    return true;
                }
                else if (field == 's_email_view_ticket' && confirm('<?php echo addslashes($hesklang['re_confirm2']); ?>'))
                {
                    document.getElementById('s_require_email_1').checked = true;
                    return true;
                }
                return false;
            }
            return true;
        }
        //-->
    </script>
    <form method="post" action="admin_settings_save.php" name="form1" onsubmit="return hesk_checkFields()">
        <div class="settings__form form">
            <section class="settings__form_block">
                <h3><?php echo $hesklang['tab_6']; ?></h3>
                <?php
                $on = '';
                $off = '';
                $onload_div = 'none';
                $onload_status = '';

                if ($hesk_settings['smtp'])
                {
                    $on = 'checked';
                    $onload_div = 'block';
                }
                else
                {
                    $off = 'checked';
                    $onload_status=' disabled ';
                }
                ?>
                <input type="hidden" name="tmp_smtp_host_name" value="<?php echo $hesk_settings['smtp_host_name']; ?>" />
                <input type="hidden" name="tmp_smtp_host_port" value="<?php echo $hesk_settings['smtp_host_port']; ?>" />
                <input type="hidden" name="tmp_smtp_timeout" value="<?php echo $hesk_settings['smtp_timeout']; ?>" />
                <input type="hidden" name="tmp_smtp_user" value="<?php echo $hesk_settings['smtp_user']; ?>" />
                <input type="hidden" name="tmp_smtp_password" value="<?php echo $hesk_settings['smtp_password']; ?>" />
                <input type="hidden" name="tmp_smtp_ssl" value="<?php echo $hesk_settings['smtp_ssl']; ?>" />
                <input type="hidden" name="tmp_smtp_tls" value="<?php echo $hesk_settings['smtp_tls']; ?>" />
                <div class="radio-group">
                    <h5>
                        <span><?php echo $hesklang['emlsend2']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#55','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="radio-list">
                        <div class="radio-custom">
                            <input type="radio" id="s_smtp0" name="s_smtp" value="0"
                                   onclick="hesk_attach_disable(new Array('s1','s2','s3','s4','s5','s6','s7','s8','s9'))"
                                   onchange="hesk_toggleLayerDisplay('smtp_settings');" <?php echo $off; ?>>
                            <label for="s_smtp0"><?php echo $hesklang['phpmail']; ?></label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="s_smtp1" name="s_smtp" value="1"
                                   onclick="hesk_attach_enable(new Array('s1','s2','s3','s4','s5','s6','s7','s8','s9'))"
                                   onchange="hesk_toggleLayerDisplay('smtp_settings');" <?php echo $on; ?>>
                            <label for="s_smtp1"><?php echo $hesklang['smtp']; ?></label>
                        </div>
                    </div>
                </div>
                <div id="smtp_settings" style="display:<?php echo $onload_div; ?>; margin-bottom: 20px">
                    <div class="form-group">
                        <label>
                            <span><?php echo $hesklang['smtph']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#55','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="text" id="s1" class="form-control" name="s_smtp_host_name" maxlength="255" value="<?php echo $hesk_settings['smtp_host_name']; ?>" <?php echo $onload_status; ?>>
                    </div>
                    <div class="form-group">
                        <label>
                            <span><?php echo $hesklang['smtpp']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#55','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="text" id="s2" class="form-control" name="s_smtp_host_port" maxlength="255" value="<?php echo $hesk_settings['smtp_host_port']; ?>" <?php echo $onload_status; ?>>
                    </div>
                    <div class="form-group">
                        <label>
                            <span><?php echo $hesklang['smtpt']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#55','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="text" id="s3" class="form-control" name="s_smtp_timeout" size="5" maxlength="255" value="<?php echo $hesk_settings['smtp_timeout']; ?>" <?php echo $onload_status; ?>>
                    </div>
                    <div class="checkbox-group row">
                        <h5>
                            <span><?php echo $hesklang['smtpssl']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#55','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </h5>
                        <label class="switch-checkbox">
                            <input type="checkbox" name="s_smtp_ssl" id="s7" value="1" <?php if ($hesk_settings['smtp_ssl']) { echo 'checked'; } ?>>
                            <div class="switch-checkbox__bullet">
                                <i>
                                    <svg class="icon icon-close">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                                    </svg>
                                    <svg class="icon icon-tick">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tick"></use>
                                    </svg>
                                </i>
                            </div>
                        </label>
                    </div>
                    <div class="checkbox-group row">
                        <h5>
                            <span><?php echo $hesklang['smtptls']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#55','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </h5>
                        <label class="switch-checkbox">
                            <input type="checkbox" name="s_smtp_tls" id="s9" value="1" <?php if ($hesk_settings['smtp_tls']) { echo 'checked'; } ?>>
                            <div class="switch-checkbox__bullet">
                                <i>
                                    <svg class="icon icon-close">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                                    </svg>
                                    <svg class="icon icon-tick">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tick"></use>
                                    </svg>
                                </i>
                            </div>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <span><?php echo $hesklang['smtpu']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#55','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="text" id="s4" class="form-control" name="s_smtp_user" maxlength="255" value="<?php echo $hesk_settings['smtp_user']; ?>" <?php echo $onload_status; ?> autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>
                            <span><?php echo $hesklang['smtpw']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#55','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="password" id="s5" class="form-control" name="s_smtp_password" size="40" maxlength="255" value="<?php echo $hesk_settings['smtp_password']; ?>" <?php echo $onload_status; ?> autocomplete="off">
                    </div>
                    <div class="settings__form_submit" style="margin-top: 0">
                        <a style="height: 40px" href="javascript:hesk_testSMTP()" class="btn btn--blue-border test-connection" ripple="ripple">
                            <?php echo $hesklang['smtptest']; ?>
                        </a>
                    </div>
                    <!-- START SMTP TEST -->
                    <div id="smtp_test" style="display:none">
                    </div>

                    <script language="Javascript" type="text/javascript"><!--
                        function hesk_testSMTP()
                        {
                            var element = document.getElementById('smtp_test');
                            element.innerHTML = '<img src="<?php echo HESK_PATH; ?>img/loading.gif" width="24" height="24" alt="" border="0" style="vertical-align:text-bottom" /> <i><?php echo addslashes($hesklang['contest']); ?></i>';
                            element.style.display = 'block';

                            var s_smtp_host_name = document.getElementById('s1').value;
                            var s_smtp_host_port = document.getElementById('s2').value;
                            var s_smtp_timeout   = document.getElementById('s3').value;
                            var s_smtp_user      = document.getElementById('s4').value;
                            var s_smtp_password  = document.getElementById('s5').value;
                            var s_smtp_ssl       = document.getElementById('s7').checked ? 1 : 0;
                            var s_smtp_tls       = document.getElementById('s9').checked ? 1 : 0;

                            var params = "test=smtp" +
                                "&s_smtp_host_name=" + encodeURIComponent( s_smtp_host_name ) +
                                "&s_smtp_host_port=" + encodeURIComponent( s_smtp_host_port ) +
                                "&s_smtp_timeout="   + encodeURIComponent( s_smtp_timeout ) +
                                "&s_smtp_user="      + encodeURIComponent( s_smtp_user ) +
                                "&s_smtp_password="  + encodeURIComponent( s_smtp_password ) +
                                "&s_smtp_ssl="       + encodeURIComponent( s_smtp_ssl ) +
                                "&s_smtp_tls="       + encodeURIComponent( s_smtp_tls );

                            xmlHttp=GetXmlHttpObject();
                            if (xmlHttp==null)
                            {
                                return;
                            }

                            xmlHttp.open('POST','test_connection.php',true);
                            xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                            xmlHttp.setRequestHeader("Content-length", params.length);
                            xmlHttp.setRequestHeader("Connection", "close");

                            xmlHttp.onreadystatechange = function()
                            {
                                if (xmlHttp.readyState == 4 && xmlHttp.status == 200)
                                {
                                    element.innerHTML = xmlHttp.responseText;
                                }
                            }

                            xmlHttp.send(params);
                        }
                        //-->
                    </script>
                    <!-- END SMTP TEST -->
                    <div class="divider"></div>
                </div>
                <div class="checkbox-group row">
                    <h5>
                        <span><?php echo $hesklang['emlpipe']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#54','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_email_piping" value="1" <?php if ($hesk_settings['email_piping']) { echo 'checked'; } ?>>
                        <div class="switch-checkbox__bullet">
                            <i>
                                <svg class="icon icon-close">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                                </svg>
                                <svg class="icon icon-tick">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tick"></use>
                                </svg>
                            </i>
                        </div>
                    </label>
                </div>
                <tr>
                    <td>
                        <?php
                        $onload_div = 'none';
                        $onload_status = '';

                        if ($hesk_settings['pop3']) {
                            $onload_div = 'block';
                        } else {
                            $onload_status=' disabled ';
                        }
                        ?>
                        <input type="hidden" name="tmp_pop3_host_name" value="<?php echo $hesk_settings['pop3_host_name']; ?>">
                        <input type="hidden" name="tmp_pop3_host_port" value="<?php echo $hesk_settings['pop3_host_port']; ?>">
                        <input type="hidden" name="tmp_pop3_user" value="<?php echo $hesk_settings['pop3_user']; ?>">
                        <input type="hidden" name="tmp_pop3_password" value="<?php echo $hesk_settings['pop3_password']; ?>">
                        <input type="hidden" name="tmp_pop3_tls" value="<?php echo $hesk_settings['pop3_tls']; ?>">
                        <input type="hidden" name="tmp_pop3_keep" value="<?php echo $hesk_settings['pop3_keep']; ?>">
                    </td>
                </tr>
                <div class="checkbox-group row">
                    <h5>
                        <span><?php echo $hesklang['pop3']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#59','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_email_piping" value="1"
                               onclick="hesk_attach_handle(this, new Array('p0','p1','p2','p4','p5','p6','p7'))" onchange="hesk_toggleLayerDisplay('pop3_settings');"
                            <?php if ($hesk_settings['pop3']) { echo 'checked'; } ?>>
                        <div class="switch-checkbox__bullet">
                            <i>
                                <svg class="icon icon-close">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                                </svg>
                                <svg class="icon icon-tick">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tick"></use>
                                </svg>
                            </i>
                        </div>
                    </label>
                </div>
                <div id="pop3_settings" style="display:<?php echo $onload_div; ?>; margin-bottom: 20px">
                    <div class="form-group short">
                        <label>
                            <span><?php echo $hesklang['pjt']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#59','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="text" id="p0" class="form-control" name="s_pop3_job_wait" maxlength="5" value="<?php echo $hesk_settings['pop3_job_wait']; ?>" <?php echo $onload_status; ?>>
                        <span><?php echo $hesklang['pjt2']; ?></span>
                    </div>
                    <div class="form-group">
                        <label>
                            <span><?php echo $hesklang['pop3h']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#59','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="text" id="p1" class="form-control" name="s_pop3_host_name" maxlength="255" value="<?php echo $hesk_settings['pop3_host_name']; ?>" <?php echo $onload_status; ?>>
                    </div>
                    <div class="form-group short">
                        <label>
                            <span><?php echo $hesklang['pop3p']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#59','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="text" id="p2" class="form-control" name="s_pop3_host_port" maxlength="255" value="<?php echo $hesk_settings['pop3_host_port']; ?>" <?php echo $onload_status; ?>>
                    </div>
                    <div class="checkbox-group row">
                        <h5>
                            <span><?php echo $hesklang['pop3tls']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#59','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </h5>
                        <label class="switch-checkbox">
                            <input type="checkbox" name="s_pop3_tls" id="p4" value="1" <?php if ($hesk_settings['pop3_tls']) { echo 'checked'; } ?> <?php echo $onload_status; ?>>
                            <div class="switch-checkbox__bullet">
                                <i>
                                    <svg class="icon icon-close">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                                    </svg>
                                    <svg class="icon icon-tick">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tick"></use>
                                    </svg>
                                </i>
                            </div>
                        </label>
                    </div>
                    <div class="checkbox-group row">
                        <h5>
                            <span><?php echo $hesklang['pop3keep']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#59','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </h5>
                        <label class="switch-checkbox">
                            <input type="checkbox" name="s_pop3_keep" id="p7" value="1" <?php if ($hesk_settings['pop3_keep']) { echo 'checked'; } ?> <?php echo $onload_status; ?>>
                            <div class="switch-checkbox__bullet">
                                <i>
                                    <svg class="icon icon-close">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                                    </svg>
                                    <svg class="icon icon-tick">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tick"></use>
                                    </svg>
                                </i>
                            </div>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <span><?php echo $hesklang['pop3u']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#59','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="text" id="p5" name="s_pop3_user" class="form-control" maxlength="255" value="<?php echo $hesk_settings['pop3_user']; ?>" <?php echo $onload_status; ?> autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>
                            <span><?php echo $hesklang['pop3w']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#59','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="password" id="p6" name="s_pop3_password" class="form-control" maxlength="255" value="<?php echo $hesk_settings['pop3_password']; ?>" <?php echo $onload_status; ?> autocomplete="off">
                    </div>
                    <div class="settings__form_submit" style="margin-top: 0">
                        <a style="height: 40px" href="javascript:hesk_testPOP3()" class="btn btn--blue-border test-connection" ripple="ripple">
                            <?php echo $hesklang['pop3test']; ?>
                        </a>
                    </div>
                    <div id="pop3_test" style="display:none">
                    </div>
                    <script language="Javascript" type="text/javascript"><!--
                        function hesk_testPOP3()
                        {
                            var element = document.getElementById('pop3_test');
                            element.innerHTML = '<img src="<?php echo HESK_PATH; ?>img/loading.gif" width="24" height="24" alt="" border="0" style="vertical-align:text-bottom" /> <i><?php echo addslashes($hesklang['contest']); ?></i>';
                            element.style.display = 'block';

                            var s_pop3_host_name = document.getElementById('p1').value;
                            var s_pop3_host_port = document.getElementById('p2').value;
                            var s_pop3_user      = document.getElementById('p5').value;
                            var s_pop3_password  = document.getElementById('p6').value;
                            var s_pop3_tls       = document.getElementById('p4').checked ? 1 : 0;

                            var params = "test=pop3" +
                                "&s_pop3_host_name="  + encodeURIComponent( s_pop3_host_name ) +
                                "&s_pop3_host_port=" + encodeURIComponent( s_pop3_host_port ) +
                                "&s_pop3_user="      + encodeURIComponent( s_pop3_user ) +
                                "&s_pop3_password="  + encodeURIComponent( s_pop3_password ) +
                                "&s_pop3_tls="       + encodeURIComponent( s_pop3_tls );

                            xmlHttp=GetXmlHttpObject();
                            if (xmlHttp==null)
                            {
                                return;
                            }

                            xmlHttp.open('POST','test_connection.php',true);
                            xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                            xmlHttp.setRequestHeader("Content-length", params.length);
                            xmlHttp.setRequestHeader("Connection", "close");

                            xmlHttp.onreadystatechange = function()
                            {
                                if (xmlHttp.readyState == 4 && xmlHttp.status == 200)
                                {
                                    element.innerHTML = xmlHttp.responseText;
                                }
                            }

                            xmlHttp.send(params);
                        }
                        //-->
                    </script>
                    <div class="divider"></div>
                </div> <!-- END POP3 SETTINGS DIV -->
                <?php
                $onload_div = 'none';
                $onload_status = '';

                if ($hesk_settings['imap'])
                {
                    $onload_div = 'block';
                }
                else
                {
                    $onload_status=' disabled ';
                }
                ?>
                <input type="hidden" name="tmp_imap_job_wait" value="<?php echo $hesk_settings['imap_job_wait']; ?>" />
                <input type="hidden" name="tmp_imap_host_name" value="<?php echo $hesk_settings['imap_host_name']; ?>" />
                <input type="hidden" name="tmp_imap_host_port" value="<?php echo $hesk_settings['imap_host_port']; ?>" />
                <input type="hidden" name="tmp_imap_user" value="<?php echo $hesk_settings['imap_user']; ?>" />
                <input type="hidden" name="tmp_imap_password" value="<?php echo $hesk_settings['imap_password']; ?>" />
                <input type="hidden" name="tmp_imap_enc" value="<?php echo $hesk_settings['imap_enc']; ?>" />
                <input type="hidden" name="tmp_imap_keep" value="<?php echo $hesk_settings['imap_keep']; ?>" />
                <div class="checkbox-group row">
                    <h5>
                        <span><?php echo $hesklang['imap']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#67','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <?php if (!function_exists('imap_open')): ?>
                    <span style="margin-left: 24px;"><i><?php echo $hesklang['disabled']; ?></i> - <?php echo $hesklang['imap_not']; ?></span>
                    <?php $onload_div = 'none'; ?>
                    <?php else: ?>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_imap" value="1"
                               onclick="hesk_attach_handle(this, new Array('i0','i1','i2','i3','i4','i5','i6','i7','i9'))"
                               onchange="hesk_toggleLayerDisplay('imap_settings');"
                               <?php if ($hesk_settings['imap']) { echo 'checked'; } ?>>
                        <div class="switch-checkbox__bullet">
                            <i>
                                <svg class="icon icon-close">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                                </svg>
                                <svg class="icon icon-tick">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tick"></use>
                                </svg>
                            </i>
                        </div>
                    </label>
                    <?php endif; ?>
                </div>
                <div id="imap_settings" style="display:<?php echo $onload_div; ?>; margin-bottom: 20px">
                    <div class="form-group short">
                        <label>
                            <span><?php echo $hesklang['pjt']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#67','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="text" id="i0" name="s_imap_job_wait" class="form-control" maxlength="5" value="<?php echo $hesk_settings['imap_job_wait']; ?>" <?php echo $onload_status; ?>>
                        <span><?php echo $hesklang['pjt2']; ?></span>
                    </div>
                    <div class="form-group">
                        <label>
                            <span><?php echo $hesklang['imaph']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#67','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="text" id="i1" class="form-control" name="s_imap_host_name" maxlength="255" value="<?php echo $hesk_settings['imap_host_name']; ?>" <?php echo $onload_status; ?>>
                    </div>
                    <div class="form-group short">
                        <label>
                            <span><?php echo $hesklang['imapp']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#67','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="text" id="i2" name="s_imap_host_port" class="form-control" maxlength="255" value="<?php echo $hesk_settings['imap_host_port']; ?>" <?php echo $onload_status; ?>>
                    </div>
                    <?php
                    $none = $hesk_settings['imap_enc'] == '' ? 'checked="checked"' : '';
                    $ssl = $hesk_settings['imap_enc'] == 'ssl' ? 'checked="checked"' : '';
                    $tls = $hesk_settings['imap_enc'] == 'tls' ? 'checked="checked"' : '';
                    ?>
                    <div class="radio-group">
                        <h5>
                            <span><?php echo $hesklang['enc']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#67','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </h5>
                        <div class="radio-list">
                            <div class="radio-custom">
                                <input type="radio" name="s_imap_enc" value="ssl" id="i9" <?php echo $ssl; echo $onload_status; ?>>
                                <label for="i9"><?php echo $hesklang['ssl']; ?></label>
                            </div>
                            <div class="radio-custom">
                                <input type="radio" name="s_imap_enc" value="tls" id="i4" <?php echo $tls; echo $onload_status; ?>>
                                <label for="i4"><?php echo $hesklang['tls']; ?></label>
                            </div>
                            <div class="radio-custom">
                                <input type="radio" name="s_imap_enc" value="" id="i3" <?php echo $none; echo $onload_status; ?>>
                                <label for="i3"><?php echo $hesklang['none']; ?></label>
                            </div>
                        </div>
                    </div>
                    <div class="checkbox-group row">
                        <h5>
                            <span><?php echo $hesklang['pop3keep']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#67','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </h5>
                        <label class="switch-checkbox">
                            <input type="checkbox" name="s_pop3_keep" id="i7" value="1" <?php if ($hesk_settings['imap_keep']) { echo 'checked'; } ?> <?php echo $onload_status; ?>>
                            <div class="switch-checkbox__bullet">
                                <i>
                                    <svg class="icon icon-close">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                                    </svg>
                                    <svg class="icon icon-tick">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tick"></use>
                                    </svg>
                                </i>
                            </div>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <span><?php echo $hesklang['imapu']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#67','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="text" id="i5" name="s_imap_user" class="form-control" maxlength="255" value="<?php echo $hesk_settings['imap_user']; ?>" <?php echo $onload_status; ?> autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>
                            <span><?php echo $hesklang['imapw']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#67','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="password" id="i6" name="s_imap_password" class="form-control" maxlength="255" value="<?php echo $hesk_settings['imap_password']; ?>" <?php echo $onload_status; ?> autocomplete="off">
                    </div>
                    <div class="settings__form_submit" style="margin-top: 0">
                        <a style="height: 40px" href="javascript:hesk_testIMAP()" class="btn btn--blue-border test-connection" ripple="ripple">
                            <?php echo $hesklang['imaptest']; ?>
                        </a>
                    </div>
                    <!-- START IMAP TEST -->
                    <div id="imap_test" style="display:none">
                    </div>

                    <script language="Javascript" type="text/javascript"><!--
                        function hesk_testIMAP()
                        {
                            var element = document.getElementById('imap_test');
                            element.innerHTML = '<img src="<?php echo HESK_PATH; ?>img/loading.gif" width="24" height="24" alt="" border="0" style="vertical-align:text-bottom" /> <i><?php echo addslashes($hesklang['contest']); ?></i>';
                            element.style.display = 'block';

                            var s_imap_host_name = document.getElementById('i1').value;
                            var s_imap_host_port = document.getElementById('i2').value;
                            var s_imap_user      = document.getElementById('i5').value;
                            var s_imap_password  = document.getElementById('i6').value;
                            var s_imap_enc       = document.getElementById('i4').checked ? 'tls' : (document.getElementById('i9').checked ? 'ssl' : '');

                            var params = "test=imap" +
                                "&s_imap_host_name="  + encodeURIComponent( s_imap_host_name ) +
                                "&s_imap_host_port=" + encodeURIComponent( s_imap_host_port ) +
                                "&s_imap_user="      + encodeURIComponent( s_imap_user ) +
                                "&s_imap_password="  + encodeURIComponent( s_imap_password ) +
                                "&s_imap_enc="       + encodeURIComponent( s_imap_enc );

                            xmlHttp=GetXmlHttpObject();
                            if (xmlHttp==null)
                            {
                                return;
                            }

                            xmlHttp.open('POST','test_connection.php',true);
                            xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                            xmlHttp.setRequestHeader("Content-length", params.length);
                            xmlHttp.setRequestHeader("Connection", "close");

                            xmlHttp.onreadystatechange = function()
                            {
                                if (xmlHttp.readyState == 4 && xmlHttp.status == 200)
                                {
                                    element.innerHTML = xmlHttp.responseText;
                                }
                            }

                            xmlHttp.send(params);
                        }
                        //-->
                    </script>
                    <!-- END IMAP TEST -->
                    <div class="divider"></div>
                </div> <!-- END IMAP SETTINGS DIV -->
            </section>
            <section class="settings__form_block">
                <h3><?php echo $hesklang['loops']; ?></h3>
                <div class="form-group short">
                    <label>
                        <span><?php echo $hesklang['looph']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#60','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" name="s_loop_hits" class="form-control" maxlength="5" value="<?php echo $hesk_settings['loop_hits']; ?>">
                </div>
                <div class="form-group short">
                    <label>
                        <span><?php echo $hesklang['loopt']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#60','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" name="s_loop_time" class="form-control" maxlength="5" value="<?php echo $hesk_settings['loop_time']; ?>">
                    <span><?php echo $hesklang['ss']; ?></span>
                </div>
            </section>
            <section class="settings__form_block">
                <h3><?php echo $hesklang['suge']; ?></h3>
                <tr>
                    <td>
                        <?php
                        $onload_div = 'none';
                        $onload_status = '';

                        if ($hesk_settings['detect_typos']) {
                            $onload_div = 'block';
                        } else {
                            $onload_status=' disabled="disabled" ';
                        }
                        ?>
                    </td>
                </tr>
                <div class="checkbox-group row">
                    <h5>
                        <span><?php echo $hesklang['suge']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#62','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_detect_typos" value="1" onclick="hesk_attach_handle(this, 'd1')"
                               onchange="hesk_toggleLayerDisplay('detect_typos')"
                               <?php if ($hesk_settings['detect_typos']) { echo 'checked'; } ?>>
                        <div class="switch-checkbox__bullet">
                            <i>
                                <svg class="icon icon-close">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                                </svg>
                                <svg class="icon icon-tick">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tick"></use>
                                </svg>
                            </i>
                        </div>
                    </label>
                </div>
                <div id="detect_typos" style="display:<?php echo $onload_div; ?>">
                    <div class="form-group">
                        <label>
                            <span><?php echo $hesklang['epro']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#63','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <textarea style="margin-left: 24px;" name="s_email_providers" id="d1" class="form-control"><?php echo implode("\n", $hesk_settings['email_providers']); ?></textarea>
                    </div>
                </div>
            </section>
            <section class="settings__form_block">
                <h3><?php echo $hesklang['custnot']; ?></h3>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['custnot']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#65','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-list">
                        <div class="checkbox-custom">
                            <input type="checkbox" id="s_notify_new1" name="s_notify_new" value="1" onchange="hesk_toggleLayerDisplay('skip_notify');" <?php if ($hesk_settings['notify_new']) {echo 'checked';} ?>>
                            <label for="s_notify_new1"><?php echo $hesklang['notnew']; ?></label>
                        </div>
                        <div id="skip_notify" style="margin-left:25px;display:<?php echo $hesk_settings['notify_new'] ? 'block' : 'none'; ?>">
                            <div class="checkbox-custom">
                                <input type="checkbox" id="s_notify_skip_spam1" name="s_notify_skip_spam" value="1" <?php if ($hesk_settings['notify_skip_spam']) {echo 'checked';} ?>/>
                                <label for="s_notify_skip_spam1"><?php echo $hesklang['enn']; ?></label>
                            </div>
                            <div class="form-group">
                                <textarea class="form-control" name="s_notify_spam_tags" rows="5" cols="40" style="margin-left:25px;"><?php echo hesk_htmlspecialchars( implode("\n", $hesk_settings['notify_spam_tags']) ); ?></textarea>
                            </div>
                        </div>
                        <div class="checkbox-custom">
                            <input type="checkbox" id="s_notify_closed1" name="s_notify_closed" value="1" <?php if ($hesk_settings['notify_closed']) {echo 'checked';} ?>>
                            <label for="s_notify_closed1"><?php echo $hesklang['notclo']; ?></label>
                        </div>
                    </div>
                </div>
            </section>
            <section class="settings__form_block">
                <h3><?php echo $hesklang['other']; ?></h3>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['remqr']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#61','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="s_strip_quoted1" name="s_strip_quoted" value="1" <?php if ($hesk_settings['strip_quoted']) {echo 'checked';} ?>>
                        <label for="s_strip_quoted1"><?php echo $hesklang['remqr2']; ?></label>
                    </div>
                </div>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['emlreqmsg']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#66','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="s_eml_req_msg1" name="s_eml_req_msg" value="1" <?php if ($hesk_settings['eml_req_msg']) {echo 'checked';} ?>>
                        <label for="s_eml_req_msg1"><?php echo $hesklang['emlreqmsg2']; ?></label>
                    </div>
                </div>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['embed']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#64','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="s_save_embedded1" name="s_save_embedded" value="1" <?php if ($hesk_settings['save_embedded']) {echo 'checked';} ?>>
                        <label for="s_save_embedded1"><?php echo $hesklang['embed2']; ?></label>
                    </div>
                </div>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['meml']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#57','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="s_multi_eml1" name="s_multi_eml" value="1" <?php if ($hesk_settings['multi_eml']) {echo 'checked';} ?>>
                        <label for="s_multi_eml1"><?php echo $hesklang['meml2']; ?></label>
                    </div>
                </div>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['sconfe']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#50','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="s_confirm_email1" name="s_confirm_email" value="1" <?php if ($hesk_settings['confirm_email']) {echo 'checked';} ?>>
                        <label for="s_confirm_email1"><?php echo $hesklang['sconfe2']; ?></label>
                    </div>
                </div>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['oo']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>email.html#58','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="s_open_only1" name="s_open_only" value="1" <?php if ($hesk_settings['open_only']) {echo 'checked';} ?>/>
                        <label for="s_open_only1"><?php echo $hesklang['ool']; ?></label>
                    </div>
                </div>
            </section>
            <div class="settings__form_submit">
                <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                <input type="hidden" name="section" value="EMAIL">
                <button id="submitbutton" style="display: inline-flex" type="submit" class="btn btn-full" ripple="ripple"
                    <?php echo $enable_save_settings ? '' : 'disabled'; ?>>
                    <?php echo $hesklang['save_changes']; ?>
                </button>

                <?php if (!$enable_save_settings): ?>
                    <div class="error"><?php echo $hesklang['e_save_settings']; ?></div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<?php
require_once(HESK_PATH . 'inc/footer.inc.php');
exit();


function hesk_checkVersion()
{
	global $hesk_settings;

	if ($latest = hesk_getLatestVersion() )
    {
    	if ( strlen($latest) > 12 )
        {
        	return -1;
        }
		elseif ($latest == $hesk_settings['hesk_version'])
        {
        	return true;
        }
        else
        {
        	return $latest;
        }
    }
    else
    {
		return -1;
    }

} // END hesk_checkVersion()


function hesk_getLatestVersion()
{
	global $hesk_settings;

	// Do we have a cached version file?
	if ( file_exists(HESK_PATH . $hesk_settings['cache_dir'] . '/__latest.txt') )
    {
        if ( preg_match('/^(\d+)\|([\d.]+)+$/', @file_get_contents(HESK_PATH . $hesk_settings['cache_dir'] . '/__latest.txt'), $matches) && (time() - intval($matches[1])) < 3600  )
        {
			return $matches[2];
        }
    }

	// No cached file or older than 3600 seconds, try to get an update
    $hesk_version_url = 'http://hesk.com/version';

	// Try using cURL
	if ( function_exists('curl_init') )
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $hesk_version_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
		$latest = curl_exec($ch);
		curl_close($ch);
		return hesk_cacheLatestVersion($latest);
	}

    // Try using a simple PHP function instead
	if ($latest = @file_get_contents($hesk_version_url) )
    {
		return hesk_cacheLatestVersion($latest);
    }

	// Can't check automatically, will need a manual check
    return false;

} // END hesk_getLatestVersion()


function hesk_cacheLatestVersion($latest)
{
	global $hesk_settings;

	@file_put_contents(HESK_PATH . $hesk_settings['cache_dir'] . '/__latest.txt', time() . '|' . $latest);

	return $latest;

} // END hesk_cacheLatestVersion()
?>
