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

if ( ! isset($status) )
{
	$status = $hesk_settings['statuses'];
    unset($status[3]);
}

if ( ! isset($priority) )
{
	$priority = array(
	0 => 'CRITICAL',
	1 => 'HIGH',
	2 => 'MEDIUM',
	3 => 'LOW',
	);
}

if ( ! isset($what) )
{
	$what = 'trackid';
}

if ( ! isset($owner_input) )
{
	$owner_input = 0;
}

if ( ! isset($date_input) )
{
	$date_input = '';
}

/* Can view tickets that are unassigned or assigned to others? */
$can_view_ass_others = hesk_checkPermission('can_view_ass_others',0);
$can_view_unassigned = hesk_checkPermission('can_view_unassigned',0);
$can_view_ass_by     = hesk_checkPermission('can_view_ass_by', 0);

/* Category options */
$category_options = '';
if ( isset($hesk_settings['categories']) && count($hesk_settings['categories']) )
{
	foreach ($hesk_settings['categories'] as $row['id'] => $row['name'])
	{
		$row['name'] = (hesk_mb_strlen($row['name']) > 30) ? hesk_mb_substr($row['name'],0,30) . '...' : $row['name'];
		$selected = ($row['id'] == $category) ? 'selected="selected"' : '';
		$category_options .= '<option value="'.$row['id'].'" '.$selected.'>'.$row['name'].'</option>';
	}
}
else
{
	$res2 = hesk_dbQuery('SELECT `id`, `name` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'categories` WHERE ' . hesk_myCategories('id') . ' ORDER BY `cat_order` ASC');
	while ($row=hesk_dbFetchAssoc($res2))
	{
		$row['name'] = (hesk_mb_strlen($row['name']) > 30) ? hesk_mb_substr($row['name'],0,30) . '...' : $row['name'];
		$selected = ($row['id'] == $category) ? 'selected="selected"' : '';
		$category_options .= '<option value="'.$row['id'].'" '.$selected.'>'.$row['name'].'</option>';
	}
}

/* List of staff */
if (($can_view_ass_others || $can_view_ass_by) && ! isset($admins))
{
	$admins = array();
	$res2 = hesk_dbQuery("SELECT `id`,`name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ORDER BY `name` ASC");
	while ($row=hesk_dbFetchAssoc($res2))
	{
		$admins[$row['id']]=$row['name'];
	}
}

$more = empty($_GET['more']) ? 0 : 1;
$more2 = empty($_GET['more2']) ? 0 : 1;

#echo "SQL: $sql";
?>

<!-- ** START SHOW TICKET FORM ** -->
<h2 style="font-size: 18px; font-weight: bold"><?php echo $hesklang['show_tickets']; ?></h2>
<div class="table-wrap">
    <form name="showt" action="show_tickets.php" method="get" class="show_tickets form">
        <div class="search-option">
            <div class="search-name">
                <?php echo $hesklang['status']; ?>
            </div>
            <div class="search-options">
                <div class="checkbox-list">
                <?php
                hesk_get_status_checkboxes($status);
                ?>
                </div>
            </div>
        </div>
        <div id="topSubmit" style="display:<?php echo $more ? 'none' : 'block' ; ?>">
            <div style="display: flex">
                <button type="submit" class="btn btn-full"><?php echo $hesklang['show_tickets']; ?></button>
                <a class="btn btn--blue-border" href="javascript:void(0)" onclick="Javascript:hesk_toggleLayerDisplay('divShow');Javascript:hesk_toggleLayerDisplay('topSubmit');document.showt.more.value='1';"><?php echo $hesklang['mopt']; ?></a>
            </div>
        </div>
        <div id="divShow" style="display:<?php echo $more ? 'block' : 'none' ; ?>">
            <div class="search-option">
                <div class="search-name">
                    <?php echo $hesklang['priority']; ?>
                </div>
                <div class="search-options">
                    <div class="checkbox-list">
                        <div class="checkbox-custom">
                            <input type="checkbox" id="p0" name="p0" value="1" <?php if (isset($priority[0])) {echo 'checked';} ?>>
                            <label for="p0"><span class="critical"><?php echo $hesklang['critical']; ?></span></label>
                        </div>
                        <div class="checkbox-custom">
                            <input type="checkbox" id="p2" name="p2" value="1" <?php if (isset($priority[2])) {echo 'checked';} ?>>
                            <label for="p2"><span class="medium"><?php echo $hesklang['medium']; ?></span></label>
                        </div>
                        <div class="checkbox-custom">
                            <input type="checkbox" id="p1" name="p1" value="1" <?php if (isset($priority[1])) {echo 'checked';} ?>>
                            <label for="p1"><span class="important"><?php echo $hesklang['high']; ?></span></label>
                        </div>
                        <div class="checkbox-custom">
                            <input type="checkbox" id="p3" name="p3" value="1" <?php if (isset($priority[3])) {echo 'checked';} ?>>
                            <label for="p3"><span class="normal"><?php echo $hesklang['low']; ?></span></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="search-option">
                <div class="search-name">
                    <?php echo $hesklang['show']; ?>
                </div>
                <div class="search-options">
                    <div class="checkbox-list">
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
                        ?>
                        <?php
                        if ($can_view_ass_others || $can_view_ass_by)
                        {
                            ?>
                            <div class="checkbox-custom">
                                <input type="checkbox" name="s_ot" id="s_ot" value="1" <?php if ($s_ot[1]) echo 'checked'; ?>>
                                <label for="s_ot"><?php echo $hesklang['s_ot']; ?></label>
                            </div>
                            <?php
                        }
                        ?>
                        <div class="checkbox-custom">
                            <input type="checkbox" name="archive" id="s_archive" value="1" <?php if ($archive[1]) echo 'checked'; ?>>
                            <label for="s_archive"><?php echo $hesklang['disp_only_archived']; ?></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="search-option">
                <div class="search-name">
                    <?php echo $hesklang['sort_by']; ?>
                </div>
                <div class="search-options">
                    <div class="radio-list">
                        <?php
                        array_unshift($hesk_settings['ticket_list'], 'priority');
                        $hesk_settings['possible_ticket_list']['priority'] = $hesklang['priority'];

                        foreach ($hesk_settings['ticket_list'] as $key): ?>
                            <div class="radio-custom">
                                <input type="radio" id="sort<?php echo $key; ?>" name="sort" value="<?php echo $key; ?>"
                                    <?php if ($sort == $key) { echo 'checked'; } ?>>
                                <label for="sort<?php echo $key; ?>">
                                    <?php echo $hesk_settings['possible_ticket_list'][$key]; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="search-option">
                <div class="search-name">
                    <?php echo $hesklang['gb']; ?>
                </div>
                <div class="search-options">
                    <div class="radio-list">
                        <div class="radio-custom">
                            <input type="radio" name="g" value="" id="g_" <?php if (!$group) {echo 'checked';} ?>>
                            <label for="g_">
                                <?php echo $hesklang['dg']; ?>
                            </label>
                        </div>
                        <?php
                        if ($can_view_unassigned || $can_view_ass_others || $can_view_ass_by)
                        {
                            ?>
                            <div class="radio-custom">
                                <input type="radio" name="g" value="owner" id="g_owner" <?php if ($group == 'owner') {echo 'checked';} ?>>
                                <label for="g_owner">
                                    <?php echo $hesklang['owner']; ?>
                                </label>
                            </div>
                            <?php
                        }
                        ?>
                        <div class="radio-custom">
                            <input type="radio" name="g" value="category" id="g_category" <?php if ($group == 'category') {echo 'checked';} ?>>
                            <label for="g_category">
                                <?php echo $hesklang['category']; ?>
                            </label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" name="g" value="priority" id="g_priority" <?php if ($group == 'priority') {echo 'checked';} ?>>
                            <label for="g_priority">
                                <?php echo $hesklang['priority']; ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="search-option">
                <div class="search-name">
                    <?php echo $hesklang['category']; ?>
                </div>
                <div class="search-options">
                    <select name="category">
                        <option value="0" ><?php echo $hesklang['any_cat']; ?></option>
                        <?php echo $category_options; ?>
                    </select>
                </div>
            </div>
            <div class="search-option">
                <div class="search-name">
                    <?php echo $hesklang['display']; ?>
                </div>
                <div class="search-options">
                    <input class="form-control" type="text" name="limit" value="<?php echo $maxresults; ?>" style="width: 25%">
                    <?php echo $hesklang['tickets_page']; ?>
                </div>
            </div>
            <div class="search-option">
                <div class="search-name">
                    <?php echo $hesklang['order']; ?>
                </div>
                <div class="search-options">
                    <div class="radio-list">
                        <div class="radio-custom">
                            <input type="radio" name="asc" value="1" id="g_asc1" <?php if ($asc) {echo 'checked';} ?>>
                            <label for="g_asc1">
                                <?php echo $hesklang['ascending']; ?>
                            </label>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" name="asc" value="0" id="g_asc0" <?php if (!$asc) {echo 'checked';} ?>>
                            <label for="g_asc0">
                                <?php echo $hesklang['descending']; ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="search-option">
                <div class="search-name">
                    <?php echo $hesklang['opt']; ?>
                </div>
                <div class="search-options">
                    <div class="checkbox-list">
                        <div class="checkbox-custom">
                            <input type="checkbox" name="cot" id="g_cot" value="1" <?php if ($cot) {echo 'checked';} ?>>
                            <label for="g_cot"><?php echo $hesklang['cot']; ?></label>
                        </div>
                        <div class="checkbox-custom">
                            <input type="checkbox" name="def" value="1" id="g_def">
                            <label for="g_def"><?php echo $hesklang['def']; ?></label>&nbsp;
                            (<a href="admin_main.php?reset=1&amp;token=<?php echo hesk_token_echo(0); ?>"><?php echo $hesklang['redv']; ?></a>)
                        </div>
                    </div>
                </div>
            </div>
            <div id="bottomSubmit">
                <div style="display: flex">
                    <button type="submit" id="bottom-showtickets" class="btn btn-full"><?php echo $hesklang['show_tickets']; ?></button>
                    <a class="btn btn--blue-border" href="javascript:void(0)" onclick="hesk_toggleLayerDisplay('divShow');hesk_toggleLayerDisplay('topSubmit');document.showt.more.value='0';"><?php echo $hesklang['lopt']; ?></a>
                    <input type="hidden" name="more" value="<?php echo $more ? 1 : 0 ; ?>">
                </div>
            </div>
        </div>
    </form>
</div>

<!-- ** END SHOW TICKET FORM ** -->

<!-- ** START SEARCH TICKETS FORM ** -->
<h2 style="margin-top: 20px; font-size: 18px; font-weight: bold"><?php echo $hesklang['find_ticket_by']; ?></h2>
<div class="table-wrap">
    <form action="find_tickets.php" method="get" name="findby" id="findby" class="show_tickets form">
        <div class="search-option">
            <div class="search-name">
                <?php echo $hesklang['s_for']; ?>
            </div>
            <div class="search-options">
                <input class="form-control" type="text" name="q" <?php if (isset($q)) {echo 'value="'.$q.'"';} ?>>
            </div>
        </div>
        <div class="search-option">
            <div class="search-name">
                <?php echo $hesklang['s_in']; ?>
            </div>
            <div class="search-options">
                <select name="what">
                    <option value="trackid" <?php if ($what=='trackid') {echo 'selected="selected"';} ?> ><?php echo $hesklang['trackID']; ?></option>
                    <?php
                    if ($hesk_settings['sequential'])
                    {
                        ?>
                        <option value="seqid" <?php if ($what=='seqid') {echo 'selected="selected"';} ?> ><?php echo $hesklang['seqid']; ?></option>
                        <?php
                    }
                    ?>
                    <option value="name"    <?php if ($what=='name') {echo 'selected="selected"';} ?> ><?php echo $hesklang['name']; ?></option>
                    <option value="email"	<?php if ($what=='email') {echo 'selected="selected"';} ?> ><?php echo $hesklang['email']; ?></option>
                    <option value="subject" <?php if ($what=='subject') {echo 'selected="selected"';} ?> ><?php echo $hesklang['subject']; ?></option>
                    <option value="message" <?php if ($what=='message') {echo 'selected="selected"';} ?> ><?php echo $hesklang['message']; ?></option>
                    <?php
                    foreach ($hesk_settings['custom_fields'] as $k=>$v)
                    {
                        $selected = ($what == $k) ? 'selected="selected"' : '';
                        if ($v['use'])
                        {
                            $v['name'] = (hesk_mb_strlen($v['name']) > 30) ? hesk_mb_substr($v['name'],0,30) . '...' : $v['name'];
                            echo '<option value="'.$k.'" '.$selected.'>'.$v['name'].'</option>';
                        }
                    }
                    ?>
                    <option value="notes" <?php if ($what=='notes') {echo 'selected="selected"';} ?> ><?php echo $hesklang['notes']; ?></option>
                    <option value="ip" <?php if ($what=='ip') {echo 'selected="selected"';} ?> ><?php echo $hesklang['IP_addr']; ?></option>
                </select>
            </div>
        </div>
        <div id="topSubmit2" style="display:<?php echo $more2 ? 'none' : 'block' ; ?>">
            <div style="display: flex">
                <button type="submit" class="btn btn-full" id="findticket"><?php echo $hesklang['find_ticket']; ?></button>
                <a class="btn btn--blue-border" id="moreoptions2" href="javascript:void(0)" onclick="hesk_toggleLayerDisplay('divShow2');hesk_toggleLayerDisplay('topSubmit2');document.findby.more2.value='1';"><?php echo $hesklang['mopt']; ?></a>
            </div>
        </div>
        <div id="divShow2" style="display:<?php echo $more2 ? 'block' : 'none' ; ?>">
            <div class="search-option">
                <div class="search-name">
                    <?php echo $hesklang['category']; ?>
                </div>
                <div class="search-options">
                    <select id="categoryfind" name="category">
                        <option value="0" ><?php echo $hesklang['any_cat']; ?></option>
                        <?php echo $category_options; ?>
                    </select>
                </div>
            </div>
            <?php
            if ($can_view_ass_others || $can_view_ass_by)
            {
                ?>
                <div class="search-option">
                    <div class="search-name">
                        <?php echo $hesklang['owner']; ?>
                    </div>
                    <div class="search-options">
                        <select id="ownerfind" name="owner">
                            <option value="0" ><?php echo $hesklang['anyown']; ?></option>
                            <?php
                            foreach ($admins as $staff_id => $staff_name)
                            {
                                echo '<option value="'.$staff_id.'" '.($owner_input == $staff_id ? 'selected="selected"' : '').'>'.$staff_name.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <?php
            }
            ?>
            <div class="search-option">
                <div class="search-name">
                    <?php echo $hesklang['date']; ?>
                </div>
                <div class="search-options">
                    <section class="param calendar">
                        <div class="calendar--button">
                            <button type="button">
                                <svg class="icon icon-calendar">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-calendar"></use>
                                </svg>
                            </button>
                            <input name="dt"
                                <?php if ($date_input) {echo 'value="'.$date_input.'"';} ?>
                                   type="text" class="datepicker">
                        </div>
                        <div class="calendar--value" <?php echo ($date_input ? 'style="display: block"' : ''); ?>>
                            <span><?php echo $date_input; ?></span>
                            <i class="close">
                                <svg class="icon icon-close">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                                </svg>
                            </i>
                    </div>
                    </section>
                </div>
            </div>
            <div class="search-option">
                <div class="search-name">
                    <?php echo $hesklang['s_incl']; ?>
                </div>
                <div class="search-options">
                    <div class="checkbox-list">
                        <div class="checkbox-custom">
                            <input type="checkbox" id="find_s_my" name="s_my" value="1" <?php if ($s_my[2]) echo 'checked'; ?>>
                            <label for="find_s_my"><?php echo $hesklang['s_my']; ?></label>
                        </div>
                        <?php
                        if ($can_view_ass_others || $can_view_ass_by)
                        {
                            ?>
                            <div class="checkbox-custom">
                                <input type="checkbox" id="find_s_ot" name="s_ot" value="1" <?php if ($s_ot[2]) echo 'checked'; ?>>
                                <label for="find_s_ot"><?php echo $hesklang['s_ot']; ?></label>
                            </div>
                            <?php
                        }

                        if ($can_view_unassigned)
                        {
                            ?>
                            <div class="checkbox-custom">
                                <input type="checkbox" id="find_s_un" name="s_un" value="1" <?php if ($s_un[2]) echo 'checked'; ?>>
                                <label for="find_s_un"><?php echo $hesklang['s_un']; ?></label>
                            </div>
                            <?php
                        }
                        ?>
                        <div class="checkbox-custom">
                            <input type="checkbox" id="find_archive" name="archive" value="1" <?php if ($archive[2]) echo 'checked'; ?>>
                            <label for="find_archive"><?php echo $hesklang['disp_only_archived']; ?></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="search-option">
                <div class="search-name">
                    <?php echo $hesklang['display']; ?>
                </div>
                <div class="search-options">
                    <input type="text" name="limit" value="<?php echo $maxresults; ?>" class="form-control" style="width: 25%">
                    <?php echo $hesklang['results_page']; ?>
                </div>
            </div>
            <div id="bottomSubmit">
                <div style="display: flex">
                    <button type="submit" id="findticket2" class="btn btn-full"><?php echo $hesklang['find_ticket']; ?></button>
                    <a class="btn btn--blue-border" href="javascript:void(0)" onclick="hesk_toggleLayerDisplay('divShow2');hesk_toggleLayerDisplay('topSubmit2');document.findby.more2.value='0';"><?php echo $hesklang['lopt']; ?></a>
                    <input type="hidden" name="more2" value="<?php echo $more2 ? 1 : 0 ; ?>">
                </div>
            </div>
        </div>
    </form>
</div>

<!-- ** END SEARCH TICKETS FORM ** -->

