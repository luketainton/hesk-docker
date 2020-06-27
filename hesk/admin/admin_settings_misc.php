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
    require_once(HESK_PATH . 'inc/admin_settings_demo.inc.php');
}

/* This will handle error, success and notice messages */
hesk_handle_messages();
?>
<div class="main__content settings">

    <?php require_once(HESK_PATH . 'inc/admin_settings_status.inc.php'); ?>

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
        //-->
    </script>
    <form method="post" action="admin_settings_save.php" name="form1" onsubmit="return hesk_checkFields()">
        <div class="settings__form form">
            <section class="settings__form_block">
                <h3><?php echo $hesklang['dat']; ?></h3>
                <div class="form-group timezone">
                    <label>
                        <span><?php echo $hesklang['TZ']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>misc.html#63','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <?php
                    // Get list of supported timezones
                    $timezone_list = hesk_generate_timezone_list();

                    // Do we need to localize month names?
                    if ($hesk_settings['language'] != 'English')
                    {
                        $timezone_list = hesk_translate_timezone_list($timezone_list);
                    }
                    ?>
                    <select name="s_timezone" id="timezone-select">
                        <?php
                        foreach ($timezone_list as $timezone => $description)
                        {
                            echo '<option value="' . $timezone . '"' . ($hesk_settings['timezone'] == $timezone ? ' selected' : '') . '>' . $description . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['tfor']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>misc.html#20','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_timeformat" maxlength="255" value="<?php echo $hesk_settings['timeformat']; ?>">
                </div>
                <div class="radio-group">
                    <h5>
                        <span><?php echo $hesklang['tdis']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>misc.html#64','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <?php
                        $on = $hesk_settings['time_display'] ? 'checked="checked"' : '';
                        $off = $hesk_settings['time_display'] ? '' : 'checked="checked"';
                    ?>
                    <div class="radio-list">
                        <div class="radio-custom">
                            <input type="radio" id="s_time_display0" name="s_time_display" value="0" <?php echo $off; ?>>
                            <label for="s_time_display0"><?php echo $hesklang['tdisd']; ?></label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="s_time_display1" name="s_time_display" value="1" <?php echo $on; ?>>
                            <label for="s_time_display1"><?php echo $hesklang['tdisa']; ?></label>
                        </div>
                    </div>
                </div>
            </section>
            <section class="settings__form_block">
                <h3><?php echo $hesklang['other']; ?></h3>
                <div class="form-group">
                    <label>
                        <span><?php echo $hesklang['ip_whois']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>misc.html#61','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_ip_whois_url" maxlength="255" value="<?php echo $hesk_settings['ip_whois']; ?>">
                </div>
                <tr>
                    <td><label> </label></td>
                </tr>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['mms']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>misc.html#62','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="s_maintenance_mode1" name="s_maintenance_mode" value="1" <?php if ($hesk_settings['maintenance_mode']) {echo 'checked';} ?>>
                        <label for="s_maintenance_mode1"><?php echo $hesklang['mmd']; ?></label>
                    </div>
                </div>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['al']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>misc.html#21','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="s_alink1" name="s_alink" value="1" <?php if ($hesk_settings['alink']) {echo 'checked';} ?>/>
                        <label for="s_alink1"><?php echo $hesklang['dap']; ?></label>
                    </div>
                </div>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['subnot']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>misc.html#48','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="s_submit_notice1" name="s_submit_notice" value="1" <?php if ($hesk_settings['submit_notice']) {echo 'checked';} ?>/>
                        <label for="s_submit_notice1"><?php echo $hesklang['subnot2']; ?></label>
                    </div>
                </div>
                <div class="checkbox-group multiple-emails">
                    <h5>
                        <span><?php echo $hesklang['sonline']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>misc.html#56','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="s_online1" name="s_online" value="1" <?php if ($hesk_settings['online']) {echo 'checked';} ?>>
                        <label for="s_online1"><?php echo $hesklang['sonline2']; ?></label>
                        <div class="form-group">
                            <input type="text" name="s_online_min" class="form-control" maxlength="4" value="<?php echo $hesk_settings['online_min']; ?>">
                        </div>
                    </div>
                </div>
                <div class="checkbox-group">
                    <h5>
                        <span><?php echo $hesklang['updates']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>misc.html#59','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="s_check_updates1" name="s_check_updates" value="1" <?php if ($hesk_settings['check_updates']) {echo 'checked';} ?>>
                        <label for="s_check_updates1"><?php echo $hesklang['updates2']; ?></label>
                    </div>
                </div>
            </section>
            <div class="settings__form_submit">
                <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                <input type="hidden" name="section" value="MISC">
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
