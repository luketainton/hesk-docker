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
define('CALENDAR',1);

// Get all the req files and functions
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');
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

// List of categories
$hesk_settings['categories'] = array();
$res = hesk_dbQuery("SELECT `id`, `name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` ORDER BY `cat_order` ASC");
while ($row=hesk_dbFetchAssoc($res))
{
	$hesk_settings['categories'][$row['id']] = $row['name'];
}

// What should we do?
if ( $action = hesk_REQUEST('a') )
{
	if ($action == 'edit_cf') {edit_cf();}
	elseif ( defined('HESK_DEMO') ) {hesk_process_messages($hesklang['ddemo'], 'custom_fields.php', 'NOTICE');}
	elseif ($action == 'new_cf') {new_cf();}
	elseif ($action == 'save_cf') {save_cf();}
	elseif ($action == 'order_cf') {order_cf();}
	elseif ($action == 'remove_cf') {remove_cf();}
}

// Print header
require_once(HESK_PATH . 'inc/header.inc.php');

// Print main manage users page
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');

/* This will handle error, success and notice messages */
if (!hesk_SESSION(array('new_cf','errors'))) {
    hesk_handle_messages();
}

// Did we reach the custom fields limit?
if ($hesk_settings['num_custom_fields'] >= 50 && $action !== 'edit_cf')
{
    hesk_show_info($hesklang['cf_limit']);
}
?>

