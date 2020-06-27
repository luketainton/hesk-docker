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

#error_reporting(E_ALL);

/*
 * If code is executed from CLI, don't force SSL
 * else set correct Content-Type header
 */
if (defined('NO_HTTP_HEADER'))
{
    $hesk_settings['force_ssl'] = false;
}
else
{
	header('Content-Type: text/html; charset=utf-8');

    // Don't allow HESK to be loaded in a frame on third party domains
    if ($hesk_settings['x_frame_opt'])
    {
        header('X-Frame-Options: SAMEORIGIN');
    }
}

// Set backslash options
if (version_compare(PHP_VERSION, '5.4.0', '<') && get_magic_quotes_gpc())
{
	define('HESK_SLASH',false);
}
else
{
	define('HESK_SLASH',true);
}

// Define some constants for backward-compatibility
if ( ! defined('ENT_SUBSTITUTE'))
{
	define('ENT_SUBSTITUTE', 0);
}
if ( ! defined('ENT_XHTML'))
{
	define('ENT_XHTML', 0);
}

// Is this is a SSL connection?
if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on')
{
    define('HESK_SSL', true);

    // Use https-only cookies
    @ini_set('session.cookie_secure', 1);
}
else
{
    // Force redirect?
    if ($hesk_settings['force_ssl'])
    {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit();
    }

    define('HESK_SSL', false);
}

// Prevents javascript XSS attacks aimed to steal the session ID
@ini_set('session.cookie_httponly', 1);

// **PREVENTING SESSION FIXATION**
// Session ID cannot be passed through URLs
@ini_set('session.use_only_cookies', 1);

// Load language file
hesk_getLanguage();

// Set timezone
hesk_setTimezone();

/*** FUNCTIONS ***/


function hesk_getClientIP()
{
    global $hesk_settings;

    // Already set? Just return it
    if (isset($hesk_settings['client_IP']))
    {
        return $hesk_settings['client_IP'];
    }

    // Empty client IP, for example when used in CLI (piping, cron jobs, ...)
    $hesk_settings['client_IP'] = '';

    // Server (environment) variables to loop through
    // the first valid one found will be returned as client IP
    // Uncomment those used on your server
    $server_client_IP_variables = array(
        // 'HTTP_CF_CONNECTING_IP', // CloudFlare
        // 'HTTP_CLIENT_IP',
        // 'HTTP_X_FORWARDED_FOR',
        // 'HTTP_X_FORWARDED',
        // 'HTTP_FORWARDED_FOR',
        // 'HTTP_FORWARDED',
        'REMOTE_ADDR',
    );

    // The first valid environment variable is our client IP
    foreach ($server_client_IP_variables as $server_client_IP_variable)
    {
        // Must be set
        if ( ! isset($_SERVER[$server_client_IP_variable]))
        {
            continue;
        }

        // Must be a valid IP
        if ( ! hesk_isValidIP($_SERVER[$server_client_IP_variable]))
        {
            continue;
        }

        // Bingo!
        $hesk_settings['client_IP'] = $_SERVER[$server_client_IP_variable];
        break;
    }

    return $hesk_settings['client_IP'];

} // END hesk_getClientIP()


function hesk_isValidIP($ip)
{
    // Use filter_var for PHP 5.2.0+
    if ( function_exists('filter_var') && filter_var($ip, FILTER_VALIDATE_IP) !== false )
    {
        return true;
    }

    // Use regex for PHP < 5.2.0

    // -> IPv4
    if ( preg_match('/^[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}$/', $ip) )
    {
        return true;
    }

    // -> IPv6
    if ( preg_match('/^[0-9A-Fa-f\:\.]+$/', $ip) )
    {
        return true;
    }

    // Not a valid IP
    return false;

} // END hesk_isValidIP()


function hesk_setcookie($name, $value, $expire=0, $path="")
{
    if (HESK_SSL)
    {
        setcookie($name, $value, $expire, $path, "", true, true);
    }
    else
    {
        setcookie($name, $value, $expire, $path, "", false, true);
    }

    return true;
} // END hesk_setcookie()


function hesk_service_message($sm)
{
	switch ($sm['style'])
	{
		case 1:
			$style = "green";
			break;
		case 2:
			$style = "blue";
			break;
		case 3:
			$style = "orange";
			break;
		case 4:
			$style = "red";
			break;
		default:
			$style = "white";
	}

	?>
    <div class="main__content notice-flash">
        <div class="notification <?php echo $style; ?> browser-default">
            <p><b><?php echo $sm['title']; ?></b></p>
            <?php echo $sm['message']; ?>
        </div>
    </div>
	<?php
} // END hesk_service_message()


function hesk_isBannedIP($ip)
{
	global $hesk_settings, $hesklang, $hesk_db_link;

	$ip = ip2long($ip) or $ip = 0;

	// We need positive value of IP
	if ($ip < 0)
	{
		$ip += 4294967296;
	}
	elseif ($ip > 4294967296)
	{
		$ip = 4294967296;
	}

	$res = hesk_dbQuery("SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."banned_ips` WHERE {$ip} BETWEEN `ip_from` AND `ip_to` LIMIT 1");

	return ( hesk_dbNumRows($res) == 1 ) ? hesk_dbResult($res) : false;

} // END hesk_isBannedIP()


function hesk_isBannedEmail($email)
{
	global $hesk_settings, $hesklang, $hesk_db_link;

	$email = strtolower($email);

	$res = hesk_dbQuery("SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."banned_emails` WHERE `email` IN ('".hesk_dbEscape($email)."', '".hesk_dbEscape( substr($email, strrpos($email, "@") ) )."') LIMIT 1");

	return ( hesk_dbNumRows($res) == 1 ) ? hesk_dbResult($res) : false;

} // END hesk_isBannedEmail()


function hesk_clean_utf8($in)
{
	//reject overly long 2 byte sequences, as well as characters above U+10000 and replace with ?
	$in = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
	 '|[\x00-\x7F][\x80-\xBF]+'.
	 '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
	 '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
	 '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
	 '?', $in );

	//reject overly long 3 byte sequences and UTF-16 surrogates and replace with ?
	$in = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]'.
	 '|\xED[\xA0-\xBF][\x80-\xBF]/S','?', $in );

	return $in;     
} // END hesk_clean_utf8()


function hesk_load_database_functions()
{
    // Already loaded?
    if (function_exists('hesk_dbQuery'))
    {
        return true;
    }
	// Preferrably use the MySQLi functions
	elseif ( function_exists('mysqli_connect') )
	{
		require(HESK_PATH . 'inc/database_mysqli.inc.php');
	}
	// Default to MySQL
	else
	{
		require(HESK_PATH . 'inc/database.inc.php');
	}
} // END hesk_load_database_functions()


function hesk_unlink($file, $older_than=0)
{
	return ( is_file($file) && ( ! $older_than || (time()-filectime($file)) > $older_than ) && @unlink($file) ) ? true : false;
} // END hesk_unlink()


function hesk_unlink_callable($file, $key, $older_than=0)
{
	return hesk_unlink($file, $older_than);
} // END hesk_unlink_callable()


function hesk_utf8_urldecode($in)
{
	$in = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;", urldecode($in));
	return hesk_html_entity_decode($in);
} // END hesk_utf8_urldecode


function hesk_SESSION($in, $default = '')
{
	if (is_array($in))
	{
		return isset($_SESSION[$in[0]][$in[1]]) && ! is_array(isset($_SESSION[$in[0]][$in[1]])) ? $_SESSION[$in[0]][$in[1]] : $default;
	}
	else
	{
		return isset($_SESSION[$in]) && ! is_array($_SESSION[$in]) ? $_SESSION[$in] : $default;
	}
} // END hesk_SESSION();


function hesk_COOKIE($in, $default = '')
{
	return isset($_COOKIE[$in]) && ! is_array($_COOKIE[$in]) ? $_COOKIE[$in] : $default;
} // END hesk_COOKIE();


function hesk_GET($in, $default = '')
{
	return isset($_GET[$in]) && ! is_array($_GET[$in]) ? $_GET[$in] : $default;
} // END hesk_GET()


function hesk_POST($in, $default = '')
{
	return isset($_POST[$in]) && ! is_array($_POST[$in]) ? $_POST[$in] : $default;
} // END hesk_POST()


function hesk_POST_array($in, $default = array() )
{
	return isset($_POST[$in]) && is_array($_POST[$in]) ? $_POST[$in] : $default;
} // END hesk_POST_array()


function hesk_REQUEST($in, $default = false)
{
	return isset($_GET[$in]) ? hesk_input( hesk_GET($in) ) : ( isset($_POST[$in]) ? hesk_input( hesk_POST($in) ) : $default );
} // END hesk_REQUEST()


function hesk_isREQUEST($in)
{
	return isset($_GET[$in]) || isset($_POST[$in]) ? true : false;
} // END hesk_isREQUEST()


function hesk_mb_substr($in, $start, $length)
{
	return function_exists('mb_substr') ? mb_substr($in, $start, $length, 'UTF-8') : substr($in, $start, $length);
} // END hesk_mb_substr()


function hesk_mb_strlen($in)
{
	return function_exists('mb_strlen') ? mb_strlen($in, 'UTF-8') : strlen($in);
} // END hesk_mb_strlen()


function hesk_mb_strtolower($in)
{
	return function_exists('mb_strtolower') ? mb_strtolower($in, 'UTF-8') : strtolower($in);
} // END hesk_mb_strtolower()

function hesk_mb_strtoupper($in)
{
    return function_exists('mb_strtoupper') ? mb_strtoupper($in, 'UTF-8') : strtoupper($in);
} // END hesk_mb_strtolower()


function hesk_ucfirst($in)
{
	return function_exists('mb_convert_case') ? mb_convert_case($in, MB_CASE_TITLE, 'UTF-8') : ucfirst($in);
} // END hesk_mb_ucfirst()


function hesk_htmlspecialchars_decode($in)
{
	return str_replace( array('&amp;', '&lt;', '&gt;', '&quot;'), array('&', '<', '>', '"'), $in);
} // END hesk_htmlspecialchars_decode()


function hesk_html_entity_decode($in)
{
	return html_entity_decode($in, ENT_COMPAT | ENT_XHTML, 'UTF-8');
    #return html_entity_decode($in, ENT_COMPAT | ENT_XHTML, 'ISO-8859-1');
} // END hesk_html_entity_decode()


