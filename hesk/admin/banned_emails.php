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
hesk_checkPermission('can_ban_emails');
$can_unban = hesk_checkPermission('can_unban_emails', 0);

// Define required constants
define('LOAD_TABS',1);

// What should we do?
if ( $action = hesk_REQUEST('a') )
{
	if ( defined('HESK_DEMO') ) {hesk_process_messages($hesklang['ddemo'], 'banned_emails.php', 'NOTICE');}
	elseif ($action == 'ban')   {ban_email();}
	elseif ($action == 'unban' && $can_unban) {unban_email();}
}

/* Print header */
require_once(HESK_PATH . 'inc/header.inc.php');

/* Print main manage users page */
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');

/* This will handle error, success and notice messages */
hesk_handle_messages();
?>

<div class="main__content tools">
    <h2><?php echo $hesklang['banemail']; ?></h2>
    <form action="banned_emails.php" method="post" name="form1">
        <div class="tools__add-mail form">
            <div class="form-group">
                <input type="text" name="email" class="form-control" maxlength="255" placeholder="<?php echo htmlspecialchars($hesklang['bananemail']); ?>">
                <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
                <input type="hidden" name="a" value="ban" />
                <button type="submit" class="btn btn--blue-border" ripple="ripple"><?php echo $hesklang['savebanemail']; ?></button>
            </div>
            <div class="mail--examples"><?php echo $hesklang['banex']; ?>: john@example.com, @example.com</div>
        </div>
    </form>
    <?php
    // Get banned emails from database
    $res = hesk_dbQuery('SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'banned_emails` ORDER BY `email` ASC');
    $num = hesk_dbNumRows($res);
    ?>
    <div class="table-wrapper email">
        <table id="default-table" class="table sindu-table">
            <thead>
            <tr>
                <th><?php echo $hesklang['email']; ?></th>
                <th><?php echo $hesklang['banby']; ?></th>
                <th><?php echo $hesklang['date']; ?></th>
                <?php if ($can_unban): ?>
                    <th><?php echo $hesklang['opt']; ?></th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php if ($num < 1): ?>
            <tr>
                <td colspan="<?php echo $can_unban ? 4 : 3; ?>"><?php echo $hesklang['no_banemails']; ?></td>
            </tr>
            <?php
            else:
                // List of staff
                if ( ! isset($admins) )
                {
                    $admins = array();
                    $res2 = hesk_dbQuery("SELECT `id`,`name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users`");
                    while ($row=hesk_dbFetchAssoc($res2))
                    {
                        $admins[$row['id']]=$row['name'];
                    }
                }

                while ($ban = hesk_dbFetchAssoc($res)):
                    $table_row = '';
                    if (isset($_SESSION['ban_email']['id']) && $ban['id'] == $_SESSION['ban_email']['id'])
                    {
                        $table_row = 'class="ticket-new"';
                        unset($_SESSION['ban_email']['id']);
                    }
                ?>
                <tr <?php echo $table_row; ?>>
                    <td><?php echo $ban['email']; ?></td>
                    <td><?php echo isset($admins[$ban['banned_by']]) ? $admins[$ban['banned_by']] : $hesklang['e_udel']; ?></td>
                    <td><?php echo $ban['dt']; ?></td>
                    <?php if ($can_unban): ?>
                    <td class="unban">
                        <?php $modal_id = hesk_generate_delete_modal($hesklang['confirm_deletion'],
                            $hesklang['delban_confirm'],
                            'banned_emails.php?a=unban&amp;id='. $ban['id'] .'&amp;token='. hesk_token_echo(0)); ?>
                        <a title="<?php echo $hesklang['delban']; ?>" href="javascript:" data-modal="[data-modal-id='<?php echo $modal_id; ?>']">
                            <?php echo $hesklang['delban']; ?>
                        </a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile;
                endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
require_once(HESK_PATH . 'inc/footer.inc.php');
exit();


/*** START FUNCTIONS ***/

function ban_email()
{
	global $hesk_settings, $hesklang;

	// A security check
	hesk_token_check();

	// Get the email
	$email = hesk_emailCleanup( strtolower( hesk_input( hesk_REQUEST('email') ) ) );

	// Nothing entered?
	if ( ! strlen($email) )
	{
    	hesk_process_messages($hesklang['enterbanemail'],'banned_emails.php');
	}

	// Only allow one email to be entered
	$email = ($index = strpos($email, ',')) ? substr($email, 0,  $index) : $email;
	$email = ($index = strpos($email, ';')) ? substr($email, 0,  $index) : $email;

	// Validate email address
	$hesk_settings['multi_eml'] = 0;

	if ( ! hesk_validateEmail($email, '', 0) && ! verify_email_domain($email) )
	{
		hesk_process_messages($hesklang['validbanemail'],'banned_emails.php');
	}

	// Redirect either to banned emails or ticket page from now on
	$redirect_to = ($trackingID = hesk_cleanID()) ? 'admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999) : 'banned_emails.php';

	// Prevent duplicate rows
	if ( $_SESSION['ban_email']['id'] = hesk_isBannedEmail($email) )
	{
    	hesk_process_messages( sprintf($hesklang['emailbanexists'], $email) ,$redirect_to,'NOTICE');
	}

	// Insert the email address into database
	hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."banned_emails` (`email`,`banned_by`) VALUES ('".hesk_dbEscape($email)."','".intval($_SESSION['id'])."')");

	// Remember email that got banned
	$_SESSION['ban_email']['id'] = hesk_dbInsertID();

	// Show success
    hesk_process_messages( sprintf($hesklang['email_banned'], $email) ,$redirect_to,'SUCCESS');

} // End ban_email()


function unban_email()
{
	global $hesk_settings, $hesklang;

	// A security check
	hesk_token_check();

	// Delete from bans
	hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."banned_emails` WHERE `id`=" . intval( hesk_GET('id') ) );

	// Redirect either to banned emails or ticket page from now on
	$redirect_to = ($trackingID = hesk_cleanID()) ? 'admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999) : 'banned_emails.php';

	// Show success
    hesk_process_messages($hesklang['email_unbanned'],$redirect_to,'SUCCESS');

} // End unban_email()


function verify_email_domain($domain)
{
    // Does it start with an @?
	$atIndex = strrpos($domain, "@");
	if ($atIndex !== 0)
	{
		return false;
	}

	// Get the domain and domain length
	$domain = substr($domain, 1);
	$domainLen = strlen($domain);

    // Check domain part length
	if ($domainLen < 1 || $domainLen > 254)
	{
		return false;
	}

    // Check domain part characters
	if ( ! preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain) )
	{
		return false;
	}

	// Domain part mustn't have two consecutive dots
	if ( strpos($domain, '..') !== false )
	{
		return false;
	}

	// All OK
	return true;

} // END verify_email_domain()

?>
