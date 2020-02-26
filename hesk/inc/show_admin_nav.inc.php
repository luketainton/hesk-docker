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

$num_mail = hesk_checkNewMail();

// Name of the page that is being requested, without '.php' at the end
$calling_script = basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- NEW DESIGN -->
<aside class="main-menu">
    <nav class="navbar">
        <div class="navbar__header">
            <button class="btn navbar__toggler" id="navbarToggler" type="button" aria-label="Toggle navigation">
                <svg class="icon icon-menu">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-menu"></use>
                </svg>
            </button>
            <a class="navbar__logo" href="admin_main.php">
                <?php echo $hesklang['help_desk']; ?>
            </a>
        </div>
        <div class="navbar__menu-wrap">
            <ul class="navbar__list">
                <li class="listitem <?php if ($calling_script === 'admin_main') { ?>current<?php } ?>">
                    <div class="listitem__icon">
                        <a href="admin_main.php">
                            <svg class="icon icon-tickets">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tickets"></use>
                            </svg>
                        </a>
                    </div>
                    <div class="listitem__menu">
                        <a href="admin_main.php" class="listitem__caption">
                            <?php echo $hesklang['tickets']; ?>
                        </a>
                        <?php //<span class="badge listitem__notification">109</span> ?>
                    </div>
                </li>
                <?php if (hesk_checkPermission('can_man_canned',0) &&
                          hesk_checkPermission('can_man_ticket_tpl',0)) {
                    $pages = array('manage_canned', 'manage_ticket_templates');
                    $open_menu = in_array($calling_script, $pages) ? 'current submenu-is-opened' : '';
                    ?>
                <li class="listitem submenu <?php echo $open_menu; ?>">
                    <div class="listitem__icon">
                        <a href="#">
                            <svg class="icon icon-templates">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-templates"></use>
                            </svg>
                        </a>
                    </div>
                    <div class="listitem__menu">
                        <a href="#" class="listitem__caption">Templates</a>
                        <ul class="submenu__list">
                            <li class="submenu__listitem <?php if ($calling_script === 'manage_canned') { ?>current<?php } ?>">
                                <a href="manage_canned.php">
                                    <?php echo $hesklang['responses']; ?>
                                </a>
                            </li>
                            <li class="submenu__listitem <?php if ($calling_script === 'manage_ticket_templates') { ?>current<?php } ?>">
                                <a href="manage_ticket_templates.php">
                                    <?php echo $hesklang['tickets']; ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <?php } elseif (hesk_checkPermission('can_man_canned',0)) { ?>
                    <li class="listitem <?php if ($calling_script === 'manage_canned') { ?>current<?php } ?>">
                        <div class="listitem__icon">
                            <a href="manage_canned.php">
                                <svg class="icon icon-tickets">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-templates"></use>
                                </svg>
                            </a>
                        </div>
                        <div class="listitem__menu">
                            <a href="manage_canned.php" class="listitem__caption">
                                <?php echo $hesklang['responses']; ?>
                            </a>
                        </div>
                    </li>
                <?php } elseif (hesk_checkPermission('can_man_ticket_tpl',0)) { ?>
                    <li class="listitem <?php if ($calling_script === 'manage_ticket_templates') { ?>current<?php } ?>">
                        <div class="listitem__icon">
                            <a href="manage_ticket_templates.php">
                                <svg class="icon icon-tickets">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-templates"></use>
                                </svg>
                            </a>
                        </div>
                        <div class="listitem__menu">
                            <a href="manage_ticket_templates.php" class="listitem__caption">
                                <?php echo $hesklang['tickets']; ?>
                            </a>
                        </div>
                    </li>
                <?php
                }

                if ($hesk_settings['kb_enable'] && hesk_checkPermission('can_man_kb',0)) {
                    $pages = array('manage_knowledgebase', 'knowledgebase_private');
                    $current = in_array($calling_script, $pages) ? 'current' : '';
                    ?>
                    <li class="listitem <?php echo $current; ?>">
                        <div class="listitem__icon">
                            <a href="manage_knowledgebase.php">
                                <svg class="icon icon-knowledge">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-knowledge"></use>
                                </svg>
                            </a>
                        </div>
                        <div class="listitem__menu">
                            <a href="manage_knowledgebase.php" class="listitem__caption">
                                <?php echo $hesklang['menu_kb']; ?>
                            </a>
                        </div>
                    </li>
                    <?php
                } elseif ($hesk_settings['kb_enable']) {
                    ?>
                    <li class="listitem <?php if ($calling_script === 'knowledgebase_private') { ?>current<?php } ?>">
                        <div class="listitem__icon">
                            <a href="knowledgebase_private.php">
                                <svg class="icon icon-knowledge">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-knowledge"></use>
                                </svg>
                            </a>
                        </div>
                        <div class="listitem__menu">
                            <a href="knowledgebase_private.php" class="listitem__caption">
                                <?php echo $hesklang['menu_kb']; ?>
                            </a>
                        </div>
                    </li>
                    <?php
                }

                if (hesk_checkPermission('can_man_cat',0)) { ?>
                <li class="listitem <?php if ($calling_script === 'manage_categories') { ?>current<?php } ?>">
                    <div class="listitem__icon">
                        <a href="manage_categories.php">
                            <svg class="icon icon-categories">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-categories"></use>
                            </svg>
                        </a>
                    </div>
                    <div class="listitem__menu">
                        <a href="manage_categories.php" class="listitem__caption">
                            <?php echo $hesklang['menu_cat']; ?>
                        </a>
                    </div>
                </li>
                <?php } ?>
                <li class="separator"></li>
                <?php if (hesk_checkPermission('can_man_users',0)) { ?>
                <li class="listitem <?php if ($calling_script === 'manage_users') { ?>current<?php } ?>">
                    <div class="listitem__icon">
                        <a href="manage_users.php">
                            <svg class="icon icon-team">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-team"></use>
                            </svg>
                        </a>
                    </div>
                    <div class="listitem__menu">
                        <a href="manage_users.php" class="listitem__caption">
                            <?php echo $hesklang['team']; ?>
                        </a>
                    </div>
                </li>
                <?php
                }

                //Reports
                if (hesk_checkPermission('can_run_reports',0)) {
                    $pages = array('reports', 'export');
                    $open_menu = in_array($calling_script, $pages) ? 'current submenu-is-opened' : '';
                ?>
                <li class="listitem submenu <?php echo $open_menu; ?>">
                    <div class="listitem__icon">
                        <a href="#">
                            <svg class="icon icon-reports">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-reports"></use>
                            </svg>
                        </a>
                    </div>
                    <div class="separator"></div>
                    <div class="listitem__menu">
                        <a href="#" class="listitem__caption"><?php echo $hesklang['reports']; ?></a>
                        <ul class="submenu__list">
                            <li class="submenu__listitem <?php if ($calling_script === 'reports') { ?>current<?php } ?>">
                                <a href="reports.php">
                                    <?php echo $hesklang['reports_tab']; ?>
                                </a>
                            </li>
                            <li class="submenu__listitem <?php if ($calling_script === 'export') { ?>current<?php } ?>">
                                <a href="export.php">
                                    <?php echo $hesklang['export']; ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <?php
                } elseif (hesk_checkPermission('can_export',0)) {
                    ?>
                    <li class="listitem <?php if ($calling_script === 'export') { ?>current<?php } ?>">
                        <div class="listitem__icon">
                            <a href="export.php">
                                <svg class="icon icon-team">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-team"></use>
                                </svg>
                            </a>
                        </div>
                        <div class="listitem__menu">
                            <a href="export.php" class="listitem__caption">
                                <?php echo $hesklang['export']; ?>
                            </a>
                        </div>
                    </li>
                    <?php
                }

                if (hesk_checkPermission('can_ban_emails',0) ||
                    hesk_checkPermission('can_ban_ips',0) ||
                    hesk_checkPermission('can_service_msg',0) ||
                    hesk_checkPermission('can_email_tpl',0) ||
                    hesk_checkPermission('can_man_settings',0)) {
                    $pages = array('banned_emails', 'banned_ips', 'service_messages', 'email_templates', 'custom_fields', 'custom_statuses');
                    $open_menu = in_array($calling_script, $pages) ? 'current submenu-is-opened' : '';
                ?>
                <li class="separator"></li>
                <li class="listitem submenu <?php echo $open_menu; ?>">
                    <div class="listitem__icon">
                        <a href="#">
                            <svg class="icon icon-tools">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tools"></use>
                            </svg>
                        </a>
                    </div>
                    <div class="listitem__menu">
                        <a href="#" class="listitem__caption">
                            <?php echo $hesklang['tools']; ?>
                        </a>
                        <ul class="submenu__list">
                            <?php if (hesk_checkPermission('can_ban_emails',0)) {
                                ?>
                                <li class="submenu__listitem <?php if ($calling_script === 'banned_emails') { ?>current<?php } ?>">
                                    <a href="banned_emails.php">
                                        <?php echo $hesklang['banemail']; ?>
                                    </a>
                                </li>
                                <?php
                            }

                            if (hesk_checkPermission('can_ban_ips',0)) {
                                ?>
                                <li class="submenu__listitem <?php if ($calling_script === 'banned_ips') { ?>current<?php } ?>">
                                    <a href="banned_ips.php">
                                        <?php echo $hesklang['banip']; ?>
                                    </a>
                                </li>
                                <?php
                            }

                            if (hesk_checkPermission('can_service_msg',0)) {
                                ?>
                                <li class="submenu__listitem <?php if ($calling_script === 'service_messages') { ?>current<?php } ?>">
                                    <a href="service_messages.php">
                                        <?php echo $hesklang['sm_title']; ?>
                                    </a>
                                </li>
                                <?php
                            }

                            if (hesk_checkPermission('can_email_tpl',0)) {
                                ?>
                                <li class="submenu__listitem <?php if ($calling_script === 'email_templates') { ?>current<?php } ?>">
                                    <a href="email_templates.php">
                                        <?php echo $hesklang['et_title']; ?>
                                    </a>
                                </li>
                                <?php
                            }

                            if (hesk_checkPermission('can_man_settings',0)) {
                                ?>
                                <li class="submenu__listitem <?php if ($calling_script === 'custom_fields') { ?>current<?php } ?>">
                                    <a href="custom_fields.php">
                                        <?php echo $hesklang['tab_4']; ?>
                                    </a>
                                </li>
                                <li class="submenu__listitem <?php if ($calling_script === 'custom_statuses') { ?>current<?php } ?>">
                                    <a href="custom_statuses.php">
                                        <?php echo $hesklang['statuses']; ?>
                                    </a>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>
                    </div>
                </li>
                <?php
                }

                if (hesk_checkPermission('can_man_settings',0)) {
                    $pages = array('admin_settings_general', 'admin_settings_help_desk', 'admin_settings_knowledgebase',
                        'admin_settings_email', 'admin_settings_ticket_list', 'admin_settings_misc');
                    $open_menu = in_array($calling_script, $pages) ? 'current submenu-is-opened' : '';
                    ?>
                    <li class="listitem submenu <?php echo $open_menu; ?>">
                        <div class="listitem__icon">
                            <a href="#">
                                <svg class="icon icon-settings">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-settings"></use>
                                </svg>
                            </a>
                        </div>
                        <div class="listitem__menu">
                            <a href="#" class="listitem__caption">
                                <?php echo $hesklang['settings']; ?>
                            </a>
                            <ul class="submenu__list">
                                <li class="submenu__listitem <?php if ($calling_script === 'admin_settings_general') { ?>current<?php } ?>">
                                    <a href="admin_settings_general.php">
                                        <?php echo $hesklang['tab_1']; ?>
                                    </a>
                                </li>
                                <li class="submenu__listitem <?php if ($calling_script === 'admin_settings_help_desk') { ?>current<?php } ?>">
                                    <a href="admin_settings_help_desk.php">
                                        <?php echo $hesklang['tab_2']; ?>
                                    </a>
                                </li>
                                <li class="submenu__listitem <?php if ($calling_script === 'admin_settings_knowledgebase') { ?>current<?php } ?>">
                                    <a href="admin_settings_knowledgebase.php">
                                        <?php echo $hesklang['tab_3']; ?>
                                    </a>
                                </li>
                                <li class="submenu__listitem <?php if ($calling_script === 'admin_settings_email') { ?>current<?php } ?>">
                                    <a href="admin_settings_email.php">
                                        <?php echo $hesklang['tab_6']; ?>
                                    </a>
                                </li>
                                <li class="submenu__listitem <?php if ($calling_script === 'admin_settings_ticket_list') { ?>current<?php } ?>">
                                    <a href="admin_settings_ticket_list.php">
                                        <?php echo $hesklang['tab_7']; ?>
                                    </a>
                                </li>
                                <li class="submenu__listitem <?php if ($calling_script === 'admin_settings_misc') { ?>current<?php } ?>">
                                    <a href="admin_settings_misc.php">
                                        <?php echo $hesklang['tab_5']; ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <?php
                }
                ?>
                <li class="separator mobile"></li>
                <li class="listitem mobile <?php if ($calling_script === 'mail') { ?>current<?php } ?>">
                    <div class="listitem__icon">
                        <a href="mail.php">
                            <svg class="icon icon-mail">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-mail"></use>
                            </svg>
                        </a>
                    </div>
                    <div class="listitem__menu">
                        <a href="mail.php" class="listitem__caption">
                            <?php echo $hesklang['menu_msg']; ?>
                        </a>
                        <?php if ($num_mail > 0): ?>
                        <span class="badge listitem__notification">
                            <?php echo $num_mail; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </li>
                <li class="listitem mobile <?php if ($calling_script === 'profile') { ?>current<?php } ?>">
                    <div class="listitem__icon">
                        <a href="profile.php" class="mobile_ava">
                            <?php
                            $letter = substr($_SESSION['name'], 0, 1);

                            echo hesk_mb_strtoupper($letter);
                            ?>
                        </a>
                    </div>
                    <div class="listitem__menu">
                        <a href="profile.php" class="listitem__caption">
                            <?php echo $hesklang['profile']; ?>
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</aside>
<main class="main">
    <!-- begin header -->
    <header class="header">
        <div class="header__left">
        </div>
        <div class="header__right" style="border-left: none">
            <a href="new_ticket.php" class="btn btn-full" ripple="ripple">
                <?php echo $hesklang['create_new_ticket']; ?>
            </a>
            <div class="profile">
                <div class="profile__item profile__item--mail">
                    <a href="mail.php" class="btn btn-empty">
                        <div class="profile__item_rel">
                            <svg class="icon icon-mail">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-mail"></use>
                            </svg>
                            <?php if ($num_mail > 0): ?>
                            <div class="badge"><?php echo $num_mail; ?></div>
                            <?php
                            endif;
                            unset($num_mail);
                            ?>
                        </div>
                    </a>
                </div>
                <div class="profile__item profile__user out-close">
                    <div class="user__ava" data-action="show-profile">
                        <?php
                        $letter = substr($_SESSION['name'], 0, 1);

                        echo hesk_mb_strtoupper($letter);
                        ?>
                    </div>
                    <div class="user__name" data-action="show-profile">
                        <p>
                            <span><?php echo $_SESSION['name']; ?></span>
                            <svg class="icon icon-chevron-down">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                            </svg>
                        </p>
                    </div>
                    <section class="profile__menu">
                        <div class="profile--view">
                            <a href="profile.php" class="btn btn-border" ripple="ripple"><?php echo $hesklang['view_profile']; ?></a>
                        </div>
                        <div class="profile--logout">
                            <a href="index.php?a=logout&token=<?php hesk_token_echo(); ?>">
                                <svg class="icon icon-log-out">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-log-out"></use>
                                </svg>
                                <span><?php echo $hesklang['logout']; ?></span>
                            </a>
                        </div>
                    </section>
                </div>
            </div>
        </div>
        <div class="header__mobile">
            <button class="btn btn-empty header__menu" data-action="toggle-menu">
                <svg class="icon icon-menu-mobile">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-menu-mobile"></use>
                </svg>
                <svg class="icon icon-close-mobile">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-close-mobile"></use>
                </svg>
            </button>
            <a class="navbar__logo" href="admin_main.php"><?php echo $hesklang['help_desk']; ?></a>
            <div class="header__mobile_actions">
                <a href="new_ticket.php" class="btn btn-empty" data-action="create-ticket">
                    <svg class="icon icon-add">
                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-add"></use>
                    </svg>
                </a>
            </div>
        </div>
    </header>
<?php
// Show a notice if we are in maintenance mode
if ( hesk_check_maintenance(false) )
{
	echo '<br />';
	hesk_show_notice($hesklang['mma2'], $hesklang['mma1'], false);
}

// Show a notice if we are in "Knowledgebase only" mode
if ( hesk_check_kb_only(false) )
{
	echo '<br />';
	hesk_show_notice($hesklang['kbo2'], $hesklang['kbo1'], false);
}
?>