<div class="main__content tools">
    <section class="tools__between-head wider">
        <h2><?php echo $hesklang['tab_4']; ?></h2>
        <?php if ($hesk_settings['num_custom_fields'] < 50 && $action !== 'edit_cf'): ?>
        <div class="btn btn--blue-border" ripple="ripple" data-action="create-custom-field">
            <?php echo $hesklang['new_cf']; ?>
        </div>
        <?php endif; ?>
    </section>
    <div class="table-wrapper custom-field">
        <div class="table">
            <table id="default-table" class="table sindu-table">
                <thead>
                <tr>
                    <th><?php echo $hesklang['custom_n']; ?></th>
                    <th><?php echo $hesklang['s_type']; ?></th>
                    <th><?php echo $hesklang['visibility']; ?></th>
                    <th><?php echo $hesklang['custom_r']; ?></th>
                    <th><?php echo $hesklang['category']; ?></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php if ($hesk_settings['num_custom_fields'] < 1): ?>
                <tr>
                    <td colspan="6">
                        <?php echo $hesklang['no_cf']; ?>
                    </td>
                </tr>
                <?php
                endif;

                $num_before = 0;
                $num_after = 0;

                foreach ($hesk_settings['custom_fields'] as $tmp_id => $cf) {
                    if ($cf['place']) {
                        $num_after++;
                    } else {
                        $num_before++;
                    }
                }

                $k = 1;
                $first_before_custom_field = true;
                $first_after_custom_field = true;
                $hide_up = false;

                foreach ($hesk_settings['custom_fields'] as $tmp_id => $cf) {
                    $tmp_id = intval(str_replace('custom', '', $tmp_id));

                    if ($hide_up)
                    {
                        $hide_up = false;
                    }

                    if ($first_before_custom_field && $cf['place'] == 0) {
                        ?>
                        <tr class="title">
                            <td colspan="6"><?php echo $hesklang['place_before']; ?></td>
                        </tr>
                        <?php
                        $first_before_custom_field = false;
                    } elseif ($first_after_custom_field && $cf['place'] == 1) {
                        ?>
                        <tr class="title">
                            <td colspan="6"><?php echo $hesklang['place_after']; ?></td>
                        </tr>
                        <?php
                        $after = false;
                        $first_after_custom_field = false;
                        $hide_up = true;
                    }

                    $cf['type'] = hesk_custom_field_type($cf['type']);

                    $cf['use'] = ($cf['use'] == 1) ? $hesklang['cf_public'] : $hesklang['cf_private'];

                    $cf['req'] = ($cf['req'] == 0) ? $hesklang['no'] : ($cf['req'] == 2 ? $hesklang['yes'] : $hesklang['cf_cust']);

                    $cf['category'] = count($cf['category']) ? $hesklang['cf_cat'] : $hesklang['cf_all'];

                    $table_row = '';
                    if (isset($_SESSION['cford']) && $_SESSION['cford'] == $tmp_id) {
                        $table_row = 'class="ticket-new"';
                        unset($_SESSION['cford']);
                    }

                    ?>
                    <tr <?php echo $table_row; ?>>
                        <td><?php echo $cf['name']; ?></td>
                        <td><?php echo $cf['type']; ?></td>
                        <td><?php echo $cf['use']; ?></td>
                        <td><?php echo $cf['req']; ?></td>
                        <td><?php echo $cf['category']; ?></td>
                        <td class="nowrap buttons">
                            <?php $modal_id = hesk_generate_delete_modal($hesklang['confirm_deletion'],
                                $hesklang['del_cf'],
                                'custom_fields.php?a=remove_cf&amp;id='. $tmp_id .'&amp;token='. hesk_token_echo(0)); ?>
                            <p>
                                <?php
                                if ($hesk_settings['num_custom_fields'] == 2 && $num_before == 1)
                                {
                                    // Special case, don't print anything
                                }
                                elseif ($hesk_settings['num_custom_fields'] > 1)
                                {
                                    if (($num_before == 1 && $cf['place'] == 0) || ($num_after == 1 && $cf['place'] == 1))
                                    {
                                        // Only 1 custom fields in this place, don't print anything
                                        ?>
                                        <a href="#" style="visibility: hidden">
                                            <svg class="icon icon-chevron-up">
                                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                            </svg>
                                        </a>
                                        <a href="#" style="visibility: hidden"
                                           title="<?php echo $hesklang['move_dn']; ?>">
                                            <svg class="icon icon-chevron-down">
                                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                            </svg>
                                        </a>
                                        <?php
                                    }
                                    elseif ($k == 1 || $hide_up)
                                    {
                                        ?>
                                        <a href="#" style="visibility: hidden">
                                            <svg class="icon icon-chevron-up">
                                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                            </svg>
                                        </a>
                                        <a href="custom_fields.php?a=order_cf&amp;id=<?php echo $tmp_id; ?>&amp;move=15&amp;token=<?php hesk_token_echo(); ?>"
                                           title="<?php echo $hesklang['move_dn']; ?>">
                                            <svg class="icon icon-chevron-down">
                                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                            </svg>
                                        </a>
                                        <?php
                                    }
                                    elseif ($k == $hesk_settings['num_custom_fields'] || $k == $num_before)
                                    {
                                        ?>
                                        <a href="custom_fields.php?a=order_cf&amp;id=<?php echo $tmp_id; ?>&amp;move=-15&amp;token=<?php hesk_token_echo(); ?>"
                                           title="<?php echo $hesklang['move_up']; ?>">
                                            <svg class="icon icon-chevron-up">
                                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                            </svg>
                                        </a>
                                        <a href="#" style="visibility: hidden"
                                           title="<?php echo $hesklang['move_dn']; ?>">
                                            <svg class="icon icon-chevron-down">
                                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                            </svg>
                                        </a>
                                        <?php
                                    }
                                    else
                                    {
                                        ?>
                                        <a href="custom_fields.php?a=order_cf&amp;id=<?php echo $tmp_id; ?>&amp;move=-15&amp;token=<?php hesk_token_echo(); ?>"
                                           title="<?php echo $hesklang['move_up']; ?>">
                                            <svg class="icon icon-chevron-up">
                                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                            </svg>
                                        </a>
                                        <a href="custom_fields.php?a=order_cf&amp;id=<?php echo $tmp_id; ?>&amp;move=15&amp;token=<?php hesk_token_echo(); ?>"
                                           title="<?php echo $hesklang['move_dn']; ?>">
                                            <svg class="icon icon-chevron-down">
                                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                            </svg>
                                        </a>
                                        <?php
                                    }
                                }
                                ?>
                                <a href="custom_fields.php?a=edit_cf&amp;id=<?php echo $tmp_id; ?>"
                                   title="<?php echo $hesklang['edit']; ?>"
                                   class="edit">
                                    <svg class="icon icon-edit-ticket">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-edit-ticket"></use>
                                    </svg>
                                </a>
                                <a href="javascript:"
                                   data-modal="[data-modal-id='<?php echo $modal_id; ?>']"
                                   title="<?php echo $hesklang['delete']; ?>"
                                   class="delete">
                                    <svg class="icon icon-delete">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-delete"></use>
                                    </svg>
                                </a>
                            </p>
                        </td>
                    </tr>
                    <?php
                    $k++;
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<form action="custom_fields.php" method="post" name="form1" class="form right-bar create-custom-field <?php echo hesk_SESSION(array('new_cf','errors')) ? 'invalid' : ''; ?>"
      <?php if ($action === 'edit_cf' || hesk_SESSION(array('new_cf','errors'))) { ?>style="display: block"<?php } ?>>
    <div class="right-bar__body form">
        <h3>
            <a href="<?php echo $action === 'edit_cf' ? 'custom_fields.php' : 'javascript:' ?>">
                <svg class="icon icon-back">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-back"></use>
                </svg>
                <span><?php echo hesk_SESSION('edit_cf') ? $hesklang['edit_cf'] : $hesklang['new_cf']; ?></span>
            </a>
        </h3>
        <?php
        if (hesk_SESSION(array('new_cf','errors'))) {
            hesk_handle_messages();
        }
        ?>

        <h4><?php echo $hesklang['custom_n']; ?></h4>
        <section class="item--section">
            <?php
            $names = hesk_SESSION(array('new_cf','names'));
            $errors = hesk_SESSION(array('new_cf','errors'));
            $errors = is_array($errors) ? $errors : array();

            if ($hesk_settings['can_sel_lang'] && count($hesk_settings['languages']) > 1) {
                foreach ($hesk_settings['languages'] as $lang => $info): ?>
                    <div class="form-group">
                        <label><?php echo $lang; ?></label>
                        <input type="text" name="name[<?php echo $lang; ?>]" class="form-control <?php echo in_array('name', $errors) ? 'isError' : ''; ?>"
                               value="<?php (isset($names[$lang]) ? $names[$lang] : ''); ?>">
                    </div>
                <?php
                endforeach;
            } else { ?>
                <div class="form-group">
                    <label><?php echo $hesk_settings['language']; ?></label>
                    <input type="text" name="name[<?php echo $hesk_settings['language']; ?>]" class="form-control <?php echo in_array('name', $errors) ? 'isError' : ''; ?>"
                           value="<?php echo isset($names[$hesk_settings['language']]) ? $names[$hesk_settings['language']] : ''; ?>" />
                </div>
            <?php } ?>
            <div class="form-select">
                <label><?php echo $hesklang['s_type']; ?></label>
                <div class="dropdown-select center out-close">
                    <select name="type" onchange="hesk_setType(this.value);">
                        <?php $type = hesk_SESSION(array('new_cf','type'), 'text'); ?>
                        <option value="text"     <?php if ($type == 'text') {echo 'selected';} ?> ><?php echo $hesklang['stf']; ?></option>
                        <option value="textarea" <?php if ($type == 'textarea') {echo 'selected';} ?> ><?php echo $hesklang['stb']; ?></option>
                        <option value="radio"    <?php if ($type == 'radio') {echo 'selected';} ?> ><?php echo $hesklang['srb']; ?></option>
                        <option value="select"   <?php if ($type == 'select') {echo 'selected';} ?> ><?php echo $hesklang['ssb']; ?></option>
                        <option value="checkbox" <?php if ($type == 'checkbox') {echo 'selected';} ?> ><?php echo $hesklang['scb']; ?></option>
                        <option value="date"     <?php if ($type == 'date') {echo 'selected';} ?> ><?php echo $hesklang['date']; ?></option>
                        <option value="email"    <?php if ($type == 'email') {echo 'selected';} ?> ><?php echo $hesklang['email']; ?></option>
                        <option value="hidden"   <?php if ($type == 'hidden') {echo 'selected';} ?> ><?php echo $hesklang['sch']; ?></option>
                    </select>
                </div>
            </div>
            <?php
            $value = hesk_SESSION(array('new_cf','value'));

            if (is_string($value))
            {
                $value = json_decode($value, true);
            }
            ?>
            <div id="text" style="display:<?php echo ($type == 'text') ? 'block' : 'none' ?>">
                <div class="form-group">
                    <label><?php echo $hesklang['custom_l']; ?></label>
                    <input type="text" name="max_length" value="<?php echo isset($value['max_length']) ? intval($value['max_length']) : '255'; ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label><?php echo $hesklang['defw']; ?></label>
                    <input type="text" class="form-control" name="default_value" value="<?php echo isset($value['default_value']) ? $value['default_value'] : ''; ?>">
                </div>
            </div>
            <div id="textarea" style="display:<?php echo ($type == 'textarea') ? 'block' : 'none' ?>">
                <div class="form-group">
                    <label><?php echo $hesklang['rows']; ?></label>
                    <input type="text" class="form-control" name="rows" value="<?php echo isset($value['rows']) ? intval($value['rows']) : '12'; ?>">
                </div>
                <div class="form-group">
                    <label><?php echo $hesklang['cols']; ?></label>
                    <input type="text" class="form-control" name="cols" value="<?php echo isset($value['cols']) ? intval($value['cols']) : '60'; ?>">
                </div>
            </div>
            <div id="radio" style="display:<?php echo ($type == 'radio') ? 'block' : 'none' ?>">
                <?php echo $hesklang['opt2']; ?>
                <div class="category-create__autoassign">
                    <label class="switch-checkbox">
                        <input value="1" name="no_default" type="checkbox" id="no_default" <?php if (!empty($value['no_default'])) {echo 'checked';} ?>>
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
                        <span><?php echo $hesklang['rcheck']; ?></span>
                    </label>
                </div>
                <div class="form-group">
                    <textarea name="radio_options"
                              class="form-control <?php echo in_array('radio_options', $errors) ? 'isError' : ''; ?>"
                              rows="8"
                              cols="40"
                              style="height: inherit;"><?php echo (isset($value['radio_options']) && is_array($value['radio_options'])) ? implode("\n", $value['radio_options']) : ''; ?></textarea>
                </div>
            </div>
            <div id="select" style="display:<?php echo ($type == 'select') ? 'block' : 'none' ?>">
                <p><?php echo $hesklang['opt3']; ?></p>
                <div class="category-create__autoassign">
                    <label class="switch-checkbox">
                        <input value="1" name="show_select" type="checkbox" id="show_select" <?php if ( ! empty($value['show_select'])) {echo 'checked';} ?>>
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
                        <span><?php echo $hesklang['show_select']; ?></span>
                    </label>
                </div>
                <div class="form-group">
                    <textarea name="select_options"
                              class="form-control <?php echo in_array('select_options', $errors) ? 'isError' : ''; ?>"
                              style="height: inherit"
                              rows="6"
                              cols="40"><?php echo isset($value['select_options']) && is_array($value['select_options']) ? implode("\n", $value['select_options']) : ''; ?></textarea>
                </div>
            </div>
            <div id="checkbox" style="display:<?php echo ($type == 'checkbox') ? 'block' : 'none' ?>">
                <p><?php echo $hesklang['opt4']; ?></p>
                <div class="form-group">
                    <textarea name="checkbox_options"
                              class="form-control <?php echo in_array('checkbox_options', $errors) ? 'isError' : ''; ?>"
                              style="height: inherit"
                              rows="6"
                              cols="40"><?php echo isset($value['checkbox_options']) && is_array($value['checkbox_options']) ? implode("\n", $value['checkbox_options']) : ''; ?></textarea>
                </div>
            </div>
            <div id="date" style="display:<?php echo ($type == 'date') ? 'block' : 'none' ?>">
                <div class="form-group">
                    <?php
                    // min date
                    $dmin = isset($value['dmin']) ? $value['dmin'] : '';

                    // Defaults
                    $dmin_pm = '+';
                    $dmin_num = 1;
                    $dmin_type = 'day';

                    // Minimum date is in "+1 day" format
                    if (preg_match("/^([+-]{1})(\d+) (day|week|month|year)$/", $dmin, $matches))
                    {
                        $dmin = '';
                        $dmin_rf = 2;
                        $dmin_pm = $matches[1];
                        $dmin_num = $matches[2];
                        $dmin_type = $matches[3];
                    }
                    // Minimum date is in "MM/DD/YYYY" format
                    elseif (preg_match("/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/", $dmin))
                    {
                        $dmin_rf = 1;
                    }
                    else
                    {
                        $dmin = '';
                        $dmin_rf = 0;
                    }
                    ?>
                    <label><?php echo $hesklang['dmin']; ?></label>
                    <div class="radio-custom">
                        <input type="radio" name="dmin_rf" id="dmin_rf0" value="0" <?php if ($dmin_rf == 0) {echo 'checked';} ?>>
                        <label for="dmin_rf0"><?php echo $hesklang['d_any']; ?></label>
                    </div>
                    <div class="radio-custom">
                        <input type="radio" name="dmin_rf" id="dmin_rf1" value="1" <?php if ($dmin_rf == 1) {echo 'checked';} ?>>
                        <label for="dmin_rf1"><?php echo $hesklang['d_fixed']; ?></label>
                        <section class="param calendar" style="margin-left: 10px;">
                            <div class="calendar--button">
                                <button type="button" onclick="document.getElementById('dmin_rf1').checked = true">
                                    <svg class="icon icon-calendar">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-calendar"></use>
                                    </svg>
                                </button>
                                <input name="dmin"
                                       id="dmin"
                                    <?php if ($dmin) {echo 'value="'.$dmin.'"';} ?>
                                       type="text" class="datepicker <?php echo in_array('date_range', $errors) ? 'isError' : ''; ?>">
                            </div>
                            <div class="calendar--value" <?php echo ($dmin ? 'style="display: block"' : ''); ?>>
                                <span><?php echo $dmin; ?></span>
                                <i class="close">
                                    <svg class="icon icon-close">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                                    </svg>
                                </i>
                            </div>
                        </section>
                    </div>
                    <div class="radio-custom">
                        <input type="radio" name="dmin_rf" id="dmin_rf2" value="2" <?php if ($dmin_rf == 2) {echo 'checked';} ?>>
                        <label for="dmin_rf2"><?php echo $hesklang['d_relative']; ?></label>
                        <div class="dropdown-select center out-close" style="margin-left: 5px;">
                            <select class="form-control" name="dmin_pm" onclick="document.getElementById('dmin_rf2').checked = true" onchange="document.getElementById('dmin_rf2').checked = true">
                                <option <?php if ($dmin_pm == '+') {echo 'selected';} ?>>+</option>
                                <option <?php if ($dmin_pm == '-') {echo 'selected';} ?>>-</option>
                            </select>
                        </div>
                        <input type="text" class="form-control" style="height: inherit; width: inherit; margin-left: 5px; margin-right: 5px;"
                               name="dmin_num" value="<?php echo $dmin_num; ?>"
                               onclick="document.getElementById('dmin_rf2').checked = true" onchange="document.getElementById('dmin_rf2').checked = true">
                        <div class="dropdown-select center out-close">
                            <select name="dmin_type" onclick="document.getElementById('dmin_rf2').checked = true" onchange="document.getElementById('dmin_rf2').checked = true">
                                <option value="day"   <?php if ($dmin_type == 'day') {echo 'selected';} ?>><?php echo $hesklang['d_day']; ?></option>
                                <option value="week"  <?php if ($dmin_type == 'week') {echo 'selected';} ?>><?php echo $hesklang['d_week']; ?></option>
                                <option value="month" <?php if ($dmin_type == 'month') {echo 'selected';} ?>><?php echo $hesklang['d_month']; ?></option>
                                <option value="year"  <?php if ($dmin_type == 'year') {echo 'selected';} ?>><?php echo $hesklang['d_year']; ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <?php
                    // max date
                    $dmax = isset($value['dmax']) ? $value['dmax'] : '';

                    // Defaults
                    $dmax_pm = '+';
                    $dmax_num = 1;
                    $dmax_type = 'day';

                    // Minimum date is in "+1 day" format
                    if (preg_match("/^([+-]{1})(\d+) (day|week|month|year)$/", $dmax, $matches))
                    {
                        $dmax = '';
                        $dmax_rf = 2;
                        $dmax_pm = $matches[1];
                        $dmax_num = $matches[2];
                        $dmax_type = $matches[3];
                    }
                    // Minimum date is in "MM/DD/YYYY" format
                    elseif (preg_match("/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/", $dmax))
                    {
                        $dmax_rf = 1;
                    }
                    else
                    {
                        $dmax = '';
                        $dmax_rf = 0;
                    }
                    ?>
                    <label><?php echo $hesklang['dmax']; ?></label>
                    <div class="radio-custom">
                        <input type="radio" name="dmax_rf" id="dmax_rf0" value="0" <?php if ($dmax_rf == 0) {echo 'checked';} ?>>
                        <label for="dmax_rf0"><?php echo $hesklang['d_any']; ?></label>
                    </div>
                    <div class="radio-custom">
                        <input type="radio" name="dmax_rf" id="dmax_rf1" value="1" <?php if ($dmax_rf == 1) {echo 'checked';} ?>>
                        <label for="dmax_rf1"><?php echo $hesklang['d_fixed']; ?></label>
                        <section class="param calendar" style="margin-left: 10px;">
                            <div class="calendar--button">
                                <button type="button" onclick="document.getElementById('dmax_rf1').checked = true">
                                    <svg class="icon icon-calendar">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-calendar"></use>
                                    </svg>
                                </button>
                                <input name="dmax"
                                       id="dmax"
                                    <?php if ($dmax) {echo 'value="'.$dmax.'"';} ?>
                                       type="text" class="datepicker <?php echo in_array('date_range', $errors) ? 'isError' : ''; ?>">
                            </div>
                            <div class="calendar--value" <?php echo ($dmax ? 'style="display: block"' : ''); ?>>
                                <span><?php echo $dmax; ?></span>
                                <i class="close">
                                    <svg class="icon icon-close">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                                    </svg>
                                </i>
                            </div>
                        </section>
                    </div>
                    <div class="radio-custom">
                        <input type="radio" name="dmax_rf" id="dmax_rf2" value="2" <?php if ($dmax_rf == 2) {echo 'checked';} ?>>
                        <label for="dmax_rf2"><?php echo $hesklang['d_relative']; ?></label>
                        <div class="dropdown-select center out-close" style="margin-left: 5px;">
                            <select class="form-control" name="dmax_pm" onclick="document.getElementById('dmax_rf2').checked = true" onchange="document.getElementById('dmin_rf2').checked = true">
                                <option <?php if ($dmax_pm == '+') {echo 'selected';} ?>>+</option>
                                <option <?php if ($dmax_pm == '-') {echo 'selected';} ?>>-</option>
                            </select>
                        </div>
                        <input type="text" class="form-control" style="height: inherit; width: inherit; margin-left: 5px; margin-right: 5px;"
                               name="dmax_num" value="<?php echo $dmax_num; ?>"
                               onclick="document.getElementById('dmax_rf2').checked = true" onchange="document.getElementById('dmax_rf2').checked = true">
                        <div class="dropdown-select center out-close">
                            <select name="dmax_type" onclick="document.getElementById('dmax_rf2').checked = true" onchange="document.getElementById('dmax_rf2').checked = true">
                                <option value="day"   <?php if ($dmax_type == 'day') {echo 'selected';} ?>><?php echo $hesklang['d_day']; ?></option>
                                <option value="week"  <?php if ($dmax_type == 'week') {echo 'selected';} ?>><?php echo $hesklang['d_week']; ?></option>
                                <option value="month" <?php if ($dmax_type == 'month') {echo 'selected';} ?>><?php echo $hesklang['d_month']; ?></option>
                                <option value="year"  <?php if ($dmax_type == 'year') {echo 'selected';} ?>><?php echo $hesklang['d_year']; ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo $hesklang['d_format']; ?></label>
                    <?php
                    $date_format = isset($value['date_format']) ? $value['date_format'] : 'F j, Y';

                    $default_formats = array(
                        'm/d/Y',
                        'd/m/Y',
                        'm-d-Y',
                        'd-m-Y',
                        'd.m.Y',
                        'M j Y',
                        'j M Y',
                        'j M y',
                        'F j, Y',
                    );

                    $time = mktime(0, 0, 0, 12, 30, date('Y'));

                    foreach ($default_formats as $format): ?>
                        <div class="radio-custom">
                            <input type="radio" name="date_format" id="format_<?php echo $format; ?>" value="<?php echo $format; ?>" <?php echo $date_format == $format ? 'checked' : ''; ?>>
                            <label for="format_<?php echo $format; ?>"><?php echo date($format, $time); ?></label>
                        </div>
                    <?php endforeach; ?>
                    <div class="radio-custom">
                        <input type="radio" name="date_format" value="custom" id="d_custom" <?php if (!in_array($date_format, $default_formats)) {echo 'checked';} ?>>
                        <label for="d_custom"><?php echo $hesklang['d_custom']; ?></label>
                        <input type="text"
                               class="form-control"
                               name="date_format_custom"
                               style="height: inherit; width: inherit; margin-left: 5px;"
                               value="<?php echo $date_format; ?>"
                               onclick="document.getElementById('d_custom').checked = true" onchange="document.getElementById('d_custom').checked = true">
                    </div>
                </div>
                <p><?php echo $hesklang['d_ci']; ?></p>
            </div>
            <div id="email" style="display:<?php echo ($type == 'email') ? 'block' : 'none' ?>">
                <div class="form-group">
                    <label><?php echo $hesklang['meml3']; ?></label>
                    <?php $email_multi = empty($value['multiple']) ? 0 : 1; ?>
                    <div class="radio-custom">
                        <input type="radio" name="email_multi" id="email_multi0" value="0" <?php if ($email_multi == 0) {echo 'checked';} ?>>
                        <label for="email_multi0"><?php echo $hesklang['no']; ?></label>
                    </div>
                    <div class="radio-custom">
                        <input type="radio" name="email_multi" id="email_multi1" value="1" <?php if ($email_multi == 1) {echo 'checked';} ?>>
                        <label for="email_multi0"><?php echo $hesklang['yes']; ?></label>
                    </div>
                </div>
            </div>
            <div id="hidden" style="display:<?php echo ($type == 'hidden') ? 'block' : 'none' ?>">
                <p><?php echo $hesklang['hidf']; ?></p>
                <div class="form-group">
                    <label><?php echo $hesklang['custom_l']; ?></label>
                    <input type="text" class="form-control" name="hidden_max_length" value="<?php echo isset($value['max_length']) ? intval($value['max_length']) : '255'; ?>">
                </div>
                <div class="form-group">
                    <label><?php echo $hesklang['defw']; ?></label>
                    <input type="text" class="form-control" name="hidden_default_value" value="<?php echo isset($value['default_value']) ? $value['default_value'] : ''; ?>">
                </div>
            </div>
        </section>
        <h4><?php echo $hesklang['visibility']; ?></h4>
        <section class="item--section">
            <?php $use = hesk_SESSION(array('new_cf','use'), 1); ?>
            <div class="radio-custom">
                <input type="radio" name="use" id="use1" value="1" onchange="hesk_setRadioOptions();" <?php if ($use == 1) {echo 'checked';} ?>>
                <label for="use1"><?php echo $hesklang['cf_public']; ?></label>
            </div>
            <div class="radio-custom">
                <input type="radio" name="use" id="use2" value="2" onchange="hesk_setRadioOptions();" <?php if ($use == 2) {echo 'checked';} ?>>
                <label for="use2"><?php echo $hesklang['cf_private']; ?></label>
            </div>
        </section>
        <h4><?php echo $hesklang['custom_r']; ?></h4>
        <section class="item--section">
            <?php $req = hesk_SESSION(array('new_cf','req'), 0); ?>
            <div class="radio-custom">
                <input type="radio" name="req" id="req0" value="0" <?php if ($req == 0) {echo 'checked';} ?>>
                <label for="req0"><?php echo $hesklang['no']; ?></label>
            </div>
            <div class="radio-custom">
                <input type="radio" name="req" id="req2" value="2" <?php if ($req == 2) {echo 'checked';} ?>>
                <label for="req2"><?php echo $hesklang['yes']; ?></label>
            </div>
            <div class="radio-custom" id="req_customers" style="display:<?php echo ($use == 2) ? 'none' : 'inline'; ?>">
                <input type="radio" name="req" id="req1" value="1" <?php if ($req == 1) {echo 'checked';} ?>>
                <label for="req1"><?php echo $hesklang['cf_cust']; ?></label>
            </div>
        </section>
        <h4><?php echo $hesklang['custom_place']; ?></h4>
        <section class="item--section">
            <?php $place = hesk_SESSION(array('new_cf','place')) ? 1 : 0; ?>
            <div class="radio-custom">
                <input type="radio" name="place" value="0" id="place0" <?php if ($place == 0) {echo 'checked';} ?>>
                <label for="place0"><?php echo $hesklang['place_before']; ?></label>
            </div>
            <div class="radio-custom">
                <input type="radio" name="place" value="1" id="place1" <?php if ($place == 1) {echo 'checked';} ?>>
                <label for="place1"><?php echo $hesklang['place_after']; ?></label>
            </div>
        </section>
        <h4><?php echo $hesklang['category']; ?></h4>
        <section class="item--section">
            <?php $category = hesk_SESSION(array('new_cf','category')) ? 1 : 0; ?>
            <div class="radio-custom">
                <input type="radio" name="category" id="category0" value="0" onchange="hesk_setRadioOptions();" <?php if ($category == 0) {echo 'checked';} ?>>
                <label for="category0"><?php echo $hesklang['cf_all']; ?></label>
            </div>
            <div class="radio-custom">
                <input type="radio" name="category" id="category1" value="1" onchange="hesk_setRadioOptions();" <?php if ($category == 1) {echo 'checked';} ?>>
                <label for="category1"><?php echo $hesklang['cf_cat']; ?></label>
            </div>
            <div id="selcat" style="display:<?php echo $category ? 'block' : 'none'; ?>">
                <select class="multiple form-control <?php echo in_array('categories', $errors) ? 'isError' : ''; ?>" name="categories[]" multiple="multiple" size="10">
                    <?php
                    $categories = hesk_SESSION(array('new_cf','categories'));
                    $categories = is_array($categories) ? $categories : array();

                    foreach ($hesk_settings['categories'] as $cat_id => $cat_name)
                    {
                        echo '<option value="'.$cat_id.'"'.(in_array($cat_id, $categories) ? ' selected="selected"' : '').'>'.$cat_name.'</option>';
                    }
                    ?>
                </select>
                <?php echo $hesklang['cf_ctrl']; ?>
            </div>
        </section>
        <div class="right-bar__footer">
            <?php if (isset($_SESSION['edit_cf'])): ?>
                <input type="hidden" name="a" value="save_cf" />
                <input type="hidden" name="id" value="<?php echo intval($_SESSION['new_cf']['id']); ?>">
            <?php else: ?>
                <input type="hidden" name="a" value="new_cf">
            <?php endif; ?>
            <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
            <button class="btn btn-full" type="submit" ripple="ripple"><?php echo $hesklang['cf_save']; ?></button>
        </div>
    </div>
</form>

<script type="text/javascript"><!--
function hesk_toggleLayer(nr,setto) {
	if (document.all)
		document.all[nr].style.display = setto;
	else if (document.getElementById)
		document.getElementById(nr).style.display = setto;
}

function hesk_setType(myType) {
	var divs = new Array("text", "textarea", "radio", "select", "checkbox", "date", "email", "hidden");
	var index;
	var setTo;

	for (index = 0; index < divs.length; ++index) {
		setTo = (myType == divs[index] + "") ? 'block' : 'none';
		hesk_toggleLayer(divs[index], setTo);
	}
}

function hesk_setRadioOptions() {
	if(document.getElementById('use1').checked) {
		hesk_toggleLayer('req_customers', 'inline');
	} else {
		hesk_toggleLayer('req_customers', 'none');
		if(document.getElementById('req1').checked) {
			document.getElementById('req0').checked = true;
		}
	}

	if(document.getElementById('category1').checked) {
		hesk_toggleLayer('selcat', 'block');
	} else {
		hesk_toggleLayer('selcat', 'none');
	}
}
//-->
</script>
<?php
hesk_cleanSessionVars( array('new_cf', 'edit_cf') );

require_once(HESK_PATH . 'inc/footer.inc.php');
exit();

/*** START FUNCTIONS ***/

function save_cf()
{
	global $hesk_settings, $hesklang;
	global $hesk_error_buffer;

	// A security check
	# hesk_token_check('POST');

	// Get custom field ID
	$id = intval( hesk_POST('id') ) or hesk_error($hesklang['cf_e_id']);

	// Validate inputs
	if (($cf = cf_validate()) == false)
	{
		$_SESSION['edit_cf'] = true;
		$_SESSION['new_cf']['id'] = $id;

		$tmp = '';
		foreach ($hesk_error_buffer as $error)
		{
			$tmp .= "<li>$error</li>\n";
		}
		$hesk_error_buffer = $tmp;

		$hesk_error_buffer = $hesklang['rfm'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
		hesk_process_messages($hesk_error_buffer,'custom_fields.php');
	}

	// Add custom field data into database
	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_fields` SET
	`use`      = '{$cf['use']}',
	`place`    = '{$cf['place']}',
	`type`     = '{$cf['type']}',
	`req`      = '{$cf['req']}',
	`category` = ".(count($cf['categories']) ? "'".json_encode($cf['categories'])."'" : 'NULL').",
	`name`     = '".hesk_dbEscape($cf['names'])."',
	`value`    = ".(strlen($cf['value']) ? "'".hesk_dbEscape($cf['value'])."'" : 'NULL')."
	WHERE `id`={$id}");

	// Clear cache
	hesk_purge_cache('cf');

	// Show success
	$_SESSION['cford'] = $id;
	hesk_process_messages($hesklang['cf_mdf'],'custom_fields.php','SUCCESS');

} // End save_cf()


function edit_cf()
{
	global $hesk_settings, $hesklang;

	// Get custom field ID
	$id = intval( hesk_GET('id') ) or hesk_error($hesklang['cf_e_id']);

	// Get details from the database
	$res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_fields` WHERE `id`={$id} LIMIT 1");
	if ( hesk_dbNumRows($res) != 1 )
	{
		hesk_error($hesklang['cf_not_found']);
	}
	$cf = hesk_dbFetchAssoc($res);

	$cf['names'] = json_decode($cf['name'], true);
	unset($cf['name']);

	if (strlen($cf['category']))
	{
		$cf['categories'] = json_decode($cf['category'], true);
		$cf['category'] = 1;
	}
	else
	{
		$cf['categories'] = array();
		$cf['category'] = 0;
	}

	$_SESSION['new_cf'] = $cf;
	$_SESSION['edit_cf'] = true;

} // End edit_cf()


function order_cf()
{
	global $hesk_settings, $hesklang;

	// A security check
	hesk_token_check();

	// Get ID and move parameters
	$id    = intval( hesk_GET('id') ) or hesk_error($hesklang['cf_e_id']);
	$move  = intval( hesk_GET('move') );
	$_SESSION['cford'] = $id;

	// Update article details
	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_fields` SET `order`=`order`+".intval($move)." WHERE `id`={$id}");

	// Update order of all custom fields
	update_cf_order();

	// Clear cache
	hesk_purge_cache('cf');

	// Finish
	header('Location: custom_fields.php');
	exit();

} // End order_cf()


function update_cf_order()
{
	global $hesk_settings, $hesklang;

	// Get list of current custom fields
	$res = hesk_dbQuery("SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_fields` WHERE `use` IN ('1','2') ORDER BY `place` ASC, `order` ASC");

	// Update database
	$i = 10;
	while ( $cf = hesk_dbFetchAssoc($res) )
	{
		hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_fields` SET `order`=".intval($i)." WHERE `id`='".intval($cf['id'])."'");
		$i += 10;
	}

	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_fields` SET `order`=1000 WHERE `use`='0'");

	return true;

} // END update_cf_order()


function remove_cf()
{
	global $hesk_settings, $hesklang;

	// A security check
	hesk_token_check();

	// Get ID
	$id = intval( hesk_GET('id') ) or hesk_error($hesklang['cf_e_id']);

	// Reset the custom field
	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_fields` SET `use`='0', `place`='0', `type`='text', `req`='0', `category`=NULL, `name`='', `value`=NULL, `order`=1000 WHERE `id`={$id}");

	// Were we successful?
	if ( hesk_dbAffectedRows() == 1 )
	{
		// Update order
		update_cf_order();

		// Clear cache
		hesk_purge_cache('cf');

		// Delete custom field data from tickets
		hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `custom{$id}`=''");

		// Show success message
		hesk_process_messages($hesklang['cf_deleted'],'./custom_fields.php','SUCCESS');
	}
	else
	{
		hesk_process_messages($hesklang['cf_not_found'],'./custom_fields.php');
	}

} // End remove_cf()


function cf_validate()
{
	global $hesk_settings, $hesklang;
	global $hesk_error_buffer;

	$hesk_error_buffer = array();
	$errors = array();

	// Get names
	$cf['names'] = hesk_POST_array('name');

	// Make sure only valid names pass
	foreach ($cf['names'] as $key => $name)
	{
		if ( ! isset($hesk_settings['languages'][$key]))
		{
			unset($cf['names'][$key]);
		}
		else
		{
			$name = is_array($name) ? '' : hesk_input($name, 0, 0, HESK_SLASH);

			if (strlen($name) < 1)
			{
				unset($cf['names'][$key]);
			}
			else
			{
				$cf['names'][$key] = stripslashes($name);
			}
		}
	}

	// No name entered?
	if ( ! count($cf['names']))
	{
		$hesk_error_buffer[] = $hesklang['err_custname'];
		$errors[] = 'name';
	}

	// Get type and values
	$cf['type'] = hesk_POST('type');
	switch ($cf['type'])
	{
		case 'textarea':
			$cf['rows'] = hesk_checkMinMax(intval(hesk_POST('rows')), 1, 100, 12);
			$cf['cols'] = hesk_checkMinMax(intval(hesk_POST('cols')), 1, 500, 60);
			$cf['value'] = array('rows' => $cf['rows'], 'cols' => $cf['cols']);
			break;

		case 'radio':
			$cf['radio_options'] = stripslashes(hesk_input(hesk_POST('radio_options'), 0, 0, HESK_SLASH));

			$options = preg_split("/\\r\\n|\\r|\\n/", $cf['radio_options']);

			$no_default = hesk_POST('no_default') ? 1 : 0;

			$cf['value'] = array('radio_options' => $options, 'no_default' => $no_default);

			if (count($options) < 2)
			{
				$hesk_error_buffer[] = $hesklang['atl2'];
                $errors[] = 'radio_options';
			}

			break;

		case 'select':
			$cf['select_options'] = stripslashes(hesk_input(hesk_POST('select_options'), 0, 0, HESK_SLASH));

			$options = preg_split("/\\r\\n|\\r|\\n/", $cf['select_options']);

			$show_select = hesk_POST('show_select') ? 1 : 0;

			$cf['value'] = array('show_select' => $show_select, 'select_options' => $options);

			if (count($options) < 2)
			{
				$hesk_error_buffer[] = $hesklang['atl2'];
                $errors[] = 'select_options';
			}

			break;

		case 'checkbox':
			$cf['checkbox_options'] = stripslashes(hesk_input(hesk_POST('checkbox_options'), 0, 0, HESK_SLASH));

			$options = preg_split("/\\r\\n|\\r|\\n/", $cf['checkbox_options']);

			$cf['value'] = array('checkbox_options' => $options);

			if ( ! isset($options[0]) || strlen($options[0]) < 1)
			{
				$hesk_error_buffer[] = $hesklang['atl1'];
                $errors[] = 'checkbox_options';
			}

			break;

		case 'date':
        	$cf['dmin'] = '';
            $cf['dmax'] = '';

            // Minimum date
            $dmin_rf = hesk_POST('dmin_rf');

            if ($dmin_rf == 1)
            {
            	$dmin = hesk_POST('dmin');

            	if (preg_match("/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/", $dmin))
                {
                	$cf['dmin'] = $dmin;
                }
            }
            elseif ($dmin_rf == 2)
            {
				$dmin_pm = hesk_POST('dmin_pm') == '+' ? '+' : '-';
				$dmin_num = intval(hesk_POST('dmin_num', 0));
				$dmin_type = hesk_POST('dmin_type');
                if ( ! in_array($dmin_type, array('day', 'week', 'month', 'year')))
                {
                	$dmin_type = 'day';
                }

                $cf['dmin'] = $dmin_pm . $dmin_num . ' ' . $dmin_type;
            }

			// Maximum date
            $dmax_rf = hesk_POST('dmax_rf');

            if ($dmax_rf == 1)
            {
            	$dmax = hesk_POST('dmax');

            	if (preg_match("/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/", $dmax))
                {
                	$cf['dmax'] = $dmax;
                }
            }
            elseif ($dmax_rf == 2)
            {
				$dmax_pm = hesk_POST('dmax_pm') == '+' ? '+' : '-';
				$dmax_num = intval(hesk_POST('dmax_num', 0));
				$dmax_type = hesk_POST('dmax_type');
                if ( ! in_array($dmax_type, array('day', 'week', 'month', 'year')))
                {
                	$dmax_type = 'day';
                }

                $cf['dmax'] = $dmax_pm . $dmax_num . ' ' . $dmax_type;
            }

            // Minimum date should not be higher than maximum date
            if (strlen($cf['dmin']) && strlen($cf['dmax']))
            {
				if (strtotime($cf['dmin']) > strtotime($cf['dmax']))
				{
					$hesk_error_buffer[] = $hesklang['d_mm'];
                    $errors[] = 'date_range';
				}
            }

            // Date format
            $date_format = hesk_POST('date_format');
            if ($date_format == 'custom')
            {
            	$date_format = hesk_POST('date_format_custom');
            }

            $cf['date_format'] = preg_replace('/[^a-zA-Z0-9 \/\.\_+\-,;:#(){}\[\]\'@*]/', '', $date_format);

            $cf['value'] = array('dmin' => $cf['dmin'], 'dmax' => $cf['dmax'], 'date_format' => $cf['date_format']);

			break;

		case 'email':
			$cf['email_multi'] = hesk_POST('email_multi') ? 1 : 0;
			$cf['value'] = array('multiple' => $cf['email_multi']);
			break;

		case 'hidden':
			$cf['hidden_max_length'] = hesk_checkMinMax(intval(hesk_POST('hidden_max_length')), 1, 10000, 255);
			$cf['hidden_default_value'] = stripslashes(hesk_input(hesk_POST('hidden_default_value'), 0, 0, HESK_SLASH));
			$cf['value'] = array('max_length' => $cf['hidden_max_length'], 'default_value' => $cf['hidden_default_value']);
			break;

		default:
			$cf['type'] = 'text';
			$cf['max_length'] = hesk_checkMinMax(intval(hesk_POST('max_length')), 1, 10000, 255);
			$cf['default_value'] = stripslashes(hesk_input(hesk_POST('default_value'), 0, 0, HESK_SLASH));
			$cf['value'] = array('max_length' => $cf['max_length'], 'default_value' => $cf['default_value']);

	}

	// Enable
	$cf['use'] = hesk_POST('use') == 2 ? 2 : 1;

	// req
	$cf['req'] = hesk_POST('req');
	$cf['req'] = $cf['req'] == 2 ? 2 : ($cf['req'] == 1 ? 1 : 0);

	// Private fields cannot be req for customers
	if ($cf['use'] == 2 && $cf['req'] == 1)
	{
		$cf['req'] = 0;
	}

	// Located above or below "Message"?
	$cf['place'] = hesk_POST('place') ? 1 : 0;

	// Get allowed categories
	if (hesk_POST('category'))
	{
		$cf['category'] = 1;
		$cf['categories'] = hesk_POST_array('categories');

		foreach ($cf['categories'] as $key => $cat_id)
		{
			if ( ! isset($hesk_settings['categories'][$cat_id]) )
			{
				unset($cf['categories'][$key]);
			}
		}

		if ( ! count($cf['categories']))
		{
			$hesk_error_buffer[] = $hesklang['cf_nocat'];
            $errors[] = 'categories';
		}
	}
	else
	{
		$cf['category'] = 0;
		$cf['categories'] = array();
	}

	// Any errors?
	if (count($hesk_error_buffer))
	{
		$_SESSION['new_cf'] = $cf;
		$_SESSION['new_cf']['errors'] = $errors;
		return false;
	}

	$cf['names'] = addslashes(json_encode($cf['names']));
	$cf['value'] = $cf['type'] == 'date' ? json_encode($cf['value']) : addslashes(json_encode($cf['value']));

	return $cf;
} // END cf_validate()


function new_cf()
{
	global $hesk_settings, $hesklang;
	global $hesk_error_buffer;

	// A security check
	# hesk_token_check('POST');

	// Validate inputs
	if (($cf = cf_validate()) == false)
	{
		$tmp = '';
		foreach ($hesk_error_buffer as $error)
		{
			$tmp .= "<li>$error</li>\n";
		}
		$hesk_error_buffer = $tmp;

		$hesk_error_buffer = $hesklang['rfm'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
		hesk_process_messages($hesk_error_buffer,'custom_fields.php');
	}

	// Get the lowest available custom field ID
	$res = hesk_dbQuery("SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_fields` WHERE `use`='0' ORDER BY `id` ASC LIMIT 1");
	$row = hesk_dbFetchRow($res);
	$_SESSION['cford'] = intval($row[0]);

	// Insert custom field into database
	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_fields` SET
	`use`      = '{$cf['use']}',
	`place`    = '{$cf['place']}',
	`type`     = '{$cf['type']}',
	`req`      = '{$cf['req']}',
	`category` = ".(count($cf['categories']) ? "'".json_encode($cf['categories'])."'" : 'NULL').",
	`name`     = '".hesk_dbEscape($cf['names'])."',
	`value`    = ".(strlen($cf['value']) ? "'".hesk_dbEscape($cf['value'])."'" : 'NULL').",
	`order`    = 990
	WHERE `id`={$_SESSION['cford']}");

	// Update order
	update_cf_order();

	// Clear cache
	hesk_purge_cache('cf');

	// Show success
	hesk_process_messages($hesklang['cf_added'],'custom_fields.php','SUCCESS');

} // End new_cf()