function hesk_htmlspecialchars($in)
{
	return htmlspecialchars($in, ENT_COMPAT | ENT_SUBSTITUTE | ENT_XHTML, 'UTF-8');
    #return htmlspecialchars($in, ENT_COMPAT | ENT_SUBSTITUTE | ENT_XHTML, 'ISO-8859-1');
} // END hesk_htmlspecialchars()


function hesk_htmlentities($in)
{
	return htmlentities($in, ENT_COMPAT | ENT_SUBSTITUTE | ENT_XHTML, 'UTF-8');
    #return htmlentities($in, ENT_COMPAT | ENT_SUBSTITUTE | ENT_XHTML, 'ISO-8859-1');
} // END hesk_htmlentities()


function hesk_slashJS($in)
{
	return str_replace( '\'', '\\\'', $in);
} // END hesk_slashJS()


function hesk_verifyEmailMatch($trackingID, $my_email = 0, $ticket_email = 0, $error = 1)
{
	global $hesk_settings, $hesklang, $hesk_db_link;

	/* Email required to view ticket? */
	if ( ! $hesk_settings['email_view_ticket'])
    {
		$hesk_settings['e_param'] = '';
        $hesk_settings['e_query'] = '';
        $hesk_settings['e_email'] = '';
		return true;
    }

	/* Limit brute force attempts */
	hesk_limitBfAttempts();

	/* Get email address */
	if ($my_email)
	{
		$hesk_settings['e_param'] = '&e=' . rawurlencode($my_email);
		$hesk_settings['e_query'] = '&amp;e=' . rawurlencode($my_email);
		$hesk_settings['e_email'] = $my_email;
	}
	else
	{
		$my_email = hesk_getCustomerEmail();
	}

	/* Get email from ticket */
	if ( ! $ticket_email)
	{
		$res = hesk_dbQuery("SELECT `email` FROM `".$hesk_settings['db_pfix']."tickets` WHERE `trackid`='".hesk_dbEscape($trackingID)."' LIMIT 1");
		if (hesk_dbNumRows($res) == 1)
		{
			$ticket_email = hesk_dbResult($res);
		}
        else
        {
			hesk_process_messages($hesklang['ticket_not_found'],'ticket.php');
        }
	}

	/* Validate email */
	if ($hesk_settings['multi_eml'])
	{
		$valid_emails = explode(',', strtolower($ticket_email) );
		if ( in_array(strtolower($my_email), $valid_emails) )
		{
			/* Match, clean brute force attempts and return true */
			hesk_cleanBfAttempts();
			return true;
		}
	}
	elseif ( strtolower($ticket_email) == strtolower($my_email) )
	{
		/* Match, clean brute force attempts and return true */
		hesk_cleanBfAttempts();
		return true;
	}

	/* Email doesn't match, clean cookies and error out */
    if ($error)
    {
	    hesk_setcookie('hesk_myemail', '');
	    hesk_process_messages($hesklang['enmdb'],'ticket.php?track='.$trackingID.'&Refresh='.rand(10000,99999));
    }
    else
    {
    	return false;
    }

} // END hesk_verifyEmailMatch()


function hesk_getCustomerEmail($can_remember = 0, $field = '', $force_only_one = 0)
{
	global $hesk_settings, $hesklang;

	/* Email required to view ticket? */
	if ( ! $hesk_settings['email_view_ticket'])
    {
		$hesk_settings['e_param'] = '';
		$hesk_settings['e_query'] = '';
        $hesk_settings['e_email'] = '';
		return '';
    }

	/* Is this a form that enables remembering email? */
    if ($can_remember)
    {
    	global $do_remember;
    }

	$my_email = '';

	/* Is email in session? */
	if ( strlen($field) && isset($_SESSION[$field]) )
	{
		$my_email = hesk_validateEmail($_SESSION[$field], 'ERR', 0);
	}

	/* Is email in query string? */
	elseif ( isset($_GET['e']) || isset($_POST['e']) )
	{
		$my_email = hesk_validateEmail( hesk_REQUEST('e') ,'ERR',0);
	}

    /* Is email in cookie? */
	elseif ( isset($_COOKIE['hesk_myemail']) )
	{
		$my_email = hesk_validateEmail( hesk_COOKIE('hesk_myemail'), 'ERR', 0);
		if ($can_remember && $my_email)
		{
			$do_remember = ' checked="checked" ';
		}
	}

    // Remove unwanted side-effects
    $my_email = hesk_emailCleanup($my_email);

    // Force only one email address? Use the first one.
    if ($force_only_one)
    {
        $my_email = strtok($my_email, ',');
    }

    $hesk_settings['e_param'] = '&e=' . rawurlencode($my_email);
    $hesk_settings['e_query'] = '&amp;e=' . rawurlencode($my_email);
    $hesk_settings['e_email'] = $my_email;

    return $my_email;

} // END hesk_getCustomerEmail()


function hesk_emailCleanup($my_email)
{
    return preg_replace("/(\\\)+'/", "'", $my_email);
} // END hesk_emailCleanup()


function hesk_formatBytes($size, $translate_unit = 1, $precision = 2)
{
	global $hesklang;

    $units = array(
    	'GB' => 1073741824,
        'MB' => 1048576,
        'kB' => 1024,
        'B'  => 1
    );

    foreach ($units as $suffix => $bytes)
    {
    	if ($bytes > $size)
        {
        	continue;
        }

        $full  = $size / $bytes;
        $round = round($full, $precision);

        if ($full == $round)
        {
            if ($translate_unit)
            {
            	return $round . ' ' . $hesklang[$suffix];
            }
            else
            {
            	return $round . ' ' . $suffix;
            }
        }
    }

    return false;
} // End hesk_formatBytes()


function hesk_autoAssignTicket($ticket_category)
{
	global $hesk_settings, $hesklang;

	/* Auto assign ticket enabled? */
	if ( ! $hesk_settings['autoassign'])
	{
		return false;
	}

	$autoassign_owner = array();

	/* Get all possible auto-assign staff, order by number of open tickets */
	$res = hesk_dbQuery("SELECT `t1`.`id`,`t1`.`user`,`t1`.`name`, `t1`.`email`, `t1`.`language`, `t1`.`isadmin`, `t1`.`categories`, `t1`.`notify_assigned`, `t1`.`heskprivileges`,
					    (SELECT COUNT(*) FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` FORCE KEY (`statuses`) WHERE `owner`=`t1`.`id` AND `status` IN ('0','1','2','4','5') ) as `open_tickets`
						FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` AS `t1`
						WHERE `t1`.`autoassign`='1' ORDER BY `open_tickets` ASC, RAND()");

	/* Loop through the rows and return the first appropriate one */
	while ($myuser = hesk_dbFetchAssoc($res))
	{
		/* Is this an administrator? */
		if ($myuser['isadmin'])
		{
			$autoassign_owner = $myuser;
            $hesk_settings['user_data'][$myuser['id']] = $myuser;
			hesk_dbFreeResult($res);
			break;
		}

		/* Not and administrator, check two things: */

        /* --> can view and reply to tickets */
		if (strpos($myuser['heskprivileges'], 'can_view_tickets') === false || strpos($myuser['heskprivileges'], 'can_reply_tickets') === false)
		{
			continue;
		}

        /* --> has access to ticket category */
		$myuser['categories']=explode(',',$myuser['categories']);
		if (in_array($ticket_category,$myuser['categories']))
		{
			$autoassign_owner = $myuser;
            $hesk_settings['user_data'][$myuser['id']] = $myuser;
			hesk_dbFreeResult($res);
			break;
		}
	} 

    return $autoassign_owner;

} // END hesk_autoAssignTicket()


function hesk_cleanID($field='track', $in=false)
{
    $id = '';

    if ($in !== false)
    {
        $id = $in;
    }
    elseif ( isset($_SESSION[$field]) )
    {
        $id = $_SESSION[$field];
    }
    elseif ( isset($_GET[$field]) && ! is_array($_GET[$field]) )
    {
        $id = $_GET[$field];
    }
    elseif ( isset($_POST[$field]) && ! is_array($_POST[$field]) )
    {
        $id = $_POST[$field];
    }
    else
    {
        return false;
    }

    return substr( preg_replace('/[^A-Z0-9\-]/','',strtoupper($id)) , 0, 12);

} // END hesk_cleanID()


function hesk_createID()
{
	global $hesk_settings, $hesklang, $hesk_error_buffer;

	/*** Generate tracking ID and make sure it's not a duplicate one ***/

	/* Ticket ID can be of these chars */
	$useChars = 'AEUYBDGHJLMNPQRSTVWXZ123456789';

    /* Set tracking ID to an empty string */
	$trackingID = '';

	/* Let's avoid duplicate ticket ID's, try up to 3 times */
	for ($i=1;$i<=3;$i++)
    {
	    /* Generate raw ID */
	    $trackingID .= $useChars[mt_rand(0,29)];
	    $trackingID .= $useChars[mt_rand(0,29)];
	    $trackingID .= $useChars[mt_rand(0,29)];
	    $trackingID .= $useChars[mt_rand(0,29)];
	    $trackingID .= $useChars[mt_rand(0,29)];
	    $trackingID .= $useChars[mt_rand(0,29)];
	    $trackingID .= $useChars[mt_rand(0,29)];
	    $trackingID .= $useChars[mt_rand(0,29)];
	    $trackingID .= $useChars[mt_rand(0,29)];
	    $trackingID .= $useChars[mt_rand(0,29)];

		/* Format the ID to the correct shape and check wording */
        $trackingID = hesk_formatID($trackingID);

		/* Check for duplicate IDs */
		$res = hesk_dbQuery("SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE `trackid` = '".hesk_dbEscape($trackingID)."' LIMIT 1");

		if (hesk_dbNumRows($res) == 0)
		{
        	/* Everything is OK, no duplicates found */
			return $trackingID;
        }

        /* A duplicate ID has been found! Let's try again (up to 2 more) */
        $trackingID = '';
    }

    /* No valid tracking ID, try one more time with microtime() */
	$trackingID  = $useChars[mt_rand(0,29)];
	$trackingID .= $useChars[mt_rand(0,29)];
	$trackingID .= $useChars[mt_rand(0,29)];
	$trackingID .= $useChars[mt_rand(0,29)];
	$trackingID .= $useChars[mt_rand(0,29)];
	$trackingID .= substr(microtime(), -5);

	/* Format the ID to the correct shape and check wording */
	$trackingID = hesk_formatID($trackingID);

	$res = hesk_dbQuery("SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE `trackid` = '".hesk_dbEscape($trackingID)."' LIMIT 1");

	/* All failed, must be a server-side problem... */
	if (hesk_dbNumRows($res) == 0)
	{
		return $trackingID;
    }

    $hesk_error_buffer['etid'] = $hesklang['e_tid'];
	return false;

} // END hesk_createID()


function hesk_formatID($id)
{

	$useChars = 'AEUYBDGHJLMNPQRSTVWXZ123456789';

    $replace  = $useChars[mt_rand(0,29)];
    $replace .= mt_rand(1,9);
    $replace .= $useChars[mt_rand(0,29)];

    /*
    Remove 3 letter bad words from ID
    Possiblitiy: 1:27,000
    */
	$remove = array(
    'ASS',
    'CUM',
    'FAG',
    'FUK',
    'GAY',
    'SEX',
    'TIT',
    'XXX',
    );

    $id = str_replace($remove,$replace,$id);

    /*
    Remove 4 letter bad words from ID
    Possiblitiy: 1:810,000
    */
	$remove = array(
	'ANAL',
	'ANUS',
	'BUTT',
	'CAWK',
	'CLIT',
	'COCK',
	'CRAP',
	'CUNT',
	'DICK',
	'DYKE',
	'FART',
	'FUCK',
	'JAPS',
	'JERK',
	'JIZZ',
	'KNOB',
	'PISS',
	'POOP',
	'SHIT',
	'SLUT',
	'SUCK',
	'TURD',

    // Also, remove words that are known to trigger mod_security
	'WGET',
    );

	$replace .= mt_rand(1,9);
    $id = str_replace($remove,$replace,$id);

    /* Format the ID string into XXX-XXX-XXXX format for easier readability */
    $id = $id[0].$id[1].$id[2].'-'.$id[3].$id[4].$id[5].'-'.$id[6].$id[7].$id[8].$id[9];

    return $id;

} // END hesk_formatID()


function hesk_cleanBfAttempts()
{
	global $hesk_settings, $hesklang;

	/* If this feature is disabled, just return */
    if ( ! $hesk_settings['attempt_limit'] || defined('HESK_BF_CLEAN') )
    {
    	return true;
    }

    /* Delete expired logs from the database */
	$res = hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."logins` WHERE `ip`='".hesk_dbEscape(hesk_getClientIP())."'");

    define('HESK_BF_CLEAN', 1);

	return true;
} // END hesk_cleanAttempts()


function hesk_limitBfAttempts($showError=1)
{
	global $hesk_settings, $hesklang;

	// Check if this IP is banned permanently
	if ( hesk_isBannedIP(hesk_getClientIP()) )
	{
    	hesk_error($hesklang['baned_ip'], 0);
	}

	/* If this feature is disabled or already called, return false */
    if ( ! $hesk_settings['attempt_limit'] || defined('HESK_BF_LIMIT') )
    {
    	return false;
    }

    /* Define this constant to avoid duplicate checks */
    define('HESK_BF_LIMIT', 1);

	$ip = hesk_getClientIP();

    /* Get number of failed attempts from the database */
	$res = hesk_dbQuery("SELECT `number`, (CASE WHEN `last_attempt` IS NOT NULL AND DATE_ADD(`last_attempt`, INTERVAL ".intval($hesk_settings['attempt_banmin'])." MINUTE ) > NOW() THEN 1 ELSE 0 END) AS `banned` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."logins` WHERE `ip`='".hesk_dbEscape($ip)."' LIMIT 1");

    /* Not in the database yet? Add first one and return false */
	if (hesk_dbNumRows($res) != 1)
	{
		hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."logins` (`ip`) VALUES ('".hesk_dbEscape($ip)."')");
		return false;
	}

    /* Get number of failed attempts and increase by 1 */
    $row = hesk_dbFetchAssoc($res);
    $row['number']++;

    /* If too many failed attempts either return error or reset count if time limit expired */
	if ($row['number'] >= $hesk_settings['attempt_limit'])
    {
    	if ($row['banned'])
        {
        	$tmp = sprintf($hesklang['yhbb'],$hesk_settings['attempt_banmin']);

            unset($_SESSION); 

        	if ($showError)
            {
            	hesk_error($tmp,0);
            }
            else
            {
        		return $tmp;
            }
        }
        else
        {
			$row['number'] = 1;
        }
    }

	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."logins` SET `number`=".intval($row['number'])." WHERE `ip`='".hesk_dbEscape($ip)."'");

	return false;

} // END hesk_limitAttempts()


