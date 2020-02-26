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

//============================================================================//
//                         END OPTIONAL MODIFICATIONS                         //
//============================================================================//

// Is this feature enabled?
if (empty($hesk_settings['email_piping']))
{
	die($hesklang['epd']);
}

// Email piping is enabled, get other required includes
require(HESK_PATH . 'inc/pipe_functions.inc.php');

// Parse the incoming email
$results = parser();

// Convert email into a ticket (or new reply)
hesk_dbConnect();
hesk_email2ticket($results, 0, $set_category, $set_priority);

return NULL;
