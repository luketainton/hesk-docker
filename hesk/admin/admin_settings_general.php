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

// Test languages function
if (isset($_GET['test_languages']))
{
	hesk_testLanguage(0);
} elseif (isset($_GET['test_themes'])) {
    hesk_testTheme(0);
}

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
            var d=document.form1;

            // GENERAL
            if (d.s_site_title.value=='') {alert('<?php echo addslashes($hesklang['err_sname']); ?>'); return false;}
            if (d.s_site_url.value=='') {alert('<?php echo addslashes($hesklang['err_surl']); ?>'); return false;}
            if (d.s_hesk_title.value=='') {alert('<?php echo addslashes($hesklang['err_htitle']); ?>'); return false;}
            if (d.s_hesk_url.value=='') {alert('<?php echo addslashes($hesklang['err_hurl']); ?>'); return false;}
            if (d.s_webmaster_mail.value=='' || d.s_webmaster_mail.value.indexOf(".") == -1 || d.s_webmaster_mail.value.indexOf("@") == -1)
            {alert('<?php echo addslashes($hesklang['err_wmmail']); ?>'); return false;}
            if (d.s_noreply_mail.value=='' || d.s_noreply_mail.value.indexOf(".") == -1 || d.s_noreply_mail.value.indexOf("@") == -1)
            {alert('<?php echo addslashes($hesklang['err_nomail']); ?>'); return false;}

            if (d.s_db_host.value=='') {alert('<?php echo addslashes($hesklang['err_dbhost']); ?>'); return false;}
            if (d.s_db_name.value=='') {alert('<?php echo addslashes($hesklang['err_dbname']); ?>'); return false;}
            if (d.s_db_user.value=='') {alert('<?php echo addslashes($hesklang['err_dbuser']); ?>'); return false;}
            if (d.s_db_pass.value=='')
            {
                if (!confirm('<?php echo addslashes($hesklang['mysql_root']); ?>'))
                {
                    return false;
                }
            }

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

        function hesk_testLanguage()
        {
            window.open('admin_settings_general.php?test_languages=1',"Hesk_window","height=400,width=500,menubar=0,location=0,toolbar=0,status=0,resizable=1,scrollbars=1");
            return false;
        }

        function hesk_testTheme()
        {
            window.open('admin_settings_general.php?test_themes=1',"Hesk_window","height=400,width=500,menubar=0,location=0,toolbar=0,status=0,resizable=1,scrollbars=1");
            return false;
        }
        //-->
    </script>
    <form method="post" action="admin_settings_save.php" name="form1" onsubmit="return hesk_checkFields()">
        <div class="settings__form form">
            <section class="settings__form_block">
                <h3><?php echo $hesklang['gs']; ?></h3>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['wbst_title']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>general.html#1','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_site_title" maxlength="255" value="<?php echo $hesk_settings['site_title']; ?>">
                </div>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['wbst_url']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>general.html#2','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_site_url" maxlength="255" value="<?php echo $hesk_settings['site_url']; ?>">
                </div>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['hesk_title']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#6','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_hesk_title" maxlength="255" value="<?php echo $hesk_settings['hesk_title']; ?>">
                </div>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['hesk_url']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#7','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_hesk_url" maxlength="255" value="<?php echo $hesk_settings['hesk_url']; ?>">
                </div>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['email_wm']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>general.html#4','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_webmaster_mail" maxlength="255" value="<?php echo $hesk_settings['webmaster_mail']; ?>">
                </div>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['email_noreply']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>general.html#5','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_noreply_mail" maxlength="255" value="<?php echo $hesk_settings['noreply_mail']; ?>">
                </div>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['email_name']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>general.html#6','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_noreply_name" maxlength="255" value="<?php echo $hesk_settings['noreply_name']; ?>">
                </div>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['site_theme']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>general.html#58','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <div class="dropdown-select center out-close">
                        <select name="s_site_theme">
                            <?php echo hesk_testTheme(1); ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn--blue-border" style="margin-left: 20px" ripple="ripple"
                            onclick="return hesk_testTheme()">
                        <?php echo $hesklang['test_theme_folder']; ?>
                    </button>
                </div>
            </section>
            <section class="settings__form_block language">
                <h3><?php echo $hesklang['lgs']; ?></h3>
                <div class="form-group row">
                    <label>
                        <span><?php echo $hesklang['hesk_lang']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>general.html#9','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <div class="dropdown-select center out-close">
                        <select name="s_language">
                            <?php echo hesk_testLanguage(1); ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn--blue-border" style="margin-left: 20px" ripple="ripple"
                            onclick="return hesk_testLanguage()">
                        <?php echo $hesklang['s_inl']; ?>
                    </button>
                </div>
                <div class="checkbox-group row">
                    <h5>
                        <span><?php echo $hesklang['s_mlang']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>general.html#43','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_can_sel_lang" <?php echo $hesk_settings['can_sel_lang'] ? 'checked' : ''; ?>>
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
            </section>
            <section class="settings__form_block">
                <h3><?php echo $hesklang['db']; ?></h3>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['db_host']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>general.html#32','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_db_host" id="m1" maxlength="255" value="<?php echo $hesk_settings['db_host']; ?>" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['db_name']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>general.html#33','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_db_name" id="m2" maxlength="255" value="<?php echo $hesk_settings['db_name']; ?>" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['db_user']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>general.html#34','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_db_user" id="m3" maxlength="255" value="<?php echo $hesk_settings['db_user']; ?>" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['db_pass']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>general.html#35','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="password" class="form-control" name="s_db_pass" id="m4" maxlength="255" value="<?php echo $hesk_settings['db_pass'] ; ?>" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['prefix']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>general.html#36','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_db_pfix" id="m5" maxlength="255" value="<?php echo $hesk_settings['db_pfix']; ?>" autocomplete="off">
                </div>
            </section>
            <div class="settings__form_submit">
                <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                <input type="hidden" name="section" value="GENERAL">
                <button id="submitbutton" style="display: inline-flex" type="submit" class="btn btn-full" ripple="ripple"
                    <?php echo $enable_save_settings ? '' : 'disabled'; ?>>
                    <?php echo $hesklang['save_changes']; ?>
                </button>
                <a style="height: 40px" href="javascript:hesk_testMySQL()" class="btn btn--blue-border test-connection" ripple="ripple">
                    <?php echo $hesklang['mysqltest']; ?>
                </a>

                <?php if (!$enable_save_settings): ?>
                    <div class="error"><?php echo $hesklang['e_save_settings']; ?></div>
                <?php endif; ?>
            </div>
            <!-- START MYSQL TEST -->
            <div id="mysql_test" style="display:none">
            </div>

            <script language="Javascript" type="text/javascript"><!--
                function hesk_testMySQL()
                {
                    var element = document.getElementById('mysql_test');
                    element.innerHTML = '<img src="<?php echo HESK_PATH; ?>img/loading.gif" width="24" height="24" alt="" border="0" style="vertical-align:text-bottom" /> <i><?php echo addslashes($hesklang['contest']); ?></i>';
                    element.style.display = 'block';

                    var s_db_host = document.getElementById('m1').value;
                    var s_db_name = document.getElementById('m2').value;
                    var s_db_user = document.getElementById('m3').value;
                    var s_db_pass = document.getElementById('m4').value;
                    var s_db_pfix = document.getElementById('m5').value;

                    var params = "test=mysql" +
                        "&s_db_host=" + encodeURIComponent( s_db_host ) +
                        "&s_db_name=" + encodeURIComponent( s_db_name ) +
                        "&s_db_user=" + encodeURIComponent( s_db_user ) +
                        "&s_db_pass=" + encodeURIComponent( s_db_pass ) +
                        "&s_db_pfix=" + encodeURIComponent( s_db_pfix );

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
            <!-- END MYSQL TEST -->
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

function hesk_testTheme($return_options = 1) {
    global $hesk_settings, $hesklang;

    $dir = HESK_PATH . 'theme/';
    $path = opendir($dir);

    $themes = "/theme\n";
    $html = '';

    /* Test all folders inside the theme folder */
    while (false !== ($subdir = readdir($path))) {
        if ($subdir === '.' || $subdir === '..') {
            continue;
        }

        if (filetype($dir . $subdir) === 'dir') {
            $add = 1;
            $themes .= "   |-> /$subdir\n";
            $themes .= "      |-> /customer\n";
            $err = '';

            //region Create Ticket
            $files_to_test = array('category-select.php', 'create-ticket.php', 'create-ticket-confirmation.php');
            $themes .= "         |-> /create-ticket: ";
            foreach ($files_to_test as $test_file) {
                if (!file_exists($dir . $subdir . '/customer/create-ticket/' . $test_file)) {
                    $err .= "            |----> MISSING: $test_file\n";
                }
            }

            if ($err) {
                $add = 0;
                $themes .= "ERROR\n$err";
            } else {
                $themes .= "OK\n";
            }
            //endregion
            $err = '';
            //region Knowledgebase
            $files_to_test = array('search-results.php', 'view-article.php', 'view-category.php');
            $themes .= "         |-> /knowledgebase: ";
            foreach ($files_to_test as $test_file) {
                if (!file_exists($dir . $subdir . '/customer/knowledgebase/' . $test_file)) {
                    $err .= "            |----> MISSING: $test_file\n";
                }
            }

            if ($err) {
                $add = 0;
                $themes .= "ERROR\n$err";
            } else {
                $themes .= "OK\n";
            }
            //endregion
            $err = '';
            //region View Ticket
            $files_to_test = array('form.php', 'view-ticket.php');
            $themes .= "         |-> /view-ticket";
            foreach ($files_to_test as $test_file) {
                if (!file_exists($dir . $subdir . '/customer/view-ticket/' . $test_file)) {
                    $err .= "            |----> MISSING: $test_file\n";
                }
            }

            if ($err) {
                $add = 0;
                $themes .= "ERROR\n$err";
            } else {
                $themes .= ": OK\n";
            }
            //endregion
            //region Solo files
            $files_to_test = array('error.php', 'index.php', 'maintenance.php');
            foreach ($files_to_test as $test_file) {
                if (!file_exists($dir . $subdir . '/customer/' . $test_file)) {
                    $add = 0;
                    $themes .= "         |----> MISSING: $test_file\n";
                } else {
                    $themes .= "         |-> $test_file: OK\n";
                }
            }
            //endregion
            if (!file_exists($dir . $subdir . '/print-ticket.php')) {
                $add = 0;
                $themes .= "      |----> MISSING: print-ticket.php\n";
            } else {
                $themes .= "      |-> print-ticket.php: OK\n";
            }
            if (!file_exists($dir . $subdir . '/config.json')) {
                $add = 0;
                $themes .= "      |----> MISSING: config.json\n";
            } else {
                $themes .= "      |-> config.json: OK\n";
            }
        }

        // Build markup
        if ($add) {
            // Pull the name from config.json
            $config = file_get_contents($dir . $subdir . '/config.json');
            $config_json = json_decode($config, true);

            $html .= '<option value="'.$subdir.'" '.($hesk_settings['site_theme'] === $subdir ? 'selected' : '').'>'.$config_json['name'].'</option>';
        }
    }

    if ($return_options) {
        return $html;
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <title><?php echo $hesklang['test_theme_folder']; ?></title>
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
                    text-align:center;
                }
                .title
                {
                    color : black;
                    font-family : Verdana, Geneva, Arial, Helvetica, sans-serif;
                    font-weight: bold;
                    font-size: 1.0em;
                }
                .wrong   {color : red;}
                .correct {color : green;}
                pre {font-size:1.2em;}
            </style>
        </head>
        <body>
            <h3><?php echo $hesklang['test_theme_folder']; ?></h3>
            <p><i><?php echo $hesklang['test_theme_folder_description']; ?></i></p>
            <pre><?php echo $themes; ?></pre>
            <p class="text-center">
                <a href="admin_settings_general.php?test_themes=1&amp;<?php echo rand(10000,99999); ?>">
                    <?php echo $hesklang['ta']; ?>
                </a> |
                <a href="#" onclick="Javascript:window.close()">
                    <?php echo $hesklang['cwin']; ?>
                </a>
            </p>
        </body>
        </html>
        <?php
        exit();
    }
}


