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
hesk_checkPermission('can_service_msg');

// Define required constants
define('LOAD_TABS',1);
define('WYSIWYG',1);

// Do we need to show the language options?
$hesk_settings['show_language'] = (count($hesk_settings['languages']) > 1);

// What should we do?
if ( $action = hesk_REQUEST('a') )
{
	if ($action == 'edit_sm') {edit_sm();}
	elseif ( defined('HESK_DEMO') ) {hesk_process_messages($hesklang['ddemo'], 'service_messages.php', 'NOTICE');}
	elseif ($action == 'new_sm') {new_sm();}
	elseif ($action == 'save_sm') {save_sm();}
	elseif ($action == 'order_sm') {order_sm();}
	elseif ($action == 'remove_sm') {remove_sm();}
}

/* Print header */
require_once(HESK_PATH . 'inc/header.inc.php');

/* Print main manage users page */
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');

/* This will handle error, success and notice messages */
if (!hesk_SESSION(array('new_sm', 'errors'))) {
    hesk_handle_messages();
}

// Get service messages from database
$res = hesk_dbQuery('SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'service_messages` ORDER BY `order` ASC');
$num = hesk_dbNumRows($res);
?>
<div class="main__content tools">
    <section class="tools__between-head">
        <h2><?php echo $hesklang['sm_title']; ?></h2>
        <?php if ($action !== 'edit_sm' && !isset($_SESSION['preview_sm'])): ?>
            <div class="btn btn--blue-border" ripple="ripple" data-action="create-service-message"><?php echo $hesklang['new_sm']; ?></div>
        <?php endif;?>
    </section>
    <div class="table-wrapper service-message">
        <div class="table">
            <table id="default-table" class="table sindu-table">
                <thead>
                <tr>
                    <th><?php echo $hesklang['sm_mtitle']; ?></th>
                    <th><?php echo $hesklang['sm_style']; ?></th>
                    <?php
                    if ($hesk_settings['show_language'])
                    {
                        ?>
                        <th><?php echo $hesklang['lgs']; ?></th>
                        <?php
                    }
                    ?>
                    <th><?php echo $hesklang['sm_author']; ?></th>
                    <th><?php echo $hesklang['sm_type']; ?></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php if ($num < 1): ?>
                <tr>
                    <td colspan="<?php echo $hesk_settings['show_language'] ? 2 : 1; ?>">
                        <?php echo $hesklang['no_sm']; ?>
                    </td>
                </tr>
                <?php
                else:
                    // List of staff
                    if (!isset($admins)) {
                        $admins = array();
                        $res2 = hesk_dbQuery("SELECT `id`,`name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users`");
                        while ($row=hesk_dbFetchAssoc($res2))
                        {
                            $admins[$row['id']]=$row['name'];
                        }
                    }

                    $k = 1;

                    while ($sm=hesk_dbFetchAssoc($res)) {
                        switch ($sm['style']) {
                            case 1:
                                $sm_style = "success";
                                break;
                            case 2:
                                $sm_style = "info";
                                break;
                            case 3:
                                $sm_style = "notice";
                                break;
                            case 4:
                                $sm_style = "error";
                                break;
                            default:
                                $sm_style = "none";
                        }

                        $table_row = '';
                        if (isset($_SESSION['smord']) && $_SESSION['smord'] == $sm['id']) {
                            $table_row = 'class="ticket-new"';
                            unset($_SESSION['smord']);
                        }

                        $type = $sm['type'] ? $hesklang['sm_draft']: $hesklang['sm_published'];
                        ?>
                        <tr <?php echo $table_row; ?>>
                            <td><?php echo $sm['title']; ?></td>
                            <td>
                                <div class="style <?php echo $sm_style; ?>">
                                    <?php echo $hesklang['sm_' . $sm_style]; ?>
                                </div>
                            </td>
                            <?php
                            if ($hesk_settings['show_language'])
                            {
                                ?>
                                <td><?php echo strlen($sm['language']) ? $sm['language'] : $hesklang['all']; ?></td>
                                <?php
                            }
                            ?>
                            <td><?php echo (isset($admins[$sm['author']]) ? $admins[$sm['author']] : $hesklang['e_udel']); ?></td>
                            <td><?php echo $type; ?></td>
                            <td class="nowrap buttons">
                                <?php $modal_id = hesk_generate_delete_modal($hesklang['confirm_deletion'],
                                    $hesklang['del_sm'],
                                    'service_messages.php?a=remove_sm&amp;id='. $sm['id'] .'&amp;token='. hesk_token_echo(0)); ?>
                                <p>
                                    <?php
                                    if ($num > 1)
                                    {
                                        if ($k == 1)
                                        {
                                            ?>
                                            <a href="#" style="visibility: hidden">
                                                <svg class="icon icon-chevron-up">
                                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                                </svg>
                                            </a>
                                            <a href="service_messages.php?a=order_sm&amp;id=<?php echo $sm['id']; ?>&amp;move=15&amp;token=<?php hesk_token_echo(); ?>"
                                               title="<?php echo $hesklang['move_dn']; ?>">
                                                <svg class="icon icon-chevron-down">
                                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                                </svg>
                                            </a>
                                            <?php
                                        }
                                        elseif ($k == $num)
                                        {
                                            ?>
                                            <a href="service_messages.php?a=order_sm&amp;id=<?php echo $sm['id']; ?>&amp;move=-15&amp;token=<?php hesk_token_echo(); ?>"
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
                                            <a href="service_messages.php?a=order_sm&amp;id=<?php echo $sm['id']; ?>&amp;move=-15&amp;token=<?php hesk_token_echo(); ?>"
                                               title="<?php echo $hesklang['move_up']; ?>">
                                                <svg class="icon icon-chevron-up">
                                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                                </svg>
                                            </a>
                                            <a href="service_messages.php?a=order_sm&amp;id=<?php echo $sm['id']; ?>&amp;move=15&amp;token=<?php hesk_token_echo(); ?>"
                                               title="<?php echo $hesklang['move_dn']; ?>">
                                                <svg class="icon icon-chevron-down">
                                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                                </svg>
                                            </a>
                                            <?php
                                        }
                                    }
                                    ?>
                                    <a href="service_messages.php?a=edit_sm&amp;id=<?php echo $sm['id']; ?>" class="edit" title="<?php echo $hesklang['edit']; ?>">
                                        <svg class="icon icon-edit-ticket">
                                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-edit-ticket"></use>
                                        </svg>
                                    </a>
                                    <a href="javascript:" class="delete" title="<?php echo $hesklang['delete']; ?>" data-modal="[data-modal-id='<?php echo $modal_id; ?>']">
                                        <svg class="icon icon-delete">
                                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-delete"></use>
                                        </svg>
                                    </a>
                                </p>
                            </td>
                        </tr>
                        <?php
                        $k++;
                    } // End while
                ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
if ($hesk_settings['kb_wysiwyg'])
{
    ?>
    <script>
        tinymce.init({
            selector: '#content',
            convert_urls: false,
            branding: false,
            browser_spellcheck: true,
            toolbar: 'undo redo | styleselect fontselect fontsizeselect | bold italic underline | alignleft aligncenter alignright alignjustify | forecolor backcolor | bullist numlist outdent indent | link unlink image codesample code',
            plugins: 'charmap code codesample image link lists table',
        });
    </script>
    <?php
}
?>
<div class="right-bar service-message-create" <?php if ($action === 'edit_sm' || isset($_SESSION['preview_sm']) || hesk_SESSION(array('new_sm','errors'))) {echo 'style="display: block"';} ?>>
    <div class="right-bar__body form" data-step="1">
        <h3 class="">
            <a href="<?php echo $action === 'edit_sm' || isset($_SESSION['preview_sm']) ? 'service_messages.php' : 'javascript:' ?>">
                <svg class="icon icon-back">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-back"></use>
                </svg>
                <span><?php echo hesk_SESSION('edit_sm') ? $hesklang['edit_sm'] : $hesklang['new_sm']; ?></span>
            </a>
        </h3>
        <?php
        if (hesk_SESSION(array('new_sm', 'errors'))) {
            hesk_handle_messages();
        }

        /* Do we have a service message to preview? */
        if (isset($_SESSION['preview_sm'])) {
            hesk_service_message($_SESSION['new_sm']);
        }
        ?>
        <ul class="step-bar">
            <li data-link="1" data-all="2"><?php echo $hesklang['sm_content']; ?></li>
            <li data-link="2" data-all="2"><?php echo $hesklang['sm_settings']; ?></li>
        </ul>
        <form action="service_messages.php" method="post" name="form1" class="form <?php echo hesk_SESSION(array('new_sm','errors')) ? 'invalid' : ''; ?>">
            <div class="step-slider">
                <div class="step-item step-1">
                    <div class="form-group">
                        <label for="sm-title"><?php echo $hesklang['sm_mtitle']; ?></label>
                        <input id="sm-title" type="text" name="title" class="form-control <?php echo in_array('title', hesk_SESSION(array('new_sm','errors'))) ? 'isError' : ''; ?>" maxlength="255" <?php if (isset($_SESSION['new_sm']['title'])) {echo 'value="'.$_SESSION['new_sm']['title'].'"';} ?>>
                    </div>
                    <div class="form-group" style="width: 100%">
                        <label for="content"><?php echo $hesklang['sm_msg']; ?></label>
                        <textarea class="form-control" name="message" id="content" style="height: 300px;"><?php if (isset($_SESSION['new_sm']['message'])) {echo $_SESSION['new_sm']['message'];} ?></textarea>
                    </div>
                </div>
                <div class="step-item step-2">
                    <h4><?php echo $hesklang['sm_style']; ?></h4>
                    <div class="styles__radio">
                        <label class="none">
                            <input type="radio" value="0" name="style" <?php if (!isset($_SESSION['new_sm']['style']) || (isset($_SESSION['new_sm']['style']) && $_SESSION['new_sm']['style'] == 0) ) {echo 'checked';} ?>>
                            <svg class="icon icon-tick">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tick"></use>
                            </svg>
                            <span><?php echo $hesklang['sm_none']; ?></span>
                        </label>
                        <label class="success">
                            <input type="radio" value="1" name="style" <?php if (isset($_SESSION['new_sm']['style']) && $_SESSION['new_sm']['style'] == 1 ) {echo 'checked';} ?>>
                            <svg class="icon icon-tick">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tick"></use>
                            </svg>
                            <span><?php echo $hesklang['sm_success']; ?></span>
                        </label>
                        <label class="info">
                            <input type="radio" value="2" name="style" <?php if (isset($_SESSION['new_sm']['style']) && $_SESSION['new_sm']['style'] == 2) {echo 'checked';} ?>>
                            <svg class="icon icon-tick">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tick"></use>
                            </svg>
                            <span><?php echo $hesklang['sm_info']; ?></span>
                        </label>
                        <label class="notice">
                            <input type="radio" value="3" name="style" <?php if (isset($_SESSION['new_sm']['style']) && $_SESSION['new_sm']['style'] == 3) {echo 'checked';} ?>>
                            <svg class="icon icon-tick">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tick"></use>
                            </svg>
                            <span><?php echo $hesklang['sm_notice']; ?></span>
                        </label>
                        <label class="error">
                            <input type="radio" value="4" name="style" <?php if (isset($_SESSION['new_sm']['style']) && $_SESSION['new_sm']['style'] == 4) {echo 'checked';} ?>>
                            <svg class="icon icon-tick">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tick"></use>
                            </svg>
                            <span><?php echo $hesklang['sm_error']; ?></span>
                        </label>
                    </div>
                    <section class="param">
                        <span><?php echo $hesklang['sm_type']; ?></span>
                        <div class="dropdown-select center out-close">
                            <select name="type">
                                <option value="0" <?php if (!isset($_SESSION['new_sm']['type']) || (isset($_SESSION['new_sm']['type']) && $_SESSION['new_sm']['type'] == 0) ) {echo 'selected="selected"';} ?>>
                                    <?php echo $hesklang['sm_published']; ?>
                                </option>
                                <option value="1" <?php if (isset($_SESSION['new_sm']['type']) && $_SESSION['new_sm']['type'] == 1) {echo 'selected="selected"';} ?>>
                                    <?php echo $hesklang['sm_draft']; ?>
                                </option>
                            </select>
                        </div>
                    </section>
                    <?php if ($hesk_settings['show_language']): ?>
                    <section class="param">
                        <span><?php echo $hesklang['lgs']; ?></span>
                        <div class="dropdown-select center out-close">
                            <select name="language">
                                <option value=""><?php echo $hesklang['all']; ?></option>
                                <?php foreach ($hesk_settings['languages'] as $lang => $v): ?>
                                    <option <?php echo (isset($_SESSION['new_sm']['language']) && $_SESSION['new_sm']['language'] == $lang ? 'selected="selected"' : ''); ?>>
                                        <?php echo $lang; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </section>
                    <?php endif; ?>
                </div>
            </div>
            <div class="right-bar__footer">
                <button type="button" class="btn btn-border" ripple="ripple" data-action="back"><?php echo $hesklang['wizard_back']; ?></button>
                <button type="button" class="btn btn-full next" data-action="next" ripple="ripple"><?php echo $hesklang['sm_go_to_settings']; ?></button>
                <?php if (isset($_SESSION['edit_sm'])): ?>
                    <input type="hidden" name="a" value="save_sm" />
                    <input type="hidden" name="id" value="<?php echo intval($_SESSION['new_sm']['id']); ?>" />
                <?php else: ?>
                    <input type="hidden" name="a" value="new_sm" />
                <?php endif; ?>
                <button type="submit" name="sm_preview" class="btn btn-border preview" ripple="ripple"><?php echo $hesklang['sm_preview']; ?></button>
                <button type="submit" name="sm_save" class="btn btn-full save" ripple="ripple"><?php echo $hesklang['sm_save']; ?></button>
                <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
            </div>
        </form>
    </div>
</div>
<?php

if ( isset($_SESSION['new_sm']) && ! isset($_SESSION['edit_sm']) )
{
	$_SESSION['new_sm'] = hesk_stripArray($_SESSION['new_sm']);
}

hesk_cleanSessionVars( array('new_sm', 'preview_sm', 'edit_sm') );

require_once(HESK_PATH . 'inc/footer.inc.php');
exit();


/*** START FUNCTIONS ***/


function save_sm()
{
	global $hesk_settings, $hesklang, $listBox;
    global $hesk_error_buffer;

	// A security check
	# hesk_token_check('POST');

    $hesk_error_buffer = array();

	// Get service messageID
	$id = intval( hesk_POST('id') ) or hesk_error($hesklang['sm_e_id']);

	$style = intval( hesk_POST('style', 0) );
	if ($style > 4 || $style < 0)
	{
    	$style = 0;
	}

    $type  = empty($_POST['type']) ? 0 : 1;
    $title = hesk_input( hesk_POST('title') ) or $hesk_error_buffer[] = $hesklang['sm_e_title'];
	$message = hesk_getHTML( hesk_POST('message') );

    // Clean the HTML code
    require(HESK_PATH . 'inc/htmlpurifier/HeskHTMLPurifier.php');
    $purifier = new HeskHTMLPurifier($hesk_settings['cache_dir']);
    $message = $purifier->heskPurify($message);

    // Any errors?
    if (count($hesk_error_buffer))
    {
		$_SESSION['edit_sm'] = true;

		$_SESSION['new_sm'] = array(
		'id' => $id,
		'style' => $style,
		'type' => $type,
		'title' => $title,
		'message' => hesk_input( hesk_POST('message') ),
        'errors' => array('title')
		);

		$tmp = '';
		foreach ($hesk_error_buffer as $error)
		{
			$tmp .= "<li>$error</li>\n";
		}
		$hesk_error_buffer = $tmp;

    	$hesk_error_buffer = $hesklang['rfm'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
    	hesk_process_messages($hesk_error_buffer,'service_messages.php');
    }

	// Just preview the message?
	if ( isset($_POST['sm_preview']) )
	{
    	$_SESSION['preview_sm'] = true;
		$_SESSION['edit_sm'] = true;

		$_SESSION['new_sm'] = array(
		'id' => $id,
		'style' => $style,
		'type' => $type,
		'title' => $title,
		'message' => $message
		);

		header('Location: service_messages.php');
		exit;
	}

	// Update the service message in the database
	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."service_messages` SET
	`author` = '".intval($_SESSION['id'])."',
	`title` = '".hesk_dbEscape($title)."',
	`message` = '".hesk_dbEscape($message)."',
	`style` = '{$style}',
	`type` = '{$type}'
	WHERE `id`={$id}");

    $_SESSION['smord'] = $id;
    hesk_process_messages($hesklang['sm_mdf'],'service_messages.php','SUCCESS');

} // End save_sm()


