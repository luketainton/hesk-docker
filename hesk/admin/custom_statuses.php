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

// Load statuses
require_once(HESK_PATH . 'inc/statuses.inc.php');

// What should we do?
if ( $action = hesk_REQUEST('a') )
{
	if ($action == 'edit_status') {edit_status();}
	elseif ( defined('HESK_DEMO') ) {hesk_process_messages($hesklang['ddemo'], 'custom_statuses.php', 'NOTICE');}
	elseif ($action == 'new_status') {new_status();}
	elseif ($action == 'save_status') {save_status();}
	elseif ($action == 'remove_status') {remove_status();}
}

// Print header
require_once(HESK_PATH . 'inc/header.inc.php');

// Print main manage users page
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');

/* This will handle error, success and notice messages */
if (!hesk_SESSION('edit_status') && !hesk_SESSION(array('new_status','errors'))) {
    hesk_handle_messages();
}


// Number of custom statuses
$hesk_settings['num_custom_statuses'] = count($hesk_settings['statuses']) - 6;

$reached_status_limit = $hesk_settings['num_custom_statuses'] >= 100;

// Did we reach the custom statuses limit?
if ($reached_status_limit && $action !== 'edit_status') {
    hesk_show_info($hesklang['status_limit']);
}

?>
<div class="main__content tools">
    <section class="tools__between-head">
        <h2>
            <?php echo $hesklang['statuses']; ?>
            <div class="tooltype right out-close">
                <svg class="icon icon-info">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                </svg>
                <div class="tooltype__content">
                    <div class="tooltype__wrapper">
                        <?php echo $hesklang['statuses_intro']; ?>
                    </div>
                </div>
            </div>
        </h2>
        <?php if (!$reached_status_limit && $action !== 'edit_status'): ?>
        <div class="btn btn--blue-border" ripple="ripple" data-action="create-custom-status">
            <?php echo $hesklang['new_status']; ?>
        </div>
        <?php endif; ?>
    </section>
    <div class="table-wrapper status">
        <div class="table">
            <table id="default-table" class="table sindu-table">
                <thead>
                <tr>
                    <th><?php echo $hesklang['status']; ?></th>
                    <th><?php echo $hesklang['csscl']; ?></th>
                    <th><?php echo $hesklang['tickets']; ?></th>
                    <th><?php echo $hesklang['cbc']; ?></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr class="title">
                    <td colspan="5"><?php echo $hesklang['status_hesk']; ?></td>
                </tr>
                <?php
                // Number of tickets per status
                $tickets_all = array();

                $res = hesk_dbQuery('SELECT COUNT(*) AS `cnt`, `status` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'tickets` GROUP BY `status`');
                while ($tmp = hesk_dbFetchAssoc($res)) {
                    $tickets_all[$tmp['status']] = $tmp['cnt'];
                }

                $is_custom = false;

                $i = 1;

                foreach ($hesk_settings['statuses'] as $tmp_id => $status) {
                    $status['span'] = isset($status['class']) ? '<span class="' . $status['class'] . '">' : '<span style="color: ' . $status['color'] . '">';
                    $status['color'] = isset($status['class']) ? $status['span'] . '.' . $status['class'] . '</span>' : $status['span'] . $status['color'] . '</span>';
                    $status['tickets'] = isset($tickets_all[$tmp_id]) ? $tickets_all[$tmp_id] : 0;
                    $status['can_customers_change'] = ! isset($status['can_customers_change']) ? '' : ($status['can_customers_change'] == 1 ? $hesklang['yes'] : $hesklang['no']);

                    if (!$is_custom && $tmp_id > 5) {
                        $is_custom = true;
                        echo '
        <tr class="title">
        <td colspan="5">' . $hesklang['status_custom'] . '</td>
        </tr>
        ';
                    }

                    $table_row = '';
                    if (isset($_SESSION['statusord']) && $_SESSION['statusord'] == $tmp_id) {
                        $table_row = 'class="ticket-new"';
                        unset($_SESSION['statusord']);
                    }
                    ?>
                    <tr <?php echo $table_row; ?>>
                        <td><?php echo $status['name']; ?></td>
                        <td><?php echo $status['color']; ?></td>
                        <td><a class="tooltip" href="show_tickets.php?<?php echo 's'.$tmp_id.'=1'; ?>&amp;s_my=1&amp;s_ot=1&amp;s_un=1" alt="<?php echo $hesklang['list_tkt_status']; ?>" title="<?php echo $hesklang['list_tkt_status']; ?>"><?php echo $status['tickets']; ?></a></td>
                        <td><?php echo $status['can_customers_change']; ?></td>
                        <td class="nowrap buttons">
                            <?php $modal_id = hesk_generate_delete_modal($hesklang['confirm_deletion'],
                                $hesklang['confirm_delete_status'],
                                'custom_statuses.php?a=remove_status&amp;id='. $tmp_id .'&amp;token='. hesk_token_echo(0)); ?>
                            <p>
                            <?php if ($is_custom): ?>
                                <a href="custom_statuses.php?a=edit_status&amp;id=<?php echo $tmp_id; ?>" class="edit tooltip" title="<?php echo $hesklang['edit']; ?>">
                                    <svg class="icon icon-edit-ticket">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-edit-ticket"></use>
                                    </svg>
                                </a>
                                <?php if ($status['tickets'] > 0): ?>
                                    <a onclick="alert('<?php echo hesk_makeJsString($hesklang['status_not_empty']); ?>');"
                                       class="delete tooltip not-allowed"
                                       title="<?php echo $hesklang['status_not_empty']; ?>">
                                        <svg class="icon icon-delete">
                                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-delete"></use>
                                        </svg>
                                    </a>
                                <?php else: ?>
                                    <a class="delete tooltip" title="<?php echo $hesklang['delete']; ?>" href="javascript:" data-modal="[data-modal-id='<?php echo $modal_id; ?>']">
                                        <svg class="icon icon-delete">
                                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-delete"></use>
                                        </svg>
                                    </a>
                                <?php
                                endif;
                            endif;
                            ?>
                            </p>
                        </td>
                    </tr>
                    <?php
                } // End foreach

                if ($hesk_settings['num_custom_statuses'] == 0):
                ?>
                    <tr class="title">
                        <td colspan="5"><?php echo $hesklang['status_custom']; ?></td>
                    </tr>
                    <tr>
                        <td colspan="5"><?php echo $hesklang['status_custom_none']; ?></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script type="text/javascript" src="<?php echo HESK_PATH; ?>inc/jscolor/jscolor.min.js"></script>
<script type="text/javascript">
    function hesk_preview(jscolor) {
        document.getElementById('color_preview').style.color = "#" + jscolor;
    }
</script>
<div class="right-bar create-status" <?php echo hesk_SESSION('edit_status') || hesk_SESSION(array('new_status','errors')) ? 'style="display: block"' : ''; ?>>
    <form action="custom_statuses.php" method="post" name="form1" class="form <?php echo hesk_SESSION(array('new_status','errors')) ? 'invalid' : ''; ?>">
        <div class="right-bar__body form">
            <h3>
                <a href="<?php echo hesk_SESSION('edit_status') ? 'custom_statuses.php' : 'javascript:'; ?>">
                    <svg class="icon icon-back">
                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-back"></use>
                    </svg>
                    <span><?php echo hesk_SESSION('edit_status') ? $hesklang['edit_status'] : $hesklang['new_status']; ?></span>
                </a>
            </h3>
            <?php
            /* This will handle error, success and notice messages */
            if (hesk_SESSION(array('new_status', 'errors'))) {
                echo '<div style="margin: -24px -24px 10px -16px;">';
                hesk_handle_messages();
                echo '</div>';
            }

            $names = hesk_SESSION(array('new_status','names'));
            $errors = hesk_SESSION(array('new_status','errors'));
            $errors = is_array($errors) ? $errors : array();

            if ($hesk_settings['can_sel_lang'] && count($hesk_settings['languages']) > 1) {
                echo '<h4>' . $hesklang['status'] . '</h4>';
                foreach ($hesk_settings['languages'] as $lang => $info) { ?>
                    <div class="form-group">
                        <label><?php echo $lang; ?></label>
                        <input type="text" class="form-control <?php echo in_array('names', $errors) ? 'isError' : ''; ?>" name="name[<?php echo $lang; ?>]" value="<?php echo (isset($names[$lang]) ? $names[$lang] : ''); ?>">
                    </div>
                <?php }
            } else { ?>
                <div class="form-group">
                    <label><?php echo $hesklang['status']; ?></label>
                    <input type="text" class="form-control <?php echo in_array('names', $errors) ? 'isError' : ''; ?>" name="name[<?php echo $hesk_settings['language']; ?>]"
                           value="<?php echo isset($names[$hesk_settings['language']]) ? $names[$hesk_settings['language']] : ''; ?>">
                </div>
            <?php } ?>
            <div class="form-group color">
                <?php $color = hesk_validate_color_hex(hesk_SESSION(array('new_status','color'))); ?>
                <label><?php echo $hesklang['color']; ?></label>
                <input type="text" class="form-control jscolor {hash:true, uppercase:false, onFineChange:'hesk_preview(this)'}" name="color" value="<?php echo $color; ?>">
                <span id="color_preview" style="color:<?php echo $color; ?>"><?php echo $hesklang['clr_view']; ?></span>
            </div>
            <div class="form-switcher">
                <?php $can_customers_change = hesk_SESSION(array('new_status','can_customers_change'), 0); ?>
                <label class="switch-checkbox">
                    <input type="checkbox" name="can_customers_change" <?php if ($can_customers_change) {echo 'checked';} ?>>
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
                    <span><?php echo $hesklang['ccc']; ?></span>
                </label>
            </div>
            <?php if (isset($_SESSION['edit_status'])): ?>
                <input type="hidden" name="a" value="save_status">
                <input type="hidden" name="id" value="<?php echo intval($_SESSION['new_status']['id']); ?>">
            <?php else: ?>
                <input type="hidden" name="a" value="new_status">
            <?php endif; ?>
            <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
            <button type="submit" class="btn btn-full save" ripple="ripple"><?php echo $hesklang['status_save']; ?></button>
        </div>
    </form>
</div>
<?php

hesk_cleanSessionVars( array('new_status', 'edit_status') );

require_once(HESK_PATH . 'inc/footer.inc.php');
exit();


/*** START FUNCTIONS ***/


function save_status()
{
	global $hesk_settings, $hesklang;
	global $hesk_error_buffer;

	// A security check
	# hesk_token_check('POST');

	// Get custom status ID
	$id = intval( hesk_POST('id') ) or hesk_error($hesklang['status_e_id']);

	// Validate inputs
	if (($status = status_validate()) == false)
	{
		$_SESSION['edit_status'] = true;
		$_SESSION['new_status']['id'] = $id;

		$tmp = '';
		foreach ($hesk_error_buffer as $error)
		{
			$tmp .= "<li>$error</li>\n";
		}
		$hesk_error_buffer = $tmp;

		$hesk_error_buffer = $hesklang['rfm'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
		hesk_process_messages($hesk_error_buffer,'custom_statuses.php');
	}

    // Remove # from color
    $color = str_replace('#', '', $status['color']);

	// Add custom status data into database
	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_statuses` SET
	`name` = '".hesk_dbEscape($status['names'])."',
	`color` = '{$color}',
	`can_customers_change` = '{$status['can_customers_change']}'
	WHERE `id`={$id}");

	// Clear cache
	hesk_purge_cache('status');

	// Show success
	$_SESSION['statusord'] = $id;
	hesk_process_messages($hesklang['status_mdf'],'custom_statuses.php','SUCCESS');

} // End save_status()


