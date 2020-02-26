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

/* Get all the required files and functions */
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');
require(HESK_PATH . 'inc/admin_functions.inc.php');
hesk_load_database_functions();

hesk_session_start();
hesk_dbConnect();
hesk_isLoggedIn();

/* Check permissions for this feature */
hesk_checkPermission('can_man_cat');

// Possible priorities
$priorities = array(
	'low' => array('id' => 3, 'value' => 'low', 'text' => $hesklang['low'],		'formatted' => $hesklang['low']),
	'medium' => array('id' => 2, 'value' => 'medium', 'text' => $hesklang['medium'],		'formatted' => $hesklang['medium']),
	'high' => array('id' => 1, 'value' => 'high', 'text' => $hesklang['high'],		'formatted' => $hesklang['high']),
	'critical' => array('id' => 0, 'value' => 'critical', 'text' => $hesklang['critical'],	'formatted' => $hesklang['critical']),
);

/* What should we do? */
if ( $action = hesk_REQUEST('a') )
{
	if ($action == 'linkcode')       {generate_link_code();}
	elseif ( defined('HESK_DEMO') )  {hesk_process_messages($hesklang['ddemo'], 'manage_categories.php', 'NOTICE');}
	elseif ($action == 'new')        {new_cat();}
	elseif ($action == 'rename')     {rename_cat();}
	elseif ($action == 'remove')     {remove();}
	elseif ($action == 'order')      {order_cat();}
	elseif ($action == 'autoassign') {toggle_autoassign();}
	elseif ($action == 'type')       {toggle_type();}
	elseif ($action == 'priority')   {change_priority();}
}

/* Print header */
require_once(HESK_PATH . 'inc/header.inc.php');

/* Print main manage users page */
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');

