<?php
// This guard is used to ensure that users can't hit this outside of actual HESK code
if (!defined('IN_SCRIPT')) {
    die();
}

function hesk3_show_messages($messages) {
    $style_to_class = array(
        '0' => 'white',
        '1' => 'green',
        '2' => 'blue', // Info has no CSS class
        '3' => 'orange',
        '4' => 'red'
    );
    foreach ($messages as $message):
    ?>
    <div class="main__content notice-flash">
        <div class="notification <?php echo $style_to_class[$message['style']]; ?> browser-default">
            <p><b><?php echo $message['title']; ?></b></p>
            <?php echo $message['message']; ?>
        </div>
    </div>
<?php
    endforeach;
}