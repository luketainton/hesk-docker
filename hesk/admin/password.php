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

// Is the password reset function enabled?
if ( ! $hesk_settings['reset_pass'])
{
	die($hesklang['attempt']);
}

// Allow additional 5 attempts in case the user is already blocked
$hesk_settings['attempt_limit'] += 5;

$show_sent_email_message = false;

// Start session
hesk_session_start();

if (!isset($_SESSION['a_iserror']))
{
	$_SESSION['a_iserror'] = array();
}

$hesk_error_buffer = array();

// If this is a POST method, check input
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	// Verify security image
	if ($hesk_settings['secimg_use'])
	{
		// Using reCAPTCHA?
		if ($hesk_settings['recaptcha_use'])
		{
			require(HESK_PATH . 'inc/recaptcha/recaptchalib_v2.php');

			$resp = null;
			$reCaptcha = new ReCaptcha($hesk_settings['recaptcha_private_key']);

			// Was there a reCAPTCHA response?
			if ( isset($_POST["g-recaptcha-response"]) )
			{
				$resp = $reCaptcha->verifyResponse(hesk_getClientIP(), hesk_POST("g-recaptcha-response") );
			}

			if ($resp != null && $resp->success)
			{
				//$_SESSION['img_a_verified']=true;
			}
			else
			{
				$hesk_error_buffer['mysecnum']=$hesklang['recaptcha_error'];
			}
		}
		// Using PHP generated image
		else
		{
			$mysecnum = intval( hesk_POST('mysecnum', 0) );

			if ( empty($mysecnum) )
			{
				$hesk_error_buffer['mysecnum'] = $hesklang['sec_miss'];
			}
			else
			{
				require(HESK_PATH . 'inc/secimg.inc.php');
				$sc = new PJ_SecurityImage($hesk_settings['secimg_sum']);
				if ( isset($_SESSION['checksum']) && $sc->checkCode($mysecnum, $_SESSION['checksum']) )
				{
					//$_SESSION['img_a_verified'] = true;
				}
				else
				{
					$hesk_error_buffer['mysecnum'] = $hesklang['sec_wrng'];
				}
			}
		}
	}

	// Connect to database and check for brute force attempts
	hesk_load_database_functions();
	hesk_dbConnect();
	hesk_limitBfAttempts();

	// Get email
	$email = hesk_validateEmail( hesk_POST('email'), 'ERR', 0) or $hesk_error_buffer['email']=$hesklang['enter_valid_email'];

	// Any errors?
	if (count($hesk_error_buffer)!=0)
	{
		$_SESSION['a_iserror'] = array_keys($hesk_error_buffer);

		$tmp = '';
		foreach ($hesk_error_buffer as $error)
		{
			$tmp .= "<li>$error</li>\n";
		}
		$hesk_error_buffer = $tmp;

		$hesk_error_buffer = $hesklang['pcer'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
		hesk_process_messages($hesk_error_buffer,'NOREDIRECT');
	}
	elseif( defined('HESK_DEMO') )
	{
		hesk_process_messages($hesklang['ddemo'],'NOREDIRECT');
	}
	else
	{
		// Get user data from the database
		$res = hesk_dbQuery("SELECT `id`, `name`, `pass` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` WHERE `email` LIKE '".hesk_dbEscape($email)."' LIMIT 1");
		if (hesk_dbNumRows($res) != 1)
		{
			hesk_process_messages($hesklang['noace'],'NOREDIRECT');
		}
		else
		{
			$row = hesk_dbFetchAssoc($res);
			$hash = sha1(microtime() . hesk_getClientIP() . mt_rand() . $row['id'] . $row['name'] . $row['pass']);

			// Insert the verification hash into the database
			hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."reset_password` (`user`, `hash`, `ip`) VALUES (".intval($row['id']).", '{$hash}', '".hesk_dbEscape(hesk_getClientIP())."') ");

			// Prepare and send email
			require(HESK_PATH . 'inc/email_functions.inc.php');

			// Get the email message
			$msg = hesk_getEmailMessage('reset_password',array(),1,0,1);

			// Replace message special tags
			$msg = str_replace('%%NAME%%',				hesk_msgToPlain($row['name'],1,1),	$msg);
			$msg = str_replace('%%SITE_URL%%',			$hesk_settings['site_url'],			$msg);
			$msg = str_replace('%%SITE_TITLE%%',   		$hesk_settings['site_title'],		$msg);
			$msg = str_replace('%%PASSWORD_RESET%%',	$hesk_settings['hesk_url'].'/'.$hesk_settings['admin_dir'].'/password.php?h='.$hash, $msg);

			// Send email
			hesk_mail($email, $hesklang['reset_password'], $msg);

			// Show success
            $show_sent_email_message = true;
		}
	}
}
// If the "h" parameter is set verify it and reset the password
elseif ( isset($_GET['h']) )
{
	// Get the hash
	$hash = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['h']);

	// Connect to database
	hesk_load_database_functions();
	hesk_dbConnect();

	// Expire verification hashes older than 2 hours
	hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."reset_password` WHERE `dt` < (NOW() - INTERVAL 2 HOUR)");

	// Verify the hash exists
	$res = hesk_dbQuery("SELECT `user`, `ip` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."reset_password` WHERE `hash` = '{$hash}' LIMIT 1");
	if (hesk_dbNumRows($res) != 1)
	{
		// Not a valid hash
		hesk_limitBfAttempts();
		hesk_process_messages($hesklang['ehash'],'NOREDIRECT');
	}
	else
	{
		// Get info from database
		$row = hesk_dbFetchAssoc($res);

		// Only allow resetting password from the same IP address that submitted password reset request
		if ($row['ip'] != hesk_getClientIP())
		{
			hesk_limitBfAttempts();
			hesk_process_messages($hesklang['ehaip'],'NOREDIRECT');
		}
		else
		{
			// Expire all verification hashes for this user
			hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."reset_password` WHERE `user`=".intval($row['user']));

			// Load additional required functions
			require(HESK_PATH . 'inc/admin_functions.inc.php');

			// Get user details
			$res = hesk_dbQuery('SELECT * FROM `'.$hesk_settings['db_pfix']."users` WHERE `id`=".intval($row['user'])." LIMIT 1");
			$row = hesk_dbFetchAssoc($res);
			foreach ($row as $k=>$v)
			{
				$_SESSION[$k]=$v;
			}

			// Set a tag that will be used to expire sessions after username or password change
			$_SESSION['session_verify'] = hesk_activeSessionCreateTag($_SESSION['user'], $_SESSION['pass']);

			// We don't need the password hash anymore
			unset($_SESSION['pass']);

			// Clean brute force attempts
			hesk_cleanBfAttempts();

			// Regenerate session ID (security)
			hesk_session_regenerate_id();

			// Get allowed categories
			if (empty($_SESSION['isadmin']))
			{
				$_SESSION['categories']=explode(',',$_SESSION['categories']);
			}

			// Redirect to the profile page
			hesk_process_messages($hesklang['resim'],'profile.php','NOTICE');
			exit();

		} // End IP matches
	}
}