/* This will handle error, success and notice messages */
if (!hesk_SESSION('error')) {
    hesk_handle_messages();
}
?>
<div class="main__content categories">
    <section class="categories__head">
        <h2><?php echo $hesklang['menu_cat']; ?></h2>
        <button class="btn btn btn--blue-border" ripple="ripple" data-action="category-create">
            <?php echo $hesklang['add_cat']; ?>
        </button>
    </section>
    <div class="table-wrap">
        <div class="table">
            <table id="default-table" class="table sindu-table">
                <thead>
                <tr>
                    <th><?php echo $hesklang['cat_name']; ?></th>
                    <th>
                        <span><?php echo $hesklang['priority']; ?></span>
                        <?php if ($hesk_settings['cust_urgency']): ?>
                        <div class="tooltype right out-close">
                            <svg class="icon icon-info">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                            </svg>
                            <div class="tooltype__content">
                                <div class="tooltype__wrapper">
                                    <?php echo $hesklang['cat_pri_info'] . ' <a href="#">' . $hesklang['cpri'] . '</a>'; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </th>
                    <th>
                        <span><?php echo $hesklang['not']; ?></span>
                    </th>
                    <th>
                        <span><?php echo $hesklang['cat_type']; ?></span>
                    </th>
                    <?php if ($hesk_settings['autoassign']): ?>
                    <th><?php echo $hesklang['aass']; ?></th>
                    <?php endif; ?>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php
                /* Get number of tickets per category */
                $tickets_all   = array();
                $tickets_total = 0;

                $res = hesk_dbQuery('SELECT COUNT(*) AS `cnt`, `category` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'tickets` GROUP BY `category`');
                while ($tmp = hesk_dbFetchAssoc($res))
                {
                    $tickets_all[$tmp['category']] = $tmp['cnt'];
                    $tickets_total += $tmp['cnt'];
                }

                /* Get list of categories */
                $res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` ORDER BY `cat_order` ASC");
                $options='';

                $i=1;
                $j=0;
                $num = hesk_dbNumRows($res);

                while ($mycat=hesk_dbFetchAssoc($res))
                {
                    $j++;

                    $table_row = '';
                    if (isset($_SESSION['selcat2']) && $mycat['id'] == $_SESSION['selcat2'])
                    {
                        $table_row = 'class="ticket-new"';
                        unset($_SESSION['selcat2']);
                    }
                    else
                    {
                        $color = $i ? 'admin_white' : 'admin_gray';
                    }

                    $tmp   = $i ? 'White' : 'Blue';
                    $style = 'class="option'.$tmp.'OFF" onmouseover="this.className=\'option'.$tmp.'ON\'" onmouseout="this.className=\'option'.$tmp.'OFF\'"';
                    $i     = $i ? 0 : 1;

                    /* Number of tickets and graph width */
                    $all = isset($tickets_all[$mycat['id']]) ? $tickets_all[$mycat['id']] : 0;
                    $width_all = 0;
                    if ($tickets_total && $all)
                    {
                        $width_all  = round(($all / $tickets_total) * 100);
                    }

                    $options .= '<option value="'.$mycat['id'].'" ';
                    $options .= (isset($_SESSION['selcat']) && $mycat['id'] == $_SESSION['selcat']) ? ' selected="selected" ' : '';
                    $options .= '>'.$mycat['name'].'</option>';


                    ?>
                    <tr <?php echo $table_row; ?> data-category-id="<?php echo $mycat['id']; ?>">
                        <td>
                            <span class="category-name"><?php echo $mycat['name']; ?></span>
                            <div class="rename-link tooltype right out-close" data-modal=".rename-category" data-callback="initRenameCategoryModal">
                                <svg class="icon icon-edit">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-edit"></use>
                                </svg>
                                <div class="tooltype__content">
                                    <div class="tooltype__wrapper">
                                        <?php echo $hesklang['ren_cat']; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="dropdown-select center out-close priority" data-type="form-submit-change">
                                <form action="manage_categories.php" method="post">
                                    <select name="priority" onchange="this.form.submit()">
                                        <?php foreach ($priorities as $id => $priority): ?>
                                            <option value="<?php echo $priority['value']; ?>"
                                                <?php if ($priority['id'] === intval($mycat['priority'])): ?>selected<?php endif; ?>>
                                                <?php echo $priority['text']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="a" value="priority" />
                                    <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
                                    <input type="hidden" name="catid" value="<?php echo $mycat['id']; ?>" />
                                </form>
                            </div>
                        </td>
                        <td>
                            <?php
                            $tickets_url = 'show_tickets.php?category='.$mycat['id'].'&amp;s_all=1&amp;s_my=1&amp;s_ot=1&amp;s_un=1';
                            ?>
                            <a href="<?php echo $tickets_url; ?>" title="<?php $hesklang['list_tickets_cat']; ?>">
                                <?php echo $all; ?>
                                (<?php echo $width_all; ?>%)
                            </a>
                        </td>
                        <td>
                            <div class="dropdown-select center out-close">
                                <form action="manage_categories.php" method="get">
                                    <select name="s" onchange="this.form.submit()">
                                        <option value="0" <?php if ($mycat['type']): ?>selected<?php endif; ?>>
                                            <?php echo $hesklang['cat_public']; ?>
                                        </option>
                                        <option value="1" <?php if ($mycat['type']): ?>selected<?php endif; ?>>
                                            <?php echo $hesklang['cat_private']; ?>
                                        </option>
                                    </select>
                                    <input type="hidden" name="a" value="type">
                                    <input type="hidden" name="catid" value="<?php echo $mycat['id']; ?>">
                                    <input type="hidden" name="token" value="<?php echo hesk_token_echo(); ?>">
                                </form>
                            </div>
                        </td>
                        <?php if ($hesk_settings['autoassign']): ?>
                        <td class="assign">
                            <form action="manage_categories.php" method="get">
                                <label class="switch-checkbox">
                                    <input type="checkbox" onchange="this.form.submit()" name="s" <?php if ($mycat['autoassign']): ?>checked<?php endif; ?> />
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
                                <input type="hidden" name="a" value="autoassign">
                                <input type="hidden" name="catid" value="<?php echo $mycat['id']; ?>">
                                <input type="hidden" name="token" value="<?php echo hesk_token_echo(); ?>">
                            </form>
                        </td>
                        <?php endif; ?>
                        <td class="nowrap generate">
                            <a href="javascript:" data-action="generate-link" data-link="<?php echo htmlspecialchars($hesk_settings['hesk_url']) . '/index.php?a=add&catid=' . intval($mycat['id']); ?>">Generate link</a>
                            <?php
                            if ($num > 1) {
                                if ($j == 1) {
                                    ?>
                                    <a href="#" style="visibility: hidden">
                                        <svg class="icon icon-chevron-up">
                                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                        </svg>
                                    </a>
                                    <a href="manage_categories.php?a=order&amp;catid=<?php echo $mycat['id']; ?>&amp;move=15&amp;token=<?php hesk_token_echo(); ?>"
                                       title="<?php echo $hesklang['move_dn']; ?>">
                                        <svg class="icon icon-chevron-down">
                                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                        </svg>
                                    </a>
                                    <?php
                                    echo'';
                                } elseif ($j == $num) {
                                    ?>
                                    <a href="manage_categories.php?a=order&amp;catid=<?php echo $mycat['id']; ?>&amp;move=-15&amp;token=<?php hesk_token_echo(); ?>"
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
                                } else {
                                    ?>
                                    <a href="manage_categories.php?a=order&amp;catid=<?php echo $mycat['id']; ?>&amp;move=-15&amp;token=<?php hesk_token_echo(); ?>"
                                       title="<?php echo $hesklang['move_up']; ?>">
                                        <svg class="icon icon-chevron-up">
                                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                        </svg>
                                    </a>
                                    <a href="manage_categories.php?a=order&amp;catid=<?php echo $mycat['id']; ?>&amp;move=15&amp;token=<?php hesk_token_echo(); ?>"
                                       title="<?php echo $hesklang['move_dn']; ?>">
                                        <svg class="icon icon-chevron-down">
                                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                        </svg>
                                    </a>
                                    <?php
                                }
                            }
                            ?>
                            <?php
                            if ($mycat['id'] != 1):
                                $modal_id = hesk_generate_delete_modal($hesklang['confirm_deletion'],
                                    $hesklang['confirm_del_cat'],
                                    'manage_categories.php?a=remove&catid='. $mycat['id'] .'&token='. hesk_token_echo(0));
                                ?>
                            <a href="javascript:" class="delete" data-modal="[data-modal-id='<?php echo $modal_id; ?>']">
                                <svg class="icon icon-delete">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-delete"></use>
                                </svg>
                            </a>
                            <?php
                            endif;
                            ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="notification-flash green" data-type="link-generate-message">
    <i class="close">
        <svg class="icon icon-close">
            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
        </svg>
    </i>
    <div class="notification--title"><?php echo $hesklang['genl']; ?></div>
    <div class="notification--text"><?php echo $hesklang['genl2']; ?></div>
</div>
<div class="modal rename-category">
    <div class="modal__body">
        <form action="manage_categories.php" method="post">
            <i class="modal__close" data-action="modal-close">
                <svg class="icon icon-close">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                </svg>
            </i>
            <h3><?php echo $hesklang['ren_cat']; ?></h3>
            <div class="modal__description form">
                <div class="form-group">
                    <label style="text-align: left"><?php echo $hesklang['cat_name']; ?> (<?php echo $hesklang['max_chars']; ?>):</label>
                    <input type="text"
                           name="name"
                           id="renamecat"
                           class="form-control"
                           size="40"
                           maxlength="40"
                           <?php if (isset($_SESSION['catname2'])): ?>value="<?php echo $_SESSION['catname2']; ?>"<?php endif; ?>>
                    <input type="hidden" name="catid">
                    <input type="hidden" name="a" value="rename">
                    <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
                </div>
            </div>
            <div class="modal__buttons">
                <button class="btn btn-border" ripple="ripple" data-action="cancel"><?php echo $hesklang['cancel']; ?></button>
                <button class="btn btn-full" ripple="ripple" type="submit"><?php echo $hesklang['ren_cat']; ?></button>
            </div>
        </form>
    </div>
</div>
<div class="right-bar category-create" <?php echo hesk_SESSION('error') ? 'style="display: block"' : ''; ?>>
    <form action="manage_categories.php" method="post">
        <div class="right-bar__body form">
            <h3>
                <a href="javascript:">
                    <svg class="icon icon-back">
                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-back"></use>
                    </svg>
                    <span><?php echo $hesklang['add_cat']; ?></span>
                </a>
            </h3>
            <?php
            if (hesk_SESSION('error')) {
                echo '<div style="margin: -24px -24px 0 -16px;">';
                hesk_handle_messages();
                echo '</div>';
            }
            ?>
            <div class="form-group">
                <label><?php echo $hesklang['cat_name']; ?> (<?php echo $hesklang['max_chars']; ?>):</label>
                <input type="text"
                       name="name"
                       class="form-control"
                       <?php if (isset($_SESSION['catname'])): ?>value="<?php echo $_SESSION['catname']; ?>"<?php endif; ?>>
            </div>
            <?php
            if (!isset($_SESSION['cat_priority'])) {
                $_SESSION['cat_priority'] = 3;
            }
            ?>
            <div class="category-create__select">
                <span><?php echo $hesklang['def_pri']; ?></span>
                <div class="dropdown-select center out-close">
                    <select name="priority">
                        <?php foreach ($priorities as $id => $priority): ?>
                            <option value="<?php echo $priority['value']; ?>" <?php if ($_SESSION['cat_priority'] == $id): ?>selected<?php endif; ?>>
                                <?php echo $priority['text']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php if ($hesk_settings['autoassign']): ?>
                <div class="category-create__autoassign">
                    <label class="switch-checkbox">
                        <input value="Y" name="autoassign" type="checkbox" id="autoassign" <?php if (!isset($_SESSION['cat_autoassign']) || $_SESSION['cat_autoassign'] == 1) { echo 'checked'; } ?>>
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
                        <span><?php echo $hesklang['cat_aa']; ?></span>
                    </label>
                </div>
            <?php endif; ?>
            <div class="category-create__select">
                <span><?php echo $hesklang['cat_type']; ?>:</span>
                <div class="dropdown-select center out-close">
                    <select name="type" id="category-create-type">
                        <option value="0" <?php if (!isset($_SESSION['cat_type']) || $_SESSION['cat_type'] == 0) {echo 'checked';} ?>><?php echo $hesklang['cat_public']; ?></option>
                        <option value="1" <?php if (isset($_SESSION['cat_type']) && $_SESSION['cat_type'] == 1) {echo 'checked';} ?>><?php echo $hesklang['cat_private']; ?></option>
                    </select>
                </div>
            </div>
            <input type="hidden" name="a" value="new" />
            <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
            <button class="btn btn-full" type="submit" ripple="ripple"><?php echo $hesklang['create_cat']; ?></button>
        </div>
    </form>
</div>

<script language="Javascript" type="text/javascript"><!--

function initRenameCategoryModal($clickedElement) {
    $('.rename-category')
        .find('input[name="name"]').val($clickedElement.parent().find('.category-name').text()).end()
        .find('input[name="catid"]').val($clickedElement.parent().parent().attr('data-category-id')).end();
}
//-->
</script>
<?php
hesk_cleanSessionVars('error');
require_once(HESK_PATH . 'inc/footer.inc.php');
exit();


/*** START FUNCTIONS ***/

function change_priority()
{
	global $hesk_settings, $hesklang, $priorities;

	/* A security check */
	hesk_token_check('POST');

	$_SERVER['PHP_SELF'] = 'manage_categories.php?catid='.intval( hesk_POST('catid') );

	$catid = hesk_isNumber( hesk_POST('catid'), $hesklang['choose_cat_ren'], $_SERVER['PHP_SELF']);
	$_SESSION['selcat'] = $catid;
	$_SESSION['selcat2'] = $catid;

    $priority = hesk_POST('priority', 'low');
    if ( ! array_key_exists($priority, $priorities) )
    {
        $priority = 'low';
    }

	$priority_id = $priorities[$priority]['id'];

	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` SET `priority`='{$priority_id}' WHERE `id`='".intval($catid)."'");

    hesk_cleanSessionVars('cat_ch_priority');

	hesk_process_messages($hesklang['cat_pri_ch'].' '.$priorities[$priority]['formatted'],$_SERVER['PHP_SELF'],'SUCCESS');
} // END change_priority()


function new_cat()
{
	global $hesk_settings, $hesklang, $priorities;

	/* A security check */
	hesk_token_check('POST');

    /* Options */
    $_SESSION['cat_autoassign'] = hesk_POST('autoassign') == 'Y' ? 1 : 0;
    $_SESSION['cat_type'] = hesk_POST('type') === '1' ? 1 : 0;

	// Default priority
	$_SESSION['cat_priority'] = hesk_POST('priority', 'low');
    if ( ! array_key_exists($_SESSION['cat_priority'], $priorities) )
    {
        $_SESSION['cat_priority'] = 'low';
    }
    $priority_id = $priorities[$_SESSION['cat_priority']]['id'];

    /* Category name */
    $catname = hesk_input(hesk_POST('name'));

    if ($catname == '') {
        $_SESSION['error'] = 1;
    }
	$catname = hesk_input( hesk_POST('name') , $hesklang['enter_cat_name'], 'manage_categories.php');

    /* Do we already have a category with this name? */
	$res = hesk_dbQuery("SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE `name` LIKE '".hesk_dbEscape( hesk_dbLike($catname) )."' LIMIT 1");
    if (hesk_dbNumRows($res) != 0)
    {
		$_SESSION['catname'] = stripslashes($catname);
		hesk_process_messages($hesklang['cndupl'],'manage_categories.php');
    }

	/* Get the latest cat_order */
	$res = hesk_dbQuery("SELECT `cat_order` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` ORDER BY `cat_order` DESC LIMIT 1");
	$row = hesk_dbFetchRow($res);
	$my_order = isset($row[0]) ? intval($row[0]) + 10 : 10;

	hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` (`name`,`cat_order`,`autoassign`,`type`, `priority`) VALUES ('".hesk_dbEscape($catname)."','".intval($my_order)."','".intval($_SESSION['cat_autoassign'])."','".intval($_SESSION['cat_type'])."','{$priority_id}')");

    hesk_cleanSessionVars('catname');
    hesk_cleanSessionVars('cat_autoassign');
    hesk_cleanSessionVars('cat_type');
    hesk_cleanSessionVars('cat_priority');
    hesk_cleanSessionVars('error');

    $_SESSION['selcat2'] = hesk_dbInsertID();

	hesk_process_messages(sprintf($hesklang['cat_name_added'],'<i>'.stripslashes($catname).'</i>'),'manage_categories.php','SUCCESS');
} // End new_cat()


function rename_cat()
{
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check('POST');

    $_SERVER['PHP_SELF'] = 'manage_categories.php?catid='.intval( hesk_POST('catid') );

	$catid = hesk_isNumber( hesk_POST('catid'), $hesklang['choose_cat_ren'], $_SERVER['PHP_SELF']);
	$_SESSION['selcat'] = $catid;
    $_SESSION['selcat2'] = $catid;

	$catname = hesk_input( hesk_POST('name'), $hesklang['cat_ren_name'], $_SERVER['PHP_SELF']);
    $_SESSION['catname2'] = stripslashes($catname);

	$res = hesk_dbQuery("SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE `name` LIKE '".hesk_dbEscape( hesk_dbLike($catname) )."' LIMIT 1");
    if (hesk_dbNumRows($res) != 0)
    {
    	$old = hesk_dbFetchAssoc($res);
        if ($old['id'] == $catid)
        {
        	hesk_process_messages($hesklang['noch'],$_SERVER['PHP_SELF'],'NOTICE');
        }
        else
        {
    		hesk_process_messages($hesklang['cndupl'],$_SERVER['PHP_SELF']);
        }
    }

	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` SET `name`='".hesk_dbEscape($catname)."' WHERE `id`='".intval($catid)."'");

    unset($_SESSION['selcat']);
    unset($_SESSION['catname2']);

    hesk_process_messages($hesklang['cat_renamed_to'].' <i>'.stripslashes($catname).'</i>',$_SERVER['PHP_SELF'],'SUCCESS');
} // End rename_cat()


function remove()
{
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check();

    $_SERVER['PHP_SELF'] = 'manage_categories.php';

	$mycat = intval( hesk_GET('catid') ) or hesk_error($hesklang['no_cat_id']);
	if ($mycat == 1)
    {
    	hesk_process_messages($hesklang['cant_del_default_cat'],$_SERVER['PHP_SELF']);
    }

	hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE `id`='".intval($mycat)."'");
	if (hesk_dbAffectedRows() != 1)
    {
    	hesk_error("$hesklang[int_error]: $hesklang[cat_not_found].");
    }

	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `category`=1 WHERE `category`='".intval($mycat)."'");

    hesk_process_messages($hesklang['cat_removed_db'],$_SERVER['PHP_SELF'],'SUCCESS');
} // End remove()


function order_cat()
{
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check();

	$catid = intval( hesk_GET('catid') ) or hesk_error($hesklang['cat_move_id']);
	$_SESSION['selcat2'] = $catid;

	$cat_move=intval( hesk_GET('move') );

	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` SET `cat_order`=`cat_order`+".intval($cat_move)." WHERE `id`='".intval($catid)."'");
	if (hesk_dbAffectedRows() != 1)
    {
    	hesk_error("$hesklang[int_error]: $hesklang[cat_not_found].");
    }

	/* Update all category fields with new order */
	$res = hesk_dbQuery("SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` ORDER BY `cat_order` ASC");

	$i = 10;
	while ($mycat=hesk_dbFetchAssoc($res))
	{
	    hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` SET `cat_order`=".intval($i)." WHERE `id`='".intval($mycat['id'])."'");
	    $i += 10;
	}

    header('Location: manage_categories.php');
    exit();
} // End order_cat()


function toggle_autoassign()
{
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check();

	$catid = intval( hesk_GET('catid') ) or hesk_error($hesklang['cat_move_id']);
	$_SESSION['selcat2'] = $catid;

    if (hesk_GET('s') === 'on')
    {
		$autoassign = 1;
        $tmp = $hesklang['caaon'];
    }
    else
    {
        $autoassign = 0;
        $tmp = $hesklang['caaoff'];
    }

	/* Update auto-assign settings */
	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` SET `autoassign`='".intval($autoassign)."' WHERE `id`='".intval($catid)."'");
	if (hesk_dbAffectedRows() != 1)
    {
        hesk_process_messages($hesklang['int_error'].': '.$hesklang['cat_not_found'],'./manage_categories.php');
    }

    hesk_process_messages($tmp,'./manage_categories.php','SUCCESS');

} // End toggle_autoassign()


function toggle_type()
{
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check();

	$catid = intval( hesk_GET('catid') ) or hesk_error($hesklang['cat_move_id']);
	$_SESSION['selcat2'] = $catid;

    if ( intval( hesk_GET('s') ) )
    {
		$type = 1;
        $tmp = $hesklang['cpriv'];
    }
    else
    {
        $type = 0;
        $tmp = $hesklang['cpub'];
    }

	/* Update auto-assign settings */
	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` SET `type`='{$type}' WHERE `id`='".intval($catid)."'");
	if (hesk_dbAffectedRows() != 1)
    {
        hesk_process_messages($hesklang['int_error'].': '.$hesklang['cat_not_found'],'./manage_categories.php');
    }

    hesk_process_messages($tmp,'./manage_categories.php','SUCCESS');

} // End toggle_type()
?>
