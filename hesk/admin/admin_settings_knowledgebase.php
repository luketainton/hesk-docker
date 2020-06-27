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
                <h3><?php echo $hesklang['tab_3']; ?></h3>
                <div class="radio-group">
                    <h5>
                        <span><?php echo $hesklang['s_ekb']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>knowledgebase.html#22','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <?php
                    $on = $hesk_settings['kb_enable'] == 1 ? 'checked' : '';
                    $off = $hesk_settings['kb_enable'] ? '' : 'checked';
                    $only = $hesk_settings['kb_enable'] == 2 ? 'checked' : '';
                    ?>
                    <div class="radio-list">
                        <div class="radio-custom">
                            <input type="radio" id="s_kb_enable1" name="s_kb_enable" value="1" <?php echo $on; ?>>
                            <label for="s_kb_enable1"><?php echo $hesklang['ekb_y']; ?></label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="s_kb_enable2" name="s_kb_enable" value="2" <?php echo $only; ?>>
                            <label for="s_kb_enable2"><?php echo $hesklang['ekb_o']; ?></label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="s_kb_enable0" name="s_kb_enable" value="0" <?php echo $off; ?>>
                            <label for="s_kb_enable0"><?php echo $hesklang['ekb_n']; ?></label>
                        </div>
                    </div>
                </div>
            </section>
            <section class="settings__form_block">
                <h3><?php echo $hesklang['kb_set']; ?></h3>
                <div class="checkbox-group row">
                    <h5>
                        <span><?php echo $hesklang['swyse']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>knowledgebase.html#52','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_kb_wysiwyg" value="1" <?php if ($hesk_settings['kb_wysiwyg']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['s_suggest']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>knowledgebase.html#23','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_kb_recommendanswers" value="1" <?php if ($hesk_settings['kb_recommendanswers']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['s_kbr']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>knowledgebase.html#24','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_kb_rating" value="1" <?php if ($hesk_settings['kb_rating']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['sav']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>knowledgebase.html#58','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_kb_views" value="1" <?php if ($hesk_settings['kb_views']) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['sad']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>knowledgebase.html#59','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <label class="switch-checkbox">
                        <input type="checkbox" name="s_kb_date" value="1" <?php if ($hesk_settings['kb_date']) { echo 'checked'; } ?>>
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
                $off = $hesk_settings['kb_search'] ? '' : 'checked="checked"';
                $small = $hesk_settings['kb_search'] == 1 ? 'checked="checked"' : '';
                $large = $hesk_settings['kb_search'] == 2 ? 'checked="checked"' : '';
                ?>
                <div class="radio-group">
                    <h5>
                        <span><?php echo $hesklang['s_kbs']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>knowledgebase.html#25','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </h5>
                    <div class="radio-list">
                        <div class="radio-custom">
                            <input type="radio" id="s_kb_search0" name="s_kb_search" value="0" <?php echo $off; ?>>
                            <label for="s_kb_search0"><?php echo $hesklang['off']; ?></label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="s_kb_search1" name="s_kb_search" value="1" <?php echo $small; ?>>
                            <label for="s_kb_search1"><?php echo $hesklang['small']; ?></label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="s_kb_search2" name="s_kb_search" value="2" <?php echo $large; ?>>
                            <label for="s_kb_search2"><?php echo $hesklang['large']; ?></label>
                        </div>
                    </div>
                </div>
                <div class="form-group short">
                    <label>
                        <span><?php echo $hesklang['s_maxsr']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>knowledgebase.html#26','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_kb_search_limit" maxlength="3" value="<?php echo $hesk_settings['kb_search_limit']; ?>">
                </div>
                <div class="form-group short">
                    <label>
                        <span><?php echo $hesklang['s_ptxt']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>knowledgebase.html#27','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" name="s_kb_substrart" class="form-control" maxlength="5" value="<?php echo $hesk_settings['kb_substrart']; ?>">
                </div>
                <div class="form-group short">
                    <label>
                        <span><?php echo $hesklang['s_scol']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>knowledgebase.html#28','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_kb_cols" maxlength="2" value="<?php echo $hesk_settings['kb_cols']; ?>">
                </div>
                <div class="form-group short">
                    <label>
                        <span><?php echo $hesklang['s_psubart']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>knowledgebase.html#29','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_kb_numshow" maxlength="2" value="<?php echo $hesk_settings['kb_numshow']; ?>">
                </div>
                <div class="form-group short list">
                    <label>
                        <span><?php echo $hesklang['s_spop']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>knowledgebase.html#30','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <ul class="input-list">
                        <li style="display: list-item">
                            <input type="text" class="form-control" name="s_kb_index_popart" maxlength="2" value="<?php echo $hesk_settings['kb_index_popart']; ?>">
                            <?php echo $hesklang['s_onin']; ?>
                        </li>
                        <li style="display: list-item">
                            <input type="text" class="form-control" name="s_kb_popart" maxlength="2" value="<?php echo $hesk_settings['kb_popart']; ?>">
                            <?php echo $hesklang['s_onkb']; ?>
                        </li>
                    </ul>
                </div>
                <div class="form-group short list">
                    <label>
                        <span><?php echo $hesklang['s_slat']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>knowledgebase.html#31','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <ul class="input-list">
                        <li style="display: list-item">
                            <input type="text" class="form-control" name="s_kb_index_latest" maxlength="2" value="<?php echo $hesk_settings['kb_index_latest']; ?>">
                            <?php echo $hesklang['s_onin']; ?>
                        </li>
                        <li style="display: list-item">
                            <input type="text" class="form-control" name="s_kb_latest" maxlength="2" value="<?php echo $hesk_settings['kb_latest']; ?>">
                            <?php echo $hesklang['s_onkb']; ?>
                        </li>
                    </ul>
                </div>
                <div class="form-group short">
                    <label>
                        <span><?php echo $hesklang['s_relart']; ?></span>
                        <a onclick="hesk_window('<?php echo $help_folder; ?>knowledgebase.html#60','400','500')">
                            <div class="tooltype right">
                                <svg class="icon icon-info">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                                </svg>
                            </div>
                        </a>
                    </label>
                    <input type="text" class="form-control" name="s_kb_related" maxlength="2" value="<?php echo $hesk_settings['kb_related']; ?>">
                </div>
            </section>
            <div class="settings__form_submit">
                <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                <input type="hidden" name="section" value="KNOWLEDGEBASE">
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