function hesk_getCategoryName($id)
{
	global $hesk_settings, $hesklang;

	if (empty($id))
	{
		return $hesklang['unas'];
	}

	// If we already have the name no need to query DB another time
	if ( isset($hesk_settings['category_data'][$id]['name']) )
	{
		return $hesk_settings['category_data'][$id]['name'];
	}

	$res = hesk_dbQuery("SELECT `name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE `id`='".intval($id)."' LIMIT 1");

	if (hesk_dbNumRows($res) != 1)
	{
		return $hesklang['catd'];
	}

	$hesk_settings['category_data'][$id]['name'] = hesk_dbResult($res,0,0);

	return $hesk_settings['category_data'][$id]['name'];
} // END hesk_getOwnerName()


function hesk_getReplierName($ticket)
{
	global $hesk_settings, $hesklang;

    // Already have this info?
    if (isset($ticket['last_reply_by']))
    {
        return $ticket['last_reply_by'];
    }

    // Last reply by staff
    if ( ! empty($ticket['lastreplier']))
    {
        // We don't know who from staff so just send "Staff"
        if (empty($ticket['replierid']))
        {
            return $hesklang['staff'];
        }

        // Get the name using another function
        $replier = hesk_getOwnerName($ticket['replierid']);

        // If replier comes back as "unassigned", default to "Staff"
        if ($replier == $hesklang['unas'])
        {
            return $hesklang['staff'];
        }

        return $replier;
    }

    // Last reply by customer
    return $ticket['name'];

} // END hesk_getReplierName()


function hesk_getOwnerName($id)
{
	global $hesk_settings, $hesklang;

	if (empty($id))
	{
		return $hesklang['unas'];
	}

	// If we already have the name no need to query DB another time
	if ( isset($hesk_settings['user_data'][$id]['name']) )
	{
		return $hesk_settings['user_data'][$id]['name'];
	}

	$res = hesk_dbQuery("SELECT `name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` WHERE `id`='".intval($id)."' LIMIT 1");

	if (hesk_dbNumRows($res) != 1)
	{
		return $hesklang['unas'];
	}

	$hesk_settings['user_data'][$id]['name'] = hesk_dbResult($res,0,0);

	return $hesk_settings['user_data'][$id]['name'];
} // END hesk_getOwnerName()


function hesk_cleanSessionVars($arr)
{
	if (is_array($arr))
	{
		foreach ($arr as $str)
		{
			if (isset($_SESSION[$str]))
			{
				unset($_SESSION[$str]);
			}
		}
	}
	elseif (isset($_SESSION[$arr]))
	{
		unset($_SESSION[$arr]);
	}
} // End hesk_cleanSessionVars()


function hesk_process_messages($message,$redirect_to,$type='ERROR')
{
	global $hesk_settings, $hesklang;

    switch ($type)
    {
    	case 'SUCCESS':
        	$_SESSION['HESK_SUCCESS'] = TRUE;
            break;
        case 'NOTICE':
        	$_SESSION['HESK_NOTICE'] = TRUE;
            break;
        case 'INFO':
        	$_SESSION['HESK_INFO'] = TRUE;
            break;
        default:
        	$_SESSION['HESK_ERROR'] = TRUE;
    }

	$_SESSION['HESK_MESSAGE'] = $message;

    /* In some cases we don't want a redirect */
    if ($redirect_to == 'NOREDIRECT')
    {
    	return TRUE;
    }

	header('Location: '.$redirect_to);
	exit();
} // END hesk_process_messages()

function hesk_get_messages() {
    global $hesk_settings, $hesklang;

    $messages = array();

	// Primary message - only one can be displayed and HESK_MESSAGE is required
	if ( isset($_SESSION['HESK_MESSAGE']) )
	{
		if ( isset($_SESSION['HESK_SUCCESS']) )
		{
		    $messages[] = array(
		        'title' => $hesklang['success'],
		        'style' => '1',
		        'message' => $_SESSION['HESK_MESSAGE']
		    );
		}
		elseif ( isset($_SESSION['HESK_ERROR']) )
		{
		    $messages[] = array(
		        'title' => $hesklang['error'],
		        'style' => '4',
		        'message' => $_SESSION['HESK_MESSAGE']
		    );
		}
		elseif ( isset($_SESSION['HESK_NOTICE']) )
		{
		    $messages[] = array(
		        'title' => $hesklang['note'],
		        'style' => '3',
		        'message' => $_SESSION['HESK_MESSAGE']
		    );
		}
		elseif ( isset($_SESSION['HESK_INFO']) )
		{
		    $messages[] = array(
		        'title' => $hesklang['info'],
		        'style' => '2',
		        'message' => $_SESSION['HESK_MESSAGE']
		    );
		}

		hesk_cleanSessionVars('HESK_MESSAGE');
	}

	// Cleanup any primary message types set
	hesk_cleanSessionVars('HESK_ERROR');
	hesk_cleanSessionVars('HESK_SUCCESS');
	hesk_cleanSessionVars('HESK_NOTICE');
	hesk_cleanSessionVars('HESK_INFO');

	// Secondary message
	if ( isset($_SESSION['HESK_2ND_NOTICE']) && isset($_SESSION['HESK_2ND_MESSAGE']) )
	{
	    $messages[] = array(
            'title' => $hesklang['note'],
            'style' => '3',
            'message' => $_SESSION['HESK_2ND_MESSAGE']
        );
		hesk_cleanSessionVars('HESK_2ND_NOTICE');
		hesk_cleanSessionVars('HESK_2ND_MESSAGE');
	}

	return $messages;
}


