<?php
global $hesk_settings, $hesklang;
/**
 * @var array $article
 * @var array $attachments
 * @var boolean $showRating
 * @var string $categoryLink
 * @var array $relatedArticles
 */

// This guard is used to ensure that users can't hit this outside of actual HESK code
if (!defined('IN_SCRIPT')) {
    die();
}

require_once(TEMPLATE_PATH . 'customer/util/rating.php');
require_once(TEMPLATE_PATH . 'customer/util/kb-search.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?php echo $hesk_settings['tmp_title']; ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge" />
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo HESK_PATH; ?>img/favicon/apple-touch-icon.png" />
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo HESK_PATH; ?>img/favicon/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo HESK_PATH; ?>img/favicon/favicon-16x16.png" />
    <link rel="manifest" href="<?php echo HESK_PATH; ?>img/favicon/site.webmanifest" />
    <link rel="mask-icon" href="<?php echo HESK_PATH; ?>img/favicon/safari-pinned-tab.svg" color="#5bbad5" />
    <link rel="shortcut icon" href="<?php echo HESK_PATH; ?>img/favicon/favicon.ico" />
    <meta name="msapplication-TileColor" content="#2d89ef" />
    <meta name="msapplication-config" content="<?php echo HESK_PATH; ?>img/favicon/browserconfig.xml" />
    <meta name="theme-color" content="#ffffff" />
    <meta name="format-detection" content="telephone=no" />
    <link rel="stylesheet" media="all" href="<?php echo TEMPLATE_PATH; ?>customer/css/app<?php echo $hesk_settings['debug_mode'] ? '' : '.min'; ?>.css" />
    <link rel="stylesheet" media="all" href="<?php echo TEMPLATE_PATH; ?>customer/css/prism.css" />
    <script src="<?php echo TEMPLATE_PATH; ?>customer/js/prism.js"></script>
    <!--[if IE]>
    <link rel="stylesheet" media="all" href="<?php echo TEMPLATE_PATH; ?>customer/css/ie9.css" />
    <![endif]-->
    <?php include(TEMPLATE_PATH . '../../head.txt'); ?>
    <style>
        <?php outputSearchStyling(); ?>
    </style>
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
                    <a href="<?php echo $hesk_settings['hesk_url']; ?>">
                        <span><?php echo $hesk_settings['hesk_title']; ?></span>
                    </a>
                    <svg class="icon icon-chevron-right">
                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-chevron-right"></use>
                    </svg>
                    <?php foreach ($hesk_settings['public_kb_categories'][$article['catid']]['parents'] as $parent_id): ?>
                    <a href="knowledgebase.php<?php if ($parent_id > 1) echo "?category={$parent_id}"; ?>">
                        <span><?php echo $hesk_settings['public_kb_categories'][$parent_id]['name']; ?></span>
                    </a>
                    <svg class="icon icon-chevron-right">
                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-chevron-right"></use>
                    </svg>
                    <?php endforeach; ?>
                    <a href="knowledgebase.php<?php if ($article['catid'] > 1) echo "?category={$article['catid']}"; ?>">
                        <span><?php echo $hesk_settings['public_kb_categories'][$article['catid']]['name']; ?></span>
                    </a>
                    <svg class="icon icon-chevron-right">
                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-chevron-right"></use>
                    </svg>
                    <div class="last"><?php echo $article['subject']; ?></div>
                </div>
            </div>
        </div>
        <div class="main__content">
            <div class="contr">
                <div class="help-search">
                    <?php displayKbSearch(); ?>
                </div>
                <div class="ticket ticket--article">
                    <div class="ticket__body">
                        <article class="ticket__body_block naked">
                            <h1><?php echo $article['subject']; ?></h1>
                            <div class="block--description browser-default">
                                <?php echo $article['content']; ?>
                            </div>
                            <?php if (count($attachments)): ?>
                            <div class="block--uploads">
                                <?php foreach ($attachments as $attachment): ?>
                                &raquo;
                                <svg class="icon icon-attach">
                                    <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-attach"></use>
                                </svg>
                                <a title="<?php echo $hesklang['dnl']; ?>" href="download_attachment.php?kb_att=<?php echo $attachment['id']; ?>" rel="nofollow">
                                    <?php echo $attachment['name']; ?>
                                </a>
                                <br>
                                <?php
                                endforeach;
                                ?>
                            </div>
                            <?php
                            endif;
                            if ($showRating):
                            ?>
                            <div id="rate-me" class="ticket__block-footer">
                                <span><?php echo $hesklang['rart']; ?></span>
                                <a href="javascript:" onclick="HESK_FUNCTIONS.rate('rate_kb.php?rating=5&amp;id=<?php echo $article['id']; ?>','article-rating');document.getElementById('rate-me').innerHTML='<?php echo hesk_slashJS($hesklang['tyr']); ?>';" class="link" rel="nofollow">
                                    <?php echo $hesklang['yes_title_case']; ?>
                                </a>
                                <span>|</span>
                                <a href="javascript:" onclick="HESK_FUNCTIONS.rate('rate_kb.php?rating=1&amp;id=<?php echo $article['id']; ?>','article-rating');document.getElementById('rate-me').innerHTML='<?php echo hesk_slashJS($hesklang['tyr']); ?>';" class="link" rel="nofollow">
                                    <?php echo $hesklang['no_title_case']; ?>
                                </a>
                            </div>
                            <?php endif; ?>
                        </article>
                    </div>
                    <div class="ticket__params">
                        <section class="params--block details">
                            <h4 class="accordion-title">
                                <span><?php echo $hesklang['ad']; ?></span>
                            </h4>
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="title"><?php echo $hesklang['aid']; ?>:</div>
                                    <div class="value"><?php echo $article['id']; ?></div>
                                </div>
                                <div class="row">
                                    <div class="title"><?php echo $hesklang['category']; ?>:</div>
                                    <div class="value">
                                        <a href="<?php echo $categoryLink; ?>" class="link">
                                            <?php echo $article['cat_name']; ?>
                                        </a>
                                    </div>
                                </div>
                                <?php if ($hesk_settings['kb_date']): ?>
                                    <div class="row">
                                        <div class="title"><?php echo $hesklang['dta']; ?>:</div>
                                        <div class="value"><?php echo hesk_date($article['dt'], true); ?></div>
                                    </div>
                                <?php
                                endif;
                                if ($hesk_settings['kb_views']): ?>
                                <div class="row">
                                    <div class="title">
                                        <?php echo $hesklang['views']; ?>:
                                    </div>
                                    <div class="value">
                                        <?php echo $article['views_formatted']; ?>
                                    </div>
                                </div>
                                <?php
                                endif;
                                if ($hesk_settings['kb_rating']):
                                ?>
                                <div class="row">
                                    <div class="title">
                                        <?php echo $hesklang['rating']; ?>
                                        <?php if ($hesk_settings['kb_views']) echo ' ('.$hesklang['votes'].')'; ?>:
                                    </div>
                                    <div class="value">
                                        <div id="article-rating" class="rate">
                                            <?php echo hesk3_get_customer_rating($article['rating']); ?>
                                            <?php if ($hesk_settings['kb_views']) echo ' <span class="lightgrey">('.$article['votes_formatted'].')</span>'; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div style="text-align:right">
                                    <a href="javascript:history.go(<?php echo isset($_GET['rated']) ? '-2' : '-1'; ?>)" class="link">
                                        <svg class="icon icon-back go-back">
                                            <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-back"></use>
                                        </svg>
                                        <?php echo $hesklang['back']; ?>
                                    </a>
                                </div>
                            </div>
                        </section>
                        <?php if (count($relatedArticles) > 0): ?>
                        <section class="params--block">
                            <h4 class="accordion-title">
                                <span><?php echo $hesklang['relart']; ?></span>
                            </h4>
                            <div class="accordion-body">
                                <ul class="list">
                                    <?php foreach ($relatedArticles as $id => $subject): ?>
                                    <li>
                                        <a href="knowledgebase.php?article=<?php echo $id; ?>">
                                            <?php echo $subject; ?>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </section>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="divider"></div>
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
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/jquery-3.5.1.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/hesk_functions.js"></script>
<?php outputSearchJavascript(); ?>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/svg4everybody.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/selectize.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/app<?php echo $hesk_settings['debug_mode'] ? '' : '.min'; ?>.js"></script>
</body>
</html>
