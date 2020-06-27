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

?>
<div class="settings__status">
    <h3><?php echo $hesklang['check_status']; ?></h3>
    <ul class="settings__status_list">
        <li>
            <div class="list--name"><?php echo $hesklang['v']; ?></div>
            <div class="list--status">
                <?php echo $hesk_settings['hesk_version']; ?>
                <?php
                if ($hesk_settings['check_updates']) {
                    $latest = hesk_checkVersion();

                    if ($latest === true) {
                        echo ' - <span style="color:green">' . $hesklang['hud'] . '</span> ';
                    } elseif ($latest != -1) {
                        // Is this a beta/dev version?
                        if (strpos($hesk_settings['hesk_version'], 'beta') || strpos($hesk_settings['hesk_version'], 'dev') || strpos($hesk_settings['hesk_version'], 'RC')) {
                            echo ' <span style="color:darkorange">' . $hesklang['beta'] . '</span> '; ?><br><a href="https://www.hesk.com/update.php?v=<?php echo $hesk_settings['hesk_version']; ?>" target="_blank"><?php echo $hesklang['check4updates']; ?></a><?php
                        } else {
                            echo ' - <span style="color:darkorange;font-weight:bold">' . $hesklang['hnw'] . '</span> '; ?><br><a href="https://www.hesk.com/update.php?v=<?php echo $hesk_settings['hesk_version']; ?>" target="_blank"><?php echo $hesklang['getup']; ?></a><?php
                        }
                    } else {
                        ?> - <a href="https://www.hesk.com/update.php?v=<?php echo $hesk_settings['hesk_version']; ?>" target="_blank"><?php echo $hesklang['check4updates']; ?></a><?php
                    }
                } else {
                    ?> - <a href="https://www.hesk.com/update.php?v=<?php echo $hesk_settings['hesk_version']; ?>" target="_blank"><?php echo $hesklang['check4updates']; ?></a><?php
                }
                ?>
            </div>
        </li>
        <li>
            <div class="list--name"><?php echo $hesklang['phpv']; ?></div>
            <div class="list--status"><?php echo defined('HESK_DEMO') ? $hesklang['hdemo'] : PHP_VERSION . ' ' . (function_exists('mysqli_connect') ? '(MySQLi)' : '(MySQL)'); ?></div>
        </li>
        <li>
            <div class="list--name"><?php echo $hesklang['mysqlv']; ?></div>
            <div class="list--status"><?php echo defined('HESK_DEMO') ? $hesklang['hdemo'] : hesk_dbResult( hesk_dbQuery('SELECT VERSION() AS version') ); ?></div>
        </li>
        <li>
            <div class="list--name">/hesk_settings.inc.php</div>
            <div class="list--status">
                <?php
                if (is_writable(HESK_PATH . 'hesk_settings.inc.php')) {
                    $enable_save_settings = 1;
                    echo '<span style="color:green">'.$hesklang['exists'].'</span>, <span style="color:green">'.$hesklang['writable'].'</span>';
                } else {
                    echo '<span style="color:green">'.$hesklang['exists'].'</span><br><span style="color:red">'.$hesklang['not_writable'].'</span></div></li><li><div style="text-align:justify">'.$hesklang['e_settings'];
                }
                ?>
            </div>
        </li>
        <li>
            <div class="list--name">/<?php echo $hesk_settings['attach_dir']; ?></div>
            <div class="list--status">
                <?php
                if (is_dir(HESK_PATH . $hesk_settings['attach_dir'])) {
                    echo '<span style="color:green">'.$hesklang['exists'].'</span>, ';
                    if (is_writable(HESK_PATH . $hesk_settings['attach_dir'])) {
                        $enable_use_attachments = 1;
                        echo '<span style="color:green">'.$hesklang['writable'].'</span>';
                    } else {
                        echo '<br><span style="color:red">'.$hesklang['not_writable'].'</span></div></li><li><div style="text-align:justify">'.$hesklang['e_attdir'];
                    }
                } else {
                    echo '<span style="color:red">'.$hesklang['no_exists'].'</span><br><span style="color:red">'.$hesklang['not_writable'].'</span></div></li><li><div style="text-align:justify">'.$hesklang['e_attdir'];
                }
                ?>
            </div>
        </li>
        <li>
            <div class="list--name">/<?php echo $hesk_settings['cache_dir']; ?></div>
            <div class="list--status">
                <?php
                if (is_dir(HESK_PATH . $hesk_settings['cache_dir'])) {
                    echo '<span style="color:green">'.$hesklang['exists'].'</span>, ';
                    if (is_writable(HESK_PATH . $hesk_settings['cache_dir'])) {
                        $enable_use_attachments = 1;
                        echo '<span style="color:green">'.$hesklang['writable'].'</span>';
                    } else {
                        echo '<br><span style="color:red">'.$hesklang['not_writable'].'</span></div></li><li><div style="text-align:justify">'.$hesklang['e_cdir'];
                    }
                } else {
                    echo '<span style="color:red">'.$hesklang['no_exists'].'</span><br><span style="color:red">'.$hesklang['not_writable'].'</span></div></li><li><div style="text-align:justify">'.$hesklang['e_cdir'];
                }
                ?>
            </div>
        </li>
    </ul>
</div>
<?php

function hesk_checkVersion()
{
    global $hesk_settings;

    if ($latest = hesk_getLatestVersion() )
    {
        if ( strlen($latest) > 12 )
        {
            return -1;
        }
        elseif ($latest == $hesk_settings['hesk_version'])
        {
            return true;
        }
        else
        {
            return $latest;
        }
    }
    else
    {
        return -1;
    }

} // END hesk_checkVersion()


function hesk_getLatestVersion()
{
    global $hesk_settings;

    // Do we have a cached version file?
    if ( file_exists(HESK_PATH . $hesk_settings['cache_dir'] . '/__latest.txt') )
    {
        if ( preg_match('/^(\d+)\|([\d.]+)+$/', @file_get_contents(HESK_PATH . $hesk_settings['cache_dir'] . '/__latest.txt'), $matches) && (time() - intval($matches[1])) < 3600  )
        {
            return $matches[2];
        }
    }

    // No cached file or older than 3600 seconds, try to get an update
    $hesk_version_url = 'http://hesk.com/version';

    // Try using cURL
    if ( function_exists('curl_init') )
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $hesk_version_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
        $latest = curl_exec($ch);
        curl_close($ch);
        return hesk_cacheLatestVersion($latest);
    }

    // Try using a simple PHP function instead
    if ($latest = @file_get_contents($hesk_version_url) )
    {
        return hesk_cacheLatestVersion($latest);
    }

    // Can't check automatically, will need a manual check
    return false;

} // END hesk_getLatestVersion()


function hesk_cacheLatestVersion($latest)
{
    global $hesk_settings;

    @file_put_contents(HESK_PATH . $hesk_settings['cache_dir'] . '/__latest.txt', time() . '|' . $latest);

    return $latest;

} // END hesk_cacheLatestVersion()
