#!/usr/bin/php -q
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
define('HESK_PATH', dirname(dirname(dirname(__FILE__))) . '/');

// Do not send out the default UTF-8 HTTP header
define('NO_HTTP_HEADER',1);

// Get required files and functions
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');

//============================================================================//
//                           OPTIONAL MODIFICATIONS                           //
//============================================================================//

// Set category ID where new tickets will be submitted to
$set_category = 1;

// Set ticket priority of new tickets with the following options:
// -1  = use default category priority
//  0  = critical
//  1  = high
//  2  = medium
//  3  = low
$set_priority = -1;

// Uncomment lines below to use different IMAP login details than in settings
/*
$hesk_settings['imap']           = 1;
$hesk_settings['imap_job_wait']  = 15;
$hesk_settings['imap_host_name'] = 'imap.gmail.com';
$hesk_settings['imap_host_port'] = 993;
$hesk_settings['imap_enc']       = 'ssl';
$hesk_settings['imap_keep']      = 0;
$hesk_settings['imap_user']      = 'test@example.com';
$hesk_settings['imap_password']  = 'password';
*/

//============================================================================//
//                         END OPTIONAL MODIFICATIONS                         //
//============================================================================//

// Is this feature enabled?
if (empty($hesk_settings['imap']))
{
	die($hesklang['ifd']);
}

// Is IMAP available?
if ( ! function_exists('imap_open'))
{
	die($hesklang['iei']);
}

// Are we in maintenance mode?
if ( hesk_check_maintenance(false) )
{
	// If Debug mode is ON show "Maintenance mode" message
	$message = $hesk_settings['debug_mode'] ? $hesklang['mm1'] : '';
	die($message);
}

// Don't start IMAP fetching if an existing job is in progress
if ($hesk_settings['imap_job_wait'])
{
	// A log file used to store start of IMAP fetching
	$job_file = HESK_PATH . $hesk_settings['cache_dir'] . '/__imap-' . sha1(__FILE__) . '.txt';

	// If the job file already exists, wait for the previous job to complete unless expired
	if ( file_exists($job_file ) )
	{
		// Get time when the active IMAP fetching started
		$last = intval( file_get_contents($job_file) );

		// Give a running process at least X minutes to finish
		if ( $last + $hesk_settings['imap_job_wait'] * 60 > time() )
		{
			$message = $hesk_settings['debug_mode'] ? $hesklang['ifr'] : '';
			die($message);
		}
		else
		{
			// Start the process (force)
			file_put_contents($job_file, time() );
		}
	}
	else
	{
		// No job in progress, log when this one started
		file_put_contents($job_file, time() );
	}
}

// Get other required includes
require(HESK_PATH . 'inc/pipe_functions.inc.php');

// Tell Hesk we are in IMAP mode
define('HESK_IMAP', true);

// IMAP mailbox based on required encryption
switch ($hesk_settings['imap_enc'])
{
    case 'ssl':
        $hesk_settings['imap_mailbox'] = '{'.$hesk_settings['imap_host_name'].':'.$hesk_settings['imap_host_port'].'/imap/ssl/novalidate-cert}';
        break;
    case 'tls':
        $hesk_settings['imap_mailbox'] = '{'.$hesk_settings['imap_host_name'].':'.$hesk_settings['imap_host_port'].'/imap/tls/novalidate-cert}';
        break;
    default:
        $hesk_settings['imap_mailbox'] = '{'.$hesk_settings['imap_host_name'].':'.$hesk_settings['imap_host_port'].'}';
}

// Connect to IMAP
$imap = @imap_open($hesk_settings['imap_mailbox'], $hesk_settings['imap_user'], $hesk_settings['imap_password']);

// Connection successful?
if ($imap !== false)
{
    echo $hesk_settings['debug_mode'] ? "<pre>Connected to the IMAP server &quot;" . $hesk_settings['imap_mailbox'] . "&quot;.</pre>\n" : '';

    if($emails = imap_search($imap, 'UNSEEN'))
    {
        echo $hesk_settings['debug_mode'] ? "<pre>Unread messages found: " . count($emails) . "</pre>\n" : '';

        // Connect to the database
        hesk_dbConnect();

        // Download and parse each email
        foreach($emails as $email_number)
        {
            // Parse email from the stream
            $results = parser();

            // Convert email into a ticket (or new reply)
            if ( $id = hesk_email2ticket($results, 2, $set_category, $set_priority) )
            {
                echo $hesk_settings['debug_mode'] ? "<pre>Ticket $id created/updated.</pre>\n" : '';
            }
            else
            {
                echo $hesk_settings['debug_mode'] ? "<pre>Ticket NOT inserted - may be duplicate, blocked or an error.</pre>\n" : '';
            }

            // Queue message to be deleted on connection close
            if ( ! $hesk_settings['imap_keep'])
            {
                imap_delete($imap, $email_number);
            }

            echo $hesk_settings['debug_mode'] ? "<br /><br />\n\n" : '';
        }

        if ( ! $hesk_settings['imap_keep'])
        {
            imap_expunge($imap);
        }
    }
    else
    {
        echo $hesk_settings['debug_mode'] ? "<pre>No unread messages found.</pre>\n" : '';
    }

    // Close IMAP connection
    imap_close($imap);
    echo $hesk_settings['debug_mode'] ? "<pre>Disconnected from the IMAP server.</pre>\n" : '';
}

// Any error messages?
if($errors = imap_errors())
{
    if ($hesk_settings['debug_mode'])
    {
        foreach ($errors as $error)
        {
            echo "<pre>" . hesk_htmlspecialchars($error) . "</pre>\n";
        }
    }
    else
    {
        	echo "<h2>An error occured.</h2><p>For details turn <b>Debug mode</b> ON in settings and run this script again.</p>\n";
    }
}

// Remove active IMAP fetching log file
if ($hesk_settings['imap_job_wait'])
{
	unlink($job_file);
}

return NULL;
