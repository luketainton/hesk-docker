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
hesk_checkPermission('can_man_ticket_tpl');

// Define required constants
define('LOAD_TABS',1);

/* What should we do? */
if ( $action = hesk_REQUEST('a') )
{
    if ( defined('HESK_DEMO') )  {hesk_process_messages($hesklang['ddemo'], 'manage_ticket_templates.php', 'NOTICE');}
    elseif ($action == 'new')    {new_saved();}
    elseif ($action == 'edit')   {edit_saved();}
    elseif ($action == 'remove') {remove();}
    elseif ($action == 'order')  {order_saved();}
}

/* Print header */
require_once(HESK_PATH . 'inc/header.inc.php');

/* Print main manage users page */
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');
?>
<script language="javascript" type="text/javascript"><!--
    function confirm_delete()
    {
        if (confirm('<?php echo hesk_makeJsString($hesklang['delete_tpl']); ?>')) {return true;}
        else {return false;}
    }
//-->
</script>
<?php
/* This will handle error, success and notice messages */
if (!isset($_SESSION['canned']['what'])) {
    hesk_handle_messages();
}

// Get canned responses from database
$result = hesk_dbQuery('SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'ticket_templates` ORDER BY `tpl_order` ASC');
$javascript_messages='';
$javascript_titles='';

$i=1;
$j=0;
$num = hesk_dbNumRows($result);
?>
<div class="main__content templates">
    <section class="templates__head">
        <h2>
            <?php echo $hesklang['ticket_tpl']; ?>
            <div class="tooltype right out-close">
                <svg class="icon icon-info">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                </svg>
                <div class="tooltype__content">
                    <div class="tooltype__wrapper">
                        <?php echo $hesklang['ticket_tpl_intro']; ?>
                    </div>
                </div>
            </div>
        </h2>
        <div class="btn btn--blue-border" ripple="ripple" data-action="create-template" onclick="diplayAddTitle()"><?php echo $hesklang['ticket_tpl_add']; ?></div>
    </section>
    <ul class="response__list">
        <?php if ($num < 1): ?>
            <li><h3><?php echo $hesklang['no_ticket_tpl']; ?></h3></li>
        <?php
        endif;

        while ($mysaved=hesk_dbFetchAssoc($result))
        {
            $j++;

            $table_row = '';
            if (isset($_SESSION['canned']['selcat2']) && $mysaved['id'] == $_SESSION['canned']['selcat2']) {
                $table_row = 'class="ticket-new"';
                unset($_SESSION['canned']['selcat2']);
            }

            $javascript_messages.='myMsgTxt['.$mysaved['id'].']=\''.str_replace("\r\n","\\r\\n' + \r\n'", addslashes($mysaved['message']) )."';\n";
            $javascript_titles.='myTitle['.$mysaved['id'].']=\''.addslashes($mysaved['title'])."';\n";

            echo '
	    <li ' . $table_row . '>
	    <h3>'.$mysaved['title'].'</h3>
        ';

            if ($num > 1)
            {
                if ($j == 1)
                {
                    echo'
                    <a href="#" style="visibility: hidden">
                        <svg class="icon icon-chevron-down">
                            <use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-chevron-down"></use>
                        </svg>
                    </a>
                    <a title="'.$hesklang['move_dn'].'" href="manage_ticket_templates.php?a=order&amp;replyid='.$mysaved['id'].'&amp;move=15&amp;token='.hesk_token_echo(0).'">
                        <svg class="icon icon-chevron-down">
                            <use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-chevron-down"></use>
                        </svg>
                    </a>';
                }
                elseif ($j == $num)
                {
                    echo'
                    <a title="'.$hesklang['move_up'].'" href="manage_ticket_templates.php?a=order&amp;replyid='.$mysaved['id'].'&amp;move=-15&amp;token='.hesk_token_echo(0).'">
                        <svg class="icon icon-chevron-up">
                            <use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-chevron-down"></use>
                        </svg>
                    </a>
                    <a href="#" style="visibility: hidden">
                        <svg class="icon icon-chevron-down">
                            <use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-chevron-down"></use>
                        </svg>
                    </a>';
                }
                else
                {
                    echo'
                    <a title="'.$hesklang['move_up'].'" href="manage_ticket_templates.php?a=order&amp;replyid='.$mysaved['id'].'&amp;move=-15&amp;token='.hesk_token_echo(0).'">
                        <svg class="icon icon-chevron-up">
                            <use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-chevron-down"></use>
                        </svg>
                    </a>
                    <a title="'.$hesklang['move_dn'].'" href="manage_ticket_templates.php?a=order&amp;replyid='.$mysaved['id'].'&amp;move=15&amp;token='.hesk_token_echo(0).'">
                        <svg class="icon icon-chevron-down">
                            <use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-chevron-down"></use>
                        </svg>
                    </a>';
                }
            }
            else
            {
                echo '';
            }

            $modal_id = hesk_generate_delete_modal($hesklang['confirm_deletion'],
                $hesklang['delete_tpl'],
                'manage_ticket_templates.php?a=remove&amp;id='.$mysaved['id'].'&amp;token='.hesk_token_echo(0));

            echo '
            <a title="'.$hesklang['edit'].'" href="javascript:setMessage(' . $mysaved['id'] . ')">
                <svg class="icon icon-edit-ticket">
                    <use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-edit-ticket"></use>
                </svg>
            </a>
            <a title="'.$hesklang['remove'].'" href="javascript:" data-modal="[data-modal-id=\''.$modal_id.'\']">
                <svg class="icon icon-delete">
                    <use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-delete"></use>
                </svg>
            </a>
	    </li>
		';
        } // End while
        ?>
    </ul>
</div>
<div class="right-bar template-create" <?php if (isset($_SESSION['canned']['what'])) { echo 'style="display: block"'; } ?>>
    <div class="right-bar__body template-create__body">
        <h3>
            <a href="javascript:">
                <svg class="icon icon-back">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-back"></use>
                </svg>
                <span <?php if (isset($_SESSION['canned']['what']) && $_SESSION['canned']['what'] !== 'NEW') { echo 'style="display: none"'; } ?> id="add-title"><?php echo $hesklang['ticket_tpl_add']; ?></span>
                <span <?php if (isset($_SESSION['canned']['what']) && $_SESSION['canned']['what'] !== 'EDIT') { echo 'style="display: none"'; } ?> id="edit-title"><?php echo $hesklang['ticket_tpl_edit']; ?></span>
            </a>
        </h3>
        <div class="form">
            <?php
            /* This will handle error, success and notice messages */
            if (isset($_SESSION['canned']['what'])) {
                echo '<div style="margin: -24px -24px 10px -16px;">';
                hesk_handle_messages();
                echo '</div>';
            }

            $errors = hesk_SESSION(array('canned', 'errors'));
            $errors = is_array($errors) ? $errors : array();
            ?>
            <form action="manage_ticket_templates.php" method="post" name="form1" class="form <?php echo hesk_SESSION(array('canned', 'errors')) ? 'invalid' : ''; ?>">
                <div class="form-group">
                    <label for="canned_title"><?php echo $hesklang['saved_title']; ?></label>
                    <span id="HeskTitle">
                        <input type="text" class="form-control <?php echo in_array('name', $errors) ? 'isError' : ''; ?>" id="canned_title" name="name" maxlength="50"
                            <?php if (isset($_SESSION['canned']['name'])) {echo ' value="'.stripslashes($_SESSION['canned']['name']).'" ';} ?>>
                    </span>
                </div>
                <div class="form-group">
                    <label for="canned_message"><?php echo $hesklang['message']; ?></label>
                    <span id="HeskMsg">
                        <textarea class="form-control <?php echo in_array('msg', $errors) ? 'isError' : ''; ?>" name="msg" rows="15" cols="70" id="canned_message"><?php
                            if (isset($_SESSION['canned']['msg'])) {
                                echo stripslashes($_SESSION['canned']['msg']);
                            }
                            ?></textarea>
                    </span>
                </div>
                <div class="template--submit">
                    <?php if(isset($_SESSION['canned']['what']) && $_SESSION['canned']['what'] == 'EDIT'): ?>
                        <input type="hidden" name="a" value="edit">
                        <input type="hidden" name="saved_replies" value="<?php echo $_SESSION['canned']['id']; ?>">
                    <?php else: ?>
                        <input type="hidden" name="a" value="new">
                        <input type="hidden" name="saved_replies" value="0">
                    <?php endif; ?>
                    <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                    <button class="btn btn-full" ripple="ripple"><?php echo $hesklang['save_ticket_tpl']; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<script language="javascript" type="text/javascript"><!--
var myMsgTxt = new Array();
myMsgTxt[0]='';
var myTitle = new Array();
myTitle[0]='';

<?php
echo $javascript_titles;
echo $javascript_messages;
?>

function setMessage(msgid) {
    if (document.getElementById) {
        document.getElementById('HeskMsg').innerHTML='<textarea class="form-control" id="canned_message" name="msg" rows="15" cols="70">'+myMsgTxt[msgid]+'</textarea>';
        document.getElementById('HeskTitle').innerHTML='<input type="text" class="form-control" id="canned_title" name="name" maxlength="50" value="'+myTitle[msgid]+'">';
    } else {
        document.form1.msg.value=myMsgTxt[msgid];
        document.form1.name.value=myTitle[msgid];
    }

    document.form1.a.value = 'edit';
    document.form1.saved_replies.value = msgid;
    document.getElementById('add-title').style.display = 'none';
    document.getElementById('edit-title').style.display = 'block';
    document.getElementsByClassName('template-create')[0].style.display = 'block';
}

function diplayAddTitle() {
    document.form1.msg.value = '';
    document.form1.name.value = '';
    document.form1.saved_replies.value = 0;
    document.form1.a.value = 'new';
    document.getElementById('add-title').style.display = 'block';
    document.getElementById('edit-title').style.display = 'none';
}
//-->
</script>
<?php

hesk_cleanSessionVars('canned');

require_once(HESK_PATH . 'inc/footer.inc.php');
exit();


/*** START FUNCTIONS ***/
function edit_saved()
{
    global $hesk_settings, $hesklang;

    /* A security check */
    hesk_token_check('POST');

    $hesk_error_buffer = '';
    $errors = array();

    $id = intval( hesk_POST('saved_replies') );
    if (!$id) {
        $hesk_error_buffer .= '<li>' . $hesklang['sel_ticket_tpl'] . '</li>';
        $errors[] = 'id';
    }
    $savename = hesk_input( hesk_POST('name') );
    if (!$savename) {
        $hesk_error_buffer .= '<li>' . $hesklang['ent_ticket_tpl_title'] . '</li>';
        $errors[] = 'name';
    }
    $msg = hesk_input( hesk_POST('msg') );
    if (!$msg) {
        $hesk_error_buffer .= '<li>' . $hesklang['ent_ticket_tpl_msg'] . '</li>';
        $errors[] = 'msg';
    }

    // Avoid problems with utf-8 newline chars in Javascript code, detect and remove them
    $msg = preg_replace('/\R/u', "\r\n", $msg);

    $_SESSION['canned']['what'] = 'EDIT';
    $_SESSION['canned']['id'] = $id;
    $_SESSION['canned']['name'] = $savename;
    $_SESSION['canned']['msg'] = $msg;
    $_SESSION['canned']['errors'] = $errors;

    /* Any errors? */
    if (strlen($hesk_error_buffer))
    {
        $hesk_error_buffer = $hesklang['rfm'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
        hesk_process_messages($hesk_error_buffer,'manage_ticket_templates.php?saved_replies='.$id);
    }

    $result = hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."ticket_templates` SET `title`='".hesk_dbEscape($savename)."',`message`='".hesk_dbEscape($msg)."' WHERE `id`='".intval($id)."'");

    unset($_SESSION['canned']['what']);
    unset($_SESSION['canned']['id']);
    unset($_SESSION['canned']['name']);
    unset($_SESSION['canned']['msg']);
    unset($_SESSION['canned']['errors']);

    hesk_process_messages($hesklang['ticket_tpl_saved'],'manage_ticket_templates.php?saved_replies='.$id,'SUCCESS');
} // End edit_saved()