function edit_sm()
{
	global $hesk_settings, $hesklang;

	// Get service messageID
	$id = intval( hesk_GET('id') ) or hesk_error($hesklang['sm_e_id']);

	// Get details from the database
	$res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."service_messages` WHERE `id`={$id} LIMIT 1");
	if ( hesk_dbNumRows($res) != 1 )
	{
    	hesk_error($hesklang['sm_not_found']);
	}
	$sm = hesk_dbFetchAssoc($res);

    $_SESSION['smord'] = $id;
	$_SESSION['new_sm'] = $sm;
	$_SESSION['edit_sm'] = true;

} // End edit_sm()


function order_sm()
{
	global $hesk_settings, $hesklang;

	// A security check
	hesk_token_check();

	// Get ID and move parameters
	$id    = intval( hesk_GET('id') ) or hesk_error($hesklang['sm_e_id']);
	$move  = intval( hesk_GET('move') );
    $_SESSION['smord'] = $id;

	// Update article details
	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."service_messages` SET `order`=`order`+".intval($move)." WHERE `id`={$id}");

    // Update order of all service messages
    update_sm_order();

    $_SESSION['smord'] = $id;

	// Finish
	header('Location: service_messages.php');
	exit();

} // End order_sm()


function update_sm_order()
{
	global $hesk_settings, $hesklang;

	// Get list of current service messages
	$res = hesk_dbQuery("SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."service_messages` ORDER BY `order` ASC");

	// Update database
	$i = 10;
	while ( $sm = hesk_dbFetchAssoc($res) )
	{
		hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."service_messages` SET `order`=".intval($i)." WHERE `id`='".intval($sm['id'])."'");
		$i += 10;
	}

	return true;

} // END update_sm_order()


