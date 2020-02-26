<?php
global $hesk_settings, $hesklang;

// This guard is used to ensure that users can't hit this outside of actual HESK code
if (!defined('IN_SCRIPT')) {
    die();
}

/**
 * @var array $top_articles
 * @var array $latest_articles
 * @var array $service_messages
 */

$service_message_type_to_class = array(
    '0' => 'none',
    '1' => 'success',
    '2' => '', // Info has no CSS class
    '3' => 'warning',
    '4' => 'danger'
);

require_once(TEMPLATE_PATH . 'customer/util/alerts.php');
require_once(TEMPLATE_PATH . 'customer/util/kb-search.php');
require_once(TEMPLATE_PATH . 'customer/util/rating.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?php echo $hesk_settings['hesk_title']; ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge" />
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0" />
    <meta name="description" content="" />
    <meta name="keywords" content="" />
    <meta name="author" content="" />
    <meta name="theme-color" content="#fff" />
    <meta name="format-detection" content="telephone=no" />
    <link rel="stylesheet" media="all" href="<?php echo TEMPLATE_PATH; ?>customer/css/app<?php echo $hesk_settings['debug_mode'] ? '' : '.min'; ?>.css" />

    <style>
        <?php outputSearchStyling(); ?>
    </style>
    <?php include(TEMPLATE_PATH . '../../head.txt'); ?>
</head>

<body class="cust-help">
<?php include(TEMPLATE_PATH . '../../header.txt'); ?>
<div class="wrapper">
    <main class="main">
        <header class="header">
            <div class="contr">
                <div class="header__inner">
                    <a href="<?php echo $hesk_settings['hesk_url']; ?>" class="header__logo">
                        <?php echo $hesk_settings['hesk_title']; ?>
                    </a>
                    <?php if ($hesk_settings['can_sel_lang']): ?>
                        <div class="header__lang">
                            <form method="get" action="" style="margin:0;padding:0;border:0;white-space:nowrap;">
                            <div class="dropdown-select center out-close">
                                <select name="language" onchange="this.form.submit()">
                                    <?php hesk_listLanguages(); ?>
                                </select>
                            </div>
                            <?php foreach (hesk_getCurrentGetParameters() as $key => $value): ?>
                            <input type="hidden" name="<?php echo hesk_htmlentities($key); ?>"
                                   value="<?php echo hesk_htmlentities($value); ?>">
                            <?php endforeach; ?>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        <div class="breadcrumbs">
            <div class="contr">
                <div class="breadcrumbs__inner">
                    <a href="<?php echo $hesk_settings['site_url']; ?>">
                        <span><?php echo $hesk_settings['site_title']; ?></span>
                    </a>
                    <svg class="icon icon-chevron-right">
                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-chevron-right"></use>
                    </svg>
                    <div class="last"><?php echo $hesk_settings['hesk_title']; ?></div>
                </div>
            </div>
        </div>
        <div class="main__content">
            <div class="contr">
                <div class="help-search">
                    <h2 class="search__title"><?php echo $hesklang['how_can_we_help']; ?></h2>
                    <?php displayKbSearch(); ?>
                </div>
                <?php hesk3_show_messages($service_messages); ?>
                <div class="nav">
                    <a href="index.php?a=add" class="navlink">
                        <div class="icon-in-circle">
                            <svg class="icon icon-submit-ticket">
                                <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-submit-ticket"></use>
                            </svg>
                        </div>
                        <div>
                            <h5 class="navlink__title"><?php echo $hesklang['submit_ticket']; ?></h5>
                            <div class="navlink__descr"><?php echo $hesklang['open_ticket']; ?></div>
                        </div>
                    </a>
                    <a href="ticket.php" class="navlink">
                        <div class="icon-in-circle">
                            <svg class="icon icon-document">
                                <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-document"></use>
                            </svg>
                        </div>
                        <div>
                            <h5 class="navlink__title"><?php echo $hesklang['view_existing_tickets']; ?></h5>
                            <div class="navlink__descr"><?php echo $hesklang['vet']; ?></div>
                        </div>
                    </a>
                </div>
                <?php if ($hesk_settings['kb_enable']): ?>
                <article class="article">
                    <h3 class="article__heading">
                        <a href="knowledgebase.php">
                            <div class="icon-in-circle">
                                <svg class="icon icon-knowledge">
                                    <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-knowledge"></use>
                                </svg>
                            </div>
                            <span><?php echo $hesklang['kb_text']; ?></span>
                        </a>
                    </h3>
                    <div class="tabbed__head">
                        <ul class="tabbed__head_tabs">
                            <?php
                            if (count($top_articles) > 0):
                            ?>
                            <li class="current" data-link="tab1">
                                <span><?php echo $hesklang['popart']; ?></span>
                            </li>
                            <?php
                            endif;
                            if (count($latest_articles) > 0):
                            ?>
                            <li data-link="tab2">
                                <span><?php echo $hesklang['latart']; ?></span>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="tabbed__tabs">
                        <?php if (count($top_articles) > 0): ?>
                        <div class="tabbed__tabs_tab is-visible" data-tab="tab1">
                            <?php foreach ($top_articles as $article): ?>
                            <a href="knowledgebase.php?article=<?php echo $article['id']; ?>" class="preview">
                                <div class="icon-in-circle">
                                    <svg class="icon icon-knowledge">
                                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-knowledge"></use>
                                    </svg>
                                </div>
                                <div class="preview__text">
                                    <h5 class="preview__title"><?php echo $article['subject'] ?></h5>
                                    <p>
                                        <span class="lightgrey"><?php echo $hesklang['kb_cat']; ?>:</span>
                                        <span class="ml-1"><?php echo $article['category']; ?></span>
                                    </p>
                                    <p class="navlink__descr">
                                        <?php echo $article['content_preview']; ?>
                                    </p>
                                </div>
                                <div class="rate">
                                    <?php
                                    if ($hesk_settings['kb_rating']) {
                                        echo hesk3_get_customer_rating($article['rating']);
                                    }

                                    if ($hesk_settings['kb_views'] && $hesk_settings['kb_rating']):
                                        ?>
                                        <span class="lightgrey">(<?php echo $article['views']; ?>)</span>
                                    <?php elseif ($hesk_settings['kb_views']): ?>
                                        <span class="lightgrey">
                                            <?php echo $hesklang['views'] ?>:
                                            <?php echo $article['views']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php
                        endif;
                        if (count($latest_articles) > 0):
                        ?>
                        <div class="tabbed__tabs_tab <?php echo count($top_articles) === 0 ? 'is-visible' : ''; ?>" data-tab="tab2">
                            <?php foreach ($latest_articles as $article): ?>
                                <a href="knowledgebase.php?article=<?php echo $article['id']; ?>" class="preview">
                                    <div class="icon-in-circle">
                                        <svg class="icon icon-knowledge">
                                            <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-knowledge"></use>
                                        </svg>
                                    </div>
                                    <div class="preview__text">
                                        <h5 class="preview__title"><?php echo $article['subject'] ?></h5>
                                        <p>
                                            <span class="lightgrey"><?php echo $hesklang['kb_cat']; ?>:</span>
                                            <span class="ml-1"><?php echo $article['category']; ?></span>
                                        </p>
                                        <p class="navlink__descr">
                                            <?php echo $article['content_preview']; ?>
                                        </p>
                                    </div>
                                    <div class="rate">
                                        <?php
                                        if ($hesk_settings['kb_rating']) {
                                            echo hesk3_get_customer_rating($article['rating']);
                                        }
                                        if ($hesk_settings['kb_views'] && $hesk_settings['kb_rating']): ?>
                                            <span class="lightgrey">(<?php echo $article['views']; ?>)</span>
                                        <?php elseif ($hesk_settings['kb_views']): ?>
                                            <span class="lightgrey">
                                            <?php echo $hesklang['views'] ?>:
                                            <?php echo $article['views']; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="article__footer">
                        <a href="knowledgebase.php" class="btn btn--blue-border" ripple="ripple"><?php echo $hesklang['viewkb']; ?></a>
                    </div>
                </article>
                <?php
                endif;
                if ($hesk_settings['alink']):
                ?>
                <div class="article__footer">
                    <a href="<?php echo $hesk_settings['admin_dir']; ?>/" class="link"><?php echo $hesklang['ap']; ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
<?php
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
    </main>
</div>
<?php include(TEMPLATE_PATH . '../../footer.txt'); ?>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/jquery-3.4.1.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/hesk_functions.js"></script>
<?php outputSearchJavascript(); ?>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/svg4everybody.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/jquery.scrollbar.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/selectize.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/datepicker.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/datepicker.en.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/jquery.autocomplete.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/app<?php echo $hesk_settings['debug_mode'] ? '' : '.min'; ?>.js"></script>
</body>

</html>
