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

// Auto-focus first empty or error field
define('AUTOFOCUS', true);

/* Get all the required files and functions */
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');
require(HESK_PATH . 'inc/admin_functions.inc.php');
hesk_load_database_functions();

hesk_session_start();
hesk_dbConnect();
hesk_isLoggedIn();

// Load custom fields
require_once(HESK_PATH . 'inc/custom_fields.inc.php');

// Load calendar JS and CSS
define('CALENDAR',1);

// Pre-populate fields
// Customer name
if (isset($_REQUEST['name'])) {
	$_SESSION['as_name'] = $_REQUEST['name'];
}

// Customer email address
if (isset($_REQUEST['email'])) {
	$_SESSION['as_email']  = $_REQUEST['email'];
	$_SESSION['as_email2'] = $_REQUEST['email'];
}

// Category ID
if (isset($_REQUEST['catid'])) {
	$_SESSION['as_category'] = intval($_REQUEST['catid']);
}
if (isset($_REQUEST['category'])) {
	$_SESSION['as_category'] = intval($_REQUEST['category']);
}

// Priority
if (isset($_REQUEST['priority'])) {
	$_SESSION['as_priority'] = intval($_REQUEST['priority']);
}

// Subject
if (isset($_REQUEST['subject'])) {
	$_SESSION['as_subject'] = $_REQUEST['subject'];
}

// Message
if (isset($_REQUEST['message'])) {
	$_SESSION['as_message'] = $_REQUEST['message'];
}

// Custom fields
foreach ($hesk_settings['custom_fields'] as $k=>$v) {
	if ($v['use'] && isset($_REQUEST[$k]) ) {
		$_SESSION['as_'.$k] = $_REQUEST[$k];
	}
}

/* Varibles for coloring the fields in case of errors */
if (!isset($_SESSION['iserror'])) {
	$_SESSION['iserror'] = array();
}

if (!isset($_SESSION['isnotice'])) {
	$_SESSION['isnotice'] = array();
}

/* List of users */
$admins = array();
$result = hesk_dbQuery("SELECT `id`,`name`,`isadmin`,`categories`,`heskprivileges` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ORDER BY `name` ASC");
while ($row=hesk_dbFetchAssoc($result))
{
	/* Is this an administrator? */
	if ($row['isadmin'])
    {
	    $admins[$row['id']]=$row['name'];
	    continue;
    }

	/* Not admin, is user allowed to view tickets? */
	if (strpos($row['heskprivileges'], 'can_view_tickets') !== false)
	{
		$admins[$row['id']]=$row['name'];
		continue;
	}
}

/* Print header */
require_once(HESK_PATH . 'inc/header.inc.php');

/* Print admin navigation */
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');

// Get categories
$hesk_settings['categories'] = array();

if (hesk_checkPermission('can_submit_any_cat', 0))
{
    $res = hesk_dbQuery("SELECT `id`, `name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` ORDER BY `cat_order` ASC");
}
else
{
    $res = hesk_dbQuery("SELECT `id`, `name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE ".hesk_myCategories('id')." ORDER BY `cat_order` ASC");
}

while ($row=hesk_dbFetchAssoc($res))
{
	$hesk_settings['categories'][$row['id']] = $row['name'];
}

$number_of_categories = count($hesk_settings['categories']);