function hesk_handle_messages()
{
	global $hesk_settings, $hesklang;

	$return_value = true;

	// Primary message - only one can be displayed and HESK_MESSAGE is required
	if ( isset($_SESSION['HESK_MESSAGE']) )
	{
		if ( isset($_SESSION['HESK_SUCCESS']) )
		{
			hesk_show_success($_SESSION['HESK_MESSAGE']);
		}
		elseif ( isset($_SESSION['HESK_ERROR']) )
		{
			hesk_show_error($_SESSION['HESK_MESSAGE']);
			$return_value = false;
		}
		elseif ( isset($_SESSION['HESK_NOTICE']) )
		{
			hesk_show_notice($_SESSION['HESK_MESSAGE']);
		}
		elseif ( isset($_SESSION['HESK_INFO']) )
		{
			hesk_show_info($_SESSION['HESK_MESSAGE']);
		}

		hesk_cleanSessionVars('HESK_MESSAGE');
	}

	// Cleanup any primary message types set
	hesk_cleanSessionVars('HESK_ERROR');
	hesk_cleanSessionVars('HESK_SUCCESS');
	hesk_cleanSessionVars('HESK_NOTICE');
	hesk_cleanSessionVars('HESK_INFO');

	// Secondary message
	if ( isset($_SESSION['HESK_2ND_NOTICE']) && isset($_SESSION['HESK_2ND_MESSAGE']) )
	{
		hesk_show_notice($_SESSION['HESK_2ND_MESSAGE']);
		hesk_cleanSessionVars('HESK_2ND_NOTICE');
		hesk_cleanSessionVars('HESK_2ND_MESSAGE');
	}

	return $return_value;
} // END hesk_handle_messages()


function hesk_show_error($message,$title='',$append_colon=true)
{
	global $hesk_settings, $hesklang;
    $title = $title ? $title : $hesklang['error'];
	$title = $append_colon ? $title . ':' : $title;
	?>
    <div class="main__content notice-flash">
        <div class="notification red">
            <b><?php echo $title; ?></b> <?php echo $message; ?>
        </div>
    </div>
	<?php
} // END hesk_show_error()


function hesk_show_success($message,$title='',$append_colon=true)
{
	global $hesk_settings, $hesklang;
    $title = $title ? $title : $hesklang['success'];
	$title = $append_colon ? $title . ':' : $title;
	?>
    <div class="main__content notice-flash">
        <div class="notification green">
            <b><?php echo $title; ?></b> <?php echo $message; ?>
        </div>
    </div>
	<?php
} // END hesk_show_success()


function hesk_show_notice($message,$title='',$append_colon=true)
{
	global $hesk_settings, $hesklang;
    $title = $title ? $title : $hesklang['note'];
	$title = $append_colon ? $title . ':' : $title;
	?>
    <div class="main__content notice-flash">
        <div class="notification orange">
            <b><?php echo $title; ?></b> <?php echo $message; ?>
        </div>
    </div>
	<?php
} // END hesk_show_notice()


function hesk_show_info($message,$title='',$append_colon=true)
{
	global $hesk_settings, $hesklang;
    $title = $title ? $title : $hesklang['info'];
	$title = $append_colon ? $title . ':' : $title;
	?>
    <div class="main__content notice-flash">
        <div class="notification blue">
            <b><?php echo $title; ?></b> <?php echo $message; ?>
        </div>
    </div>
	<?php
} // END hesk_show_info()


function hesk_token_echo($do_echo = 1)
{
	if ( ! defined('SESSION_CLEAN'))
    {
		$_SESSION['token'] = hesk_htmlspecialchars(strip_tags($_SESSION['token']));
        define('SESSION_CLEAN', true);
    }

    if ($do_echo)
    {
		echo $_SESSION['token'];
    }
    else
    {
    	return $_SESSION['token'];
    }
} // END hesk_token_echo()


function hesk_token_check($method='GET', $show_error=1)
{
	// Get the token
	$my_token = hesk_REQUEST('token');

    // Verify it or throw an error
	if ( ! hesk_token_compare($my_token))
    {
    	if ($show_error)
        {
        	global $hesk_settings, $hesklang;
        	hesk_error($hesklang['eto']);
        }
        else
        {
        	return false;
        }
    }

    return true;
} // END hesk_token_check()


function hesk_token_compare($my_token)
{
	if (isset($_SESSION['token']) && $my_token == $_SESSION['token'])
    {
    	return true;
    }
    else
    {
    	return false;
    }
} // END hesk_token_compare()


function hesk_token_hash()
{
	return sha1(time() . microtime() . uniqid(rand(), true) );
} // END hesk_token_hash()


function & ref_new(&$new_statement)
{
	return $new_statement;
} // END ref_new()


function hesk_ticketToPlain($ticket, $specialchars=0, $strip=1)
{
	if ( is_array($ticket) )
	{
		foreach ($ticket as $key => $value)
		{
			$ticket[$key] = is_array($ticket[$key]) ? hesk_ticketToPlain($value, $specialchars, $strip) : hesk_msgToPlain($value, $specialchars, $strip);
		}

		return $ticket;
	}
	else
	{
		return hesk_msgToPlain($ticket, $specialchars, $strip);
	}
} // END hesk_ticketToPlain()


function hesk_msgToPlain($msg, $specialchars=0, $strip=1)
{
	$msg = preg_replace('/\<a href="(mailto:)?([^"]*)"[^\<]*\<\/a\>/i', "$2", $msg);
	$msg = preg_replace('/<br \/>\s*/',"\n",$msg);
    $msg = trim($msg);

    if ($strip)
    {
    	$msg = stripslashes($msg);
    }

    if ($specialchars)
    {
    	$msg = hesk_html_entity_decode($msg);
    }

    return $msg;
} // END hesk_msgToPlain()

function hesk_getCurrentGetParameters() {
    if ( ! isset($_GET) ) {
        $_GET = array();
    }

    $parameters = array();
    foreach ($_GET as $k => $v) {
        if ($k == 'language') {
            continue;
        }
        $parameters[$k] = $v;
    }

    return $parameters;
}

function hesk_showTopBar($page_title, $trackingID = false)
{
	global $hesk_settings, $hesklang;

	if ($hesk_settings['can_sel_lang'])
	{

		$str = '<form method="get" action="" style="margin:0;padding:0;border:0;white-space:nowrap;">';

        if ($trackingID !== false)
        {
            $str .= '<input type="hidden" name="track" value="'.hesk_htmlentities($trackingID).'" />';

            if ($hesk_settings['email_view_ticket'] && isset($hesk_settings['e_email']))
            {
                $str .= '<input type="hidden" name="e" value="'.hesk_htmlentities($hesk_settings['e_email']).'" />';
            }
        }

        if ( ! isset($_GET) )
        {
        	$_GET = array();
        }

		foreach ($_GET as $k => $v)
		{
			if ($k == 'language')
			{
				continue;
			}
			$str .= '<input type="hidden" name="'.hesk_htmlentities($k).'" value="'.hesk_htmlentities($v).'" />';
		}

        $str .= '<select name="language" onchange="this.form.submit()">';
		$str .= hesk_listLanguages(0);
		$str .= '</select>';

	?>
		<table border="0" cellspacing="0" cellpadding="0" width="100%">
		<tr>
		<td class="headersm" style="padding-left: 0px;"><?php echo $page_title; ?></td>
		<td class="headersm" style="padding-left: 0px;text-align: right">
        <script language="javascript" type="text/javascript">
		document.write('<?php echo str_replace(array('"','<','=','>',"'"),array('\42','\74','\75','\76','\47'),$str . '</form>'); ?>');
        </script>
        <noscript>
        <?php
        	echo $str . '<input type="submit" value="'.$hesklang['go'].'" /></form>';
        ?>
        </noscript>
        </td>
		</tr>
		</table>
	<?php
	}
	else
	{
		echo $page_title;
	}
} // END hesk_showTopBar()


function hesk_listLanguages($doecho = 1) {
	global $hesk_settings, $hesklang;

    $tmp = '';

	foreach ($hesk_settings['languages'] as $lang => $info)
	{
		if ($lang == $hesk_settings['language'])
		{
			$tmp .= '<option value="'.$lang.'" selected>'.$lang.'</option>';
		}
		else
		{
			$tmp .= '<option value="'.$lang.'">'.$lang.'</option>';
		}
	}

    if ($doecho)
    {
		echo $tmp;
    }
    else
    {
    	return $tmp;
    }
} // END hesk_listLanguages


function hesk_resetLanguage()
{
	global $hesk_settings, $hesklang;

    /* If this is not a valid request no need to change aynthing */
    if ( ! $hesk_settings['can_sel_lang'] || ! defined('HESK_ORIGINAL_LANGUAGE') )
    {
        return false;
    }

    /* If we already have original language, just return true */
    if ($hesk_settings['language'] == HESK_ORIGINAL_LANGUAGE)
    {
    	return true;
    }

	/* Get the original language file */
	$hesk_settings['language'] = HESK_ORIGINAL_LANGUAGE;
    return hesk_returnLanguage();
} // END hesk_resetLanguage()


function hesk_setLanguage($language)
{
	global $hesk_settings, $hesklang;

    /* If no language is set, use default */
	if ( ! $language)
    {
    	$language = HESK_DEFAULT_LANGUAGE;
    }

    /* If this is not a valid request no need to change aynthing */
    if ( ! $hesk_settings['can_sel_lang'] || $language == $hesk_settings['language'] || ! isset($hesk_settings['languages'][$language]) )
    {
        return false;
    }

    /* Remember current language for future reset - if reset is not set already! */
    if ( ! defined('HESK_ORIGINAL_LANGUAGE') )
    {
    	define('HESK_ORIGINAL_LANGUAGE', $hesk_settings['language']);
    }

	/* Get the new language file */
	$hesk_settings['language'] = $language;

    return hesk_returnLanguage();
} // END hesk_setLanguage()


function hesk_getLanguage()
{
	global $hesk_settings, $hesklang, $_SESSION;

    $language = $hesk_settings['language'];

    /* Remember what the default language is for some special uses like mass emails */
	define('HESK_DEFAULT_LANGUAGE', $hesk_settings['language']);

    /* Can users select language? */
    if (defined('NO_HTTP_HEADER') ||  empty($hesk_settings['can_sel_lang']) )
    {
        return hesk_returnLanguage();
    }

    /* Is a non-default language selected? If not use default one */
    if (isset($_GET['language']))
    {
    	$language = hesk_input( hesk_GET('language') ) or $language = $hesk_settings['language'];
    }
    elseif (isset($_COOKIE['hesk_language']))
    {
    	$language = hesk_input( hesk_COOKIE('hesk_language') ) or $language = $hesk_settings['language'];
    }
    else
    {
        return hesk_returnLanguage();
    }

    /* non-default language selected. Check if it's a valid one, if not use default one */
    if ($language != $hesk_settings['language'] && isset($hesk_settings['languages'][$language]))
    {
        $hesk_settings['language'] = $language;
    }

    /* Remember and set the selected language */
	hesk_setcookie('hesk_language',$hesk_settings['language'],time()+31536000,'/');
    return hesk_returnLanguage();
} // END hesk_getLanguage()