function hesk_testLanguage($return_options = 0)
{
	global $hesk_settings, $hesklang;

	/* Get a list of valid emails */
    include_once(HESK_PATH . 'inc/email_functions.inc.php');
    $valid_emails = array_keys( hesk_validEmails() );

	$dir = HESK_PATH . 'language/';
	$path = opendir($dir);

    $text = '';
    $html = '';

	$text .= "/language\n";

    /* Test all folders inside the language folder */
	while (false !== ($subdir = readdir($path)))
	{
		if ($subdir == "." || $subdir == "..")
	    {
	    	continue;
	    }

		if (filetype($dir . $subdir) == 'dir')
		{
        	$add   = 1;
	    	$langu = $dir . $subdir . '/text.php';
	        $email = $dir . $subdir . '/emails';

			/* Check the text.php */
			$text .= "   |-> /$subdir\n";
	        $text .= "        |-> text.php: ";
	        if (file_exists($langu))
	        {
	        	$tmp = file_get_contents($langu);

				// Some servers add slashes to file_get_contents output
				if ( strpos ($tmp, '[\\\'LANGUAGE\\\']') !== false )
				{
					$tmp = stripslashes($tmp);
				}

	            $err = '';
	        	if (!preg_match('/\$hesklang\[\'LANGUAGE\'\]\=\'(.*)\'\;/',$tmp,$l))
	            {
	                $err .= "              |---->  MISSING: \$hesklang['LANGUAGE']\n";
	            }

	            if (strpos($tmp,'$hesklang[\'ENCODING\']') === false)
	            {
	            	$err .= "              |---->  MISSING: \$hesklang['ENCODING']\n";
	            }

	            if (strpos($tmp,'$hesklang[\'_COLLATE\']') === false)
	            {
	            	$err .= "              |---->  MISSING: \$hesklang['_COLLATE']\n";
	            }

	            if (strpos($tmp,'$hesklang[\'EMAIL_HR\']') === false)
	            {
	            	$err .= "              |---->  MISSING: \$hesklang['EMAIL_HR']\n";
	            }

                /* Check if language file is for current version */
	            if (strpos($tmp,'$hesklang[\'team\']') === false)
	            {
	            	$err .= "              |---->  WRONG VERSION (not ".$hesk_settings['hesk_version'].")\n";
	            }

	            if ($err)
	            {
	            	$text .= "ERROR\n" . $err;
                    $add   = 0;
	            }
	            else
	            {
                	$l[1]  = hesk_input($l[1]);
                    $l[1]  = str_replace('|',' ',$l[1]);
	        		$text .= "OK ($l[1])\n";
	            }
	        }
	        else
	        {
	        	$text .= "ERROR\n";
	            $text .= "              |---->  MISSING: text.php\n";
                $add   = 0;
	        }

            /* Check emails folder */
	        $text .= "        |-> /emails:  ";
	        if (file_exists($email) && filetype($email) == 'dir')
	        {
	        	$err = '';
	            foreach ($valid_emails as $eml)
	            {
	            	if (!file_exists($email.'/'.$eml.'.txt'))
	                {
	                	$err .= "              |---->  MISSING: $eml.txt\n";
	                }
	            }

	            if ($err)
	            {
	            	$text .= "ERROR\n" . $err;
                    $add   = 0;
	            }
	            else
	            {
	        		$text .= "OK\n";
	            }
	        }
	        else
	        {
	        	$text .= "ERROR\n";
	            $text .= "              |---->  MISSING: /emails folder\n";
                $add   = 0;
	        }

	        $text .= "\n";

            /* Add an option for the <select> if needed */
            if ($add)
            {
				if ($l[1] == $hesk_settings['language'])
				{
					$html .= '<option value="'.$subdir.'|'.$l[1].'" selected="selected">'.$l[1].'</option>';
				}
				else
				{
					$html .= '<option value="'.$subdir.'|'.$l[1].'">'.$l[1].'</option>';
				}
            }
		}
	}

	closedir($path);

    /* Output select options or the test log for debugging */
    if ($return_options)
    {
		return $html;
    }
    else
    {
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML; 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
		<head>
		<title><?php echo $hesklang['s_inl']; ?></title>
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
		        text-align:center;
		}
		.title
		{
		        color : black;
		        font-family : Verdana, Geneva, Arial, Helvetica, sans-serif;
		        font-weight: bold;
		        font-size: 1.0em;
		}
		.wrong   {color : red;}
		.correct {color : green;}
        pre {font-size:1.2em;}
		</style>
		</head>
		<body>

		<h3><?php echo $hesklang['s_inl']; ?></h3>

		<p><i><?php echo $hesklang['s_inle']; ?></i></p>

		<pre><?php echo $text; ?></pre>

		<p>&nbsp;</p>

		<p align="center"><a href="admin_settings_general.php?test_languages=1&amp;<?php echo rand(10000,99999); ?>"><?php echo $hesklang['ta']; ?></a> | <a href="#" onclick="Javascript:window.close()"><?php echo $hesklang['cwin']; ?></a></p>

		<p>&nbsp;</p>

		</body>

		</html>
		<?php
		exit();
    }
} // END hesk_testLanguage()
?>
