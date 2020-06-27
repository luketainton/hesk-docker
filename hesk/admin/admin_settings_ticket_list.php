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
        <section class="settings__form">
            <h3>
                <?php echo $hesklang['fitl']; ?>
                <a onclick="hesk_window('<?php echo $help_folder; ?>ticket_list.html#1','400','500')">
                    <div class="tooltype right">
                        <svg class="icon icon-info">
                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                        </svg>
                    </div>
                </a>
            </h3>
            <div class="checkbox-group list">
                <?php foreach ($hesk_settings['possible_ticket_list'] as $key => $title): ?>
                    <div class="checkbox-custom">
                        <input type="checkbox" name="s_tl_<?php echo $key; ?>" id="s_tl_<?php echo $key; ?>1" value="1" <?php echo in_array($key, $hesk_settings['ticket_list']) ? 'checked' : ''; ?>>
                        <label for="s_tl_<?php echo $key; ?>1"><?php echo $title; ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="settings__form">
            <h3><?php echo $hesklang['other']; ?></h3>
            <div class="radio-group">
                <h5>
                    <span><?php echo $hesklang['sdf']; ?></span>
                    <a onclick="hesk_window('<?php echo $help_folder; ?>ticket_list.html#2','400','500')">
                        <div class="tooltype right">
                            <svg class="icon icon-info">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                            </svg>
                        </div>
                    </a>
                </h5>
                <div class="radio-list">
                    <div class="radio-custom">
                        <input type="radio" id="s_submittedformat2" name="s_submittedformat" value="2" <?php echo $hesk_settings['submittedformat'] == 2 ? 'checked' : ''; ?>>
                        <label for="s_submittedformat2"><?php echo $hesklang['lcf2']; ?></label>
                    </div>
                    <div class="radio-custom">
                        <input type="radio" id="s_submittedformat1" name="s_submittedformat" value="1" <?php echo $hesk_settings['submittedformat'] == 1 ? 'checked' : ''; ?>>
                        <label for="s_submittedformat1"><?php echo $hesklang['lcf1']; ?></label>
                    </div>
                    <div class="radio-custom">
                        <input type="radio" id="s_submittedformat0" name="s_submittedformat" value="0" <?php echo $hesk_settings['submittedformat'] == 0 ? 'checked' : ''; ?>>
                        <label for="s_submittedformat0"><?php echo $hesklang['lcf0']; ?></label>
                    </div>
                </div>
            </div>
            <div class="radio-group">
                <h5>
                    <span><?php echo $hesklang['lcf']; ?></span>
                    <a onclick="hesk_window('<?php echo $help_folder; ?>ticket_list.html#2','400','500')">
                        <div class="tooltype right">
                            <svg class="icon icon-info">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                            </svg>
                        </div>
                    </a>
                </h5>
                <div class="radio-list">
                    <div class="radio-custom">
                        <input type="radio" id="s_updatedformat2" name="s_updatedformat" value="2" <?php echo $hesk_settings['updatedformat'] == 2 ? 'checked' : ''; ?>>
                        <label for="s_updatedformat2"><?php echo $hesklang['lcf2']; ?></label>
                    </div>
                    <div class="radio-custom">
                        <input type="radio" id="s_updatedformat1" name="s_updatedformat" value="1" <?php echo $hesk_settings['updatedformat'] == 1 ? 'checked' : ''; ?>>
                        <label for="s_updatedformat1"><?php echo $hesklang['lcf1']; ?></label>
                    </div>
                    <div class="radio-custom">
                        <input type="radio" id="s_updatedformat0" name="s_updatedformat" value="0" <?php echo $hesk_settings['updatedformat'] == 0 ? 'checked' : ''; ?>>
                        <label for="s_updatedformat0"><?php echo $hesklang['lcf0']; ?></label>
                    </div>
                </div>
            </div>
        </section>
        <div class="settings__form form" style="padding-top: 30px">
            <div class="settings__form_submit">
                <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                <input type="hidden" name="section" value="TICKET_LIST">
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