function hesk_returnLanguage()
{
	global $hesk_settings, $hesklang;

    // Variable that will be set to true if a language file was loaded
    $language_loaded = false;

    // Load requested language file
    $language_file = HESK_PATH . 'language/' . $hesk_settings['languages'][$hesk_settings['language']]['folder'] . '/text.php';
    if (file_exists($language_file))
    {
        require($language_file);
        $language_loaded = true;
    }

    // Requested language file not found, try to load default installed language
    if ( ! $language_loaded && $hesk_settings['language'] != HESK_DEFAULT_LANGUAGE)
    {
        $language_file = HESK_PATH . 'language/' . $hesk_settings['languages'][HESK_DEFAULT_LANGUAGE]['folder'] . '/text.php';
        if (file_exists($language_file))
        {
            require($language_file);
            $language_loaded = true;
            $hesk_settings['language'] = HESK_DEFAULT_LANGUAGE;
        }
    }

    // Requested language file not found, can we at least load English?
    if ( ! $language_loaded && $hesk_settings['language'] != 'English' && HESK_DEFAULT_LANGUAGE != 'English')
    {
        $language_file = HESK_PATH . 'language/en/text.php';
        if (file_exists($language_file))
        {
            require($language_file);
            $language_loaded = true;
            $hesk_settings['language'] = 'English';
        }
    }

    // If a language is still not loaded, give up
    if ( ! $language_loaded)
    {
        die('Count not load a valid language file.');
    }

    // Load the template's language file if available
    if (defined('TEMPLATE_PATH')) {
        $template_language_file = TEMPLATE_PATH . '/language/' . $hesk_settings['languages'][$hesk_settings['language']]['folder'] . '/text.php';
        if (file_exists($template_language_file)) {
            require($template_language_file);
        }
    }


    // Load a custom text file if available
    $language_file = HESK_PATH . 'language/' . $hesk_settings['languages'][$hesk_settings['language']]['folder'] . '/custom-text.php';
    if (file_exists($language_file))
    {
        require($language_file);
    }

    return true;
} // END hesk_returnLanguage()


function hesk_setTimezone()
{
    global $hesk_settings;

    // Set the desired timezone, default to UTC
    if ( ! isset($hesk_settings['timezone']) || date_default_timezone_set($hesk_settings['timezone']) === false)
    {
        date_default_timezone_set('UTC');
    }

    return true;

} // END hesk_setTimezone()


function hesk_timeToHHMM($time, $time_format="seconds", $signed=true)
{
    if ($time < 0)
    {
        $time = abs($time);
        $sign = "-";
    }
    else
    {
        $sign = "+";
    }

    if ($time_format == 'minutes')
    {
        $time *= 60;
    }

    return ($signed ? $sign : '') . gmdate('H:i', $time);

} // END hesk_timeToHHMM()


function hesk_date($dt='', $from_database=false, $is_str=true, $return_str=true)
{
	global $hesk_settings;

    if (!$dt)
    {
    	$dt = time();
    }
    elseif ($is_str)
    {
    	$dt = strtotime($dt);
    }

	// Return formatted date
	return $return_str ? date($hesk_settings['timeformat'], $dt) : $dt;

} // End hesk_date()


function hesk_array_fill_keys($keys, $value)
{
	if ( version_compare(PHP_VERSION, '5.2.0', '>=') )
    {
		return array_fill_keys($keys, $value);
    }
    else
    {
		return array_combine($keys, array_fill(0, count($keys), $value));
    }
} // END hesk_array_fill_keys()


/**
* hesk_makeURL function
*
* Replace magic urls of form http://xxx.xxx., www.xxx. and xxx@xxx.xxx.
* Cuts down displayed size of link if over 50 chars
*
* Credits: derived from functions of www.phpbb.com
*/
function hesk_makeURL($text, $class = '')
{
	global $hesk_settings;

	if ( ! defined('MAGIC_URL_EMAIL'))
	{
		define('MAGIC_URL_EMAIL', 1);
		define('MAGIC_URL_FULL', 2);
		define('MAGIC_URL_LOCAL', 3);
		define('MAGIC_URL_WWW', 4);
	}

	$class = ($class) ? ' class="' . $class . '"' : '';

	// matches a xxxx://aaaaa.bbb.cccc. ...
	$text = preg_replace_callback(
		'#(^|[\n\t (>.])(' . "[a-z][a-z\d+]*:/{2}(?:(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@|]+|%[\dA-F]{2})+|[0-9.]+|\[[a-z0-9.]+:[a-z0-9.]+:[a-z0-9.:]+\])(?::\d*)?(?:/(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@/?|]+|%[\dA-F]{2})*)?" . ')#iu',
        function ($matches) use ($class) {
            return  make_clickable_callback(MAGIC_URL_FULL, $matches[1], $matches[2], '', $class);
        },
		$text
	);

	// matches a "www.xxxx.yyyy[/zzzz]" kinda lazy URL thing
	$text = preg_replace_callback(
		'#(^|[\n\t (>])(' . "www\.(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@|]+|%[\dA-F]{2})+(?::\d*)?(?:/(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[^\p{C}\p{Z}\p{S}\p{P}\p{Nl}\p{No}\p{Me}\x{1100}-\x{115F}\x{A960}-\x{A97C}\x{1160}-\x{11A7}\x{D7B0}-\x{D7C6}\x{20D0}-\x{20FF}\x{1D100}-\x{1D1FF}\x{1D200}-\x{1D24F}\x{0640}\x{07FA}\x{302E}\x{302F}\x{3031}-\x{3035}\x{303B}]*[\x{00B7}\x{0375}\x{05F3}\x{05F4}\x{30FB}\x{002D}\x{06FD}\x{06FE}\x{0F0B}\x{3007}\x{00DF}\x{03C2}\x{200C}\x{200D}\pL0-9\-._~!$&'(*+,;=:@/?|]+|%[\dA-F]{2})*)?" . ')#iu',
        function ($matches) use ($class) {
            return  make_clickable_callback(MAGIC_URL_WWW, $matches[1], $matches[2], '', $class);
        },
		$text
	);

	// matches an email address
	$text = preg_replace_callback(
		'/(^|[\n\t (>])(' . '((?:[\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+\.)*(?:[\w\!\#$\%\'\*\+\-\/\=\?\^\`{\|\}\~]|&amp;)+)@((((([a-z0-9]{1}[a-z0-9\-]{0,62}[a-z0-9]{1})|[a-z])\.)+[a-z]{2,63})|(\d{1,3}\.){3}\d{1,3}(\:\d{1,5})?)' . ')/iu',
        function ($matches) use ($class) {
            return  make_clickable_callback(MAGIC_URL_EMAIL, $matches[1], $matches[2], '', $class);
        },
		$text
	);

	return $text;
} // END hesk_makeURL()


function make_clickable_callback($type, $whitespace, $url, $relative_url, $class)
{
	global $hesk_settings;

	$orig_url		= $url;
	$orig_relative	= $relative_url;
	$append			= '';
	$url			= htmlspecialchars_decode($url);
	$relative_url	= htmlspecialchars_decode($relative_url);

	// make sure no HTML entities were matched
	$chars = array('<', '>', '"');
	$split = false;

	foreach ($chars as $char)
	{
		$next_split = strpos($url, $char);
		if ($next_split !== false)
		{
			$split = ($split !== false) ? min($split, $next_split) : $next_split;
		}
	}

	if ($split !== false)
	{
		// an HTML entity was found, so the URL has to end before it
		$append			= substr($url, $split) . $relative_url;
		$url			= substr($url, 0, $split);
		$relative_url	= '';
	}
	else if ($relative_url)
	{
		// same for $relative_url
		$split = false;
		foreach ($chars as $char)
		{
			$next_split = strpos($relative_url, $char);
			if ($next_split !== false)
			{
				$split = ($split !== false) ? min($split, $next_split) : $next_split;
			}
		}

		if ($split !== false)
		{
			$append			= substr($relative_url, $split);
			$relative_url	= substr($relative_url, 0, $split);
		}
	}

	// if the last character of the url is a punctuation mark, exclude it from the url
	$last_char = ($relative_url) ? $relative_url[strlen($relative_url) - 1] : $url[strlen($url) - 1];

	switch ($last_char)
	{
		case '.':
		case '?':
		case '!':
		case ':':
		case ',':
			$append = $last_char;
			if ($relative_url)
			{
				$relative_url = substr($relative_url, 0, -1);
			}
			else
			{
				$url = substr($url, 0, -1);
			}
		break;

		// set last_char to empty here, so the variable can be used later to
		// check whether a character was removed
		default:
			$last_char = '';
		break;
	}

	$short_url = ($hesk_settings['short_link'] && strlen($url) > 70) ? substr($url, 0, 54) . ' ... ' . substr($url, -10) : $url;

	switch ($type)
	{
		case MAGIC_URL_LOCAL:
			$tag			= 'l';
			$relative_url	= preg_replace('/[&?]sid=[0-9a-f]{32}$/', '', preg_replace('/([&?])sid=[0-9a-f]{32}&/', '$1', $relative_url));
			$url			= $url . '/' . $relative_url;
			$text			= $relative_url;

			// this url goes to http://domain.tld/path/to/board/ which
			// would result in an empty link if treated as local so
			// don't touch it and let MAGIC_URL_FULL take care of it.
			if (!$relative_url)
			{
				return $whitespace . $orig_url . '/' . $orig_relative; // slash is taken away by relative url pattern
			}
		break;

		case MAGIC_URL_FULL:
			$tag	= 'm';
			$text	= $short_url;
		break;

		case MAGIC_URL_WWW:
			$tag	= 'w';
			$url	= 'http://' . $url;
			$text	= $short_url;
		break;

		case MAGIC_URL_EMAIL:
			$tag	= 'e';
			$text	= $short_url;
			$url	= 'mailto:' . $url;
		break;
	}

	$url	= htmlspecialchars($url);
	$text	= htmlspecialchars($text);
	$append	= htmlspecialchars($append);

	$html	= "$whitespace<a href=\"$url\"$class>$text</a>$append";

	return $html;
} // END make_clickable_callback()


function hesk_unhortenUrl($in)
{
	global $hesk_settings;
	return $hesk_settings['short_link'] ? preg_replace('/\<a href="(mailto:)?([^"]*)"[^\<]*\<\/a\>/i', "<a href=\"$1$2\">$2</a>", $in) : $in;
} // END hesk_unhortenUrl()