function new_saved()
{
    global $hesk_settings, $hesklang;

    /* A security check */
    hesk_token_check('POST');

    $hesk_error_buffer = '';
    $errors = array();

    $savename = hesk_input( hesk_POST('name') );
    if (!$savename) {
        $hesk_error_buffer .= '<li>' . $hesklang['ent_ticket_tpl_title'] . '</li>';
        $errors[] = 'name';
    }
    $msg = hesk_input( hesk_POST('msg') );
    if (!$msg) {
        $hesk_error_buffer .= '<li>' . $hesklang['ent_ticket_tpl_msg'] . '</li>';
        $errors[] = 'msg';
    }

    // Avoid problems with utf-8 newline chars in Javascript code, detect and remove them
    $msg = preg_replace('/\R/u', "\r\n", $msg);

    $_SESSION['canned']['what'] = 'NEW';
    $_SESSION['canned']['name'] = $savename;
    $_SESSION['canned']['msg'] = $msg;
    $_SESSION['canned']['errors'] = $errors;

    /* Any errors? */
    if (strlen($hesk_error_buffer))
    {
        $hesk_error_buffer = $hesklang['rfm'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
        hesk_process_messages($hesk_error_buffer,'manage_ticket_templates.php');
    }

    /* Get the latest tpl_order */
    $result = hesk_dbQuery('SELECT `tpl_order` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'ticket_templates` ORDER BY `tpl_order` DESC LIMIT 1');
    $row = hesk_dbFetchRow($result);
    $my_order = isset($row[0]) ? intval($row[0]) + 10 : 10;

    hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."ticket_templates` (`title`,`message`,`tpl_order`) VALUES ('".hesk_dbEscape($savename)."','".hesk_dbEscape($msg)."','".intval($my_order)."')");

    unset($_SESSION['canned']['what']);
    unset($_SESSION['canned']['name']);
    unset($_SESSION['canned']['msg']);
    unset($_SESSION['canned']['errors']);

    hesk_process_messages($hesklang['ticket_tpl_saved'],'manage_ticket_templates.php','SUCCESS');
} // End new_saved()

function remove()
{
    global $hesk_settings, $hesklang;

    /* A security check */
    hesk_token_check();

    $mysaved = intval( hesk_GET('id') ) or hesk_error($hesklang['id_not_valid']);

    hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."ticket_templates` WHERE `id`='".intval($mysaved)."'");
    if (hesk_dbAffectedRows() != 1)
    {
        hesk_error("$hesklang[int_error]: $hesklang[ticket_tpl_not_found].");
    }

    hesk_process_messages($hesklang['ticket_tpl_removed'],'manage_ticket_templates.php','SUCCESS');
} // End remove()

function order_saved()
{
    global $hesk_settings, $hesklang;

    /* A security check */
    hesk_token_check();

    $tplid = intval( hesk_GET('replyid') ) or hesk_error($hesklang['ticket_tpl_id']);
    $_SESSION['canned']['selcat2'] = $tplid;

    $tpl_move = intval( hesk_GET('move') );

    hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."ticket_templates` SET `tpl_order`=`tpl_order`+".intval($tpl_move)." WHERE `id`='".intval($tplid)."'");
    if (hesk_dbAffectedRows() != 1) {hesk_error("$hesklang[int_error]: $hesklang[ticket_tpl_not_found].");}

    /* Update all category fields with new order */
    $result = hesk_dbQuery('SELECT `id` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'ticket_templates` ORDER BY `tpl_order` ASC');

    $i = 10;
    while ($mytpl=hesk_dbFetchAssoc($result))
    {
        hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."ticket_templates` SET `tpl_order`=".intval($i)." WHERE `id`='".intval($mytpl['id'])."'");
        $i += 10;
    }

    header('Location: manage_ticket_templates.php');
    exit();
} // End order_saved()

?>
