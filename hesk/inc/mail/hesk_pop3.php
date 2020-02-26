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

// Uncomment lines below to use different POP3 login details than in settings
/*
$hesk_settings['pop3_host_name'] = 'mail.server.com';
$hesk_settings['pop3_host_port'] = 110;
$hesk_settings['pop3_tls']       = 0;
$hesk_settings['pop3_user']      = 'user@server.com';
$hesk_settings['pop3_password']  = 'password';
*/

//============================================================================//
//                         END OPTIONAL MODIFICATIONS                         //
//============================================================================//

// Is this feature enabled?
if (empty($hesk_settings['pop3']))
{
	die($hesklang['pfd']);
}

// Are we in maintenance mode?
if ( hesk_check_maintenance(false) )
{
	// If Debug mode is ON show "Maintenance mode" message
	$message = $hesk_settings['debug_mode'] ? $hesklang['mm1'] : '';
	die($message);
}

// Don't start POP3 fetching if an existing job is in progress
if ($hesk_settings['pop3_job_wait'])
{
	// A log file used to store start of POP3 fetching
	$job_file = HESK_PATH . $hesk_settings['cache_dir'] . '/__pop3-' . sha1(__FILE__) . '.txt';

	// If the job file already exists, wait for the previous job to complete unless expired
	if ( file_exists($job_file ) )
	{
		// Get time when the active POP3 fetching started
		$last = intval( file_get_contents($job_file) );

		// Give a running process at least 31 minutes to finish
		if ( $last + $hesk_settings['pop3_job_wait'] * 60 > time() )
		{
			$message = $hesk_settings['debug_mode'] ? $hesklang['pfr'] : '';
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

// Get POP3 class
require(HESK_PATH . 'inc/mail/pop3.php');

// Uncomment when using SASL authentication mechanisms
# require(HESK_PATH . 'inc/mail/sasl/sasl.php');

// If a pop3 wrapper is registered un register it, we need our custom wrapper
if ( in_array('pop3', stream_get_wrappers() ) )
{
    stream_wrapper_unregister('pop3');
}

// Register the pop3 stream handler class
stream_wrapper_register('pop3', 'pop3_stream');

// Setup required variables
$pop3 = new pop3_class;
$pop3->hostname	= $hesk_settings['pop3_host_name'];
$pop3->port		= $hesk_settings['pop3_host_port'];
$pop3->tls		= $hesk_settings['pop3_tls'];
$pop3->debug	= 0;
$pop3->join_continuation_header_lines = 1;

// Connect to POP3
if(($error=$pop3->Open())=="")
{
	echo $hesk_settings['debug_mode'] ? "<pre>Connected to the POP3 server &quot;" . $pop3->hostname . "&quot;.</pre>\n" : '';

	// Authenticate
	if(($error=$pop3->Login($hesk_settings['pop3_user'], hesk_htmlspecialchars_decode($hesk_settings['pop3_password'])))=="")
	{
		echo $hesk_settings['debug_mode'] ? "<pre>User &quot;" . $hesk_settings['pop3_user'] . "&quot; logged in.</pre>\n" : '';

		// Get number of messages and total size
		if(($error=$pop3->Statistics($messages,$size))=="")
		{
			echo $hesk_settings['debug_mode'] ? "<pre>There are $messages messages in the mail box with a total of $size bytes.</pre>\n" : '';

			// If we have any messages, process them
			if($messages>0)
			{
				// Connect to the database
				hesk_dbConnect();

				for ($message = 1; $message <= $messages; $message++)
				{
					echo $hesk_settings['debug_mode'] ? "<pre>Parsing message $message of $messages.</pre>\n" : '';

					$pop3->GetConnectionName($connection_name);
					$message_file = 'pop3://'.$connection_name.'/'.$message;

					// Parse the incoming email
					$results = parser($message_file);

					// Convert email into a ticket (or new reply)
					if ( $id = hesk_email2ticket($results, 1, $set_category, $set_priority) )
					{
						echo $hesk_settings['debug_mode'] ? "<pre>Ticket $id created/updated.</pre>\n" : '';

					}
					else
					{
						echo $hesk_settings['debug_mode'] ? "<pre>Ticket NOT inserted - may be duplicate, blocked or an error.</pre>\n" : '';
					}

					// Queue message to be deleted on connection close
					if ( ! $hesk_settings['pop3_keep'])
                    {
                    	$pop3->DeleteMessage($message);
                    }

					echo $hesk_settings['debug_mode'] ? "<br /><br />\n\n" : '';
				}
			}

			// Disconnect from the server - this also deletes queued messages
			if($error == "" && ($error=$pop3->Close()) == "")
			{
				echo $hesk_settings['debug_mode'] ? "<pre>Disconnected from the POP3 server &quot;" . $pop3->hostname . "&quot;.</pre>\n" : '';
			}
		}
	}
}

// Any error messages?
if($error != '')
{
	echo "<h2>Error: " . hesk_htmlspecialchars($error) . "</h2>";
}

// Remove active POP3 fetching log file
if ($hesk_settings['pop3_job_wait'])
{
	unlink($job_file);
}

return NULL;
