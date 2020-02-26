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
hesk_checkPermission('can_email_tpl');

// Define required constants
define('LOAD_TABS',1);

// Get valid email templates
require(HESK_PATH . 'inc/email_functions.inc.php');
$emails = array_keys(hesk_validEmails());

// Which language are we editing?
if ($hesk_settings['can_sel_lang'])
{
	$hesk_settings['edit_language'] = hesk_REQUEST('edit_language');
	if ( ! isset($hesk_settings['languages'][$hesk_settings['edit_language']]) )
	{
		$hesk_settings['edit_language'] = $hesk_settings['language'];
	}
}
else
{
	$hesk_settings['edit_language'] = $hesk_settings['language'];
}

// What should we do?
if ( $action = hesk_REQUEST('a') )
{
	if ($action == 'edit') {}
	elseif ( defined('HESK_DEMO') ) {hesk_process_messages($hesklang['ddemo'], 'email_templates.php', 'NOTICE');}
	elseif ($action == 'save') {save_et();}
}

/* Print header */
require_once(HESK_PATH . 'inc/header.inc.php');

/* Print main manage users page */
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');

/* This will handle error, success and notice messages */
if ($action != 'edit') {
    hesk_handle_messages();
}

?>
<div class="main__content tools">
    <section class="tools__between-head fw">
        <div class="head--tooltip">
            <h2><?php echo $hesklang['et_title']; ?></h2>
            <span><?php echo $hesklang['et_intro']; ?></span>
        </div>
        <?php if ($hesk_settings['can_sel_lang'] && count($hesk_settings['languages']) > 1): ?>
            <form method="get" action="email_templates.php">
            <div class="dropdown-select center out-close">
                <select name="edit_language" onchange="this.form.submit()">
                <?php foreach ($hesk_settings['languages'] as $lang => $info): ?>
                    <option value="<?php echo $lang; ?>" <?php if ($lang === $hesk_settings['edit_language']): ?>selected<?php endif; ?>>
                        <?php echo $lang; ?>
                    </option>
                <?php endforeach; ?>
                </select>
            </div>
        </form>
        <?php endif; ?>
    </section>
    <div class="table-wrapper email-templates">
        <div class="table">
            <table id="default-table" class="table sindu-table">
                <thead>
                <tr>
                    <th><?php echo $hesklang['email_tpl_title']; ?></th>
                    <th><?php echo $hesklang['rdesc']; ?></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $all_files = true;
                $all_writable = true;
                foreach ($emails as $email):
                    $eml_file = et_file_path($email);
                ?>
                <tr>
                    <td><?php echo $email; ?>.txt</td>
                    <td><?php echo $hesklang['desc_'.$email]; ?></td>
                    <td class="buttons">
                        <?php
                        if (!file_exists($eml_file)) {
                            $all_files = false;
                            echo '<span style="color:red">'.$hesklang['no_exists'].'</span>';
                        } elseif (!is_writable($eml_file)) {
                            $all_writable = false;
                            echo '<span style="color:red">'.$hesklang['not_writable'].'</span>';
                        } else {
                            ?>
                            <a title="<?php echo $hesklang['edit']; ?>" href="email_templates.php?a=edit&amp;id=<?php echo $email; ?>&amp;edit_language=<?php echo urlencode($hesk_settings['edit_language']); ?>" class="edit">
                                <svg class="icon icon-edit-ticket">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-edit-ticket"></use>
                                </svg>
                            </a>
                            <?php
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            // Any template missing?
            if (!$all_files)
            {
                hesk_show_error(sprintf($hesklang['etfm'], $hesk_settings['languages'][$hesk_settings['edit_language']]['folder']));
            }

            // Any template not writable?
            if (!$all_writable)
            {
                hesk_show_error(sprintf($hesklang['etfw'], $hesk_settings['languages'][$hesk_settings['edit_language']]['folder']));
            }
            ?>
        </div>
    </div>
</div>
<?php
// EDIT
if ($action == 'edit')
{
    // Get email ID
    $email = hesk_GET('id');

    // Get file path
    $eml_file = et_file_path($email);

    // Make sure the file exists and is writable
    if ( ! file_exists($eml_file))
    {
        hesk_error($hesklang['et_fm']);
    }
    elseif ( ! is_writable($eml_file))
    {
        hesk_error($hesklang['et_fw']);
    }

    // Start the edit form
    ?>
    <div class="right-bar tools-email-template-edit" style="display: block">
        <div class="right-bar__body form">
            <h3>
                <a href="email_templates.php">
                    <svg class="icon icon-back">
                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-back"></use>
                    </svg>
                    <span><?php echo $hesklang['edit_email_template']; ?></span>
                </a>
            </h3>
            <?php
            /* This will handle error, success and notice messages */
            echo '<div style="margin: -24px -24px 10px -16px;">';
            hesk_handle_messages();
            echo '</div>';
            ?>
            <section class="param">
                <span><?php echo $hesklang['efile']; ?></span>
                <form method="get" action="email_templates.php">
                    <div class="dropdown-select center out-close">
                        <select name="id" onchange="this.form.submit()">
                            <?php
                            foreach ($emails as $email_tmp) {
                                $eml_file_tmp = et_file_path($email_tmp);

                                if (!file_exists($eml_file_tmp) || !is_writable($eml_file_tmp)) {
                                    continue;
                                }

                                if ($email_tmp === $email) {
                                    echo '<option value="'.$email_tmp.'" selected>' . $hesklang['desc_'.$email_tmp].'</option>';
                                } else {
                                    echo '<option value="'.$email_tmp.'">' . $hesklang['desc_'.$email_tmp].'</option>';
                                }
                            }
                            ?>
                        </select>
                        <input type="hidden" name="a" value="edit">
                        <input type="hidden" name="edit_language" value="<?php echo hesk_htmlspecialchars($hesk_settings['edit_language']); ?>">
                    </div>
                </form>
            </section>
            <?php if ($hesk_settings['can_sel_lang'] && count($hesk_settings['languages']) > 1): ?>
                <section class="param">
                    <form method="get" action="email_templates.php">
                        <span><?php echo $hesklang['lgs']; ?></span>
                        <div class="dropdown-select center out-close">
                            <select name="edit_language" onchange="this.form.submit()">
                                <?php foreach ($hesk_settings['languages'] as $lang => $info): ?>
                                    <option value="<?php echo $lang; ?>" <?php if ($lang === $hesk_settings['edit_language']) { ?>selected<?php } ?>>
                                        <?php echo $lang; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="a" value="edit" />
                            <input type="hidden" name="id" value="<?php echo $email; ?>" />
                        </div>
                    </form>
                </section>
            <?php endif; ?>
            <form action="email_templates.php" method="post" name="form1">
                <div class="form-group">
                    <label for="message"><?php echo $hesklang['source'] . ': ' . substr($eml_file, 2); ?></label>
                    <span id="HeskMsg">
                        <textarea class="form-control" name="msg" rows="35" cols="100"><?php echo hesk_htmlspecialchars(file_get_contents($eml_file)); ?></textarea>
                    </span>
                </div>
                <div class="template--tags">
                    <label><?php echo $hesklang['insert_special']; ?></label>
                    <div class="tag-list">
                        <?php if ($email == 'forgot_ticket_id'): ?>
                            <a href="javascript:" title="%%NAME%%" onclick="hesk_insertTag('NAME')">
                                <?php echo $hesklang['name']; ?>
                            </a>
                            <a href="javascript:" title="%%FIRST_NAME%%" onclick="hesk_insertTag('FIRST_NAME')">
                                <?php echo $hesklang['fname']; ?>
                            </a>
                            <a href="javascript:" title="%%NUM%%" onclick="hesk_insertTag('NUM')">
                                <?php echo $hesklang['et_num']; ?>
                            </a>
                            <a href="javascript:" title="%%LIST_TICKETS%%" onclick="hesk_insertTag('LIST_TICKETS')">
                                <?php echo $hesklang['et_list']; ?>
                            </a>
                            <a href="javascript:" title="%%SITE_TITLE%%" onclick="hesk_insertTag('SITE_TITLE')">
                                <?php echo $hesklang['wbst_title']; ?>
                            </a>
                            <a href="javascript:" title="%%SITE_URL%%" onclick="hesk_insertTag('SITE_URL')">
                                <?php echo $hesklang['wbst_url']; ?>
                            </a>
                        <?php elseif ($email == 'new_pm'): ?>
                            <a href="javascript:" title="%%NAME%%" onclick="hesk_insertTag('NAME')">
                                <?php echo $hesklang['name']; ?>
                            </a>
                            <a href="javascript:" title="%%FIRST_NAME%%" onclick="hesk_insertTag('FIRST_NAME')">
                                <?php echo $hesklang['fname']; ?>
                            </a>
                            <a href="javascript:" title="%%SUBJECT%%" onclick="hesk_insertTag('SUBJECT')">
                                <?php echo $hesklang['subject']; ?>
                            </a>
                            <a href="javascript:" title="%%MESSAGE%%" onclick="hesk_insertTag('MESSAGE')">
                                <?php echo $hesklang['message']; ?>
                            </a>
                            <a href="javascript:" title="%%TRACK_URL%%" onclick="hesk_insertTag('TRACK_URL')">
                                <?php echo $hesklang['pm_url']; ?>
                            </a>
                            <a href="javascript:" title="%%SITE_TITLE%%" onclick="hesk_insertTag('SITE_TITLE')">
                                <?php echo $hesklang['wbst_title']; ?>
                            </a>
                            <a href="javascript:" title="%%SITE_URL%%" onclick="hesk_insertTag('SITE_URL')">
                                <?php echo $hesklang['wbst_url']; ?>
                            </a>
                        <?php else: ?>
                            <a href="javascript:" title="%%NAME%%" onclick="hesk_insertTag('NAME')">
                                <?php echo $hesklang['name']; ?>
                            </a>
                            <a href="javascript:" title="%%FIRST_NAME%%" onclick="hesk_insertTag('FIRST_NAME')">
                                <?php echo $hesklang['fname']; ?>
                            </a>
                            <a href="javascript:" title="%%EMAIL%%" onclick="hesk_insertTag('EMAIL')">
                                <?php echo $hesklang['email']; ?>
                            </a>
                            <a href="javascript:" title="%%CATEGORY%%" onclick="hesk_insertTag('CATEGORY')">
                                <?php echo $hesklang['category']; ?>
                            </a>
                            <a href="javascript:" title="%%PRIORITY%%" onclick="hesk_insertTag('PRIORITY')">
                                <?php echo $hesklang['priority']; ?>
                            </a>
                            <a href="javascript:" title="%%STATUS%%" onclick="hesk_insertTag('STATUS')">
                                <?php echo $hesklang['status']; ?>
                            </a>
                            <a href="javascript:" title="%%SUBJECT%%" onclick="hesk_insertTag('SUBJECT')">
                                <?php echo $hesklang['subject']; ?>
                            </a>
                            <a href="javascript:" title="%%MESSAGE%%" onclick="hesk_insertTag('MESSAGE')">
                                <?php echo $hesklang['message']; ?>
                            </a>
                            <a href="javascript:" title="%%CREATED%%" onclick="hesk_insertTag('CREATED')">
                                <?php echo $hesklang['created_on']; ?>
                            </a>
                            <a href="javascript:" title="%%UPDATED%%" onclick="hesk_insertTag('UPDATED')">
                                <?php echo $hesklang['updated_on']; ?>
                            </a>
                            <a href="javascript:" title="%%OWNER%%" onclick="hesk_insertTag('OWNER')">
                                <?php echo $hesklang['owner']; ?>
                            </a>
                            <a href="javascript:" title="%%LAST_REPLY_BY%%" onclick="hesk_insertTag('LAST_REPLY_BY')">
                                <?php echo $hesklang['last_replier']; ?>
                            </a>
                            <a href="javascript:" title="%%TIME_WORKED%%" onclick="hesk_insertTag('TIME_WORKED')">
                                <?php echo $hesklang['ts']; ?>
                            </a>
                            <a href="javascript:" title="%%TRACK_ID%%" onclick="hesk_insertTag('TRACK_ID')">
                                <?php echo $hesklang['trackID']; ?>
                            </a>
                            <a href="javascript:" title="%%ID%%" onclick="hesk_insertTag('ID')">
                                <?php echo $hesklang['seqid']; ?>
                            </a>
                            <a href="javascript:" title="%%TRACK_URL%%" onclick="hesk_insertTag('TRACK_URL')">
                                <?php echo $hesklang['ticket_url']; ?>
                            </a>
                            <a href="javascript:" title="%%SITE_TITLE%%" onclick="hesk_insertTag('SITE_TITLE')">
                                <?php echo $hesklang['wbst_title']; ?>
                            </a>
                            <a href="javascript:" title="%%SITE_URL%%" onclick="hesk_insertTag('SITE_URL')">
                                <?php echo $hesklang['wbst_url']; ?>
                            </a>
                            <?php
                            foreach ($hesk_settings['custom_fields'] as $k=>$v)
                            {
                                if ($v['use'])
                                {
                                    echo '<a href="javascript:" title="%%'.strtoupper($k).'%%" onclick="hesk_insertTag(\''.strtoupper($k).'\')">'.$v['name'].'</a>';
                                }
                            }
                        endif;
                        ?>
                    </div>
                </div>
                <div class="right-bar__footer">
                    <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
                    <input type="hidden" name="a" value="save" />
                    <input type="hidden" name="edit_language" value="<?php echo hesk_htmlspecialchars($hesk_settings['edit_language']); ?>" />
                    <input type="hidden" name="id" value="<?php echo $email; ?>" />
                    <button type="submit" class="btn btn-full save" ripple="ripple"><?php echo $hesklang['et_save']; ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php
} // END EDIT

require_once(HESK_PATH . 'inc/footer.inc.php');
exit();


/*** START FUNCTIONS ***/


function save_et()
{
	global $hesk_settings, $hesklang;

	// A security check
	# hesk_token_check('POST');

	// Get email ID
	$email = hesk_POST('id');

	// Get file path
	$eml_file = et_file_path($email);

	// Make sure the file exists and is writable
	if ( ! file_exists($eml_file))
	{
   		hesk_error($hesklang['et_fm']);
	}
	elseif ( ! is_writable($eml_file))
	{
		hesk_error($hesklang['et_fw']);
	}

	// Get message
	$message = trim(hesk_POST('msg'));

	// Do we need to remove backslashes from the message?
	if ( ! HESK_SLASH)
	{
    	$message = stripslashes($message);
	}

	// We won't accept an empty message
	if ( ! strlen($message))
	{
		hesk_process_messages($hesklang['et_empty'],'email_templates.php?a=edit&id=' . $email . '&edit_language='.$hesk_settings['edit_language']);
	}

	// Save to the file
	file_put_contents($eml_file, $message);

	// Show success
    $_SESSION['et_id'] = $email;
    hesk_process_messages($hesklang['et_saved'],'email_templates.php?edit_language='.$hesk_settings['edit_language'],'SUCCESS');
} // End save_et()


function et_file_path($id)
{
	global $hesk_settings, $hesklang, $emails;

	if ( ! in_array($id, $emails))
	{
    	hesk_error($hesklang['inve']);
	}

	return HESK_PATH . 'language/' . $hesk_settings['languages'][$hesk_settings['edit_language']]['folder'] . '/emails/' . $id . '.txt';
} // END et_file_path()