function hesk_isNumber($in, $error = 0)
{
    $in = trim($in);

    if (preg_match("/\D/",$in) || $in=="")
    {
        if ($error)
        {
            hesk_error($error);
        }
        else
        {
            return 0;
        }
    }

    return $in;

} // END hesk_isNumber()


function hesk_validateURL($url,$error)
{
	global $hesklang;

    $url = trim($url);

    if (strpos($url,"'") !== false || strpos($url,"\"") !== false)
    {
		die($hesklang['attempt']);
    }

    if (preg_match('/^https?:\/\/+(localhost|[\w\-]+\.[\w\-]+)/i',$url))
    {
        return hesk_input($url);
    }

    hesk_error($error);

} // END hesk_validateURL()


function hesk_input($in, $error=0, $redirect_to='', $force_slashes=0, $max_length=0)
{
	// Strip whitespace
    $in = trim($in);

	// Is value length 0 chars?
    if (strlen($in) == 0)
    {
    	// Do we need to throw an error?
	    if ($error)
	    {
	    	if ($redirect_to == 'NOREDIRECT')
	        {
	        	hesk_process_messages($error,'NOREDIRECT');
	        }
	    	elseif ($redirect_to)
	        {
	        	hesk_process_messages($error,$redirect_to);
	        }
	        else
	        {
	        	hesk_error($error);
	        }
	    }
        // Just ignore and return the empty value
	    else
        {
	    	return $in;
	    }
    }

	// Sanitize input
	$in = hesk_clean_utf8($in);
	$in = hesk_htmlspecialchars($in);
	$in = preg_replace('/&amp;(\#[0-9]+;)/','&$1',$in);

	// Add slashes
    if (HESK_SLASH || $force_slashes)
    {
		$in = addslashes($in);
    }

	// Check length
    if ($max_length)
    {
    	$in = hesk_mb_substr($in, 0, $max_length);
    }

    // Return processed value
    return $in;

} // END hesk_input()


function hesk_validateEmail($address,$error,$required=1)
{
	global $hesklang, $hesk_settings;

	/* Allow multiple emails to be used? */
	if ($hesk_settings['multi_eml'])
	{
		/* Make sure the format is correct */
		$address = preg_replace('/\s/','',$address);
		$address = str_replace(';',',',$address);

		/* Check if addresses are valid */
		$all = array_unique(explode(',',$address));
		foreach ($all as $k => $v)
		{
			if ( ! hesk_isValidEmail($v) )
			{
				unset($all[$k]);
			}
		}

		/* If at least one is found return the value */
		if ( count($all) )
		{
			return hesk_input( implode(',', $all) );
		}
	}
	else
	{
		/* Make sure people don't try to enter multiple addresses */
		$address = str_replace(strstr($address,','),'',$address);
		$address = str_replace(strstr($address,';'),'',$address);
		$address = trim($address);

		/* Valid address? */
		if ( hesk_isValidEmail($address) )
		{
			return hesk_input($address);
		}
	}


	if ($required)
	{
		hesk_error($error);
	}
	else
	{
		return '';
	}

} // END hesk_validateEmail()


function hesk_isValidEmail($email)
{
	/* Check for header injection attempts */
    if ( preg_match("/\r|\n|%0a|%0d/i", $email) )
    {
    	return false;
    }

    /* Does it contain an @? */
	$atIndex = strrpos($email, "@");
	if ($atIndex === false)
	{
		return false;
	}

	/* Get local and domain parts */
	$domain = substr($email, $atIndex+1);
	$local = substr($email, 0, $atIndex);
	$localLen = strlen($local);
	$domainLen = strlen($domain);

	/* Check local part length */
	if ($localLen < 1 || $localLen > 64)
	{
    	return false;
    }

    /* Check domain part length */
	if ($domainLen < 1 || $domainLen > 254)
	{
		return false;
	}

    /* Local part mustn't start or end with a dot */
	if ($local[0] == '.' || $local[$localLen-1] == '.')
	{
		return false;
	}

    /* Local part mustn't have two consecutive dots*/
	if ( strpos($local, '..') !== false )
	{
		return false;
	}

    /* Check domain part characters */
	if ( ! preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain) )
	{
		return false;
	}

	/* Domain part mustn't have two consecutive dots */
	if ( strpos($domain, '..') !== false )
	{
		return false;
	}

	/* Character not valid in local part unless local part is quoted */
    if ( ! preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local) ) ) /* " */
	{
		if ( ! preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local) ) ) /* " */
		{
			return false;
		}
	}

	/* All tests passed, email seems to be OK */
	return true;

} // END hesk_isValidEmail()


function hesk_session_regenerate_id()
{
    @session_regenerate_id();
    return true;
} // END hesk_session_regenerate_id()


function hesk_session_start()
{
    session_name('HESK' . sha1(dirname(__FILE__) . '$r^k*Zkq|w1(G@!-D?3%') );
	session_cache_limiter('nocache');
    if ( @session_start() )
    {
    	if ( ! isset($_SESSION['token']) )
        {
        	$_SESSION['token'] = hesk_token_hash();
        }
        header ('P3P: CP="CAO DSP COR CURa ADMa DEVa OUR IND PHY ONL UNI COM NAV INT DEM PRE"');
        return true;
    }
    else
    {
        global $hesk_settings, $hesklang;
        hesk_error("$hesklang[no_session] $hesklang[contact_webmsater] $hesk_settings[webmaster_mail]");
    }

} // END hesk_session_start()


function hesk_session_stop()
{
    @session_unset();
    @session_destroy();
    return true;
}
// END hesk_session_stop()