function remove_sm()
{
	global $hesk_settings, $hesklang;

	// A security check
	hesk_token_check();

	// Get ID
	$id = intval( hesk_GET('id') ) or hesk_error($hesklang['sm_e_id']);

	// Delete the service message
    hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."service_messages` WHERE `id`={$id}");

	// Were we successful?
    if ( hesk_dbAffectedRows() == 1 )
	{
		hesk_process_messages($hesklang['sm_deleted'],'./service_messages.php','SUCCESS');
	}
	else
	{
		hesk_process_messages($hesklang['sm_not_found'],'./service_messages.php');
	}

} // End remove_sm()


function new_sm()
{
	global $hesk_settings, $hesklang, $listBox;
    global $hesk_error_buffer;

	// A security check
	# hesk_token_check('POST');

    $hesk_error_buffer = array();

	$style = intval( hesk_POST('style', 0) );
	if ($style > 4 || $style < 0)
	{
    	$style = 0;
	}

    $type  = empty($_POST['type']) ? 0 : 1;
    $language = hesk_input( hesk_POST('language') );
    if ( ! isset($hesk_settings['languages'][$language]))
    {
        $language = '';
    }
    $title = hesk_input( hesk_POST('title') ) or $hesk_error_buffer[] = $hesklang['sm_e_title'];
	$message = hesk_getHTML( hesk_POST('message') );

    // Clean the HTML code
    require(HESK_PATH . 'inc/htmlpurifier/HeskHTMLPurifier.php');
    $purifier = new HeskHTMLPurifier($hesk_settings['cache_dir']);
    $message = $purifier->heskPurify($message);

    // Any errors?
    if (count($hesk_error_buffer))
    {
		$_SESSION['new_sm'] = array(
		'style' => $style,
		'type' => $type,
        'language' => $language,
		'title' => $title,
		'message' => hesk_input( hesk_POST('message') ),
        'errors' => array('title')
		);

		$tmp = '';
		foreach ($hesk_error_buffer as $error)
		{
			$tmp .= "<li>$error</li>\n";
		}
		$hesk_error_buffer = $tmp;

    	$hesk_error_buffer = $hesklang['rfm'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
    	hesk_process_messages($hesk_error_buffer,'service_messages.php');
    }

	// Just preview the message?
	if ( isset($_POST['sm_preview']) )
	{
    	$_SESSION['preview_sm'] = true;

		$_SESSION['new_sm'] = array(
		'style' => $style,
		'type' => $type,
        'language' => $language,
		'title' => $title,
		'message' => $message,
		);

		header('Location: service_messages.php');
		exit;
	}

	// Get the latest service message order
	$res = hesk_dbQuery("SELECT `order` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."service_messages` ORDER BY `order` DESC LIMIT 1");
	$row = hesk_dbFetchRow($res);
	$my_order = isset($row[0]) ? intval($row[0]) + 10 : 10;

    // Insert service message into database
	hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."service_messages` (`author`,`title`,`message`,`language`,`style`,`type`,`order`) VALUES (
    '".intval($_SESSION['id'])."',
    '".hesk_dbEscape($title)."',
    '".hesk_dbEscape($message)."',
    ".(strlen($language) ? "'".hesk_dbEscape($language)."'" : 'NULL').",
    '{$style}',
    '{$type}',
    '{$my_order}'
    )");

    $_SESSION['smord'] = hesk_dbInsertID();
    hesk_process_messages($hesklang['sm_added'],'service_messages.php','SUCCESS');

} // End new_sm()

?>
