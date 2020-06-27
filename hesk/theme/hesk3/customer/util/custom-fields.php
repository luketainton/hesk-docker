<?php
// This guard is used to ensure that users can't hit this outside of actual HESK code
if (!defined('IN_SCRIPT')) {
    die();
}

function hesk3_output_custom_fields($customFields) {
    global $hesk_settings, $hesklang;

    foreach ($customFields as $customField) {
        switch ($customField['type']) {
            case 'radio':
                ?>
                <div class="form-group <?php echo $customField['iserror'] ? 'isError' : '' ?>">
                    <label class="label <?php echo $customField['req'] ? 'required' : '' ?>">
                        <?php echo $customField['name:']; ?>
                    </label>
                    <?php
                    $i = 0;
                    foreach ($customField['value']['options'] as $option):
                        ?>
                        <div class="radio-custom">
                            <input type="radio" name="<?php echo $customField['name'] ?>"
                                   id="<?php echo $customField['name'].$i; ?>"
                                   value="<?php echo $option['value']; ?>"
                                   <?php echo $option['selected'] ? 'checked' : ''; ?>>
                            <label for="<?php echo $customField['name'].$i; ?>">
                                <?php echo $option['value']; ?>
                            </label>
                        </div>
                    <?php
                        $i++;
                    endforeach; ?>
                </div>
            <?php
                break;
            case 'select':
                ?>
                <section class="param blue-select">
                    <span class="label <?php echo $customField['req'] ? 'required' : '' ?>"><?php echo $customField['name:']; ?></span>
                        <select name="<?php echo $customField['name']; ?>" id="<?php echo $customField['name']; ?>">
                            <?php if (!empty($customField['value']['show_select'])): ?>
                            <option value=""><?php echo $hesklang['select']; ?></option>
                            <?php
                            endif;
                            $i = 0;
                            foreach ($customField['value']['options'] as $option):
                            ?>
                            <option <?php echo $option['selected'] ? 'selected' : '' ?>><?php echo $option['value']; ?></option>
                            <?php
                                $i++;
                            endforeach; ?>
                        </select>
                </section>
            <?php
                break;
            case 'checkbox':
                ?>
                <section class="param checkboxs">
                    <label class="label <?php echo $customField['req'] ? 'required' : '' ?>"><?php echo $customField['name:']; ?></label>
                    <?php
                    $i = 0;
                    foreach ($customField['value']['options'] as $option):
                        ?>
                    <div class="checkbox-custom">
                        <input type="checkbox" id="<?php echo $customField['name'].$i; ?>"
                               name="<?php echo $customField['name']; ?>[]" value="<?php echo $option['value']; ?>"
                               <?php if ($customField['iserror']): ?>class="isError"<?php endif; ?>
                            <?php echo $option['selected'] ? 'checked' : ''; ?>>
                        <label for="<?php echo $customField['name'].$i; ?>"><?php echo $option['value']; ?></label>
                    </div>
                        <?php
                        $i++;
                    endforeach;
                    ?>
                </section>
                <?php
                break;
            case 'textarea':
                ?>
                <div class="form-group">
                    <label class="label <?php echo $customField['req'] ? 'required' : '' ?>"><?php echo $customField['name:']; ?></label>
                    <textarea name="<?php echo $customField['name']; ?>"
                              rows="<?php echo intval($customField['value']['rows']); ?>"
                              cols="<?php echo intval($customField['value']['cols']); ?>"
                              class="form-control <?php if ($customField['iserror']): ?><?php endif; ?>"
                              <?php echo $customField['req'] ? 'required' : '' ?>><?php echo $customField['original_value']; ?></textarea>
                </div>
            <?php
                break;
            case 'date':
                ?>
                <!--[if !IE]><!-->
                <section class="param calendar">
                    <label class="label <?php echo $customField['req'] ? 'required' : '' ?>"><?php echo $customField['name:']; ?></label>
                    <div class="calendar--button">
                        <button type="button">
                            <svg class="icon icon-calendar">
                                <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-calendar"></use>
                            </svg>
                        </button>
                        <input name="<?php echo $customField['name']; ?>"
                               value="<?php echo $customField['original_value']; ?>"
                               type="text"
                               class="datepicker">
                    </div>
                    <div class="calendar--value" <?php if ($customField['original_value']) { ?>style="display: block"<?php } ?>>
                        <span><?php echo $customField['original_value']; ?></span>
                        <i class="close">
                            <svg class="icon icon-close">
                                <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-close"></use>
                            </svg>
                        </i>
                    </div>
                </section>
                <!--<![endif]-->
                <!--[if IE]>
                <div class="form-group">
                    <label class="label <?php echo $customField['req'] ? 'required' : '' ?>">
                        <?php echo $customField['name:']; ?>
                    </label>
                    <input type="text" class="form-control <?php if ($customField['iserror']) { ?>isError<?php } ?>"
                           value="<?php echo $customField['original_value']; ?>"
                           name="<?php echo $customField['name']; ?>"
                           <?php echo $customField['req'] ? 'required' : '' ?>>
                    <label class="label">
                        <?php echo $hesklang['d_format']; ?>: <?php echo date($customField['value']['date_format'], mktime(0, 0, 0, 12, 30, date('Y'))); ?>
                    </label>
                </div>
                <![endif]-->
            <?php
                break;
            case 'email':
                $suggest = $hesk_settings['detect_typos'] ?
                    'onblur="HESK_FUNCTIONS.suggestEmail(\''.$customField['name'].'\', \''.$customField['name'].'_suggestions\', 0'.($customField['value']['multiple'] ? ',1' : '').')"' :
                    '';
                ?>
                <div class="form-group">
                    <label class="label <?php echo $customField['req'] ? 'required' : '' ?>">
                        <?php echo $customField['name:']; ?>
                    </label>
                    <input type="<?php echo $customField['value']['multiple'] ? 'text' : 'email'; ?>"
                           id="<?php echo $customField['name']; ?>"
                           class="form-control"
                           value="<?php echo $customField['original_value']; ?>"
                           name="<?php echo $customField['name']; ?>"
                           <?php echo $customField['req'] ? 'required' : '' ?>
                            <?php echo $suggest; ?>>
                    <div id="<?php echo $customField['name']; ?>_suggestions"></div>
                </div>
            <?php
                break;
            case 'hidden':
                ?>
                <input type="hidden"
                       name="<?php echo $customField['name']; ?>"
                       value="<?php echo $customField['value']['default_value']; ?>">
            <?php
                break;
            default:
                ?>
                <div class="form-group">
                    <label class="label <?php echo $customField['req'] ? 'required' : '' ?>">
                        <?php echo $customField['name:']; ?>
                    </label>
                    <input type="text" class="form-control <?php if ($customField['iserror']) { ?>isError<?php } ?>"
                           value="<?php echo $customField['value']['default_value']; ?>"
                           name="<?php echo $customField['name']; ?>"
                           maxlength="<?php echo intval($customField['value']['max_length']); ?>"
                           <?php echo $customField['req'] ? 'required' : '' ?>>
                </div>
            <?php
                break;
        }
    }
}

function hesk3_output_custom_fields_for_display($customFields) {
    foreach ($customFields as $customField)
    {
        switch ($customField['type'])
        {
            case 'email':
                $customField['value'] = '<a href="mailto:'.$customField['value'].'">'.$customField['value'].'</a>';
                break;
            case 'date':
                $customField['value'] = hesk_custom_date_display_format($customField['value'], $customField['date_format']);
                break;
        }

        echo '
            <div>
                <span style="color: #959eb0">'.$customField['name:'].'</span>
                <span>'.$customField['value'].'</span>
            </div>
            ';
    }
}
