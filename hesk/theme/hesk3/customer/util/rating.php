<?php
// This guard is used to ensure that users can't hit this outside of actual HESK code
if (!defined('IN_SCRIPT')) {
    die();
}

function hesk3_get_customer_rating($num) {
    $rounded_num = intval(hesk_round_to_half($num) * 10);

    return '
    <div class="star-rate rate-'. $rounded_num .'">
        <svg class="icon icon-star-stroke">
            <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-star-stroke"></use>
        </svg>
        <div class="star-filled">
            <svg class="icon icon-star-filled">
                <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-star-filled"></use>
            </svg>
        </div>
    </div>';
}