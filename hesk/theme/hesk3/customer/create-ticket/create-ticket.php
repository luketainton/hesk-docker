<?php
global $hesk_settings, $hesklang;
/**
 * @var string $categoryName
 * @var int $categoryId
 * @var array $visibleCustomFieldsBeforeMessage
 * @var array $visibleCustomFieldsAfterMessage
 * @var array $customFieldsBeforeMessage
 * @var array $customFieldsAfterMessage
 */

// This guard is used to ensure that users can't hit this outside of actual HESK code
if (!defined('IN_SCRIPT')) {
    die();
}

require_once(TEMPLATE_PATH . 'customer/util/alerts.php');
require_once(TEMPLATE_PATH . 'customer/util/custom-fields.php');
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
    <!--[if IE]>
    <link rel="stylesheet" media="all" href="<?php echo TEMPLATE_PATH; ?>customer/css/ie9.css" />
    <![endif]-->
    <style>
        .form-footer .btn {
            margin-top: 20px;
        }
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
                    <a href="index.php">
                        <span><?php echo $hesk_settings['hesk_title']; ?></span>
                    </a>
                    <svg class="icon icon-chevron-right">
                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-chevron-right"></use>
                    </svg>
                    <a href="index.php?a=add">
                        <span><?php echo $hesklang['submit_ticket'] ?></span>
                    </a>
                    <svg class="icon icon-chevron-right">
                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-chevron-right"></use>
                    </svg>
                    <div class="last"><?php echo $categoryName; ?></div>
                </div>
            </div>
        </div>
        <div class="main__content">
            <div class="contr">
                <div style="margin-bottom: 20px;">
                    <?php
                    hesk3_show_messages($messages);
                    ?>
                </div>
                <h3 class="article__heading article__heading--form">
                    <div class="icon-in-circle">
                        <svg class="icon icon-submit-ticket">
                            <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-submit-ticket"></use>
                        </svg>
                    </div>
                    <span class="ml-1"><?php echo $hesklang['submit_a_support_request']; ?></span>
                </h3>
                <div class="article-heading-tip">
                    <span><?php echo $hesklang['req_marked_with']; ?></span>
                    <span class="label required"></span>
                </div>
                <form class="form form-submit-ticket ticket-create <?php echo count($_SESSION['iserror']) ? 'invalid' : ''; ?>" method="post" action="submit_ticket.php?submit=1" name="form1" id="form1" enctype="multipart/form-data">
                    <section class="form-groups">
                        <div class="form-group error">
                            <label class="label required"><?php echo $hesklang['name']; ?>:</label>
                            <input type="text" name="name" class="form-control <?php if (in_array('name',$_SESSION['iserror'])) {echo 'isEerror';} ?>" maxlength="50" value="<?php if (isset($_SESSION['c_name'])) {echo stripslashes(hesk_input($_SESSION['c_name']));} ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="label <?php if ($hesk_settings['require_email']) { ?>required<?php } ?>"><?php echo $hesklang['email']; ?>:</label>
                            <input type="<?php echo $hesk_settings['multi_eml'] ? 'text' : 'email'; ?>"
                                   class="form-control <?php if (in_array('email',$_SESSION['iserror'])) {echo 'isError';} elseif (in_array('email',$_SESSION['isnotice'])) {echo 'isNotice';} ?>"
                                   name="email" id="email" maxlength="1000"
                                   value="<?php if (isset($_SESSION['c_email'])) {echo stripslashes(hesk_input($_SESSION['c_email']));} ?>" <?php if($hesk_settings['detect_typos']) { echo ' onblur="HESK_FUNCTIONS.suggestEmail(\'email\', \'email_suggestions\', 0)"'; } ?>
                                   <?php if ($hesk_settings['require_email']) { ?>required<?php } ?>>
                            <div id="email_suggestions"></div>
                        </div>
                        <?php if ($hesk_settings['confirm_email']): ?>
                            <div class="form-group">
                                <label class="label <?php if ($hesk_settings['require_email']) { ?>required<?php } ?>"><?php echo $hesklang['confemail']; ?>:</label>
                                <input type="<?php echo $hesk_settings['multi_eml'] ? 'text' : 'email'; ?>"
                                       class="form-control <?php if (in_array('email2',$_SESSION['iserror'])) {echo 'isError';} elseif (in_array('email2',$_SESSION['isnotice'])) {echo 'isNotice';} ?>"
                                       name="email2" id="email2" maxlength="1000"
                                       value="<?php if (isset($_SESSION['c_email2'])) {echo stripslashes(hesk_input($_SESSION['c_email2']));} ?>"
                                       <?php if ($hesk_settings['require_email']) { ?>required<?php } ?>>
                            </div>
                        <?php endif; ?>
                    </section>
                    <?php if ($hesk_settings['cust_urgency']): ?>
                        <section class="param">
                            <span class="label required"><?php echo $hesklang['priority']; ?>:</span>
                            <div class="dropdown-select center out-close priority">
                                <select name="priority">
                                    <?php if ($hesk_settings['select_pri']): ?>
                                        <option value=""><?php echo $hesklang['select']; ?></option>
                                    <?php endif; ?>
                                    <option value="low" <?php if(isset($_SESSION['c_priority']) && $_SESSION['c_priority']=='low') {echo 'selected';} ?>>
                                        <?php echo $hesklang['low']; ?>
                                    </option>
                                    <option value="medium" <?php if(isset($_SESSION['c_priority']) && $_SESSION['c_priority']=='medium') {echo 'selected';} ?>>
                                        <?php echo $hesklang['medium']; ?>
                                    </option>
                                    <option value="high" <?php if(isset($_SESSION['c_priority']) && $_SESSION['c_priority']=='high') {echo 'selected';} ?>>
                                        <?php echo $hesklang['high']; ?>
                                    </option>
                                </select>
                            </div>
                        </section>
                    <?php
                    endif;
                    if (count($visibleCustomFieldsBeforeMessage) > 0):
                    ?>
                    <div class="divider"></div>
                    <?php
                    endif;
                    hesk3_output_custom_fields($customFieldsBeforeMessage);

                    if ($hesk_settings['require_subject'] != -1 || $hesk_settings['require_message'] != -1): ?>
                        <div class="divider"></div>
                        <?php if ($hesk_settings['require_subject'] != -1): ?>
                            <div class="form-group">
                                <label class="label <?php if ($hesk_settings['require_subject']) { ?>required<?php } ?>">
                                    <?php echo $hesklang['subject']; ?>:
                                </label>
                                <input type="text" class="form-control <?php if (in_array('subject',$_SESSION['iserror'])) {echo 'isError';} ?>"
                                       name="subject" maxlength="70"
                                       value="<?php if (isset($_SESSION['c_subject'])) {echo stripslashes(hesk_input($_SESSION['c_subject']));} ?>"
                                       <?php if ($hesk_settings['require_subject']) { ?>required<?php } ?>>
                            </div>
                            <?php
                        endif;
                        if ($hesk_settings['require_message'] != -1): ?>
                            <div class="form-group">
                                <label class="label <?php if ($hesk_settings['require_message']) { ?>required<?php } ?>">
                                    <?php echo $hesklang['message']; ?>:
                                </label>
                                <textarea class="form-control <?php if (in_array('message',$_SESSION['iserror'])) {echo 'isError';} ?>"
                                          name="message" rows="12" cols="60"
                                          <?php if ($hesk_settings['require_message']) { ?>required<?php } ?>><?php if (isset($_SESSION['c_message'])) {echo stripslashes(hesk_input($_SESSION['c_message']));} ?></textarea>
                                <?php if (has_public_kb() && $hesk_settings['kb_recommendanswers']): ?>
                                    <div class="kb-suggestions" style="margin: 0 auto; width: 100%; max-width: 752px; display: none">
                                        <div class="alert">
                                            <div class="alert__inner">
                                                <div class="alert__head">
                                                    <h6 class="alert__title"><?php echo $hesklang['sc']; ?>:</h6>
                                                </div>
                                                <ul id="kb-suggestion-list" class="type--list">
                                                </ul>
                                            </div>
                                        </div>
                                        <div id="suggested-article-hidden-inputs" style="display: none">
                                            <?php // Will be populated with the list sent to the create ticket logic ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php
                        endif;
                    endif;

                    if (count($visibleCustomFieldsAfterMessage) > 0): ?>
                    <div class="divider"></div>
                    <?php
                    endif;

                    hesk3_output_custom_fields($customFieldsAfterMessage);

                    if ($hesk_settings['attachments']['use']):
                    ?>
                        <div class="divider"></div>
                        <section class="param param--attach">
                            <span class="label"><?php echo $hesklang['attachments']; ?>:</span>
                            <div class="attach">
                                <div>
                                    <?php
                                    for ($i=1;$i<=$hesk_settings['attachments']['max_number'];$i++)
                                    {
                                        $cls = ($i == 1 && in_array('attachments',$_SESSION['iserror'])) ? ' class="isError" ' : '';
                                        echo '<input type="file" name="attachment['.$i.']" size="50" '.$cls.' /><br />';
                                    }
                                    ?>
                                </div>
                                <div class="attach-tooltype">
                                    <span><?php echo sprintf($hesklang['maximum_x_attachments'], $hesk_settings['attachments']['max_number']); ?></span>
                                    <a onclick="HESK_FUNCTIONS.openWindow('file_limits.php',250,500)">
                                        <div class="tooltype right">
                                            <svg class="icon icon-info">
                                                <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-info"></use>
                                            </svg>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </section>
                        <div class="divider"></div>
                        <?php
                    endif;

                    if ($hesk_settings['question_use'] || ($hesk_settings['secimg_use'] && $hesk_settings['recaptcha_use'] !== 1)):
                    ?>
                    <div class="captcha-block">
                        <h3><?php echo $hesklang['verify_header']; ?></h3>

                        <?php if ($hesk_settings['question_use']): ?>
                        <div class="form-group">
                            <label class="required"><?php echo $hesk_settings['question_ask']; ?></label>
                            <?php
                            $value = '';
                            if (isset($_SESSION['c_question']))
                            {
                                $value = stripslashes(hesk_input($_SESSION['c_question']));
                            }
                            ?>
                            <input type="text" class="form-control <?php echo in_array('question',$_SESSION['iserror']) ? 'isError' : ''; ?>"
                                   name="question" size="20" value="<?php echo $value; ?>">
                        </div>
                        <?php
                            endif;

                            if ($hesk_settings['secimg_use'] && $hesk_settings['recaptcha_use'] != 1)
                            {
                                ?>
                                <div class="form-group">
                                    <?php
                                    // SPAM prevention verified for this session
                                    if (isset($_SESSION['img_verified']))
                                    {
                                        echo $hesklang['vrfy'];
                                    }
                                    // Use reCAPTCHA V2?
                                    elseif ($hesk_settings['recaptcha_use'] == 2)
                                    {
                                        ?>
                                        <div class="g-recaptcha" data-sitekey="<?php echo $hesk_settings['recaptcha_public_key']; ?>"></div>
                                        <?php
                                    }
                                    // At least use some basic PHP generated image (better than nothing)
                                    else
                                    {
                                        $cls = in_array('mysecnum',$_SESSION['iserror']) ? 'isError' : '';
                                        ?>
                                        <img name="secimg" src="print_sec_img.php?<?php echo rand(10000,99999); ?>" width="150" height="40" alt="<?php echo $hesklang['sec_img']; ?>" title="<?php echo $hesklang['sec_img']; ?>" style="vertical-align:text-bottom">
                                        <a class="btn btn-refresh" href="javascript:void(0)" onclick="javascript:document.form1.secimg.src='print_sec_img.php?'+ ( Math.floor((90000)*Math.random()) + 10000);">
                                            <svg class="icon icon-refresh">
                                                <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-refresh"></use>
                                            </svg>
                                        </a>
                                        <label class="required"><?php echo $hesklang['sec_enter']; ?></label>
                                        <input type="text" name="mysecnum" size="20" maxlength="5" class="form-control <?php echo $cls; ?>">
                                    <?php
                                    }
                                    ?>
                                </div>
                                <?php
                            }
                            ?>
                    </div>
                    <div class="divider"></div>
                        <?php
                    endif;

                    if ($hesk_settings['submit_notice']):
                    ?>
                    <div class="alert">
                        <div class="alert__inner">
                            <b class="font-weight-bold"><?php echo $hesklang['before_submit']; ?>:</b>
                            <ul>
                                <li><?php echo $hesklang['all_info_in']; ?>.</li>
                                <li><?php echo $hesklang['all_error_free']; ?>.</li>
                            </ul>
                            <br>
                            <b class="font-weight-bold"><?php echo $hesklang['we_have']; ?>:</b>
                            <ul>
                                <li><?php echo hesk_htmlspecialchars(hesk_getClientIP()).' '.$hesklang['recorded_ip']; ?></li>
                                <li><?php echo $hesklang['recorded_time']; ?></li>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>


                    <div class="form-footer">
                        <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                        <input type="hidden" name="category" value="<?php echo $categoryId; ?>">
                        <button type="submit" class="btn btn-full" ripple="ripple" id="recaptcha-submit">
                            <?php echo $hesklang['sub_ticket']; ?>
                        </button>
                        <!-- Do not delete or modify the code below, it is used to detect simple SPAM bots -->
                        <input type="hidden" name="hx" value="3" /><input type="hidden" name="hy" value="">
                        <!-- >
                        <input type="text" name="phone" value="3">
                        < -->
                    </div>
                    <?php
                    // Use Invisible reCAPTCHA?
                    if ($hesk_settings['secimg_use'] && $hesk_settings['recaptcha_use'] == 1 && ! isset($_SESSION['img_verified']))
                    {
                        ?>
                        <div class="g-recaptcha" data-sitekey="<?php echo $hesk_settings['recaptcha_public_key']; ?>" data-bind="recaptcha-submit" data-callback="recaptcha_submitForm"></div>
                        <?php
                    }
                    ?>
                </form>
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
    <!-- end main -->
