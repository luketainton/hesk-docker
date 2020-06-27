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

// Check if this is a valid include
if (!defined('IN_SCRIPT')) {die('Invalid attempt');}

// Users online
if (defined('SHOW_ONLINE'))
{
    hesk_printOnline();
}

/*******************************************************************************
The code below handles HESK licensing and must be included in the template.

Removing this code is a direct violation of the HESK End User License Agreement,
will void all support and may result in unexpected behavior.

To purchase a HESK license and support future HESK development please visit:
https://www.hesk.com/buy.php
*******************************************************************************/
$hesk_settings['hesk_license']('Qo8Zm9vdGVyIGNsYXNzPSJmb290ZXIiPg0KICAgIDxwIGNsY
XNzPSJ0ZXh0LWNlbnRlciI+UG93ZXJlZCBieSA8YSBocmVmPSJodHRwczovL3d3dy5oZXNrLmNvbSIgY
2xhc3M9ImxpbmsiPkhlbHAgRGVzayBTb2Z0d2FyZTwvYT4gPHNwYW4gY2xhc3M9ImZvbnQtd2VpZ2h0L
WJvbGQiPkhFU0s8L3NwYW4+LCBpbiBwYXJ0bmVyc2hpcCB3aXRoIDxhIGhyZWY9Imh0dHBzOi8vd3d3L
nN5c2FpZC5jb20vP3V0bV9zb3VyY2U9SGVzayZhbXA7dXRtX21lZGl1bT1jcGMmYW1wO3V0bV9jYW1wY
Wlnbj1IZXNrUHJvZHVjdF9Ub19IUCIgY2xhc3M9ImxpbmsiPlN5c0FpZCBUZWNobm9sb2dpZXM8L2E+P
C9wPg0KPC9mb290ZXI+DQo=',"\104", "347db01e129edd4b3877f70ea6fed019462ae827");
/*******************************************************************************
END LICENSE CODE
*******************************************************************************/
?>
</main> <!-- End main -->
<?php
if (isset($login_wrapper)) {
    echo '</div> <!-- End wrapper login -->';
}
?>
</div> <!-- End wrapper -->
<input type="hidden" name="HESK_PATH" value="<?php echo HESK_PATH; ?>">
<script src="<?php echo HESK_PATH; ?>js/svg4everybody.min.js"></script>
<?php
// Do we need the calendar functions?
if (defined('CALENDAR'))
{
?>
<script src="<?php echo HESK_PATH; ?>js/datepicker.min.js"></script>
<script type="text/javascript">
(function ($) { $.fn.datepicker.language['en'] = {
    days: ['<?php echo $hesklang['d0']; ?>', '<?php echo $hesklang['d1']; ?>', '<?php echo $hesklang['d2']; ?>', '<?php echo $hesklang['d3']; ?>', '<?php echo $hesklang['d4']; ?>', '<?php echo $hesklang['d5']; ?>', '<?php echo $hesklang['d6']; ?>'],
    daysShort: ['<?php echo $hesklang['sun']; ?>', '<?php echo $hesklang['mon']; ?>', '<?php echo $hesklang['tue']; ?>', '<?php echo $hesklang['wed']; ?>', '<?php echo $hesklang['thu']; ?>', '<?php echo $hesklang['fri']; ?>', '<?php echo $hesklang['sat']; ?>'],
    daysMin: ['<?php echo $hesklang['su']; ?>', '<?php echo $hesklang['mo']; ?>', '<?php echo $hesklang['tu']; ?>', '<?php echo $hesklang['we']; ?>', '<?php echo $hesklang['th']; ?>', '<?php echo $hesklang['fr']; ?>', '<?php echo $hesklang['sa']; ?>'],
    months: ['<?php echo $hesklang['m1']; ?>','<?php echo $hesklang['m2']; ?>','<?php echo $hesklang['m3']; ?>','<?php echo $hesklang['m4']; ?>','<?php echo $hesklang['m5']; ?>','<?php echo $hesklang['m6']; ?>', '<?php echo $hesklang['m7']; ?>','<?php echo $hesklang['m8']; ?>','<?php echo $hesklang['m9']; ?>','<?php echo $hesklang['m10']; ?>','<?php echo $hesklang['m11']; ?>','<?php echo $hesklang['m12']; ?>'],
    monthsShort: ['<?php echo $hesklang['ms01']; ?>','<?php echo $hesklang['ms02']; ?>','<?php echo $hesklang['ms03']; ?>','<?php echo $hesklang['ms04']; ?>','<?php echo $hesklang['ms05']; ?>','<?php echo $hesklang['ms06']; ?>', '<?php echo $hesklang['ms07']; ?>','<?php echo $hesklang['ms08']; ?>','<?php echo $hesklang['ms09']; ?>','<?php echo $hesklang['ms10']; ?>','<?php echo $hesklang['ms11']; ?>','<?php echo $hesklang['ms12']; ?>'],
    today: '<?php echo $hesklang['r1']; ?>',
    clear: '<?php echo $hesklang['clear']; ?>',
    dateFormat: 'mm/dd/yyyy',
    timeFormat: 'hh:ii aa',
    firstDay: <?php echo $hesklang['first_day_of_week']; ?>
}; })(jQuery);
</script>
<?php
}
?>
<script type="text/javascript" src="<?php echo HESK_PATH; ?>js/app<?php echo $hesk_settings['debug_mode'] ? '' : '.min'; ?>.js"></script>
<?php

// Auto-select first empty or error field on non-staff pages?
if (defined('AUTOFOCUS'))
{
?>
<script language="javascript">
(function(){
	var forms = document.forms || [];
	for(var i = 0; i < forms.length; i++)
    {
		for(var j = 0; j < forms[i].length; j++)
        {
			if(
				!forms[i][j].readonly != undefined &&
				forms[i][j].type != "hidden" &&
				forms[i][j].disabled != true &&
				forms[i][j].style.display != 'none' &&
				(forms[i][j].className == 'isError' || forms[i][j].className == 'isNotice' || forms[i][j].value == '')
			)
	        {
				forms[i][j].focus();
				return;
			}
		}
	}
})();
</script>
<?php
}

// Apply status coloring to drop-down box; needs to be called after app.js
if (isset($hesk_settings['print_status_select_box_jquery']))
{
    hesk_print_status_select_box_jquery();
}

echo '
</body>
</html>
';

$hesk_settings['security_cleanup']('exit');
