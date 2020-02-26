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

/* Check if this is a valid include */
if (!defined('IN_SCRIPT')) {die('Invalid attempt');}


function hesk_profile_tab($session_array='new',$is_profile_page=true)
{
	global $hesk_settings, $hesklang, $can_reply_tickets, $can_view_tickets, $can_view_unassigned;

	$show_permissions = false;
	$show_preferences = false;

	$steps = array($hesklang['pinfo']);
	if (!$is_profile_page) {
	    $steps[] = $hesklang['permissions'];
	    $show_permissions = true;
    }
	$steps[] = $hesklang['sig'];
	if (!$is_profile_page || $can_reply_tickets) {
	    $steps[] = $hesklang['pref'];
	    $show_preferences = true;
    }
	$steps[] = $hesklang['notn'];

	$errors = hesk_SESSION(array($session_array, 'errors'));
	$errors = is_array($errors) ? $errors : array();
	?>
	<!-- TABS -->
    <ul class="step-bar">
        <?php
        $i = 1;
        foreach ($steps as $step_name): ?>
            <li data-link="<?php echo $i++; ?>" data-all="<?php echo count($steps); ?>">
                <?php echo $step_name; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
    $current_step = 1;
    ?>
    <div class="step-slider">
        <div class="step-item step-<?php echo $current_step++; ?>">
            <div class="form-group">
                <label for="prof_name"><?php echo $hesklang['real_name']; ?></label>
                <input type="text" class="form-control <?php echo in_array('name', $errors) ? 'isError' : ''; ?>" id="prof_name" name="name" maxlength="50"
                       value="<?php echo $_SESSION[$session_array]['name']; ?>">
            </div>
            <div class="form-group">
                <label for="prof_email"><?php echo $hesklang['email']; ?></label>
                <input type="text" class="form-control <?php echo in_array('email', $errors) ? 'isError' : ''; ?>" name="email" maxlength="255" id="prof_user"
                       value="<?php echo $_SESSION[$session_array]['email']; ?>">
            </div>
            <?php
            if ( ! $is_profile_page || $_SESSION['isadmin'])
            {
                ?>
                <div class="form-group">
                    <label for="prof_user"><?php echo $hesklang['username']; ?></label>
                    <input type="text" class="form-control <?php echo in_array('user', $errors) ? 'isError' : ''; ?>" name="user" autocomplete="off" id="prof_user" maxlength="20"
                           value="<?php echo $_SESSION[$session_array]['user']; ?>">
                </div>
                <?php
            }
            ?>
            <section class="item--section">
                <h4>
                    <?php echo $hesklang['pass']; ?>
                    <?php if ($is_profile_page): ?>
                    <span>
                        <?php echo $hesklang['optional']; ?>
                    </span>
                    <?php endif; ?>
                </h4>
                <div class="form-group">
                    <label for="prof_newpass"><?php echo $is_profile_page ? $hesklang['new_pass'] : $hesklang['pass']; ?></label>
                    <input type="password" id="prof_newpass" name="newpass" autocomplete="off" class="form-control <?php echo in_array('passwords', $errors) ? 'isError' : ''; ?>"
                           value="<?php echo isset($_SESSION[$session_array]['cleanpass']) ? $_SESSION[$session_array]['cleanpass'] : ''; ?>"
                           onkeyup="hesk_checkPassword(this.value)">
                </div>
                <div class="form-group">
                    <label for="prof_newpass2"><?php echo $hesklang['confirm_pass']; ?></label>
                    <input type="password" class="form-control <?php echo in_array('passwords', $errors) ? 'isError' : ''; ?>" id="prof_newpass2" name="newpass2" autocomplete="off"
                           value="<?php echo isset($_SESSION[$session_array]['cleanpass']) ? $_SESSION[$session_array]['cleanpass'] : ''; ?>">
                </div>
                <div class="form-group">
                    <label><?php echo $hesklang['pwdst']; ?></label>
                    <div style="border: 1px solid #d4d6e3; width: 100%; height: 40px">
                        <div id="progressBar" style="font-size: 1px; height: 38px; width: 0px; border: none;">
                        </div>
                    </div>
                </div>
                <?php if (!$is_profile_page && $hesk_settings['autoassign']): ?>
                    <div class="form-switcher">
                        <label class="switch-checkbox">
                            <input type="checkbox" name="autoassign" value="Y"
                                <?php if (isset($_SESSION[$session_array]['autoassign']) && !empty($_SESSION[$session_array]['autoassign'])) {echo 'checked';} ?>>
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
                            <span><?php echo $hesklang['user_aa']; ?></span>
                        </label>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        <?php if ($show_permissions): ?>
        <div class="step-item step-<?php echo $current_step++; ?>">
            <div class="form-group">
                <label><?php echo $hesklang['atype']; ?></label>
                <?php
                /* Only administrators can create new administrator accounts */
                if ($_SESSION['isadmin']) {
                    ?>
                    <div class="radio-list">
                        <div class="radio-custom">
                            <input type="radio" id="prof_isadmin1" name="isadmin" value="1" onchange="hesk_toggleLayerDisplay('options')"
                                <?php if ($_SESSION[$session_array]['isadmin']) echo 'checked'; ?>>
                            <label for="prof_isadmin1">
                                <?php echo $hesklang['administrator']; ?>
                                <?php echo $hesklang['admin_can']; ?>
                            </label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="prof_isadmin0" name="isadmin" value="0" onchange="hesk_toggleLayerDisplay('options')"
                                <?php if (!$_SESSION[$session_array]['isadmin']) echo 'checked'; ?>>
                            <label for="prof_isadmin0">
                                <?php echo $hesklang['astaff']; ?>
                                <?php echo $hesklang['staff_can']; ?>
                            </label>
                        </div>
                    </div>
                    <?php
                } else {
                    echo '<label>'.$hesklang['astaff'].' '.$hesklang['staff_can'] . '</label>';
                }
                ?>
            </div>
            <div id="options" style="display: <?php echo ($_SESSION['isadmin'] && $_SESSION[$session_array]['isadmin']) ? 'none' : 'block'; ?>;">
                <h4><?php echo $hesklang['allowed_cat']; ?></h4>
                <section class="item--section">
                    <?php foreach ($hesk_settings['categories'] as $catid => $catname): ?>
                    <div class="checkbox-custom <?php echo in_array('categories', $errors) ? 'isError' : ''; ?>">
                        <input type="checkbox" id="prof_category_<?php echo $catid; ?>" name="categories[]" value="<?php echo $catid; ?>"
                            <?php if (in_array($catid,$_SESSION[$session_array]['categories'])) { echo 'checked'; } ?>>
                        <label for="prof_category_<?php echo $catid; ?>"><?php echo $catname; ?></label>
                    </div>
                    <?php endforeach; ?>
                </section>
                <h4><?php echo $hesklang['allow_feat']; ?></h4>
                <section class="item--section">
                    <?php foreach ($hesk_settings['features'] as $k): ?>
                        <div class="checkbox-custom <?php echo in_array('features', $errors) ? 'isError' : ''; ?>">
                            <input type="checkbox" id="<?php echo $k; ?>" name="features[]" value="<?php echo $k; ?>"
                                <?php if (in_array($k,$_SESSION[$session_array]['features'])) { echo 'checked'; } ?>>
                            <label for="<?php echo $k ?>"><?php echo $hesklang[$k]; ?></label>
                        </div>
                    <?php endforeach; ?>
                </section>
            </div>
        </div>
        <?php endif; ?>
        <div class="step-item step-<?php echo $current_step++; ?>">
            <div class="form-group">
                <label for="prof_signature"><?php echo $hesklang['signature_max']; ?></label>
                <textarea class="form-control <?php echo in_array('signature', $errors) ? 'isError' : ''; ?>" name="signature" rows="10" cols="60"><?php echo $_SESSION[$session_array]['signature']; ?></textarea>
                <?php echo $hesklang['sign_extra']; ?>
            </div>
        </div>
        <?php if ($show_preferences): ?>
        <div class="step-item step-<?php echo $current_step++; ?>">
            <section class="item--section">
                <div class="form-group">
                    <label><?php echo $hesklang['aftrep']; ?></label>
                    <div class="radio-list">
                        <div class="radio-custom">
                            <input type="radio" id="prof_afterreply0" name="afterreply" value="0" <?php if (!$_SESSION[$session_array]['afterreply']) {echo 'checked';} ?>>
                            <label for="prof_afterreply0 ">
                                <?php echo $hesklang['showtic']; ?>
                            </label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="prof_afterreply1" name="afterreply" value="1" <?php if ($_SESSION[$session_array]['afterreply'] == 1) {echo 'checked';} ?>>
                            <label for="prof_afterreply1">
                                <?php echo $hesklang['gomain']; ?>
                            </label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" id="prof_afterreply2" name="afterreply" value="2" <?php if ($_SESSION[$session_array]['afterreply'] == 2) {echo 'checked';} ?>>
                            <label for="prof_afterreply2">
                                <?php echo $hesklang['shownext']; ?>
                            </label>
                        </div>
                    </div>
                </div>
            </section>
            <section class="item--section defaults-section">
                <h4><?php echo $hesklang['defaults']; ?></h4>
                <?php if ($hesk_settings['time_worked']): ?>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="prof_autostart" name="autostart" value="1" <?php if (!empty($_SESSION[$session_array]['autostart'])) {echo 'checked';}?>>
                        <label for="prof_autostart"><?php echo $hesklang['autoss']; ?></label>
                    </div>
                <?php
                endif;

                if (empty($_SESSION[$session_array]['autoreload'])) {
                    $reload_time = 30;
                    $sec = 'selected="selected"';
                    $min = '';
                } else {
                    $reload_time = intval($_SESSION[$session_array]['autoreload']);

                    if ($reload_time >= 60 && $reload_time % 60 == 0) {
                        $reload_time = $reload_time / 60;
                        $sec = '';
                        $min = 'selected="selected"';
                    } else {
                        $sec = 'selected="selected"';
                        $min = '';
                    }
                }
                ?>
                <div class="checkbox-custom">
                    <input type="checkbox" id="prof_notify_customer_new" name="notify_customer_new" value="1" <?php if (!empty($_SESSION[$session_array]['notify_customer_new'])) {echo 'checked';}?>>
                    <label for="prof_notify_customer_new"><?php echo $hesklang['pncn']; ?></label>
                </div>
                <div class="checkbox-custom">
                    <input type="checkbox" id="prof_notify_customer_reply" name="notify_customer_reply" value="1" <?php if (!empty($_SESSION[$session_array]['notify_customer_reply'])) {echo 'checked';}?>>
                    <label for="prof_notify_customer_reply"><?php echo $hesklang['pncr']; ?></label>
                </div>
                <div class="checkbox-custom">
                    <input type="checkbox" id="prof_show_suggested" name="show_suggested" value="1" <?php if (!empty($_SESSION[$session_array]['show_suggested'])) {echo 'checked';}?>>
                    <label for="prof_show_suggested"><?php echo $hesklang['pssy']; ?></label>
                </div>
                <div class="check-plus-input">
                    <div class="checkbox-custom">
                        <input type="checkbox" id="prof_autoreload" name="autoreload" value="1" <?php if (!empty($_SESSION[$session_array]['autoreload'])) {echo 'checked';}?>>
                        <label for="prof_autoreload"><?php echo $hesklang['arpp']; ?></label>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control" name="reload_time" value="<?php echo $reload_time; ?>" maxlength="5" onkeyup="this.value=this.value.replace(/[^\d]+/,'')">
                    </div>
                    <div class="form-group">
                        <div class="dropdown-select center out-close">
                            <select name="secmin">
                                <option value="sec" <?php echo $sec; ?>><?php echo $hesklang['seconds']; ?></option>
                                <option value="min" <?php echo $min; ?>><?php echo $hesklang['minutes']; ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <?php endif; ?>
        <div class="step-item step-<?php echo $current_step; ?>">
            <h5><?php echo $hesklang['nomw']; ?></h5>
            <?php if (!$is_profile_page || $can_view_tickets) {
                echo '<section class="item--section">';
                if (!$is_profile_page || $can_view_unassigned) { ?>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="prof_notify_new_unassigned" name="notify_new_unassigned" value="1"
                            <?php if (!empty($_SESSION[$session_array]['notify_new_unassigned'])) {echo 'checked';}?>>
                        <label for="prof_notify_new_unassigned"><?php echo $hesklang['nwts']; ?> <?php echo $hesklang['unas']; ?></label>
                    </div>
            <?php
                }
                ?>
                <div class="checkbox-custom">
                    <input type="checkbox" id="prof_notify_new_my" name="notify_new_my" value="1"
                        <?php if (!empty($_SESSION[$session_array]['notify_new_my'])) {echo 'checked';}?>>
                    <label for="prof_notify_new_my"><?php echo $hesklang['nwts']; ?> <?php echo $hesklang['s_my']; ?></label>
                </div>
            <?php
                echo '</section>';
                echo '<section class="item--section">';

                if (!$is_profile_page || $can_view_unassigned) { ?>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="prof_notify_reply_unassigned" name="notify_reply_unassigned" value="1"
                            <?php if (!empty($_SESSION[$session_array]['notify_reply_unassigned'])) {echo 'checked';}?>>
                        <label for="prof_notify_reply_unassigned"><?php echo $hesklang['ncrt']; ?> <?php echo $hesklang['unas']; ?></label>
                    </div>
                <?php
                } ?>
                <div class="checkbox-custom">
                    <input type="checkbox" id="prof_notify_reply_my" name="notify_reply_my" value="1"
                        <?php if (!empty($_SESSION[$session_array]['notify_reply_my'])) {echo 'checked';}?>>
                    <label for="prof_notify_reply_my"><?php echo $hesklang['ncrt']; ?> <?php echo $hesklang['s_my']; ?></label>
                </div>
            <?php
                echo '</section>';
            ?>
            <section class="item--section">
                <div class="checkbox-custom">
                    <input type="checkbox" id="prof_notify_assigned" name="notify_assigned" value="1"
                        <?php if (!empty($_SESSION[$session_array]['notify_assigned'])) {echo 'checked';}?>>
                    <label for="prof_notify_assigned"><?php echo $hesklang['ntam']; ?></label>
                </div>
                <div class="checkbox-custom">
                    <input type="checkbox" id="prof_notify_note" name="notify_note" value="1"
                        <?php if (!empty($_SESSION[$session_array]['notify_note'])) {echo 'checked';}?>>
                    <label for="prof_notify_note"><?php echo $hesklang['ntnote']; ?></label>
                </div>
            <?php
            } ?>
                <div class="checkbox-custom">
                    <input type="checkbox" id="prof_notify_pm" name="notify_pm" value="1"
                        <?php if (!empty($_SESSION[$session_array]['notify_pm'])) {echo 'checked';}?>>
                    <label for="prof_notify_pm"><?php echo $hesklang['npms']; ?></label>
                </div>
            </section>
        </div>
    </div>

	<script language="Javascript" type="text/javascript"><!--
	hesk_checkPassword(document.form1.newpass.value);
	//-->
	</script>
	<?php
} // END hesk_profile_tab()