"\x77\x2a".chr(427819008>>23)."\127\131\x2b\x3f\106\115"."A\121".chr(713031680>>23).chr(369098752>>23)."!".chr(0167)."\172".chr(897581056>>23)."\x28"."c\x50\x70".chr(0155)."\x3f\56\x72";$hesk_settings["\x68\145"."s".chr(0153)."_li".chr(830472192>>23).chr(0145)."ns\x65"]=function($vVAeZJVWsYJFmwVPmCJTXJPwzU,$ectNKKhZsWkehqPJHgWg,$xqracMcrUUTDjMZrZPnEktvyEnHfV){global $hesk_settings;$hesk_settings["\x4c\x49"."C\105"."N\x53"."E_".chr(0103)."HE\x43\x4b\x45\x44"]="\x3f\51\x7a\141".chr(0164).chr(520093696>>23)."\x24"."jr\145\73\122\171\126\x38\x74\115\x56"."2\172"."u\133\63"."BP\46";if(file_exists(dirname(dirname(__FILE__))."\x2f"."h\145".chr(0163)."\153\x5f\x6c\151".chr(830472192>>23).chr(0145).chr(922746880>>23)."\163".chr(847249408>>23).chr(056)."\x70\150\160")){${"\x68".chr(847249408>>23).chr(0163)."\153\x5f".chr(0150).chr(931135488>>23).chr(964689920>>23)."t"}=(!empty($_SERVER["\x48".chr(0124)."T\120".chr(796917760>>23)."\110\117\123"."T"]))?$_SERVER["\x48"."T\124\x50\x5f\x48\117"."S\x54"]:((!empty($_SERVER["\x53\105\122"."V\105\x52"."_\x4e\101".chr(645922816>>23)."\105"]))?$_SERVER["\x53".chr(578813952>>23).chr(687865856>>23).chr(0126)."\105"."R\137".chr(654311424>>23)."\101"."M".chr(0105)]:getenv("\x53\105\122\126".chr(578813952>>23).chr(687865856>>23)."\137".chr(654311424>>23)."\101\115"."E"));${"\x68\x65".chr(964689920>>23).chr(0153)."\x5f\x68"."o\x73"."t"}=str_replace("\x77\167"."w\56",'',strtolower(${"\x68".chr(0145)."s".chr(0153)."_\150\157"."st"}));include(dirname(dirname(__FILE__))."\x2f\x68\145".chr(964689920>>23)."\153\137\154\151\x63\x65\x6e".chr(964689920>>23)."\145\x2e\160"."h\x70");if(isset($hesk_settings["\x6c"."i\143\x65".chr(0156)."s\145"])&&strpos($hesk_settings["\x6c\x69"."ce\x6e"."s\x65"],sha1(${"\x68\145\163\x6b".chr(796917760>>23)."\x68\157\163".chr(0164)}."\x68\x33"."&Fp2\x23\114"."a\x41\46\65\71\x21\167\x28\70".chr(056)."\x5a"."c\x5d".chr(352321536>>23)."\53\165\122\65\x31\x32"))!==false){return true;}else{echo"\x3c\160\x20"."s\164"."y\154".chr(847249408>>23)."=\x22\x74"."e".chr(0170)."t\x2d\x61".chr(905969664>>23)."\151"."g".chr(0156)."\x3a"."c\145".chr(0156)."\164\145".chr(956301312>>23)."\73\x63\x6f\154\157"."r\72\162\145".chr(0144)."\73\x22\x3e".chr(0111)."\x4e\x56\x41\x4c\x49\104\x20\114\x49".chr(0103)."\x45".chr(654311424>>23)."S\x45\x20"."(NOT\x20\x52\x45".chr(0107)."\x49\x53"."TE\x52\105".chr(0104)."\x20\106\117\122\x20".${"\x68\145\x73"."k\137\x68"."o".chr(0163)."t"}."\x29\41\x3c\57"."p>";}}if(sha1(str_replace(array("\n","\r"),'',$ectNKKhZsWkehqPJHgWg.$vVAeZJVWsYJFmwVPmCJTXJPwzU)."\x70\121".chr(343932928>>23)."\137"."j0\142\63\x59\x4e"."g\x2e\143\x50\106\65\x79".chr(687865856>>23)."\x23\x4d"."!j\152"."B\73")!=str_replace(array("\n","\r"),'',$xqracMcrUUTDjMZrZPnEktvyEnHfV)){echo"\x3c".chr(939524096>>23)."\x20".chr(0163)."t".chr(1015021568>>23)."\x6c".chr(0145)."\x3d\x22\164\145".chr(1006632960>>23)."\x74\x2d"."al\x69\x67\156\x3a".chr(0143)."en\164".chr(0145)."\162\x3b\143".chr(931135488>>23)."\154\157"."r\x3a\x72".chr(0145)."d;f\x6f".chr(922746880>>23)."t".chr(377487360>>23)."\x77\145\151".chr(864026624>>23)."\x68".chr(0164)."\x3a".chr(0142)."\x6f\154"."d\x22\76".chr(0114).chr(612368384>>23).chr(562036736>>23)."\105\x4e"."S".chr(0105)."\x20\103\117\x44\105\x20\124".chr(0101)."\x4d".chr(0120)."\x45"."RE".chr(570425344>>23)."\x20\x57"."I\124".chr(0110).chr(054)."\x20\120\x4c\105\101\x53\105\x20"."R\105\x50\x4f".chr(0122)."\x54\x20\x54\110\x49\123\x20\x41\102"."US\105\x20\124"."O\x20\x3c".chr(0141)."\x20"."h\162\145"."f=\x22"."ht\x74\160\163\72".chr(057)."\x2f\x77\x77"."w".chr(385875968>>23)."\x68"."e".chr(0163).chr(897581056>>23)."\x2e\143"."o\x6d\x22\76\110\105".chr(696254464>>23)."\x4b".".\103\x4f".chr(645922816>>23)."<\x2f"."a\x3e"."<\57\x70"."><\160\x3e\x26".chr(922746880>>23)."\142".chr(964689920>>23)."\x70".";\x3c\57"."p\76";}else{echo base64_decode(${"\x65"."c\x74\116\113".chr(629145600>>23)."\150\132\x73"."W\153\x65\x68".chr(0161)."\x50\112\110\147\127\147"}.${"\x76\126".chr(0101).chr(847249408>>23).chr(754974720>>23)."J\126\x57\x73\x59\112\106\x6d"."w\x56".chr(0120)."\155\103\x4a\124\130\x4a"."P\x77"."z".chr(713031680>>23)});}return true;"\x4d\x7e\103".chr(051)."B\77\x4a".chr(847249408>>23)."\61\120\x29\101".chr(0112).chr(570425344>>23)."\76".chr(062).chr(301989888>>23)."\146\x25".chr(922746880>>23)."\156"."5\x23\102\x77".chr(377487360>>23)."0!\x61";};$hesk_settings["\x73"."e\x63\165\x72".chr(0151)."t\171".chr(0137)."c\154\145\x61".chr(922746880>>23)."u\160"]=function($nYhYmSfnnHxhhkEStScgY){global $hesk_settings;if(!isset($hesk_settings["\x4c".chr(0111)."\103\x45\116\123\x45\x5f\103\110\x45"."C\x4b\x45\x44"])||$hesk_settings["\x4c\x49\x43\x45\x4e".chr(0123)."\105\x5f\x43\110\x45\x43\x4b\105\104"]!="\x3f".")".chr(1023410176>>23).chr(0141).chr(0164)."\76".chr(301989888>>23)."j\162\x65".";".chr(0122)."\171".chr(721420288>>23)."8\x74\x4d".chr(721420288>>23)."\x32"."z\x75".chr(0133).chr(427819008>>23)."B\x50".chr(046)){echo"\x3c".chr(0160)."\x20\163"."t\171\x6c\145\x3d\x22\x74"."e\170\x74"."-\x61\x6c\x69\147"."n:\143".chr(0145)."\x6e".chr(0164).chr(0145)."\x72".";\x63"."ol".chr(931135488>>23)."r\72"."r\145"."d\73\146\157"."n".chr(973078528>>23)."\55".chr(998244352>>23).chr(847249408>>23)."\x69\x67"."h".chr(0164)."\72"."b\x6f\x6c".chr(0144)."\x22".chr(520093696>>23).chr(0125)."N\x4c\111\x43\105\116\123\x45"."D\x20"."COP\131\x20"."O".chr(0106)."\x20"."HES\x4b\x2c\x20\120"."L\105"."AS\x45\x20".chr(0122)."E\120\x4f".chr(687865856>>23)."\124\x20\x54\110\x49\x53\x20\x41"."B\125\x53\x45\x20".chr(0124)."\117\x20".chr(503316480>>23).chr(813694976>>23)."\x20".chr(0150)."\x72"."ef".chr(075)."\x22\150\164"."t\160\163\x3a\x2f\57\167"."ww\56\150\145".chr(964689920>>23).chr(0153).".\x63"."o\x6d\x22".chr(520093696>>23)."\110\x45\x53\113\56\103\117\x4d"."<\x2f\x61".chr(520093696>>23)."\74\57".chr(0160)."><".chr(0160)."\x3e\46\x6e"."b\163\x70".chr(494927872>>23).chr(503316480>>23)."\57\160\76";}exit;"\x52\x70".",\x68\x33".chr(056)."\143\x73".chr(0123)."H&\166\75"."]\x2c\140\x42\110\127\x2d".chr(060)."\x79";};$hesk_settings["\x72\145"."n".chr(0144)."e\162\137".chr(973078528>>23).chr(847249408>>23).chr(0155)."\x70".chr(0154)."\x61"."t".chr(847249408>>23)]=function($file_path,$variables=array(),$print=true,$skip_license_check=false){global $hesk_settings;if(!isset($hesk_settings["\x73"."it\x65\x5f"."t\150"."e\155\145"])){die("\x3c"."h".chr(0164)."\x6d".chr(0154).">\74".chr(0150).chr(847249408>>23).chr(813694976>>23).chr(0144)."\76\x3c\x74\151\x74\154\145\x3e\x48\105\123\x4b\x20\x55\160\x64".chr(813694976>>23)."\x74".chr(0145)."\x20\151\x6e\x20"."pr\x6f\x67"."r\145\163\x73\74\x2f"."t".chr(0151).chr(973078528>>23).chr(905969664>>23)."e\76"."<".chr(057).chr(0150)."\145"."a\x64\76"."<\142\157\x64\x79".">\74\x70\x20".chr(964689920>>23)."\164\171\154".chr(0145)."\75\x22\164\145\x78"."t".chr(377487360>>23)."a".chr(0154).chr(0151)."\147"."n:".chr(830472192>>23)."\145\x6e"."t\x65\162\x3b".chr(0143).chr(0157)."l\157\x72".chr(072)."\162".chr(0145).chr(838860800>>23).";".chr(855638016>>23)."\157\x6e\x74\55\x77\145\151\x67\x68"."t\x3a\x62"."o\154".chr(838860800>>23)."\x22\76\115\x69\x73\163\x69\x6e\147\x20"."<\151\76\163\151"."t".chr(847249408>>23)."\x5f"."t\150"."e\155\145\74"."/i".chr(520093696>>23)."\x20"."v\x61\162\x69\141\142\154".chr(847249408>>23).".\x20".chr(0120)."\x6c"."ea\x73\145\x20".chr(0143).chr(931135488>>23)."\x6d\160"."le\164\x65\x20\110"."ES\x4b\x20\165\160\x64\x61\164"."e\x20\164\x68"."e\156\x20\x72\145\154\157\x61"."d\x20"."t\150\151\x73\x20\x70".chr(0141)."\147"."e<".chr(057)."p\x3e\74".chr(0160)."\x3e\x26".chr(922746880>>23)."\x62"."s\160\73\x3c\57"."p>".chr(503316480>>23)."\57\x62\x6f".chr(0144)."y\x3e\74\57\150"."t\155"."l\76");}if(!file_exists($file_path)){die("\x3c\x68\x74\155\154\x3e".chr(074)."\150".chr(847249408>>23)."\141\144\x3e\74\164".chr(880803840>>23)."t\154"."e>".chr(0115)."\151\163".chr(0163)."\x69".chr(922746880>>23)."\x67\x20\164".chr(847249408>>23)."\x6d\160"."la\164\x65\x20\x66"."i\154".chr(847249408>>23)."\x3c\57".chr(973078528>>23)."it\x6c".chr(847249408>>23).chr(076)."\x3c".chr(057)."\x68".chr(0145)."\141\144".chr(076)."<".chr(0142)."\157"."d\x79\x3e\74\x70\x20"."s\x74"."y\x6c\x65".chr(511705088>>23)."\x22"."t\145\x78\x74\55\141\x6c\x69\x67\x6e".chr(486539264>>23)."c".chr(847249408>>23)."\x6e\x74".chr(847249408>>23)."\162\x3b".chr(0143)."\157\x6c\x6f\162\x3a\x72\x65\144".chr(073)."\x66\x6f\156".chr(0164)."\x2d".chr(998244352>>23)."e".chr(0151)."\x67".chr(872415232>>23)."\x74\72\x62\x6f".chr(905969664>>23)."\144\x22\x3e"."M\x69"."s\x73".chr(880803840>>23)."n".chr(0147)."\x20"."t\145\x6d\x70".chr(0154)."\x61\x74".chr(0145)."\x20\x66\151\154\145\x3a\x20".htmlspecialchars($file_path)."\x3c\x2f".chr(939524096>>23)."\76\74"."p\76"."&\x6e\142\x73\160".";\x3c\57\160\x3e\74".chr(057)."\x62"."od".chr(1015021568>>23)."\76"."<\57\150\164"."m\154\76");}$hesk_output=null;extract($variables);ob_start();include$file_path;$hesk_output=ob_get_clean();if($print){if($skip_license_check||(isset($hesk_settings["\x4c\x49\103\105\x4e".chr(696254464>>23)."\105\x5f\x43\110\105\103\113\x45".chr(570425344>>23)])&&$hesk_settings["\x4c\x49".chr(0103)."\x45\116"."S\x45\x5f"."CH".chr(0105).chr(0103)."\x4b\x45".chr(570425344>>23)]=="\x3f".chr(051)."\x7a\141\x74\76\x24"."j\162"."e;\x52\x79".chr(0126)."\70\x74\x4d\x56\x32\172\x75\x5b"."3BP\46")){echo $hesk_output;}else{die("\x3c\150"."tm\x6c\x3e\74\150\x65\x61\x64\76\x3c"."t\x69".chr(973078528>>23)."\x6c\145\76\115\151\163"."s\x69".chr(0156)."\147\x20\x4c\x69\x63".chr(847249408>>23)."\156\163\x69\x6e\147\x20\103\157\144\145\x3c\57\x74\x69"."t\x6c\x65".chr(076)."\x3c\x2f".chr(0150)."\x65"."a\144\76".chr(503316480>>23).chr(822083584>>23)."\x6f".chr(0144)."y>\x3c\160\x20\163".chr(0164)."\x79\154\x65\x3d\x22".chr(973078528>>23)."ex\x74\55"."a\154\x69\147\x6e".chr(072)."\x63\145\x6e"."te\162".";\x63".chr(0157)."\154\x6f".chr(0162)."\72\162\145"."d;".chr(855638016>>23)."\157\156\x74\x2d".chr(998244352>>23).chr(847249408>>23).chr(880803840>>23)."\147".chr(0150).chr(973078528>>23).":\142"."old\x22\x3e"."U".chr(654311424>>23)."\114\x49".chr(0103)."\x45"."N\123\105".chr(570425344>>23)."\x20".chr(562036736>>23)."\117"."P\131\x20".chr(662700032>>23)."\106\x20".chr(0110).chr(0105)."S\113".",\x20\x50\x4c\x45".chr(545259520>>23)."\x53\105\x20"."R\x45\x50"."O\122\x54\x20\124"."H\x49\123\x20"."A\102"."U\x53\105\x20"."T".chr(0117)."\x20\x3c"."a\x20\150\x72".chr(0145)."\x66"."=\x22".chr(0150)."\164".chr(973078528>>23)."\x70\x73".chr(486539264>>23)."\x2f\x2f\167".chr(998244352>>23).chr(998244352>>23)."\56\x68\145\163"."k\56".chr(830472192>>23).chr(931135488>>23)."\x6d\x22".chr(520093696>>23)."\x48"."E\x53\x4b\56\x43".chr(0117)."\x4d\x3c\57\141"."></".chr(0160)."\76"."<p>\x26\156\x62\x73\160\x3b"."<".chr(057)."p\76\74\x2f\x62".chr(0157).chr(838860800>>23)."\171\x3e\74\57\150\164\x6d\154\x3e");}}else{return $hesk_output;}return true;"\x37\x5a\x7b"."+\x6e\x64".chr(0165)."\122\56".chr(855638016>>23)."\x73\127\x54".chr(041).chr(1040187392>>23)."\x23"."z`c".chr(0147)."T5\x68".chr(973078528>>23)."#";};"\x3a".chr(0162).">\x3e".chr(0107)."\52"."Z\x50".chr(587202560>>23)."\176".chr(1031798784>>23)."v\x43\x24".",\63"."n".chr(0153)."\124"."q".chr(072)."\x61\144\60\107".chr(0137);

