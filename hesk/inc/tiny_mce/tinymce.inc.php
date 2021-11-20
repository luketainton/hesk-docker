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


function hesk_tinymce_init($selector='#message')
{
    ?>
    <script>
        tinymce.init({
            selector: '<?php echo $selector; ?>',
            convert_urls: false,
            branding: false,
            browser_spellcheck: true,
            toolbar: 'undo redo | styleselect fontselect fontsizeselect | bold italic underline | alignleft aligncenter alignright alignjustify | forecolor backcolor | bullist numlist outdent indent | link unlink image codesample code',
            plugins: 'charmap code codesample image link lists table autolink',
            height: 350,
            toolbar_mode: 'sliding',
            mobile: {
                toolbar_mode: 'scrolling',
                height: 300
            }
        });
    </script>
    <?php
} // END hesk_tinymce_init()
