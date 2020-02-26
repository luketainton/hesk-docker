<?php
// This guard is used to ensure that users can't hit this outside of actual HESK code
if (!defined('IN_SCRIPT')) {
    die();
}

function showReplyForm($trackingId, $email, $reopen) {
    global $hesk_settings, $hesklang;
    ?>
    <article class="ticket__body_block">
        <div class="text-bold"><?php echo $hesklang['add_reply']; ?></div>
        <form method="post" action="reply_ticket.php" class="form form--reply" enctype="multipart/form-data">
            <div class="form-group required">
                <label class="label"><?php echo $hesklang['message']; ?></label>
                <textarea name="message" class="form-control"><?php if (isset($_SESSION['ticket_message'])) {
                        echo stripslashes(hesk_input($_SESSION['ticket_message']));
                    } ?></textarea>
            </div>
            <?php
            /* attachments */
            if ($hesk_settings['attachments']['use']) {
                ?>
                <section class="param param--attach">
                    <span class="label"><?php echo $hesklang['attachments']; ?></span>
                    <div class="attach">
                        <?php
                        for ($i = 1; $i <= $hesk_settings['attachments']['max_number']; $i++) {
                            echo '<input type="file" name="attachment[' . $i . ']" size="50"><br>';
                        }
                        ?>
                        <div class="attach-tooltype">
                            <span><?php echo sprintf($hesklang['maximum_x_attachments'], $hesk_settings['attachments']['max_number']); ?></span>
                            <a onclick="HESK_FUNCTIONS.openWindow('file_limits.php',250,500)">
                                <div class="tooltype right">
                                    <svg class="icon icon-info">
                                        <use
                                            xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-info"></use>
                                    </svg>
                                </div>
                            </a>
                        </div>
                    </div>
                </section>
                <?php
            }
            ?>
            <section class="form__submit">
                <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                <input type="hidden" name="orig_track" value="<?php echo $trackingId; ?>">
                <button type="submit" class="btn btn-full"
                        ripple="ripple"><?php echo $hesklang['submit_reply']; ?></button>
                <?php
                if ($hesk_settings['email_view_ticket']) {
                    echo '<input type="hidden" name="e" value="' . $email . '">';
                }
                if ($reopen) {
                    echo '<input type="hidden" name="reopen" value="1">';
                }
                ?>
            </section>
        </form>
    </article>
    <?php
}