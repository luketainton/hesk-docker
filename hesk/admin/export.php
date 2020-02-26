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
require(HESK_PATH . 'inc/reporting_functions.inc.php');
hesk_load_database_functions();

hesk_session_start();
hesk_dbConnect();
hesk_isLoggedIn();

// Check permissions for this feature
hesk_checkPermission('can_export');

// Just a delete file action?
$delete = hesk_GET('delete');
if (strlen($delete) && preg_match('/^hesk_export_[0-9_\-]+$/', $delete))
{
    hesk_unlink(HESK_PATH.$hesk_settings['cache_dir'].'/'.$delete.'.zip');
    hesk_process_messages($hesklang['fd'], hesk_verifyGoto(), 'SUCCESS');
}

// Load custom fields
require_once(HESK_PATH . 'inc/custom_fields.inc.php');

// Load statuses
require_once(HESK_PATH . 'inc/statuses.inc.php');

// Set default values
define('CALENDAR',1);
define('MAIN_PAGE',1);
define('LOAD_TABS',1);

$selected = array(
	'w'    => array(0=>'',1=>''),
	'time' => array(1=>'',2=>'',3=>'',4=>'',5=>'',6=>'',7=>'',8=>'',9=>'',10=>'',11=>'',12=>''),
);
$is_all_time = 0;

// Default this month to date
$date_from = date('Y-m-d',mktime(0, 0, 0, date("m"), 1, date("Y")));
$date_to = date('Y-m-d');
$input_datefrom = date('m/d/Y', strtotime('last month'));
$input_dateto = date('m/d/Y');