if ($number_of_categories == 0)
{
	$category = 1;
}
elseif ($number_of_categories == 1)
{
	$category = current(array_keys($hesk_settings['categories']));
}
else
{
	$category = isset($_GET['catid']) ? hesk_REQUEST('catid'): hesk_REQUEST('category');

	// Force the customer to select a category?
	if (! isset($hesk_settings['categories'][$category]) )
	{
		return print_select_category($number_of_categories);
	}
}
?>
<div class="main__content categories ticket-create">
    <div class="table-wrap">

        <?php
        if ( ! isset($_SESSION['HESK_ERROR']))
        {
            hesk_show_info($hesklang['nti3'], ' ', false);
        }

        /* This will handle error, success and notice messages */
        hesk_handle_messages();
        ?>

        <h3 style="font-size: 1.3rem; margin-top: 10px"><?php echo $hesklang['nti2']; ?></h3>
        <h4><?php echo $hesklang['req_marked_with']; ?> <span class="important">*</span></h4>

        <form method="post" class="form <?php echo isset($_SESSION['iserror']) && count($_SESSION['iserror']) ? 'invalid' : ''; ?>" action="admin_submit_ticket.php" name="form1" enctype="multipart/form-data">
            <div class="form-group">
                <label for="create_name">
                    <?php echo $hesklang['name']; ?>: <span class="important">*</span>
                </label>
                <input type="text" id="create_name" name="name" class="form-control <?php if (in_array('name',$_SESSION['iserror'])) {echo 'isError';} ?>" maxlength="50" value="<?php if (isset($_SESSION['as_name'])) {echo stripslashes(hesk_input($_SESSION['as_name']));} ?>">
            </div>
            <div class="form-group">
                <label for="email">
                    <?php echo $hesklang['email'] . ':' . ($hesk_settings['require_email'] ? ' <span class="important">*</span>' : '') ; ?>
                </label>
                <input type="<?php echo ($hesk_settings['multi_eml'] ? 'text' : 'email'); ?>"
                       class="form-control <?php if (in_array('email',$_SESSION['iserror'])) {echo 'isError';} elseif (in_array('email',$_SESSION['isnotice'])) {echo 'isNotice';} ?>"
                       name="email" id="email" maxlength="1000"
                       value="<?php if (isset($_SESSION['as_email'])) {echo stripslashes(hesk_input($_SESSION['as_email']));} ?>"
                    <?php if($hesk_settings['detect_typos']) { echo ' onblur="Javascript:hesk_suggestEmail(\'email\', \'email_suggestions\', 1, 1)"'; } ?>>
            </div>
            <div id="email_suggestions"></div>
            <div class="form-group">
                <label><?php echo $hesklang['priority']; ?>: <span class="important">*</span></label>
                <div class="dropdown-select center out-close">
                    <select name="priority" <?php if (in_array('priority',$_SESSION['iserror'])) {echo ' class="isError" ';} ?> >
                        <?php
                        // Show the "Click to select"?
                        if ($hesk_settings['select_pri'])
                        {
                            echo '<option value="">'.$hesklang['select'].'</option>';
                        }
                        ?>
                        <option value="3" <?php if(isset($_SESSION['as_priority']) && $_SESSION['as_priority']==3) {echo 'selected';} ?>><?php echo $hesklang['low']; ?></option>
                        <option value="2" <?php if(isset($_SESSION['as_priority']) && $_SESSION['as_priority']==2) {echo 'selected';} ?>><?php echo $hesklang['medium']; ?></option>
                        <option value="1" <?php if(isset($_SESSION['as_priority']) && $_SESSION['as_priority']==1) {echo 'selected';} ?>><?php echo $hesklang['high']; ?></option>
                        <option value="0" <?php if(isset($_SESSION['as_priority']) && $_SESSION['as_priority']==0) {echo 'selected';} ?>><?php echo $hesklang['critical']; ?></option>
                    </select>
                </div>
            </div>

            <!-- START CUSTOM BEFORE -->
            <?php

            foreach ($hesk_settings['custom_fields'] as $k=>$v)
            {
                if ($v['use'] && $v['place']==0 && hesk_is_custom_field_in_category($k, $category) )
                {
                    $v['req'] = $v['req']==2 ? '<span class="important">*</span>' : '';

                    if ($v['type'] == 'checkbox')
                    {
                        $k_value = array();
                        if (isset($_SESSION["as_$k"]) && is_array($_SESSION["as_$k"]))
                        {
                            foreach ($_SESSION["as_$k"] as $myCB)
                            {
                                $k_value[] = stripslashes(hesk_input($myCB));
                            }
                        }
                    }
                    elseif (isset($_SESSION["as_$k"]))
                    {
                        $k_value  = stripslashes(hesk_input($_SESSION["as_$k"]));
                    }
                    else
                    {
                        $k_value  = '';
                    }

                    switch ($v['type'])
                    {
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
                            if (strlen($k_value) != 0 || isset($_SESSION["as_$k"]))
                            {
                                $v['value']['default_value'] = $k_value;
                            }

                            $cls = in_array($k,$_SESSION['iserror']) ? 'isError' : '';

                            echo '
                                <div class="form-group">
                                    <label>'.$v['name:'].' '.$v['req'].'</label>
                                    <input class="form-control '.$cls.'" type="text" name="'.$k.'" size="40" maxlength="'.intval($v['value']['max_length']).'" value="'.$v['value']['default_value'].'">
                                </div>';
                    }
                }
            }
            ?>
            <!-- END CUSTOM BEFORE -->
            <?php
            // Lets handle ticket templates
            $can_options = '';

            // Get ticket templates from the database
            $res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."ticket_templates` ORDER BY `tpl_order` ASC");

            // If we have any templates print them out
            if ( hesk_dbNumRows($res) )
            {
                ?>
                <script language="javascript" type="text/javascript"><!--
                    // -->
                    var myMsgTxt = new Array();
                    var mySubjectTxt = new Array();
                    myMsgTxt[0]='';
                    mySubjectTxt[0]='';

                    <?php
                    while ($mysaved = hesk_dbFetchRow($res))
                    {
                        $can_options .= '<option value="' . $mysaved[0] . '">' . $mysaved[1]. "</option>\n";
                        echo 'myMsgTxt['.$mysaved[0].']=\''.str_replace("\r\n","\\r\\n' + \r\n'", addslashes($mysaved[2]))."';\n";
                        echo 'mySubjectTxt['.$mysaved[0].']=\''.str_replace("\r\n","\\r\\n' + \r\n'", addslashes($mysaved[1]))."';\n";
                    }

                    ?>

                    function setMessage(msgid)
                    {
                        var myMsg=myMsgTxt[msgid];
                        var mySubject=mySubjectTxt[msgid];

                        if (myMsg == '')
                        {
                            if (document.form1.mode[1].checked)
                            {
                                document.getElementById('message').value = '';
                                document.getElementById('subject').value = '';
                            }
                            return true;
                        }
                        if (document.getElementById)
                        {
                            if (document.getElementById('moderep').checked)
                            {
                                document.getElementById('HeskMsg').innerHTML='<textarea style="height: inherit" class="form-control" name="message" id="message" rows="12" cols="60">'+myMsg+'</textarea>';
                                document.getElementById('HeskSub').innerHTML='<input class="form-control" type="text" name="subject" id="subject" maxlength="70" value="'+mySubject+'">';
                            }
                            else
                            {
                                var oldMsg = document.getElementById('message').value;
                                document.getElementById('HeskMsg').innerHTML='<textarea style="height: inherit" class="form-control" name="message" id="message" rows="12" cols="60">'+oldMsg+myMsg+'</textarea>';
                                if (document.getElementById('subject').value == '')
                                {
                                    document.getElementById('HeskSub').innerHTML='<input class="form-control" type="text" name="subject" id="subject" maxlength="70" value="'+mySubject+'">';
                                }
                            }
                        }
                        else
                        {
                            if (document.form1.mode[0].checked)
                            {
                                document.form1.message.value=myMsg;
                                document.form1.subject.value=mySubject;
                            }
                            else
                            {
                                var oldMsg = document.form1.message.value;
                                document.form1.message.value=oldMsg+myMsg;
                                if (document.form1.subject.value == '')
                                {
                                    document.form1.subject.value=mySubject;
                                }
                            }
                        }

                    }
                    //-->
                </script>
                <?php
            } // END fetchrows

            // Print templates
            if ( strlen($can_options) )
            {
                ?>
                <div class="form-group">
                    <label>
                        <?php echo $hesklang['ticket_tpl']; ?>
                        <?php echo hesk_checkPermission('can_man_ticket_tpl', 0) ? '(<a class="link" href="manage_ticket_templates.php">' . $hesklang['ticket_tpl_man'] . '</a>)' : ''; ?>
                    </label>
                    <div class="radio-list">
                        <div class="radio-custom" style="margin-bottom: 5px">
                            <input type="radio" name="mode" id="modeadd" value="1" checked="checked">
                            <label for="modeadd"><?php echo $hesklang['madd']; ?></label>
                        </div>
                        <div class="radio-custom" style="margin-bottom: 5px">
                            <input type="radio" name="mode" id="moderep" value="0">
                            <label for="moderep"><?php echo $hesklang['mrep']; ?></label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo $hesklang['select_ticket_tpl']; ?>:</label>
                    <div class="dropdown-select center out-close">
                        <select name="saved_replies" onchange="setMessage(this.value)">
                            <option value="0"> - <?php echo $hesklang['select_empty']; ?> - </option>
                            <?php echo $can_options; ?>
                        </select>
                    </div>
                </div>
                <?php
            } // END printing templates
            elseif ( hesk_checkPermission('can_man_ticket_tpl', 0) )
            {
                ?>
                <div class="form-group">
                    <label><a href="manage_ticket_templates.php" class="link"><?php echo $hesklang['ticket_tpl_man']; ?></a></label>
                </div>
                <?php
            }
            ?>
            <div class="form-group">
                <label><?php echo $hesklang['subject'] . ': ' . ($hesk_settings['require_subject']==1 ? '<span class="important">*</span>' : '') ; ?></label>
                <span id="HeskSub"><input class="form-control <?php if (in_array('subject',$_SESSION['iserror'])) {echo 'isError';} ?>" type="text" name="subject" id="subject" maxlength="70" value="<?php if (isset($_SESSION['as_subject'])) {echo stripslashes(hesk_input($_SESSION['as_subject']));} ?>"></span>
            </div>
            <div class="form-group">
                <label><?php echo $hesklang['message'] . ': ' . ($hesk_settings['require_message']==1 ? '<span class="important">*</span>' : '') ; ?></label>
                <span id="HeskMsg">
                    <textarea style="height: inherit" class="form-control <?php if (in_array('message',$_SESSION['iserror'])) {echo 'isError';} ?>"
                              name="message" id="message" rows="12" cols="60"><?php if (isset($_SESSION['as_message'])) {echo stripslashes(hesk_input($_SESSION['as_message']));} ?></textarea>
                </span>
            </div>

            <!-- START CUSTOM AFTER -->
            <?php
            /* custom fields AFTER comments */

            foreach ($hesk_settings['custom_fields'] as $k=>$v)
            {
                if ($v['use'] && $v['place']==1 && hesk_is_custom_field_in_category($k, $category) )
                {
                    $v['req'] = $v['req']==2 ? '<span class="important">*</span>' : '';

                    if ($v['type'] == 'checkbox')
                    {
                        $k_value = array();
                        if (isset($_SESSION["as_$k"]) && is_array($_SESSION["as_$k"]))
                        {
                            foreach ($_SESSION["as_$k"] as $myCB)
                            {
                                $k_value[] = stripslashes(hesk_input($myCB));
                            }
                        }
                    }
                    elseif (isset($_SESSION["as_$k"]))
                    {
                        $k_value  = stripslashes(hesk_input($_SESSION["as_$k"]));
                    }
                    else
                    {
                        $k_value  = '';
                    }

                    switch ($v['type'])
                    {
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
                            if (strlen($k_value) != 0 || isset($_SESSION["as_$k"]))
                            {
                                $v['value']['default_value'] = $k_value;
                            }

                            $cls = in_array($k,$_SESSION['iserror']) ? 'isError' : '';

                            echo '
                                <div class="form-group">
                                    <label>'.$v['name:'].' '.$v['req'].'</label>
                                    <input class="form-control '.$cls.'" type="text" name="'.$k.'" size="40" maxlength="'.intval($v['value']['max_length']).'" value="'.$v['value']['default_value'].'">
                                </div>';
                    }
                }
            }
            ?>
            <!-- END CUSTOM AFTER -->

            <?php
            /* attachments */
            if ($hesk_settings['attachments']['use']) {

                ?>
                <div class="block--attach">
                    <svg class="icon icon-attach">
                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-attach"></use>
                    </svg>
                    <div>
                        <?php echo $hesklang['attachments']; ?>:
                    </div>
                </div>
                <?php
                for ($i=1;$i<=$hesk_settings['attachments']['max_number'];$i++)
                {
                    $cls = ($i == 1 && in_array('attachments',$_SESSION['iserror'])) ? ' class="isError" ' : '';
                    echo '<input type="file" name="attachment['.$i.']" size="50" '.$cls.' /><br />';
                }
                ?>
                <a class="link" href="javascript:" onclick="hesk_window('../file_limits.php',250,500);return false;"><?php echo $hesklang['ful']; ?></a>
                <?php
            }

            // Admin options
            if ( ! isset($_SESSION['as_notify']) )
            {
                $_SESSION['as_notify'] = $_SESSION['notify_customer_new'] ? 1 : 0;
            }
            ?>
            <div class="form-group" style="margin-top: 20px">
                <label><?php echo $hesklang['addop']; ?>:</label>
                <div class="checkbox-list">
                    <div class="checkbox-custom">
                        <input type="checkbox" id="create_notify1" name="notify" value="1" <?php echo empty($_SESSION['as_notify']) ? '' : 'checked'; ?>>
                        <label for="create_notify1"><?php echo $hesklang['seno']; ?></label>
                    </div>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="create_show1" name="show" value="1" <?php echo (!isset($_SESSION['as_show']) || !empty($_SESSION['as_show'])) ? 'checked' : ''; ?>>
                        <label for="create_show1"><?php echo $hesklang['otas']; ?></label>
                    </div>
                </div>
            </div>
            <?php if (hesk_checkPermission('can_assign_others',0)) { ?>
                <div class="form-group">
                    <label><?php echo $hesklang['asst2']; ?>:</label>
                        <select name="owner" id="owner-select" <?php if (in_array('owner',$_SESSION['iserror'])) {echo ' class="isError" ';} ?>>
                            <option value="-1"> &gt; <?php echo $hesklang['unas']; ?> &lt; </option>
                            <?php

                            if ($hesk_settings['autoassign'])
                            {
                                echo '<option value="-2"> &gt; ' . $hesklang['aass'] . ' &lt; </option>';
                            }

                            $owner = isset($_SESSION['as_owner']) ? intval($_SESSION['as_owner']) : 0;

                            foreach ($admins as $k=>$v)
                            {
                                if ($k == $owner)
                                {
                                    echo '<option value="'.$k.'" selected="selected">'.$v.'</option>';
                                }
                                else
                                {
                                    echo '<option value="'.$k.'">'.$v.'</option>';
                                }

                            }
                            ?>
                        </select>
                        <script>
                            $('#owner-select').selectize();
                        </script>
                </div>
                <?php
            }
            elseif (hesk_checkPermission('can_assign_self',0))
            {
                $checked = (!isset($_SESSION['as_owner']) || !empty($_SESSION['as_owner'])) ? 'checked' : '';
                ?>
                <div class="form-group">
                    <label><?php echo $hesklang['owner']; ?></label>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="create_assing_to_self1" name="assing_to_self" value="1" <?php echo $checked; ?>>
                        <label for="create_assing_to_self1"><?php echo $hesklang['asss2']; ?></label>
                    </div>
                </div>
                <?php
            }
            ?>
            <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
            <input type="hidden" name="category" value="<?php echo $category; ?>">
            <button type="submit" class="btn btn-full"><?php echo $hesklang['sub_ticket']; ?></button>
        </form>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
    </div>