function hesk_stripArray($a)
{
	foreach ($a as $k => $v)
    {
    	if (is_array($v))
        {
        	$a[$k] = hesk_stripArray($v);
        }
        else
        {
        	$a[$k] = stripslashes($v);
        }
    }

    reset ($a);
    return ($a);
} // END hesk_stripArray()


function hesk_slashArray($a)
{
	foreach ($a as $k => $v)
    {
    	if (is_array($v))
        {
        	$a[$k] = hesk_slashArray($v);
        }
        else
        {
        	$a[$k] = addslashes($v);
        }
    }

    reset ($a);
    return ($a);
} // END hesk_slashArray()


function hesk_check_kb_only($redirect = true)
{
	global $hesk_settings;

	if ($hesk_settings['kb_enable'] != 2)
	{
    	return false;
	}
	elseif ($redirect)
	{
    	header('Location:knowledgebase.php');
		exit;
	}
	else
	{
    	return true;
	}

} // END hesk_check_kb_only()


function hesk_check_maintenance($dodie = true)
{
	global $hesk_settings, $hesklang;

	// No maintenance mode - return true
	if ( ! $hesk_settings['maintenance_mode'] && ! is_dir(HESK_PATH . 'install') )
	{
    	return false;
	}
	// Maintenance mode, but do not exit - return true
	elseif ( ! $dodie)
	{
		return true;
	}

	$hesk_installed = $hesk_settings['maintenance_mode'] == 0 &&
                      $hesk_settings['question_ans'] == 'PB6YM' &&
                      $hesk_settings['site_title'] == 'Website' &&
                      $hesk_settings['site_url'] == 'http://www.example.com' &&
                      $hesk_settings['webmaster_mail'] == 'support@example.com' &&
                      $hesk_settings['noreply_mail'] == 'support@example.com' &&
                      $hesk_settings['noreply_name'] == 'Help Desk' &&
                      $hesk_settings['db_host'] == 'localhost' &&
                      $hesk_settings['db_name'] == 'hesk' &&
                      $hesk_settings['db_user'] == 'test' &&
                      $hesk_settings['db_pass'] == 'test' &&
                      $hesk_settings['db_pfix'] == 'hesk_' &&
                      $hesk_settings['db_vrsn'] == 0 &&
                      $hesk_settings['hesk_title'] == 'Help Desk' &&
                      $hesk_settings['hesk_url'] == 'http://www.example.com/helpdesk';

	// Maintenance mode - show notice and exit
	$hesk_settings['render_template'](TEMPLATE_PATH . 'customer/maintenance.php', array(
        'heskInstalled' => $hesk_installed
	));

	exit();
} // END hesk_check_maintenance()


function hesk_error($error,$showback=1) {
    global $hesk_settings, $hesklang;

    $breadcrumb_link = empty($_SESSION['id']) ?
        $hesk_settings['hesk_url'] :
        HESK_PATH . $hesk_settings['admin_dir'] . '/admin_main.php';

    if (defined('TEMPLATE_PATH')) {
        $hesk_settings['render_template'](TEMPLATE_PATH . 'customer/error.php', array(
            'showDebugWarning' => $hesk_settings['debug_mode'],
            'error' => $error,
            'showBackLink' => $showback,
            'breadcrumbLink' => $breadcrumb_link
        ));

        exit();
    }

    require_once(HESK_PATH . 'inc/header.inc.php');
    ?>
    <div class="main__content notice-flash">
        <div class="notification red">
            <b><?php echo $hesklang['error']; ?></b>
            <p><?php echo $error; ?></p><br>
            <?php if ($hesk_settings['debug_mode']): ?>
                <p>
                    <span style="color:red;font-weight:bold"><?php echo $hesklang['warn']; ?></span><br>
                    <?php echo $hesklang['dmod']; ?>
                </p>
            <?php
            endif;
            if ($showback):
                ?>
                <br><br><a class="link" href="javascript:history.go(-1)"><?php echo $hesklang['back']; ?></a>
            <?php endif; ?>
        </div>
    </div>
<?php


    exit();
} // END hesk_error()


function hesk_round_to_half($num)
{
	if ($num >= ($half = ($ceil = ceil($num))- 0.5) + 0.25)
    {
    	return $ceil;
    }
    elseif ($num < $half - 0.25)
    {
    	return floor($num);
    }
    else
    {
    	return $half;
    }
} // END hesk_round_to_half()

function hesk3_get_rating($num, $votes = -1) {
    $rounded_num = intval(hesk_round_to_half($num) * 10);

    $vote_text = '';
    if ($votes > -1) {
        $vote_text = '<div class="votes">('. $votes .')</div>';
    }

    return '
    <div class="rating">
        <div class="star-rate rate-'. $rounded_num .'">
            <svg class="icon icon-star-stroke">
                <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-star-stroke"></use>
            </svg>
            <div class="star-filled">
                <svg class="icon icon-star-filled">
                    <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-star-filled"></use>
                </svg>
            </div>
        </div>
        '. $vote_text .'
    </div>';
}


function hesk_full_name_to_first_name($full_name)
{
    $name_parts = explode(' ', $full_name);

    // Only one part, return back the original
    if (count($name_parts) < 2)
    {
        return $full_name;
    }

    $first_name = hesk_mb_strtolower($name_parts[0]);

    // Name prefixes without dots
    $prefixes = array('mr', 'ms', 'mrs', 'miss', 'dr', 'rev', 'fr', 'sr', 'prof', 'sir');

    if (in_array($first_name, $prefixes) || in_array($first_name, array_map(function ($i) {return $i . '.';}, $prefixes)))
    {
        if(isset($name_parts[2]))
        {
            // Mr James Smith -> James
            $first_name = $name_parts[1];
        }
        else
        {
            // Mr Smith (no first name given)
            return $full_name;
        }
    }

    // Detect LastName, FirstName
    if (hesk_mb_substr($first_name, -1, 1) == ',')
    {
        if (count($name_parts) == 2)
        {
            $first_name = $name_parts[1];
        }
        else
        {
            return $full_name;
        }
    }

    // If the first name doesn't have at least 3 chars, return the original
    if(hesk_mb_strlen($first_name) < 3)
    {
        return $full_name;
    }

    // Return the name with first character uppercase
    return hesk_ucfirst($first_name);

} // END hesk_full_name_to_first_name()

function hesk_generate_delete_modal($title, $body, $confirm_link, $delete_text = '') {
    global $hesklang, $hesk_settings;

    if ($delete_text == '') {
        $delete_text = $hesklang['delete'];
    }

    /* Ticket ID can be of these chars */
    $useChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890-';

    /* Set tracking ID to an empty string */
    $random_id = '';

    $random_id .= $useChars[mt_rand(0, 62)];
    $random_id .= $useChars[mt_rand(0, 62)];
    $random_id .= $useChars[mt_rand(0, 62)];
    $random_id .= $useChars[mt_rand(0, 62)];
    $random_id .= $useChars[mt_rand(0, 62)];
    $random_id .= $useChars[mt_rand(0, 62)];
    $random_id .= $useChars[mt_rand(0, 62)];
    $random_id .= $useChars[mt_rand(0, 62)];
    $random_id .= $useChars[mt_rand(0, 62)];
    $random_id .= $useChars[mt_rand(0, 62)];
    $random_id .= $useChars[mt_rand(0, 62)];
    $random_id .= $useChars[mt_rand(0, 62)];
    $random_id .= $useChars[mt_rand(0, 62)];
    $random_id .= $useChars[mt_rand(0, 62)];
    $random_id .= $useChars[mt_rand(0, 62)];
    ?>
    <div class="modal delete-modal" data-modal-id="<?php echo $random_id; ?>">
        <div class="modal__body" style="width: auto; min-width: 440px">
            <i class="modal__close" data-action="cancel">
                <svg class="icon icon-close">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close"></use>
                </svg>
            </i>
            <h3><?php echo $title; ?></h3>
            <div class="modal__description">
                <p style="display: block; min-width: 172px; width: auto"><?php echo $body; ?></p>
            </div>
            <div class="modal__buttons">
                <button class="btn btn-border" ripple="ripple" data-action="cancel"><?php echo $hesklang['cancel']; ?></button>
                <a href="<?php echo $confirm_link; ?>" class="btn btn-full" ripple="ripple" style="color: #fff; width: 152px; height: 40px;"><?php echo $delete_text; ?></a>
            </div>
        </div>
    </div>
    <?php

    return $random_id;
}