</div>
<?php include(TEMPLATE_PATH . '../../footer.txt'); ?>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/jquery-3.5.1.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/hesk_functions.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/svg4everybody.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/selectize.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/datepicker.min.js"></script>
<script type="text/javascript">
(function ($) { $.fn.datepicker.language['en'] = {
    days: ['<?php echo $hesklang['d0']; ?>', '<?php echo $hesklang['d1']; ?>', '<?php echo $hesklang['d2']; ?>', '<?php echo $hesklang['d3']; ?>', '<?php echo $hesklang['d4']; ?>', '<?php echo $hesklang['d5']; ?>', '<?php echo $hesklang['d6']; ?>'],
    daysShort: ['<?php echo $hesklang['sun']; ?>', '<?php echo $hesklang['mon']; ?>', '<?php echo $hesklang['tue']; ?>', '<?php echo $hesklang['wed']; ?>', '<?php echo $hesklang['thu']; ?>', '<?php echo $hesklang['fri']; ?>', '<?php echo $hesklang['sat']; ?>'],
    daysMin: ['<?php echo $hesklang['su']; ?>', '<?php echo $hesklang['mo']; ?>', '<?php echo $hesklang['tu']; ?>', '<?php echo $hesklang['we']; ?>', '<?php echo $hesklang['th']; ?>', '<?php echo $hesklang['fr']; ?>', '<?php echo $hesklang['sa']; ?>'],
    months: ['<?php echo $hesklang['m1']; ?>','<?php echo $hesklang['m2']; ?>','<?php echo $hesklang['m3']; ?>','<?php echo $hesklang['m4']; ?>','<?php echo $hesklang['m5']; ?>','<?php echo $hesklang['m6']; ?>', '<?php echo $hesklang['m7']; ?>','<?php echo $hesklang['m8']; ?>','<?php echo $hesklang['m9']; ?>','<?php echo $hesklang['m10']; ?>','<?php echo $hesklang['m11']; ?>','<?php echo $hesklang['m12']; ?>'],
    monthsShort: ['<?php echo $hesklang['ms01']; ?>','<?php echo $hesklang['ms02']; ?>','<?php echo $hesklang['ms03']; ?>','<?php echo $hesklang['ms04']; ?>','<?php echo $hesklang['ms05']; ?>','<?php echo $hesklang['ms06']; ?>', '<?php echo $hesklang['ms07']; ?>','<?php echo $hesklang['ms08']; ?>','<?php echo $hesklang['ms09']; ?>','<?php echo $hesklang['ms10']; ?>','<?php echo $hesklang['ms11']; ?>','<?php echo $hesklang['ms12']; ?>'],
    today: '<?php echo hesk_slashJS($hesklang['r1']); ?>',
    clear: '<?php echo hesk_slashJS($hesklang['clear']); ?>',
    dateFormat: 'mm/dd/yyyy',
    timeFormat: 'hh:ii aa',
    firstDay: <?php echo $hesklang['first_day_of_week']; ?>
}; })(jQuery);
</script>
<?php
if (defined('RECAPTCHA'))
{
    echo '<script src="https://www.google.com/recaptcha/api.js?hl='.$hesklang['RECAPTCHA'].'" async defer></script>';
    echo '<script type="text/javascript">
        function recaptcha_submitForm() {
            document.getElementById("form1").submit();
        }
        </script>';
}
?>
<script>
    $(document).ready(function() {
        $('#select_category').selectize();
        <?php
        foreach ($customFieldsBeforeMessage as $customField)
        {
            if ($customField['type'] == 'select')
            {
                echo "$('#{$customField['name']}').selectize();";
            }
        }
        foreach ($customFieldsAfterMessage as $customField)
        {
            if ($customField['type'] == 'select')
            {
                echo "$('#{$customField['name']}').selectize();";
            }
        }
        ?>
    });
