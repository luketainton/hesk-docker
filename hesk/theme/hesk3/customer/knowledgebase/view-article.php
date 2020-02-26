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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?php echo $hesk_settings['tmp_title']; ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge" />
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0" />
    <meta name="description" content="" />
    <meta name="keywords" content="" />
    <meta name="author" content="" />
    <meta name="theme-color" content="#fff" />
    <meta name="format-detection" content="telephone=no" />
    <link rel="stylesheet" media="all" href="<?php echo TEMPLATE_PATH; ?>customer/css/app<?php echo $hesk_settings['debug_mode'] ? '' : '.min'; ?>.css" />
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
                    <a href="<?php echo $hesk_settings['hesk_url']; ?>">
                        <span><?php echo $hesk_settings['hesk_title']; ?></span>
                    </a>
                    <svg class="icon icon-chevron-right">
                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-chevron-right"></use>
                    </svg>
                    <a href="knowledgebase.php">
                        <span><?php echo $hesklang['kb_text']; ?></span>
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
                <div class="ticket ticket--article">
                    <div class="ticket__body">
                        <article class="ticket__body_block naked">
                            <h2><?php echo $article['subject']; ?></h2>
                            <div class="block--description">
                                <?php echo $article['content']; ?>
                            </div>
                            <?php foreach ($attachments as $attachment): ?>
                            <div class="block--uploads">
                                <svg class="icon icon-attach">
                                    <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-attach"></use>
                                </svg>
                                <a href="download_attachment.php?kb_att=<?php echo $attachment['id']; ?>" rel="nofollow">
                                    <?php echo $attachment['name']; ?>
                                </a>
                            </div>
                            <?php
                            endforeach;
                            if ($showRating):
                            ?>
                            <div class="ticket__block-footer">
                                <span><?php echo $hesklang['rart']; ?></span>
                                <a href="knowledgebase.php?rating=5&amp;id=<?php echo $article['id']; ?>" class="link">
                                    <?php echo $hesklang['yes_title_case']; ?>
                                </a>
                                <span>|</span>
                                <a href="knowledgebase.php?rating=1&amp;id=<?php echo $article['id']; ?>" class="link">
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
                                if ($hesk_settings['kb_rating'] || $hesk_settings['kb_views']):
                                ?>
                                <div class="row">
                                    <div class="title">
                                        <?php
                                        if ($hesk_settings['kb_rating']) {
                                            echo $hesklang['rating'];

                                            if ($hesk_settings['kb_views']) {
                                                echo ' ('.$hesklang['votes'].')';
                                            }
                                        } elseif ($hesk_settings['kb_views']) {
                                            echo $hesklang['views'];
                                        }
                                        ?>:
                                    </div>
                                    <div class="value">
                                        <div class="rate">
                                            <?php
                                            if ($hesk_settings['kb_rating']) {
                                                echo hesk3_get_customer_rating($article['rating']);
                                            }

                                            if ($hesk_settings['kb_views'] && $hesk_settings['kb_rating']): ?>
                                                <span class="lightgrey">(<?php echo $article['views']; ?>)</span>
                                            <?php elseif ($hesk_settings['kb_views']): ?>
                                                <span>
                                                    <?php echo $article['views']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
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
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/jquery-3.4.1.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/hesk_functions.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/svg4everybody.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/jquery.scrollbar.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/selectize.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/datepicker.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/datepicker.en.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/jquery.autocomplete.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/app<?php echo $hesk_settings['debug_mode'] ? '' : '.min'; ?>.js"></script>
</body>
</html>
