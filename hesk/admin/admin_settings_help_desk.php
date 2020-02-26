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

if ($hesk_settings['attachments']['use'] && ! defined('HESK_DEMO') ) {
// Check number of attachments per post
    if (version_compare(phpversion(), '5.2.12', '>=') && @ini_get('max_file_uploads') && @ini_get('max_file_uploads') < $hesk_settings['attachments']['max_number']) {
        hesk_show_notice($hesklang['fatte1']);
    }

// Check max attachment size
    $tmp = @ini_get('upload_max_filesize');
    if ($tmp) {
        $last = strtoupper(substr($tmp, -1));
        $number = substr($tmp, 0, -1);

        switch ($last) {
            case 'K':
                $tmp = $number * 1024;
                break;
            case 'M':
                $tmp = $number * 1048576;
                break;
            case 'G':
                $tmp = $number * 1073741824;
                break;
            default:
                $tmp = $number;
        }

        if ($tmp < $hesk_settings['attachments']['max_size']) {
            hesk_show_notice($hesklang['fatte2']);
        }
    }

// Check max post size
    $tmp = @ini_get('post_max_size');
    if ($tmp) {
        $last = strtoupper(substr($tmp, -1));
        $number = substr($tmp, 0, -1);

        switch ($last) {
            case 'K':
                $tmp = $number * 1024;
                break;
            case 'M':
                $tmp = $number * 1048576;
                break;
            case 'G':
                $tmp = $number * 1073741824;
                break;
            default:
                $tmp = $number;
        }

        if ($tmp < ($hesk_settings['attachments']['max_size'] * $hesk_settings['attachments']['max_number'] + 524288)) {
            hesk_show_notice($hesklang['fatte3']);
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

            // HELPDESK
            if (d.s_max_listings.value=='') {alert('<?php echo addslashes($hesklang['err_max']); ?>'); return false;}
            if (d.s_print_font_size.value=='') {alert('<?php echo addslashes($hesklang['err_psize']); ?>'); return false;}

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
                <h3><?php echo $hesklang['hd']; ?></h3>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['adf']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#61','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_admin_dir" maxlength="255" value="<?php echo $hesk_settings['admin_dir']; ?>">
                </div>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['atf']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#62','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_attach_dir" maxlength="255" value="<?php echo $hesk_settings['attach_dir']; ?>">
                </div>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['cf']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#77','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_cache_dir" maxlength="255" value="<?php echo $hesk_settings['cache_dir']; ?>">
                </div>
                <div class="form-group short">
                    <label>
                        <span><?php echo $hesklang['max_listings']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#10','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_max_listings" maxlength="30" value="<?php echo $hesk_settings['max_listings']; ?>">
                </div>
                <div class="form-group short">
                    <label>
                        <span><?php echo $hesklang['print_size']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#11','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_print_font_size" maxlength="3" value="<?php echo $hesk_settings['print_font_size']; ?>">
                </div>
                <div class="form-group short">
                    <label>
                        <span><?php echo $hesklang['aclose']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#15','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_autoclose" size="5" maxlength="3" value="<?php echo $hesk_settings['autoclose']; ?>">
                    <span><?php echo $hesklang['aclose2']; ?></span>
                </div>
                <div class="form-group short">
                    <label>
                        <span><?php echo $hesklang['mop']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#58','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_max_open" size="5" maxlength="3" value="<?php echo $hesk_settings['max_open']; ?>">
                </div>
                <div class="radio-group mt24">
                    <h5>
                        <span><?php echo $hesklang['rord']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#59','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="radio-list">
                        <?php
                        $on = $hesk_settings['new_top'] ? 'checked' : '';
                        $off = $hesk_settings['new_top'] ? '' : 'checked';
                        ?>
                        <div class="radio-custom">
                            <input type="radio" id="s_new_top1" name="s_new_top" value="1" <?php echo $on; ?>>
                            <label for="s_new_top1"><?php echo $hesklang['newtop']; ?></label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="s_new_top0" name="s_new_top" value="0"  <?php echo $off; ?>>
                            <label for="s_new_top0"><?php echo $hesklang['newbot']; ?></label>
                        </div>
                    </div>
                </div>
                <div class="radio-group">
                    <h5>
                        <span><?php echo $hesklang['ford']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#60','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <?php
                        $on = $hesk_settings['reply_top'] ? 'checked="checked"' : '';
                        $off = $hesk_settings['reply_top'] ? '' : 'checked="checked"';
                    ?>
                    <div class="radio-list">
                        <div class="radio-custom">
                            <input type="radio" id="s_reply_top1" name="s_reply_top" value="1" <?php echo $on; ?>>
                            <label for="s_reply_top1"><?php echo $hesklang['formtop']; ?></label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="s_reply_top0" name="s_reply_top" value="0" <?php echo $off; ?>>
                            <label for="s_reply_top0"><?php echo $hesklang['formbot']; ?></label>
                        </div>
                    </div>
                </div>
            </section>
            <section class="settings__form_block">
                <h3><?php echo $hesklang['features']; ?></h3>
                <div class="checkbox-group row">
                    <h5>
                        <span><?php echo $hesklang['alo']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#44','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_autologin" value="1" <?php if ($hesk_settings['autologin']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['saass']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#51','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_autoassign" value="1" <?php if ($hesk_settings['autoassign']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['req_email']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#73','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_require_email" value="1" <?php if ($hesk_settings['require_email']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['fass']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#70','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_require_owner" value="1" <?php if ($hesk_settings['require_owner']) { echo 'checked'; } ?>>
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
                <?php
                $on = $hesk_settings['require_subject']==1 ? 'checked' : '';
                $off = $hesk_settings['require_subject']==0 ? 'checked' : '';
                $hide = $hesk_settings['require_subject']==-1 ? 'checked' : '';
                ?>
                <div class="radio-group">
                    <h5>
                        <span><?php echo $hesklang['req_sub']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#72','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="radio-list">
                        <div class="radio-custom">
                            <input type="radio" id="s_require_subject0" name="s_require_subject" value="0" <?php echo $off; ?>>
                            <label for="s_require_subject0"><?php echo $hesklang['off']; ?></label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="s_require_subject1" name="s_require_subject" value="1" <?php echo $on; ?>>
                            <label for="s_require_subject1"><?php echo $hesklang['on']; ?></label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="s_require_subject-1" name="s_require_subject" value="-1" <?php echo $hide; ?>>
                            <label for="s_require_subject-1"><?php echo $hesklang['off-hide']; ?></label>
                        </div>
                    </div>
                </div>
                <?php
                $on = $hesk_settings['require_message']==1 ? 'checked' : '';
                $off = $hesk_settings['require_message']==0 ? 'checked' : '';
                $hide = $hesk_settings['require_message']==-1 ? 'checked' : '';
                ?>
                <div class="radio-group">
                    <h5>
                        <span><?php echo $hesklang['req_msg']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#74','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="radio-list">
                        <div class="radio-custom">
                            <input type="radio" id="s_require_message0" name="s_require_message" value="0" <?php echo $off; ?>>
                            <label for="s_require_message0"><?php echo $hesklang['off']; ?></label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="s_require_message1" name="s_require_message" value="1" <?php echo $on; ?>>
                            <label for="s_require_message1"><?php echo $hesklang['on']; ?></label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="s_require_message-1" name="s_require_message" value="-1" <?php echo $hide; ?>>
                            <label for="s_require_message-1"><?php echo $hesklang['off-hide']; ?></label>
                        </div>
                    </div>
                </div>
                <div class="checkbox-group row">
                    <h5>
                        <span><?php echo $hesklang['ccct']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#67','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_custclose" value="1" <?php if ($hesk_settings['custclose']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['s_ucrt']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#16','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_custopen" value="1" <?php if ($hesk_settings['custopen']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['urate']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#17','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_rating" value="1" <?php if ($hesk_settings['rating']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['cpri']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#45','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_cust_urgency" value="1" <?php if ($hesk_settings['cust_urgency']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['eseqid']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#49','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_sequential" value="1" <?php if ($hesk_settings['sequential']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['ts']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#66','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_time_worked" value="1" <?php if ($hesk_settings['time_worked']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['spamn']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#68','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_spam_notice" value="1" <?php if ($hesk_settings['spam_notice']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['lu']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#14','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_list_users" value="1" <?php if ($hesk_settings['list_users']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['debug_mode']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#12','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_debug_mode" value="1" <?php if ($hesk_settings['debug_mode']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['shu']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#63','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_short_link" value="1" <?php if ($hesk_settings['short_link']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['select']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#65','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_select_cat" value="1" <?php if ($hesk_settings['select_cat']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['category']; ?></span>
                    </label>
                </div>
                <div class="checkbox-group row">
                    <h5>&nbsp;</h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_select_pri" <?php if ($hesk_settings['select_pri']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['priority']; ?></span>
                    </label>
                </div>
                <div class="form-group short">
                    <label>
                        <span><?php echo $hesklang['scat']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#71','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_cat_show_select" maxlength="3" value="<?php echo $hesk_settings['cat_show_select']; ?>">
                    <span><?php echo $hesklang['scat2']; ?></span>
                </div>
            </section>
            <section class="settings__form_block">
                <h3><?php echo $hesklang['sp']; ?></h3>
                <div class="radio-group">
                    <h5>
                        <span><?php echo $hesklang['use_secimg']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#13','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <?php
                    $onc = $hesk_settings['secimg_use'] == 1 ? 'checked' : '';
                    $ons = $hesk_settings['secimg_use'] == 2 ? 'checked' : '';
                    $off = $hesk_settings['secimg_use'] ? '' : 'checked';
                    $div = $hesk_settings['secimg_use'] ? 'block' : 'none';
                    ?>
                    <div class="radio-list">
                        <div class="radio-custom">
                            <input type="radio" id="s_secimg_use0" name="s_secimg_use" value="0" <?php echo $off; ?> onclick="hesk_toggleLayer('captcha','none')">
                            <label for="s_secimg_use0"><?php echo $hesklang['off']; ?></label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="s_secimg_use1" name="s_secimg_use" value="1" <?php echo $onc; ?> onclick="hesk_toggleLayer('captcha','block')">
                            <label for="s_secimg_use1"><?php echo $hesklang['onc']; ?></label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="s_secimg_use2" name="s_secimg_use" value="2" <?php echo $ons; ?> onclick="hesk_toggleLayer('captcha','block')">
                            <label for="s_secimg_use2"><?php echo $hesklang['ons']; ?></label>
                        </div>
                    </div>
                </div>
                <div id="captcha" style="display: <?php echo $div; ?>;">
                    <?php

                    $on  = '';
                    $on2 = '';
                    $off = '';
                    $div = 'block';

                    if ($hesk_settings['recaptcha_use'] == 1) {
                        $on = 'checked';
                    } elseif ($hesk_settings['recaptcha_use'] == 2) {
                        $on2 = 'checked';
                    } else {
                        $off = 'checked';
                        $div = 'none';
                    }
                    ?>
                    <div class="radio-group">
                        <h5>
                            <span><?php echo $hesklang['sit']; ?></span>
                        </h5>
                        <div class="radio-list">
                            <div class="radio-custom">
                                <input type="radio" id="s_recaptcha_use0" name="s_recaptcha_use" value="0" onclick="hesk_toggleLayer('recaptcha','none')" <?php echo $off; ?>>
                                <label for="s_recaptcha_use0"><?php echo $hesklang['sis']; ?></label>
                            </div>
                            <div class="radio-custom">
                                <input type="radio" id="s_recaptcha_use2" name="s_recaptcha_use" value="2" onclick="hesk_toggleLayer('recaptcha','block')" <?php echo $on2; ?>>
                                <label for="s_recaptcha_use2"><?php echo $hesklang['recaptcha']; ?> v2</label>
                                <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#64','400','500')">
                                    <div class="tooltype right">
                                        <svg class="icon icon-info">
                                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                        </svg>
                                    </div>
                                </a>
                            </div>
                            <div class="radio-custom">
                                <input type="radio" id="s_recaptcha_use1" name="s_recaptcha_use" value="1" onclick="hesk_toggleLayer('recaptcha','block')" <?php echo $on; ?>>
                                <label for="s_recaptcha_use1"><?php echo $hesklang['sir3']; ?></label>
                                <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#64','400','500')">
                                    <div class="tooltype right">
                                        <svg class="icon icon-info">
                                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                        </svg>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div id="recaptcha" style="display: <?php echo $div; ?>; margin-bottom: 20px">
                        <div class="form-group">
                            <label>
                                <span><?php echo $hesklang['rcpb']; ?></span>
                                <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#64','400','500')">
                                    <div class="tooltype right">
                                        <svg class="icon icon-info">
                                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                        </svg>
                                    </div>
                                </a>
                            </label>
                            <input type="text" class="form-control" name="s_recaptcha_public_key" maxlength="255" value="<?php echo $hesk_settings['recaptcha_public_key']; ?>">
                        </div>
                        <div class="form-group">
                            <label>
                                <span><?php echo $hesklang['rcpv']; ?></span>
                                <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#64','400','500')">
                                    <div class="tooltype right">
                                        <svg class="icon icon-info">
                                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                        </svg>
                                    </div>
                                </a>
                            </label>
                            <input type="text" class="form-control" name="s_recaptcha_private_key" maxlength="255" value="<?php echo $hesk_settings['recaptcha_private_key']; ?>">
                        </div>
                    </div>
                    <div class="divider"></div>
                </div>
                <div class="checkbox-group row">
                    <h5>
                        <span><?php echo $hesklang['use_q']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#42','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <?php
                    $div = $hesk_settings['question_use'] ? 'block' : 'none';
                    ?>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_question_use" value="1" <?php if ($hesk_settings['question_use']) { echo 'checked'; } ?> onclick="hesk_toggleLayerDisplay('question')">
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
                <div id="question" style="display: <?php echo $div; ?>;">
                    <div class="form-group">
                        <h5></h5>
                        <button style="margin-left: 24px" type="button" class="btn btn--blue-border" onclick="Javascript:hesk_rate('generate_spam_question.php','question')">
                            <?php echo $hesklang['genq']; ?>
                        </button>
                    </div>
                    <div class="form-group">
                        <h5><span><?php echo $hesklang['q_q']; ?></span></h5>
                        <textarea style="margin-left: 24px;" name="s_question_ask" class="form-control" rows="3" cols="40"><?php echo hesk_htmlentities($hesk_settings['question_ask']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <h5><span><?php echo $hesklang['q_a']; ?></span></h5>
                        <input class="form-control" type="text" name="s_question_ans" value="<?php echo $hesk_settings['question_ans']; ?>">
                    </div>
                </div>
            </section>
            <section class="settings__form_block">
                <h3><?php echo $hesklang['security']; ?></h3>
                <div class="form-group short">
                    <label>
                        <span><?php echo $hesklang['banlim']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#47','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_attempt_limit" maxlength="30" value="<?php echo ($hesk_settings['attempt_limit'] ? ($hesk_settings['attempt_limit']-1) : 0); ?>">
                </div>
                <div class="form-group short">
                    <label>
                        <span><?php echo $hesklang['banmin']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#47','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_attempt_banmin" maxlength="3" value="<?php echo $hesk_settings['attempt_banmin']; ?>">
                </div>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['passr']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#69','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="s_reset_pass" name="s_reset_pass" value="1" <?php if ($hesk_settings['reset_pass']) {echo 'checked';} ?>>
                        <label for="s_reset_pass"><?php echo $hesklang['passa']; ?></label>
                    </div>
                </div>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['viewvtic']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#46','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="s_email_view_ticket" name="s_email_view_ticket" onclick="return checkRequiredEmail('s_email_view_ticket');" value="1" <?php if ($hesk_settings['email_view_ticket']) {echo 'checked';} ?>>
                        <label for="s_email_view_ticket"><?php echo $hesklang['reqetv']; ?></label>
                    </div>
                </div>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['frames']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#76','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="s_x_frame_opt" name="s_x_frame_opt" value="1" <?php if ($hesk_settings['x_frame_opt']) {echo 'checked';} ?>>
                        <label for="s_x_frame_opt"><?php echo $hesklang['frames2']; ?></label>
                    </div>
                </div>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['ssl']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#75','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-custom">
                        <?php if (HESK_SSL): ?>
                            <input type="checkbox" id="s_force_ssl" name="s_force_ssl" value="1" <?php echo ($hesk_settings['force_ssl'] ? 'checked' : ''); ?>>
                            <label for="s_force_ssl"><?php echo $hesklang['force_ssl']; ?></label>
                        <?php else: ?>
                            <label for="s_force_ssl"><?php echo $hesklang['d_ssl']; ?></label>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            <section class="settings__form_block">
                <h3><?php echo $hesklang['attachments']; ?></h3>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['attach_use']; $onload_status=''; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#37','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <?php if ($enable_use_attachments) { ?>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_attach_use" value="1" <?php if($hesk_settings['attachments']['use']) {echo 'checked'; $layer_onload = 'block';} else {$onload_status = 'disabled';$layer_onload = 'none';} ?> onchange="hesk_attach_handle(this, new Array('a1','a2','a3','a4')); hesk_toggleLayerDisplay('attachment_restrictions')">
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
                        <?php if (!defined('HESK_DEMO')): ?>
                            <a href="javascript:void(0);" onclick="hesk_toggleLayerDisplay('attachments_limits');"><?php echo $hesklang['vscl']; ?></a>
                        <?php endif; ?>
                    </label>
                            <?php
                    } else {
                        $onload_status=' disabled="disabled" ';
                        $layer_onload = 'none';
                        echo '<input type="hidden" name="s_attach_use" value="0" /><span style="margin-left: 24px;" class="notice">'.$hesklang['e_attach'].'</span>';
                    }
                    ?>
                </div>
                <?php if (!defined('HESK_DEMO')): ?>
                <div class="form-group">
                    <h5></h5>
                    <div id="attachments_limits" style="margin-left: 24px; display:none">
                        <i>upload_max_filesize</i>: <?php echo @ini_get('upload_max_filesize'); ?><br />
                        <?php
                        if (version_compare(phpversion(), '5.2.12', '>=')) {
                            echo '<i>max_file_uploads</i>: ' . @ini_get('max_file_uploads') . '<br />';
                        }
                        ?>
                        <i>post_max_size</i>: <?php echo @ini_get('post_max_size'); ?><br />
                    </div>
                </div>
                <?php endif; ?>
                <div id="attachment_restrictions" style="display:<?php echo $layer_onload; ?>;">
                    <div class="form-group short">
                        <label>
                            <span><?php echo $hesklang['attach_num']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#38','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="text" class="form-control" name="s_max_number" maxlength="2" id="a1" value="<?php echo $hesk_settings['attachments']['max_number']; ?>" <?php echo $onload_status; ?>>
                    </div>
                    <?php
                    $suffixes = array(
                        'B'  => $hesklang['B'] . ' (' . $hesklang['bytes'] . ')',
                        'kB' => $hesklang['kB'] . ' (' . $hesklang['kilobytes'] . ')',
                        'MB' => $hesklang['MB'] . ' (' . $hesklang['megabytes'] . ')',
                        'GB' => $hesklang['GB'] . ' (' . $hesklang['gigabytes'] . ')',
                    );
                    $tmp = hesk_formatBytes($hesk_settings['attachments']['max_size'], 0);
                    list($size, $unit) = explode(' ', $tmp);
                    ?>
                    <div class="form-group short">
                        <label>
                            <span><?php echo $hesklang['attach_size']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#39','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="text" class="form-control" name="s_max_size" maxlength="6" id="a2" value="<?php echo $size; ?>" <?php echo $onload_status; ?>>
                        <div class="dropdown-select center out-close">
                            <select name="s_max_unit" id="a4" <?php echo $onload_status; ?>>
                                <?php
                                foreach ($suffixes as $k => $v) {
                                    if ($k == $unit) {
                                        echo '<option value="'.$k.'" selected>'.$v.'</option>';
                                    } else {
                                        echo '<option value="'.$k.'">'.$v.'</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>
                            <span><?php echo $hesklang['attach_type']; ?></span>
                            <a onclick="hesk_window('<?php echo $help_folder; ?>helpdesk.html#40','400','500')">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </label>
                        <input type="text" class="form-control" name="s_allowed_types" maxlength="255" id="a3" value="<?php echo implode(',',$hesk_settings['attachments']['allowed_types']); ?>" <?php echo $onload_status; ?>>
                    </div>
                </div>
            </section>
            <div class="settings__form_submit">
                <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                <input type="hidden" name="section" value="HELP_DESK">
                <button style="display: inline-flex" type="submit" id="submitbutton" class="btn btn-full" ripple="ripple"
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