/* Date */
if (!empty($_GET['w']))
{
	$df = preg_replace('/[^0-9]/','', hesk_GET('datefrom') );
    if (strlen($df) == 8)
    {
    	$date_from = substr($df,4,4) . '-' . substr($df,0,2) . '-' . substr($df,2,2);
        $input_datefrom = substr($df,0,2) . '/' . substr($df,2,2) . '/' . substr($df,4,4);
    }
    else
    {
    	$date_from = date('Y-m-d', strtotime('last month') );
    }

	$dt = preg_replace('/[^0-9]/','', hesk_GET('dateto') );
    if (strlen($dt) == 8)
    {
    	$date_to = substr($dt,4,4) . '-' . substr($dt,0,2) . '-' . substr($dt,2,2);
        $input_dateto = substr($dt,0,2) . '/' . substr($dt,2,2) . '/' . substr($dt,4,4);
    }
    else
    {
    	$date_to = date('Y-m-d');
    }

    if ($date_from > $date_to)
    {
        $tmp = $date_from;
        $tmp2 = $input_datefrom;

        $date_from = $date_to;
        $input_datefrom = $input_dateto;

        $date_to = $tmp;
        $input_dateto = $tmp2;

        $note_buffer = $hesklang['datetofrom'];
    }

    if ($date_to > date('Y-m-d'))
    {
    	$date_to = date('Y-m-d');
        $input_dateto = date('m/d/Y');
    }

	$selected['w'][1]='checked="checked"';
    $selected['time'][3]='selected="selected"';
}
else
{
	$selected['w'][0]='checked="checked"';
	$_GET['time'] = intval( hesk_GET('time', 3) );

    switch ($_GET['time'])
    {
    	case 1:
			/* Today */
			$date_from = date('Y-m-d');
			$date_to = $date_from;
			$selected['time'][1]='selected="selected"';
            $is_all_time = 1;
        break;

    	case 2:
			/* Yesterday */
			$date_from = date('Y-m-d',mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
			$date_to = $date_from;
			$selected['time'][2]='selected="selected"';
            $is_all_time = 1;
        break;

    	case 4:
			/* Last month */
			$date_from = date('Y-m-d',mktime(0, 0, 0, date("m")-1, 1, date("Y")));
			$date_to = date('Y-m-d',mktime(0, 0, 0, date("m"), 0, date("Y")));
			$selected['time'][4]='selected="selected"';
        break;

    	case 5:
			/* Last 30 days */
			$date_from = date('Y-m-d',mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			$date_to = date('Y-m-d');
			$selected['time'][5]='selected="selected"';
        break;

    	case 6:
			/* This week */
			list($date_from,$date_to)=dateweek(0);
            $date_to = date('Y-m-d');
			$selected['time'][6]='selected="selected"';
        break;

    	case 7:
			/* Last week */
			list($date_from,$date_to)=dateweek(-1);
			$selected['time'][7]='selected="selected"';
        break;

    	case 8:
			/* This business week */
			list($date_from,$date_to)=dateweek(0,1);
            $date_to = date('Y-m-d');
			$selected['time'][8]='selected="selected"';
        break;

    	case 9:
			/* Last business week */
			list($date_from,$date_to)=dateweek(-1,1);
			$selected['time'][9]='selected="selected"';
        break;

    	case 10:
			/* This year */
			$date_from = date('Y').'-01-01';
			$date_to = date('Y-m-d');
			$selected['time'][10]='selected="selected"';
        break;

    	case 11:
			/* Last year */
			$date_from = date('Y')-1 . '-01-01';
			$date_to = date('Y')-1 . '-12-31';
			$selected['time'][11]='selected="selected"';
        break;

    	case 12:
			/* All time */
			$date_from = hesk_getOldestDate();
			$date_to = date('Y-m-d');
			$selected['time'][12]='selected="selected"';
            $is_all_time = 1;
        break;

        default:
        	$_GET['time'] = 3;
			$selected['time'][3]='selected="selected"';
    }

}

unset($tmp);

// Start SQL statement for selecting tickets
$sql = "SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE ";

// Some default settings
$archive = array(1=>0,2=>0);
$s_my = array(1=>1,2=>1);
$s_ot = array(1=>1,2=>1);
$s_un = array(1=>1,2=>1);

// --> TICKET CATEGORY
$category = intval( hesk_GET('category', 0) );

// Make sure user has access to this category
if ($category && hesk_okCategory($category, 0) )
{
	$sql .= " `category`='{$category}' ";
}
// No category selected, show only allowed categories
else
{
	$sql .= hesk_myCategories();
}

// Show only tagged tickets?
if ( ! empty($_GET['archive']) )
{
	$archive[1]=1;
	$sql .= " AND `archive`='1' ";
}

// Ticket owner preferences
$fid = 1;
require(HESK_PATH . 'inc/assignment_search.inc.php');

// --> TICKET STATUS
$status = $hesk_settings['statuses'];

foreach ($status as $k => $v)
{
	if (empty($_GET['s'.$k]))
    {
    	unset($status[$k]);
    }
}

// How many statuses are we pulling out of the database?
$tmp = count($status);

// Do we need to search by status?
if ( $tmp < 6 )
{
	// If no statuses selected, show all
	if ($tmp == 0)
	{
		$status = $hesk_settings['statuses'];
	}
	else
	{
		// Add to the SQL
		$sql .= " AND `status` IN ('" . implode("','", array_keys($status) ) . "') ";
	}
}

// --> TICKET PRIORITY
$possible_priority = array(
0 => 'CRITICAL',
1 => 'HIGH',
2 => 'MEDIUM',
3 => 'LOW',
);

$priority = $possible_priority;

foreach ($priority as $k => $v)
{
	if (empty($_GET['p'.$k]))
    {
    	unset($priority[$k]);
    }
}

// How many priorities are we pulling out of the database?
$tmp = count($priority);

// Create the SQL based on the number of priorities we need
if ($tmp == 0 || $tmp == 4)
{
	// Nothing or all selected, no need to modify the SQL code
    $priority = $possible_priority;
}
else
{
	// A custom selection of priorities
	$sql .= " AND `priority` IN ('" . implode("','", array_keys($priority) ) . "') ";
}

// Prepare variables used in search and forms
require_once(HESK_PATH . 'inc/prepare_ticket_export.inc.php');

////////////////////////////////////////////////////////////////////////////////

// Can view tickets that are unassigned or assigned to others?
$can_view_ass_others = hesk_checkPermission('can_view_ass_others',0);
$can_view_unassigned = hesk_checkPermission('can_view_unassigned',0);

// Category options
$category_options = '';
$my_cat = array();
$res2 = hesk_dbQuery("SELECT `id`, `name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE " . hesk_myCategories('id') . " ORDER BY `cat_order` ASC");
while ($row=hesk_dbFetchAssoc($res2))
{
	$my_cat[$row['id']] = hesk_msgToPlain($row['name'], 1);
	$row['name'] = (hesk_mb_strlen($row['name']) > 50) ? hesk_mb_substr($row['name'],0,50) . '...' : $row['name'];
	$cat_selected = ($row['id'] == $category) ? 'selected="selected"' : '';
	$category_options .= '<option value="'.$row['id'].'" '.$cat_selected.'>'.$row['name'].'</option>';
}

// Generate export file
if (isset($_GET['w']))
{
    require_once(HESK_PATH . 'inc/export_functions.inc.php');
    list($success_msg, $tickets_exported) = hesk_export_to_XML($sql);
}

/* Print header */
require_once(HESK_PATH . 'inc/header.inc.php');

/* Print main manage users page */
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');

/* This will handle error, success and notice messages */
hesk_handle_messages();

// If an export was generated, show the link to download
if (isset($success_msg))
{
	if ($tickets_exported > 0)
	{
		hesk_show_success($success_msg);
	}
	else
	{
		hesk_show_notice($hesklang['n2ex']);
	}
}
?>
<div class="main__content reports">
    <h2>
        <?php echo $hesklang['export']; ?>
        <div class="tooltype right out-close">
            <svg class="icon icon-info">
                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
            </svg>
            <div class="tooltype__content">
                <div class="tooltype__wrapper">
                    <?php echo $hesklang['export_intro']; ?>
                </div>
            </div>
        </div>
    </h2>
    <form name="showt" action="export.php" method="get">
        <div class="reports__range pl0">
            <h4><?php echo $hesklang['dtrg']; ?></h4>
            <div class="reports__range_form form">
                <div class="radio-list">
                    <div class="radio-custom">
                        <input type="radio" name="w" value="0" id="w0" <?php echo $selected['w'][0]; ?>>
                        <label for="w0">&nbsp;</label>
                        <div class="dropdown-select center out-close">
                            <select name="time" onclick="document.getElementById('w0').checked = true" onchange="document.getElementById('w0').checked = true" style="margin-top:5px;margin-bottom:5px;">
                                <option value="1" <?php echo $selected['time'][1]; ?>><?php echo $hesklang['r1']; ?> (<?php echo $hesklang['d'.date('w')]; ?>)</option>
                                <option value="2" <?php echo $selected['time'][2]; ?>><?php echo $hesklang['r2']; ?> (<?php echo $hesklang['d'.date('w',mktime(0, 0, 0, date('m'), date('d')-1, date('Y')))]; ?>)</option>
                                <option value="3" <?php echo $selected['time'][3]; ?>><?php echo $hesklang['r3']; ?> (<?php echo $hesklang['m'.date('n')]; ?>)</option>
                                <option value="4" <?php echo $selected['time'][4]; ?>><?php echo $hesklang['r4']; ?> (<?php echo $hesklang['m'.date('n',mktime(0, 0, 0, date('m')-1, 1, date('Y')))]; ?>)</option>
                                <option value="5" <?php echo $selected['time'][5]; ?>><?php echo $hesklang['r5']; ?></option>
                                <option value="6" <?php echo $selected['time'][6]; ?>><?php echo $hesklang['r6']; ?></option>
                                <option value="7" <?php echo $selected['time'][7]; ?>><?php echo $hesklang['r7']; ?></option>
                                <option value="8" <?php echo $selected['time'][8]; ?>><?php echo $hesklang['r8']; ?></option>
                                <option value="9" <?php echo $selected['time'][9]; ?>><?php echo $hesklang['r9']; ?></option>
                                <option value="10" <?php echo $selected['time'][10]; ?>><?php echo $hesklang['r10']; ?> (<?php echo date('Y'); ?>)</option>
                                <option value="11" <?php echo $selected['time'][11]; ?>><?php echo $hesklang['r11']; ?> (<?php echo date('Y',mktime(0, 0, 0, date('m'), date('d'), date('Y')-1)); ?>)</option>
                                <option value="12" <?php echo $selected['time'][12]; ?>><?php echo $hesklang['r12']; ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="radio-custom">
                        <input type="radio" name="w" value="1" id="w1" <?php echo $selected['w'][1]; ?>>
                        <label for="w1">&nbsp;</label>
                        <?php echo $hesklang['from']; ?>
                        <section class="param calendar" style="margin-left: 10px; margin-right: 10px">
                            <div class="calendar--button">
                                <button type="button" onclick="document.getElementById('w1').checked = true">
                                    <svg class="icon icon-calendar">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-calendar"></use>
                                    </svg>
                                </button>
                                <input name="datefrom"
                                       id="datefrom"
                                    <?php if ($input_datefrom) {echo 'value="'.$input_datefrom.'"';} ?>
                                       type="text" class="datepicker">
                            </div>
                            <div class="calendar--value" <?php echo ($input_datefrom ? 'style="display: block"' : ''); ?>>
                                <span><?php echo $input_datefrom; ?></span>
                                <i class="close">
                                    <svg class="icon icon-close">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                                    </svg>
                                </i>
                            </div>
                        </section>
                        <?php echo $hesklang['to']; ?>
                        <section class="param calendar" style="margin-left: 10px;">
                            <div class="calendar--button">
                                <button type="button" onclick="document.getElementById('w1').checked = true">
                                    <svg class="icon icon-calendar">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-calendar"></use>
                                    </svg>
                                </button>
                                <input name="dateto"
                                       id="dateto"
                                    <?php if ($input_dateto) {echo 'value="'.$input_dateto.'"';} ?>
                                       type="text" class="datepicker">
                            </div>
                            <div class="calendar--value" <?php echo ($input_dateto ? 'style="display: block"' : ''); ?>>
                                <span><?php echo $input_dateto; ?></span>
                                <i class="close">
                                    <svg class="icon icon-close">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                                    </svg>
                                </i>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
        <section class="reports__checkbox">
            <h3><?php echo $hesklang['status']; ?></h3>
            <?php
            hesk_get_status_checkboxes($status);
            ?>
        </section>
        <section class="reports__checkbox">
            <h3><?php echo $hesklang['priority']; ?></h3>
            <div class="checkbox-custom">
                <input type="checkbox" name="p0" id="p0" value="1" <?php if (isset($priority[0])) {echo 'checked';} ?>>
                <label for="p0"><?php echo $hesklang['critical']; ?></label>
            </div>
            <div class="checkbox-custom">
                <input type="checkbox" name="p1" id="p1" value="1" <?php if (isset($priority[1])) {echo 'checked';} ?>>
                <label for="p1"><?php echo $hesklang['high']; ?></label>
            </div>
            <div class="checkbox-custom">
                <input type="checkbox" name="p2" id="p2" value="1" <?php if (isset($priority[2])) {echo 'checked';} ?>>
                <label for="p2"><?php echo $hesklang['medium']; ?></label>
            </div>
            <div class="checkbox-custom">
                <input type="checkbox" name="p3" id="p3" value="1" <?php if (isset($priority[3])) {echo 'checked';} ?>>
                <label for="p3"><?php echo $hesklang['low']; ?></label>
            </div>
        </section>
        <section class="reports__checkbox">
            <h3><?php echo $hesklang['assigned_to']; ?></h3>
            <div class="checkbox-custom">
                <input type="checkbox" name="s_my" id="s_my" value="1" <?php if ($s_my[1]) echo 'checked'; ?>>
                <label for="s_my"><?php echo $hesklang['s_my']; ?></label>
            </div>
            <?php
            if ($can_view_unassigned)
            {
                ?>
                <div class="checkbox-custom">
                    <input type="checkbox" name="s_un" id="s_un" value="1" <?php if ($s_un[1]) echo 'checked'; ?>>
                    <label for="s_un"><?php echo $hesklang['s_un']; ?></label>
                </div>
                <?php
            }

            if ($can_view_ass_others)
            {
                ?>
                <div class="checkbox-custom">
                    <input type="checkbox" name="s_ot" id="s_ot" value="1" <?php if ($s_ot[1]) echo 'checked'; ?>>
                    <label for="reportCheck14"><?php echo $hesklang['s_ot']; ?></label>
                </div>
                <?php
            }
            ?>
            <div class="checkbox-custom">
                <input type="checkbox" name="archive" id="archive" value="1" <?php if ($archive[1]) echo 'checked'; ?>>
                <label for="archive"><?php echo $hesklang['disp_only_archived']; ?></label>
            </div>
        </section>
        <section class="reports__checkbox">
            <h3><?php echo $hesklang['sort_by']; ?></h3>
            <div class="radio-list">
                <div class="radio-custom">
                    <input type="radio" name="sort" id="sort_priority" value="priority" <?php if ($sort == 'priority') {echo 'checked';} ?>>
                    <label for="sort_priority"><?php echo $hesklang['priority']; ?></label>
                </div>
                <div class="radio-custom">
                    <input type="radio" name="sort" id="sort_lastchange" value="lastchange" <?php if ($sort == 'lastchange') {echo 'checked';} ?>>
                    <label for="sort_lastchange"><?php echo $hesklang['last_update']; ?></label>
                </div>
                <div class="radio-custom">
                    <input type="radio" name="sort" id="sort_name" value="name" <?php if ($sort == 'name') {echo 'checked';} ?>>
                    <label for="sort_name"><?php echo $hesklang['name']; ?></label>
                </div>
                <div class="radio-custom">
                    <input type="radio" name="sort" id="sort_subject" value="subject" <?php if ($sort == 'subject') {echo 'checked';} ?>>
                    <label for="sort_subject"><?php echo $hesklang['subject']; ?></label>
                </div>
                <div class="radio-custom">
                    <input type="radio" name="sort" id="sort_status" value="status" <?php if ($sort == 'status') {echo 'checked';} ?>>
                    <label for="sort_status"><?php echo $hesklang['status']; ?></label>
                </div>
                <div class="radio-custom">
                    <input type="radio" name="sort" id="sort_id" value="id" <?php if ($sort == 'id') {echo 'checked';} ?>>
                    <label for="sort_id"><?php echo $hesklang['sequentially']; ?></label>
                </div>
            </div>
        </section>
        <section class="reports__checkbox">
            <h3><?php echo $hesklang['category']; ?></h3>
            <div class="dropdown-select center out-close">
                <select name="category">
                    <option value="0" ><?php echo $hesklang['any_cat']; ?></option>
                    <?php echo $category_options; ?>
                </select>
            </div>
        </section>
        <section class="reports__checkbox">
            <h3><?php echo $hesklang['order']; ?></h3>
            <div class="radio-list">
                <div class="radio-custom">
                    <input type="radio" name="asc" id="asc_1" value="1" <?php if ($asc) {echo 'checked';} ?>>
                    <label for="asc_1"><?php echo $hesklang['ascending']; ?></label>
                </div>
                <div class="radio-custom">
                    <input type="radio" name="asc" id="asc_0" value="0" <?php if (!$asc) {echo 'checked';} ?>>
                    <label for="asc_0"><?php echo $hesklang['descending']; ?></label>
                </div>
            </div>
        </section>
        <div class="reports__export">
            <input type="hidden" name="cot" value="1">
            <button class="btn btn-full" ripple="ripple" data-action="reports-export"><?php echo $hesklang['export_btn']; ?></button>
        </div>
    </form>
</div>

<?php
require_once(HESK_PATH . 'inc/footer.inc.php');
exit();
?>