function edit_status()
{
	global $hesk_settings, $hesklang;

	// Get custom status ID
	$id = intval( hesk_GET('id') ) or hesk_error($hesklang['status_e_id']);

	// Get details from the database
	$res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_statuses` WHERE `id`={$id} LIMIT 1");
	if ( hesk_dbNumRows($res) != 1 )
	{
		hesk_error($hesklang['status_not_found']);
	}
	$status = hesk_dbFetchAssoc($res);

	$status['names'] = json_decode($status['name'], true);
	unset($status['name']);

    $status['color'] = '#'.$status['color'];

	$_SESSION['new_status'] = $status;
	$_SESSION['edit_status'] = true;

} // End edit_status()


function update_status_order()
{
	global $hesk_settings, $hesklang;

	// Get list of current custom statuses
	$res = hesk_dbQuery("SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_statuses` ORDER BY `order` ASC");

	// Update database
	$i = 10;
	while ( $status = hesk_dbFetchAssoc($res) )
	{
		hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_statuses` SET `order`=".intval($i)." WHERE `id`='".intval($status['id'])."'");
		$i += 10;
	}

	return true;

} // END update_status_order()


function remove_status()
{
	global $hesk_settings, $hesklang;

	// A security check
	hesk_token_check();

	// Get ID
	$id = intval( hesk_GET('id') ) or hesk_error($hesklang['status_e_id']);

    // Any tickets with this status?
    $res = hesk_dbQuery("SELECT COUNT(*) AS `cnt`, `status` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE `status` = {$id}");
    if (hesk_dbResult($res) > 0)
    {
        hesk_process_messages($hesklang['status_not_empty'],'./custom_statuses.php');
    }

	// Reset the custom status
	hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_statuses` WHERE `id`={$id}");

	// Were we successful?
	if ( hesk_dbAffectedRows() == 1 )
	{
		// Update order
		update_status_order();

		// Clear cache
		hesk_purge_cache('status');

		// Delete custom status data from tickets
		hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `custom{$id}`=''");

		// Show success message
		hesk_process_messages($hesklang['status_deleted'],'./custom_statuses.php','SUCCESS');
	}
	else
	{
		hesk_process_messages($hesklang['status_not_found'],'./custom_statuses.php');
	}

} // End remove_status()


function status_validate()
{
	global $hesk_settings, $hesklang;
	global $hesk_error_buffer;

	$hesk_error_buffer = array();

	// Get names
	$status['names'] = hesk_POST_array('name');

	// Make sure only valid names pass
	foreach ($status['names'] as $key => $name)
	{
		if ( ! isset($hesk_settings['languages'][$key]))
		{
			unset($status['names'][$key]);
		}
		else
		{
			$name = is_array($name) ? '' : hesk_input($name, 0, 0, HESK_SLASH);

			if (strlen($name) < 1)
			{
				unset($status['names'][$key]);
			}
			else
			{
				$status['names'][$key] = stripslashes($name);
			}
		}
	}

	// No name entered?
    $errors = array();
	if ( ! count($status['names']))
	{
		$hesk_error_buffer[] = $hesklang['err_status'];
		$errors[] = 'names';
	}

	// Color
	$status['color'] = hesk_validate_color_hex(hesk_POST('color'));

	// Can customers change it?
	$status['can_customers_change'] = hesk_POST('can_customers_change') ? 1 : 0;

	// Any errors?
	if (count($hesk_error_buffer))
	{
		$_SESSION['new_status'] = $status;
		$_SESSION['new_status']['errors'] = $errors;
		return false;
	}

	$status['names'] = addslashes(json_encode($status['names']));

	return $status;
} // END status_validate()


function new_status()
{
	global $hesk_settings, $hesklang;
	global $hesk_error_buffer;

	// A security check
	# hesk_token_check('POST');

	// Validate inputs
	if (($status = status_validate()) == false)
	{
		$tmp = '';
		foreach ($hesk_error_buffer as $error)
		{
			$tmp .= "<li>$error</li>\n";
		}
		$hesk_error_buffer = $tmp;

		$hesk_error_buffer = $hesklang['rfm'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
		hesk_process_messages($hesk_error_buffer,'custom_statuses.php');
	}

    // Did we reach status limit?
    if (count($hesk_settings['statuses']) >= 100)
    {
        hesk_process_messages($hesklang['status_limit'],'custom_statuses.php');
    }

    // Lowest available ID for custom statuses is 6
    $next_id = 6;

	// Any existing statuses?
    if (count($hesk_settings['statuses']) > 6)
    {
        // The lowest currently used ID
        $res = hesk_dbQuery("SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_statuses` ORDER BY `id` ASC LIMIT 1");
        $lowest_id = hesk_dbResult($res);

        if ($lowest_id > 6)
        {
            $next_id = 6;
        }
        else
        {
            // Minimum next ID
          	$res = hesk_dbQuery("
                  SELECT MIN(`t1`.`id` + 1) FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_statuses` AS `t1`
                      LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_statuses` AS `t2`
                           ON `t1`.`id` + 1 = `t2`.`id`
                  WHERE `t2`.`id` IS NULL"
            );
            $next_id = hesk_dbResult($res);
        }
    }

    // Remove # from color
    $color = str_replace('#', '', $status['color']);

	// Insert custom status into database
	hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_statuses` (`id`, `name`, `color`, `can_customers_change`, `order`) VALUES (".intval($next_id).", '".hesk_dbEscape($status['names'])."', '{$color}', '{$status['can_customers_change']}', 990)");

	// Update order
	update_status_order();

	// Clear cache
	hesk_purge_cache('status');

    $_SESSION['statusord'] = $next_id;

	// Show success
	hesk_process_messages($hesklang['status_added'],'custom_statuses.php','SUCCESS');

} // End new_status()


function hesk_validate_color_hex($hex, $def = '#000000')
{
    $hex = strtolower($hex);
    return preg_match('/^\#[a-f0-9]{6}$/', $hex) ? $hex : $def;
} // END hesk_validate_color_hex()


function hesk_get_text_color($bg_color)
{
    // Get RGB values
    list($r, $g, $b) = sscanf($bg_color, "#%02x%02x%02x");

    // Is Black a good text color?
    if (hesk_color_diff($r, $g, $b, 0, 0, 0) >= 500)
    {
        return '#000000';
    }

    // Use white instead
    return '#ffffff';
} // END hesk_get_text_color()


function hesk_color_diff($R1,$G1,$B1,$R2,$G2,$B2)
{
    return max($R1,$R2) - min($R1,$R2) +
           max($G1,$G2) - min($G1,$G2) +
           max($B1,$B2) - min($B1,$B2);
} // END hesk_color_diff()
