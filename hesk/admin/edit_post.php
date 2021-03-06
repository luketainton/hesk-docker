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
require(HESK_PATH . 'inc/posting_functions.inc.php');

hesk_session_start();
hesk_dbConnect();
hesk_isLoggedIn();

/* Check permissions for this feature */
hesk_checkPermission('can_view_tickets');
hesk_checkPermission('can_edit_tickets');

/* Ticket ID */
$trackingID = hesk_cleanID() or die($hesklang['int_error'].': '.$hesklang['no_trackID']);

// Load custom fields
require_once(HESK_PATH . 'inc/custom_fields.inc.php');

// Load calendar JS and CSS
define('CALENDAR',1);

if ($hesk_settings['staff_ticket_formatting'] == 2) {
    define('WYSIWYG',1);
}

$is_reply = 0;
$tmpvar = array();

if (!isset($_SESSION['iserror']))
{
	$_SESSION['iserror'] = array();
}

/* Get ticket info */
$result = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE `trackid`='".hesk_dbEscape($trackingID)."' LIMIT 1");
if (hesk_dbNumRows($result) != 1)
{
	hesk_error($hesklang['ticket_not_found']);
}
$ticket = hesk_dbFetchAssoc($result);

// Demo mode
if ( defined('HESK_DEMO') )
{
	$ticket['email']	= 'hidden@demo.com';
}

/* Is this user allowed to view tickets inside this category? */
hesk_okCategory($ticket['category']);

if ( hesk_isREQUEST('reply') )
{
	$tmpvar['id'] = intval( hesk_REQUEST('reply') ) or die($hesklang['id_not_valid']);

	$result = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `id`='{$tmpvar['id']}' AND `replyto`='".intval($ticket['id'])."' LIMIT 1");
	if (hesk_dbNumRows($result) != 1)
    {
    	hesk_error($hesklang['id_not_valid']);
    }
    $reply = hesk_dbFetchAssoc($result);
    $ticket['message'] = $reply['message'];
    $ticket['message_html'] = $reply['message_html'];
    $is_reply = 1;
}

// Count number of existing attachments for this post
$number_of_attachments = $is_reply ? hesk_countAttachments($reply['attachments']) : hesk_countAttachments($ticket['attachments']);