</div>
<?php

hesk_cleanSessionVars('iserror');
hesk_cleanSessionVars('isnotice');

require_once(HESK_PATH . 'inc/footer.inc.php');
exit();


/*** START FUNCTIONS ***/


function print_select_category($number_of_categories)
{
	global $hesk_settings, $hesklang;

	// A categoy needs to be selected
	if (isset($_GET['category']) && empty($_GET['category']))
	{
		hesk_process_messages($hesklang['sel_app_cat'],'NOREDIRECT','NOTICE');
	}

/* This will handle error, success and notice messages */
hesk_handle_messages();
?>
<div class="main__content categories">
    <div class="table-wrap">
        <h3><?php echo $hesklang['select_category_staff']; ?></h3>
        <div class="select_category">
            <?php
            // Print a select box if number of categories is large
            if ($number_of_categories > $hesk_settings['cat_show_select']) {
                ?>
                <form action="new_ticket.php" method="get" class="form">
                    <select class="form-control" name="category" id="select_category">
                        <?php
                        if ($hesk_settings['select_cat'])
                        {
                            echo '<option value="">'.$hesklang['select'].'</option>';
                        }
                        foreach ($hesk_settings['categories'] as $k=>$v)
                        {
                            echo '<option value="'.$k.'">'.$v.'</option>';
                        }
                        ?>
                    </select>
                    <button style="margin-top: 10px" type="submit" class="btn btn-full"><?php echo $hesklang['c2c']; ?></button>
                </form>
                <script>
                    $(document).ready(function() {
                        $('#select_category').selectize();
                    });
                </script>
                <?php
            }
            // Otherwise print quick links
            else
            {
                ?>
                <ul id="ul_category">
                    <?php
                    foreach ($hesk_settings['categories'] as $k=>$v)
                    {
                        echo '<li><a ripple="ripple" href="new_ticket.php?a=add&amp;category='.$k.'">'.$v.'</a></li>';
                    }
                    ?>
                </ul>
                <?php
            }
            ?>
        </div>
    </div>
</div>
<style>
    #ul_category {
        list-style-type: none;
        margin: 0;
        padding: 0;
        margin-top: 10px;
    }

    #ul_category li:first-child {
        border-top: 1px solid #d1d5d7;
    }

    #ul_category li {
        border: 1px solid #d1d5d7;
        border-top: none;
        border-radius: 2px;
    }

    #ul_category li:hover {
        background: rgba(0,0,0,.05);
    }

    #ul_category li a {
        display: block;
        font-size: 14px;
        padding: 0.75em 0.75em;
        text-decoration: none;
        transition: all 0.12s ease;
        word-wrap: break-word;
    }
</style>

<?php

	hesk_cleanSessionVars('iserror');
	hesk_cleanSessionVars('isnotice');

	require_once(HESK_PATH . 'inc/footer.inc.php');
	exit();
} // END print_select_category()