// Tell header to load reCaptcha API if needed
if ($hesk_settings['recaptcha_use'])
{
	define('RECAPTCHA',1);
}

$hesk_settings['tmp_title'] = $hesk_settings['hesk_title'] . ' - ' .$hesklang['passr'];
require_once(HESK_PATH . 'inc/header.inc.php');
$login_wrapper = true;
?>
<div class="wrapper login">
<main class="main">
    <div class="reg__wrap">
        <div class="reg__image">
            <div class="bg-absolute"><img src="<?php echo HESK_PATH; ?>img/hero-bg.png" alt="Hesk" /></div>
        </div>
        <div class="reg__section reset-password">
            <div class="reg__box">
                <?php if ($show_sent_email_message): ?>
                    <h2 class="reg__heading delete">
                        <?php echo $hesklang['password_reset_check_your_email']; ?>
                    </h2>
                    <div class="reset-password__text">
                        <?php echo $hesklang['password_reset_link_sent']; ?>
                    </div>
                <?php else: ?>
                    <h2 class="reg__heading delete">
                        <?php echo $hesklang['reset_your_password']; ?>
                    </h2>
                    <div class="reset-password__text"><?php echo $hesklang['reset_password_instructions']; ?></div>
                    <div style="margin-right: -24px; margin-left: -16px">
                        <?php
                        /* This will handle error, success and notice messages */
                        hesk_handle_messages();
                        ?>
                    </div>
                    <!-- To activate the error add the class "invalid" to the form -->
                    <form action="password.php" class="form <?php echo isset($_SESSION['a_iserror']) && count($_SESSION['a_iserror']) ? 'invalid' : ''; ?>" method="post" name="form1" id="form1" novalidate>
                        <div class="form-group">
                            <label for="regInputUsername"><?php echo $hesklang['email']; ?></label>
                            <input type="email"
                                   name="email"
                                   class="form-control<?php echo in_array('email',$_SESSION['a_iserror']) ? ' isError' : ''; ?>"
                                   id="regInputUsername"
                                   placeholder="<?php echo htmlspecialchars($hesklang['enter_email']); ?>"
                                   value="<?php if (isset($email)) {echo stripslashes(hesk_input($email));} ?>">
                            <div class="form-control__error"><?php echo $hesklang['this_field_is_required']; ?></div>
                        </div>
                        <?php if ($hesk_settings['secimg_use'] && $hesk_settings['recaptcha_use'] != 1) {
                            if ($hesk_settings['recaptcha_use'] == 2) { ?>
                                <div class="g-recaptcha" data-sitekey="<?php echo $hesk_settings['recaptcha_public_key']; ?>"></div>
                            <?php } else {
                                $cls = in_array('mysecnum',$_SESSION['a_iserror']) ? ' class="form-control isError" ' : ' class="form-control" ';

                                /*echo $hesklang['sec_enter'].'<br>&nbsp;<br><img src="'.HESK_PATH.'print_sec_img.php?'.rand(10000,99999).'" width="150" height="40" alt="'.$hesklang['sec_img'].'" title="'.$hesklang['sec_img'].'" border="1" name="secimg" style="vertical-align:text-bottom"> '.
                                     '<a href="javascript:void(0)" onclick="javascript:document.form1.secimg.src=\''.HESK_PATH.'print_sec_img.php?\'+ ( Math.floor((90000)*Math.random()) + 10000);"><img src="'.HESK_PATH.'img/reload.png" height="24" width="24" alt="'.$hesklang['reload'].'" title="'.$hesklang['reload'].'" border="0" style="vertical-align:text-bottom"></a>'.
                                     '<br>&nbsp;<br><input type="text" name="mysecnum" size="20" maxlength="5" '.$cls.'>';*/
                                echo '<div class="form-group"><label>'.$hesklang['sec_enter'].'</label><img src="'.HESK_PATH.'print_sec_img.php?'.rand(10000,99999).'" width="150" height="40" alt="'.$hesklang['sec_img'].'" title="'.$hesklang['sec_img'].'" border="1" name="secimg" style="vertical-align:middle" /> '.
                                    '<a style="vertical-align: middle; display: inline" class="btn btn-refresh" href="javascript:" onclick="document.form1.secimg.src=\''.HESK_PATH.'print_sec_img.php?\'+ ( Math.floor((90000)*Math.random()) + 10000);">
                                            <svg class="icon icon-refresh">
                                                <use xlink:href="' . HESK_PATH . 'img/sprite.svg#icon-refresh"></use>
                                            </svg>
                                         </a>'.
                                    '<br><br><input type="text" name="mysecnum" size="20" maxlength="5" '.$cls.'></div>';
                            }
                        } ?>
                        <div class="form__submit">
                            <button class="btn btn-full" type="submit" id="recaptcha-submit"><?php echo $hesklang['passs']; ?></button>
                        </div>
                        <?php
                        // Use Invisible reCAPTCHA?
                        if ($hesk_settings['secimg_use'] && $hesk_settings['recaptcha_use'] == 1)
                        {
                            ?>
                            <div class="g-recaptcha" data-sitekey="<?php echo $hesk_settings['recaptcha_public_key']; ?>" data-bind="recaptcha-submit" data-callback="recaptcha_submitForm"></div>
                            <?php
                        }
                        ?>
                    </form>
                <?php endif; ?>
                <div class="reg__footer">
                    <a href="index.php" class="link"><?php echo $hesklang['back_to_login']; ?></a>
                </div>
            </div>
        </div>
    </div>

<?php
// Clean session errors
hesk_cleanSessionVars('a_iserror');
hesk_cleanSessionVars('img_a_verified');

// Print footer
require_once(HESK_PATH . 'inc/footer.inc.php');
?>