if (isset($_POST['save']))
{
	/* A security check */
	hesk_token_check('POST');

	$hesk_error_buffer = array();

    // Add attachments?
    if ($hesk_settings['attachments']['use'] && $number_of_attachments < $hesk_settings['attachments']['max_number'])
    {
        require(HESK_PATH . 'inc/attachments.inc.php');
        $attachments = array();
        for ($i=$number_of_attachments+1;$i<=$hesk_settings['attachments']['max_number'];$i++)
        {
            $att = hesk_uploadFile($i);
            if ($att !== false && !empty($att))
            {
                $attachments[$i] = $att;
            }
        }
    }
    $myattachments = '';

    if ($is_reply)
    {
		$tmpvar['message'] = hesk_input( hesk_POST('message') ) or $hesk_error_buffer[]=$hesklang['enter_message'];
        $tmpvar['message_html'] = $tmpvar['message'];
        if ($hesk_settings['staff_ticket_formatting'] == 2) {
            // Decode the message we encoded earlier
            $tmpvar['message_html'] = hesk_html_entity_decode($tmpvar['message_html']);

            // Clean the HTML code and set the plaintext version
            require(HESK_PATH . 'inc/htmlpurifier/HeskHTMLPurifier.php');
            require(HESK_PATH . 'inc/html2text/html2text.php');
            $purifier = new HeskHTMLPurifier($hesk_settings['cache_dir']);
            $tmpvar['message_html'] = $purifier->heskPurify($tmpvar['message_html']);

            $tmpvar['message'] = convert_html_to_text($tmpvar['message_html']);
            $tmpvar['message'] = fix_newlines($tmpvar['message']);
        } else {
            // `message` already contains a HTML friendly version. May as well just re-use it
            $tmpvar['message'] = hesk_makeURL($tmpvar['message']);
            $tmpvar['message'] = nl2br($tmpvar['message']);
            $tmpvar['message_html'] = $tmpvar['message'];
        }


        if (count($hesk_error_buffer))
	    {
            // Remove any successfully uploaded attachments
            if ($hesk_settings['attachments']['use'] && isset($attachments))
            {
                hesk_removeAttachments($attachments);
            }

	    	$myerror = '<ul>';
		    foreach ($hesk_error_buffer as $error)
		    {
		        $myerror .= "<li>$error</li>\n";
		    }
	        $myerror .= '</ul>';
	    	hesk_error($myerror);
	    }

        if ($hesk_settings['attachments']['use'] && !empty($attachments))
        {
            foreach ($attachments as $myatt)
            {
                hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` (`ticket_id`,`saved_name`,`real_name`,`size`) VALUES ('".hesk_dbEscape($trackingID)."','".hesk_dbEscape($myatt['saved_name'])."','".hesk_dbEscape($myatt['real_name'])."','".intval($myatt['size'])."')");
                $myattachments .= hesk_dbInsertID() . '#' . $myatt['real_name'] .',';
            }
        }

        hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` SET `message`='".hesk_dbEscape($tmpvar['message'])."', `message_html`='".hesk_dbEscape($tmpvar['message_html'])."', `attachments`=CONCAT(`attachments`, '".hesk_dbEscape($myattachments)."') WHERE `id`='".intval($tmpvar['id'])."' AND `replyto`='".intval($ticket['id'])."'");
    }
    else
    {
		$tmpvar['name']    = hesk_input( hesk_POST('name') ) or $hesk_error_buffer[]=$hesklang['enter_your_name'];

        if ($hesk_settings['require_email'])
        {
            $tmpvar['email'] = hesk_validateEmail( hesk_POST('email'), 'ERR', 0) or $hesk_error_buffer['email']=$hesklang['enter_valid_email'];
        }
        else
        {
            $tmpvar['email'] = hesk_validateEmail( hesk_POST('email'), 'ERR', 0);

            // Not required, but must be valid if it is entered
            if ($tmpvar['email'] == '')
            {
                if (strlen(hesk_POST('email')))
                {
                    $hesk_error_buffer['email'] = $hesklang['not_valid_email'];
                }
            }
        }

		$tmpvar['subject'] = hesk_input( hesk_POST('subject') ) or $hesk_error_buffer[]=$hesklang['enter_ticket_subject'];
		$tmpvar['message'] = hesk_input( hesk_POST('message') );
        $tmpvar['message_html'] = $tmpvar['message'];
        if ($hesk_settings['require_message'] == 1 && $tmpvar['message'] == '')
        {
            $hesk_error_buffer[] = $hesklang['enter_message'];
        }
        if ($hesk_settings['staff_ticket_formatting'] == 2) {
            // Decode the message we encoded earlier
            $tmpvar['message_html'] = hesk_html_entity_decode($tmpvar['message_html']);

            // Clean the HTML code and set the plaintext version
            require(HESK_PATH . 'inc/htmlpurifier/HeskHTMLPurifier.php');
            require(HESK_PATH . 'inc/html2text/html2text.php');
            $purifier = new HeskHTMLPurifier($hesk_settings['cache_dir']);
            $tmpvar['message_html'] = $purifier->heskPurify($tmpvar['message_html']);

            $tmpvar['message'] = convert_html_to_text($tmpvar['message_html']);
            $tmpvar['message'] = fix_newlines($tmpvar['message']);

            // Re-encode the message
            $tmpvar['message'] = hesk_htmlspecialchars($tmpvar['message']);
        } else {
            // `message` already contains a HTML friendly version. May as well just re-use it
            $tmpvar['message'] = hesk_makeURL($tmpvar['message']);
            $tmpvar['message'] = nl2br($tmpvar['message']);
            $tmpvar['message_html'] = $tmpvar['message'];
        }

		// Demo mode
		if ( defined('HESK_DEMO') )
		{
			$tmpvar['email'] = 'hidden@demo.com';
		}

// Custom fields
foreach ($hesk_settings['custom_fields'] as $k=>$v)
{
	if ($v['use'] && hesk_is_custom_field_in_category($k, $ticket['category']))
    {
        if ($v['type'] == 'checkbox')
        {
			$tmpvar[$k]='';

        	if (isset($_POST[$k]) && is_array($_POST[$k]))
            {
				foreach ($_POST[$k] as $myCB)
				{
					$tmpvar[$k] .= ( is_array($myCB) ? '' : hesk_input($myCB) ) . '<br />';;
				}
				$tmpvar[$k]=substr($tmpvar[$k],0,-6);
            }
            else
            {
            	if ($v['req'] == 2)
                {
					$hesk_error_buffer[$k]=$hesklang['fill_all'].': '.$v['name'];
                }
            	$_POST[$k] = '';
            }
        }
        elseif ($v['type'] == 'date')
        {
        	$tmpvar[$k] = hesk_POST($k);
            $_SESSION["as_$k"] = '';

			if (preg_match("/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/", $tmpvar[$k]))
			{
            	$date = strtotime($tmpvar[$k] . ' t00:00:00 UTC');
                $dmin = strlen($v['value']['dmin']) ? strtotime($v['value']['dmin'] . ' t00:00:00 UTC') : false;
                $dmax = strlen($v['value']['dmax']) ? strtotime($v['value']['dmax'] . ' t00:00:00 UTC') : false;

                $_SESSION["as_$k"] = $tmpvar[$k];

	            if ($dmin && $dmin > $date)
	            {
					$hesk_error_buffer[$k] = sprintf($hesklang['d_emin'], $v['name'], hesk_custom_date_display_format($dmin, $v['value']['date_format']));
	            }
	            elseif ($dmax && $dmax < $date)
	            {
					$hesk_error_buffer[$k] = sprintf($hesklang['d_emax'], $v['name'], hesk_custom_date_display_format($dmax, $v['value']['date_format']));
	            }
                else
                {
                	$tmpvar[$k] = $date;
                }
			}
            else
            {
				if ($v['req'] == 2)
				{
					$hesk_error_buffer[$k]=$hesklang['fill_all'].': '.$v['name'];
				}
            }
        }
        elseif ($v['type'] == 'email')
        {
			$tmp = $hesk_settings['multi_eml'];
            $hesk_settings['multi_eml'] = $v['value']['multiple'];
			$tmpvar[$k] = hesk_validateEmail( hesk_POST($k), 'ERR', 0);
            $hesk_settings['multi_eml'] = $tmp;

            if ($tmpvar[$k] != '')
            {
				$_SESSION["as_$k"] = hesk_input($tmpvar[$k]);
            }
            else
            {
            	$_SESSION["as_$k"] = '';

                if ($v['req'] == 2)
                {
            		$hesk_error_buffer[$k] = $v['value']['multiple'] ? sprintf($hesklang['cf_noem'], $v['name']) : sprintf($hesklang['cf_noe'], $v['name']);
                }
            }
        }
		elseif ($v['req'] == 2)
        {
        	$tmpvar[$k]=hesk_makeURL(nl2br(hesk_input( hesk_POST($k) )));
            if ($tmpvar[$k] == '')
            {
            	$hesk_error_buffer[$k]=$hesklang['fill_all'].': '.$v['name'];
            }
        }
        else
        {
    		$tmpvar[$k]=hesk_makeURL(nl2br(hesk_input(hesk_POST($k))));
        }
	}
    else
    {
    	$tmpvar[$k] = '';
    }
}

	    if (count($hesk_error_buffer))
	    {
            // Remove any successfully uploaded attachments
            if ($hesk_settings['attachments']['use'] && isset($attachments))
            {
                hesk_removeAttachments($attachments);
            }

	    	$myerror = '<ul>';
		    foreach ($hesk_error_buffer as $error)
		    {
		        $myerror .= "<li>$error</li>\n";
		    }
	        $myerror .= '</ul>';
	    	hesk_error($myerror);
	    }

        if ($hesk_settings['attachments']['use'] && !empty($attachments))
        {
            foreach ($attachments as $myatt)
            {
                hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` (`ticket_id`,`saved_name`,`real_name`,`size`) VALUES ('".hesk_dbEscape($trackingID)."','".hesk_dbEscape($myatt['saved_name'])."','".hesk_dbEscape($myatt['real_name'])."','".intval($myatt['size'])."')");
                $myattachments .= hesk_dbInsertID() . '#' . $myatt['real_name'] .',';
            }
        }

		$custom_SQL = '';
		for ($i=1; $i<=50; $i++)
		{
			$custom_SQL .= '`custom'.$i.'`=' . (isset($tmpvar['custom'.$i]) ? "'".hesk_dbEscape($tmpvar['custom'.$i])."'" : "''") . ',';
		}
		$custom_SQL = rtrim($custom_SQL, ',');

		hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET
		`name`='".hesk_dbEscape( hesk_mb_substr($tmpvar['name'], 0, 255) )."',
		`email`='".hesk_dbEscape( hesk_mb_substr($tmpvar['email'], 0, 1000) )."',
		`subject`='".hesk_dbEscape( hesk_mb_substr($tmpvar['subject'], 0, 255) )."',
		`message`='".hesk_dbEscape($tmpvar['message'])."',
		`message_html`='".hesk_dbEscape($tmpvar['message_html'])."',
        `attachments`=CONCAT(`attachments`, '".hesk_dbEscape($myattachments)."'),
		$custom_SQL
		WHERE `id`='".intval($ticket['id'])."'");
    }

    unset($tmpvar);
    hesk_cleanSessionVars('tmpvar');

    hesk_process_messages($hesklang['edt2'],'admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999),'SUCCESS');
}

$ticket['message'] = hesk_msgToPlain($ticket['message'],0,0);

/* Print header */
require_once(HESK_PATH . 'inc/header.inc.php');

/* Print admin navigation */
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');
?>
<div class="main__content categories ticket-create">
    <div class="table-wrap">
        <h3 style="margin-bottom: 20px"><?php echo $hesklang['edtt']; ?></h3>
        <form method="post" class="form" action="edit_post.php" name="form1" enctype="multipart/form-data">
            <?php
            /* If it's not a reply edit all the fields */
            if (!$is_reply)
            {
                ?>
                <div class="form-group">
                    <label for="edit_subject"><?php echo $hesklang['subject']; ?>:</label>
                    <input type="text" class="form-control" id="edit_subject" name="subject" maxlength="70" value="<?php echo $ticket['subject'];?>">
                </div>
                <div class="form-group">
                    <label for="edit_name"><?php echo $hesklang['name']; ?>:</label>
                    <input type="text" name="name" id="edit_name" class="form-control" maxlength="50" value="<?php echo $ticket['name'];?>">
                </div>
                <div class="form-group">
                    <label for="edit_email"><?php echo $hesklang['email']; ?>:</label>
                    <input type="<?php echo $hesk_settings['multi_eml'] ? 'text' : 'email'; ?>" name="email" class="form-control" id="edit_email" maxlength="1000" value="<?php echo $ticket['email'];?>">
                </div>
                <?php
                foreach ($hesk_settings['custom_fields'] as $k=>$v) {
                    if ($v['use'] && $v['place']==0 && hesk_is_custom_field_in_category($k, $ticket['category']) ) {
                        $k_value  = $ticket[$k];

                        if ($v['type'] == 'checkbox') {
                            $k_value = explode('<br />',$k_value);
                        }

                        $v['req'] = $v['req']==2 ? '<span class="important">*</span>' : '';

                        switch ($v['type']) {
                            /* Radio box */
                            case 'radio':
                                echo '
                                <div class="form-group">
                                    <label>'.$v['name:'].' '.$v['req'].'</label>
                                    <div class="radio-list">';

                                        $cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

                                        $index = 0;
                                        foreach ($v['value']['radio_options'] as $option)
                                        {
                                            if (strlen($k_value) == 0)
                                            {
                                                $k_value = $option;
                                                $checked = empty($v['value']['no_default']) ? 'checked' : '';
                                            }
                                            elseif ($k_value == $option)
                                            {
                                                $k_value = $option;
                                                $checked = 'checked';
                                            }
                                            else
                                            {
                                                $checked = '';
                                            }

                                            echo '
                                            <div class="radio-custom" style="margin-bottom: 5px">
                                                <input type="radio" id="edit_'.$k.$index.'" name="'.$k.'" value="'.$option.'" '.$checked.' '.$cls.'>
                                                <label for="edit_'.$k.$index.'">'.$option.'</label>
                                            </div>';
                                            $index++;
                                        }
                                    echo '</div>
                                </div>';
                                break;

                            /* Select drop-down box */
                            case 'select':

                                $cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

                                echo '
                                <div class="form-group">
                                    <label for="edit_">'.$v['name:'].' '.$v['req'].'</label>
                                        <select name="'.$k.'" id="'.$k.'" '.$cls.'>';
                                        // Show "Click to select"?
                                        if ( ! empty($v['value']['show_select']))
                                        {
                                            echo '<option value="">'.$hesklang['select'].'</option>';
                                        }

                                        foreach ($v['value']['select_options'] as $option)
                                        {
                                            if ($k_value == $option)
                                            {
                                                $k_value = $option;
                                                $selected = 'selected';
                                            }
                                            else
                                            {
                                                $selected = '';
                                            }

                                            echo '<option '.$selected.'>'.$option.'</option>';
                                        }
                                        echo '</select>
                                </div>
                                <script>
                                    $(\'#'.$k.'\').selectize();
                                </script>
                                ';
                                break;

                            /* Checkbox */
                            case 'checkbox':
                                echo '
                                <div class="form-group">
                                    <label>'.$v['name:'].' '.$v['req'].'</label>';

                                $cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

                                $index = 0;
                                foreach ($v['value']['checkbox_options'] as $option)
                                {
                                    if (in_array($option,$k_value))
                                    {
                                        $checked = 'checked';
                                    }
                                    else
                                    {
                                        $checked = '';
                                    }

                                    echo '
                                    <div class="checkbox-custom">
                                        <input type="checkbox" id="edit_'.$k.$index.'" name="'.$k.'[]" value="'.$option.'" '.$checked.' '.$cls.'>
                                        <label for="edit_'.$k.$index.'"> '.$option.'</label>
                                    </div>';
                                    $index++;
                                }

                                echo '</div>';
                                break;

                            /* Large text box */
                            case 'textarea':
                                $cls = in_array($k,$_SESSION['iserror']) ? ' isError" ' : '';
                                $k_value = hesk_msgToPlain($k_value,0,0);

                                echo '
                                <div class="form-group">
                                    <label>'.$v['name:'].' '.$v['req'].'</label>
                                    <textarea name="'.$k.'" class="form-control'.$cls.'" style="height: inherit" rows="'.intval($v['value']['rows']).'" cols="'.intval($v['value']['cols']).'" >'.$k_value.'</textarea>
                                </div>';
                                break;

                            // Date
                            case 'date':
                                $k_value = hesk_custom_date_display_format($k_value, 'm/d/Y');

                                echo '
                                <section class="param calendar">
                                    <label>'.$v['name:'].' '.$v['req'].'</label>
                                    <div class="calendar--button">
                                        <button type="button">
                                            <svg class="icon icon-calendar">
                                                <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-calendar"></use>
                                            </svg>
                                        </button>
                                        <input name="'. $k .'"
                                               value="'. $k_value .'"
                                               type="text" class="datepicker">
                                    </div>
                                    <div class="calendar--value" '. ($k_value ? 'style="display: block"' : '') . '>
                                        <span>'. $k_value .'</span>
                                        <i class="close">
                                            <svg class="icon icon-close">
                                                <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-close"></use>
                                            </svg>
                                        </i>
                                    </div>
                                </section>';
                                break;

                            // Email
                            case 'email':
                                $cls = in_array($k,$_SESSION['iserror']) ? 'isError' : '';

                                $suggest = $hesk_settings['detect_typos'] ? 'onblur="Javascript:hesk_suggestEmail(\''.$k.'\', \''.$k.'_suggestions\', 0, 1'.($v['value']['multiple'] ? ',1' : '').')"' : '';

                                echo '
                                <div class="form-group">
                                    <label>'.$v['name:'].' '.$v['req'].'</label>
                                    <input class="form-control '.$cls.'" type="'.($v['value']['multiple'] ? 'text' : 'email').'" name="'.$k.'" id="'.$k.'" value="'.$k_value.'" size="40" '.$suggest.'>
                                </div>
                                <div id="'.$k.'_suggestions"></div>';
                                break;

                            // Hidden
                            // Handle as text fields for staff

                            /* Default text input */
                            default:
                                $k_value = hesk_msgToPlain($k_value,0,0);

                                $cls = in_array($k,$_SESSION['iserror']) ? 'isError' : '';

                                echo '
                                <div class="form-group">
                                    <label>'.$v['name:'].' '.$v['req'].'</label>
                                    <input class="form-control '.$cls.'" type="text" name="'.$k.'" size="40" maxlength="'.intval($v['value']['max_length']).'" value="'.$k_value.'">
                                </div>';
                        }
                    }
                }
                ?>
                <?php
            }
            ?>
            <div class="form-group">
                <label for="edit_message"><?php echo $hesklang['message']; ?>:</label>
                <textarea style="height: inherit" class="form-control" id="edit_message" name="message" rows="12" cols="60"><?php echo $hesk_settings['staff_ticket_formatting'] == 2 ? hesk_htmlspecialchars($ticket['message_html']) : $ticket['message']; ?></textarea>
            </div>

            <?php
            if ($hesk_settings['staff_ticket_formatting'] == 2) {
                hesk_tinymce_init('#edit_message');
            }

            if (!$is_reply)
            {
                foreach ($hesk_settings['custom_fields'] as $k=>$v) {
                    if ($v['use'] && $v['place'] && hesk_is_custom_field_in_category($k, $ticket['category']) ) {
                        $k_value  = $ticket[$k];

                        if ($v['type'] == 'checkbox') {
                            $k_value = explode('<br />',$k_value);
                        }

                        $v['req'] = $v['req']==2 ? '<span class="important">*</span>' : '';

                        switch ($v['type']) {
                            /* Radio box */
                            case 'radio':
                                echo '
                                <div class="form-group">
                                    <label>'.$v['name:'].' '.$v['req'].'</label>
                                    <div class="radio-list">';

                                        $cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

                                        $index = 0;
                                        foreach ($v['value']['radio_options'] as $option)
                                        {
                                            if (strlen($k_value) == 0)
                                            {
                                                $k_value = $option;
                                                $checked = empty($v['value']['no_default']) ? 'checked' : '';
                                            }
                                            elseif ($k_value == $option)
                                            {
                                                $k_value = $option;
                                                $checked = 'checked';
                                            }
                                            else
                                            {
                                                $checked = '';
                                            }

                                            echo '
                                            <div class="radio-custom" style="margin-bottom: 5px">
                                                <input type="radio" id="edit_'.$k.$index.'" name="'.$k.'" value="'.$option.'" '.$checked.' '.$cls.'>
                                                <label for="edit_'.$k.$index.'">'.$option.'</label>
                                            </div>';
                                            $index++;
                                        }
                                    echo '</div>
                                </div>';
                                break;

                            /* Select drop-down box */
                            case 'select':

                                $cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

                                echo '
                                <div class="form-group">
                                    <label for="edit_">'.$v['name:'].' '.$v['req'].'</label>
                                        <select name="'.$k.'" id="'.$k.'" '.$cls.'>';
                                        // Show "Click to select"?
                                        if ( ! empty($v['value']['show_select']))
                                        {
                                            echo '<option value="">'.$hesklang['select'].'</option>';
                                        }

                                        foreach ($v['value']['select_options'] as $option)
                                        {
                                            if ($k_value == $option)
                                            {
                                                $k_value = $option;
                                                $selected = 'selected';
                                            }
                                            else
                                            {
                                                $selected = '';
                                            }

                                            echo '<option '.$selected.'>'.$option.'</option>';
                                        }
                                        echo '</select>
                                </div>
                                <script>
                                    $(\'#'.$k.'\').selectize();
                                </script>
                                ';
                                break;

                            /* Checkbox */
                            case 'checkbox':
                                echo '
                                <div class="form-group">
                                    <label>'.$v['name:'].' '.$v['req'].'</label>';

                                $cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

                                $index = 0;
                                foreach ($v['value']['checkbox_options'] as $option)
                                {
                                    if (in_array($option,$k_value))
                                    {
                                        $checked = 'checked';
                                    }
                                    else
                                    {
                                        $checked = '';
                                    }

                                    echo '
                                    <div class="checkbox-custom">
                                        <input type="checkbox" id="edit_'.$k.$index.'" name="'.$k.'[]" value="'.$option.'" '.$checked.' '.$cls.'>
                                        <label for="edit_'.$k.$index.'"> '.$option.'</label>
                                    </div>';
                                    $index++;
                                }

                                echo '</div>';
                                break;

                            /* Large text box */
                            case 'textarea':
                                $cls = in_array($k,$_SESSION['iserror']) ? ' isError" ' : '';
                                $k_value = hesk_msgToPlain($k_value,0,0);

                                echo '
                                <div class="form-group">
                                    <label>'.$v['name:'].' '.$v['req'].'</label>
                                    <textarea name="'.$k.'" class="form-control'.$cls.'" style="height: inherit" rows="'.intval($v['value']['rows']).'" cols="'.intval($v['value']['cols']).'" >'.$k_value.'</textarea>
                                </div>';
                                break;

                            // Date
                            case 'date':
                                $k_value = hesk_custom_date_display_format($k_value, 'm/d/Y');

                                echo '
                                <section class="param calendar">
                                    <label>'.$v['name:'].' '.$v['req'].'</label>
                                    <div class="calendar--button">
                                        <button type="button">
                                            <svg class="icon icon-calendar">
                                                <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-calendar"></use>
                                            </svg>
                                        </button>
                                        <input name="'. $k .'"
                                               value="'. $k_value .'"
                                               type="text" class="datepicker">
                                    </div>
                                    <div class="calendar--value" '. ($k_value ? 'style="display: block"' : '') . '>
                                        <span>'. $k_value .'</span>
                                        <i class="close">
                                            <svg class="icon icon-close">
                                                <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-close"></use>
                                            </svg>
                                        </i>
                                    </div>
                                </section>';
                                break;

                            // Email
                            case 'email':
                                $cls = in_array($k,$_SESSION['iserror']) ? 'isError' : '';

                                $suggest = $hesk_settings['detect_typos'] ? 'onblur="Javascript:hesk_suggestEmail(\''.$k.'\', \''.$k.'_suggestions\', 0, 1'.($v['value']['multiple'] ? ',1' : '').')"' : '';

                                echo '
                                <div class="form-group">
                                    <label>'.$v['name:'].' '.$v['req'].'</label>
                                    <input class="form-control '.$cls.'" type="'.($v['value']['multiple'] ? 'text' : 'email').'" name="'.$k.'" id="'.$k.'" value="'.$k_value.'" size="40" '.$suggest.'>
                                </div>
                                <div id="'.$k.'_suggestions"></div>';
                                break;

                            // Hidden
                            // Handle as text fields for staff

                            /* Default text input */
                            default:
                                $k_value = hesk_msgToPlain($k_value,0,0);

                                $cls = in_array($k,$_SESSION['iserror']) ? 'isError' : '';

                                echo '
                                <div class="form-group">
                                    <label>'.$v['name:'].' '.$v['req'].'</label>
                                    <input class="form-control '.$cls.'" type="text" name="'.$k.'" size="40" maxlength="'.intval($v['value']['max_length']).'" value="'.$k_value.'">
                                </div>';
                        }
                    }
                }
            } // End if not a reply

            // attachments
            if ($hesk_settings['attachments']['use'] && $number_of_attachments < $hesk_settings['attachments']['max_number'])
            {
                echo '<div class="form-group">';
                echo '<label>' . $hesklang['attachments'] . ': (<a class="link" href="javascript:" onclick="hesk_window(\'../file_limits.php\',250,500);return false;">' . $hesklang['ful'] . '</a>)</label>';
                for ($i=$number_of_attachments+1;$i<=$hesk_settings['attachments']['max_number'];$i++)
                {
                    echo '<input type="file" name="attachment['.$i.']" size="50" /><br />';
                }
                echo '</div>';
            }
            ?>
            <input type="hidden" name="save" value="1">
            <input type="hidden" name="track" value="<?php echo $trackingID; ?>">
            <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
            <?php
            if ($is_reply)
            {
                ?>
                <input type="hidden" name="reply" value="<?php echo $tmpvar['id']; ?>" />
                <?php
            }
            ?>
            <button type="submit" class="btn btn-full" style="display: inline-flex; height: 48px">
                <?php echo $hesklang['save_changes']; ?>
            </button>
            <a href="javascript:history.go(-1)" class="btn btn--blue-border"><?php echo $hesklang['back']; ?></a>
        </form>
    </div>
</div>

<p style="text-align:center"></p>

<p>&nbsp;</p>

<?php
require_once(HESK_PATH . 'inc/footer.inc.php');
exit();


function hesk_countAttachments($attachments_string)
{
    if ( ! strlen($attachments_string) || strpos($attachments_string, ',') === false)
    {
        return 0;
    }

    $att = explode(',', substr($attachments_string, 0, -1));

    return count($att);

} // END hesk_countAttachments()