</script>
<?php if (has_public_kb() && $hesk_settings['kb_recommendanswers']): ?>
<script type="text/javascript">
    var noArticlesFoundText = <?php echo json_encode($hesklang['nsfo']); ?>;

    $(document).ready(function() {
        HESK_FUNCTIONS.getKbTicketSuggestions($('input[name="subject"]'),
            $('textarea[name="message"]'),
            function(data) {
                $('.kb-suggestions').show();
                var $suggestionList = $('#kb-suggestion-list');
                var $suggestedArticlesHiddenInputsList = $('#suggested-article-hidden-inputs');
                $suggestionList.html('');
                $suggestedArticlesHiddenInputsList.html('');
                var format = '<li style="margin-bottom: 5px">' +
                    '<a class="link" href="knowledgebase.php?article={0}">{1}</a>' +
                    '<br>' +
                    '{2}' +
                    '</li>';
                var hiddenInputFormat = '<input type="hidden" name="suggested[]" value="{0}">';
                var results = false;
                $.each(data, function() {
                    results = true;
                    $suggestionList.append(format.replace('{0}', this.id).replace('{1}', this.subject).replace('{2}', this.contentPreview));
                    $suggestedArticlesHiddenInputsList.append(hiddenInputFormat.replace('{0}', this.hiddenInputValue));
                });

                if (!results) {
                    $suggestionList.append('<li>' + noArticlesFoundText + '</li>');
                }
            }
        );
    });
</script>
<?php endif; ?>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/app<?php echo $hesk_settings['debug_mode'] ? '' : '.min'; ?>.js"></script>
</body>
</html>
