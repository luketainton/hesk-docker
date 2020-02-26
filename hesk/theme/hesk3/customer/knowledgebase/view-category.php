<?php
global $hesk_settings, $hesklang;
/**
 * @var array $currentCategory
 * @var array $subcategories
 * @var string $subcategoriesWidth
 * @var string $parentLink
 * @var array $articlesInCategory
 * @var array $serviceMessages
 * @var boolean $noSearchResults
 * @var array $topArticles
 * @var array $latestArticles
 * @var array $latestArticles
 */

// This guard is used to ensure that users can't hit this outside of actual HESK code
if (!defined('IN_SCRIPT')) {
    die();
}

require_once(TEMPLATE_PATH . 'customer/util/alerts.php');
require_once(TEMPLATE_PATH . 'customer/util/kb-search.php');
require_once(TEMPLATE_PATH . 'customer/util/rating.php');

$service_message_type_to_class = array(
    '0' => 'none',
    '1' => 'success',
    '2' => '', // Info has no CSS class
    '3' => 'warning',
    '4' => 'danger'
);
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
    <!--suppress CssOverwrittenProperties -->
    <style>
        .topics__block {
            width: <?php echo $subcategoriesWidth; ?>;
        }

        .content .block__head {
            margin-bottom: <?php echo $currentCategory['id'] != 1 ? '0' : '16px' ?>;
        }

        .back-link {
            display; -ms-flexbox;
            display: flex;
            margin-bottom: 16px;
            text-align: center;
            -ms-flex-align: center;
            align-items: center;
            -ms-flex-pack: center;
            justify-content: center;
        }

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
                    <a href="<?php echo $hesk_settings['hesk_url']; ?>">
                        <span><?php echo $hesk_settings['hesk_title']; ?></span>
                    </a>
                    <svg class="icon icon-chevron-right">
                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-chevron-right"></use>
                    </svg>
                    <?php if ($currentCategory['id'] != 1): ?>
                    <a href="knowledgebase.php">
                        <span><?php echo $hesklang['kb_text']; ?></span>
                    </a>
                    <svg class="icon icon-chevron-right">
                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-chevron-right"></use>
                    </svg>
                    <?php endif; ?>
                    <div class="last"><?php echo $currentCategory['name']; ?></div>
                </div>
            </div>
        </div>
        <div class="main__content">
            <div class="contr">
                <div class="help-search">
                    <h2 class="search__title"><?php echo $hesklang['how_can_we_help']; ?></h2>
                    <?php displayKbSearch(); ?>
                </div>
                <?php if ($noSearchResults): ?>
                    <div class="alert warning">
                        <div class="alert__inner">
                            <div class="alert__head">
                                <svg class="icon icon-warning">
                                    <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-warning"></use>
                                </svg>
                                <h6 class="alert__title"><?php echo $hesklang['no_results_found']; ?></h6>
                            </div>
                            <p class="alert__descr"><?php echo $hesklang['nosr']; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                <?php foreach ($serviceMessages as $service_message): ?>
                    <div class="alert <?php echo $service_message_type_to_class[$service_message['style']] ?>">
                        <div class="alert__inner">
                            <div class="alert__head">
                                <svg class="icon icon-warning">
                                    <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-warning"></use>
                                </svg>
                                <h6 class="alert__title"><?php echo $service_message['title']; ?></h6>
                            </div>
                            <p class="alert__descr">
                                <?php echo $service_message['message']; ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="content">
                    <div class="block__head">
                        <div class="icon-in-circle">
                            <svg class="icon icon-knowledge">
                                <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-knowledge"></use>
                            </svg>
                        </div>
                        <h3 class="h-3 ml-1"><?php echo $currentCategory['name']; ?></h3>
                    </div>
                    <?php if ($currentCategory['id'] != 1): ?>
                    <a class="link back-link" href="<?php echo $parentLink; ?>">
                        (<?php echo $hesklang['back']; ?>)
                    </a>
                    <?php
                    endif;
                    if (count($subcategories) > 0):
                    ?>
                    <div class="topics">
                        <?php foreach ($subcategories as $subcategory): ?>
                        <div class="topics__block">
                            <h5 class="topics__title">
                                <svg class="icon icon-knowledge">
                                    <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-knowledge"></use>
                                </svg>
                                <span>
                                    <a class="link" href="knowledgebase.php?category=<?php echo $subcategory['subcategory']['id']; ?>">
                                        <?php echo $subcategory['subcategory']['name']; ?>
                                    </a>
                                </span>
                            </h5>
                            <ul class="topics__list">
                                <?php foreach ($subcategory['articles'] as $article): ?>
                                <li>
                                    <a href="knowledgebase.php?article=<?php echo $article['id']; ?>">
                                        <?php echo $article['subject']; ?>
                                    </a>
                                </li>
                                <?php
                                endforeach;
                                if ($subcategory['displayShowMoreLink']):
                                ?>
                                <li class="text-bold">
                                    <a href="knowledgebase.php?category=<?php echo $subcategory['subcategory']['id']; ?>">
                                        <?php echo $hesklang['m']; ?>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (count($articlesInCategory) > 0): ?>
                <article class="article">
                    <div class="block__head">
                        <h3 class="h-3 text-center"><?php echo $hesklang['ac']; ?></h3>
                    </div>
                    <?php foreach ($articlesInCategory as $article): ?>
                    <a href="knowledgebase.php?article=<?php echo $article['id']; ?>" class="preview">
                        <div class="icon-in-circle">
                            <svg class="icon icon-knowledge">
                                <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-knowledge"></use>
                            </svg>
                        </div>
                        <div class="preview__text">
                            <h5 class="preview__title"><?php echo $article['subject']; ?></h5>
                            <p class="navlink__descr"><?php echo $article['content_preview']; ?></p>
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
                </article>
                <?php
                endif;
                if (count($topArticles) > 0 || count($latestArticles) > 0):
                ?>
                <article class="article">
                    <div class="tabbed__head">
                        <ul class="tabbed__head_tabs">
                            <?php
                            if (count($topArticles) > 0):
                                ?>
                                <li class="current" data-link="tab1">
                                    <span><?php echo $hesklang['popart']; ?></span>
                                </li>
                            <?php
                            endif;
                            if (count($latestArticles) > 0):
                                ?>
                                <li data-link="tab2">
                                    <span><?php echo $hesklang['latart']; ?></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="tabbed__tabs">
                        <?php if (count($topArticles) > 0): ?>
                            <div class="tabbed__tabs_tab is-visible" data-tab="tab1">
                                <?php foreach ($topArticles as $article): ?>
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
                        <?php
                        endif;
                        if (count($latestArticles) > 0):
                            ?>
                            <div class="tabbed__tabs_tab <?php echo count($topArticles) === 0 ? 'is-visible' : ''; ?>" data-tab="tab2">
                                <?php foreach ($latestArticles as $article): ?>
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
                </article>
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
