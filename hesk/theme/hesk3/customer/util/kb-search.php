<?php
// This guard is used to ensure that users can't hit this outside of actual HESK code
if (!defined('IN_SCRIPT')) {
    die();
}

function displayKbSearch() {
    global $hesk_settings, $hesklang;

    if ($hesk_settings['kb_search'] && $hesk_settings['kb_enable']): ?>
        <form action="knowledgebase.php" method="get" style="display: inline; margin: 0;" name="searchform">
            <div class="search__form">
                <div class="form-group">
                    <button class="btn search__submit">
                        <svg class="icon icon-search">
                            <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-search"></use>
                        </svg>
                    </button>
                    <input id="kb_search" name="search" class="form-control" type="text" placeholder="<?php echo $hesklang['search_for_articles']; ?>">
                    <?php if ($hesk_settings['kb_search'] === 1): ?>
                        <button id="search-button" type="submit" class="btn btn-full"><?php echo $hesklang['search']; ?></button>
                    <?php endif; ?>
                </div>
                <div class="kb-suggestions" style="margin: 0 auto; width: 100%; max-width: 752px; display: none">
                    <div class="alert none">
                        <div class="alert__inner">
                            <div class="alert__head">
                                <h6 class="alert__title" style="margin-bottom:10px"><?php echo $hesklang['sc']; ?>:</h6>
                            </div>
                            <ol id="kb-suggestion-list" class="type--list" style="list-style-type: decimal; padding-left: 15px;">
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php
    endif;
}

function outputSearchStyling() {
    global $hesk_settings;

    if (!$hesk_settings['kb_search'] || !$hesk_settings['kb_enable']) return;

    if ($hesk_settings['kb_search'] === 1): ?>
        #kb_search {
            width: 70%;
        }
        #search-button {
            width: 30%;
            margin-left: 10px;
            height: inherit;
        }
        <?php
    endif;
}

function outputSearchJavascript() {
    global $hesk_settings, $hesklang;

    if (!$hesk_settings['kb_search'] || !$hesk_settings['kb_enable']) return;

    ?>
    <script>
        var noArticlesFoundText = <?php echo json_encode($hesklang['nsfo']); ?>;

        $(document).ready(function() {
            HESK_FUNCTIONS.getKbSearchSuggestions($('#kb_search'), function(data) {
                $('.kb-suggestions').show();
                var $suggestionList = $('#kb-suggestion-list');
                $suggestionList.html('');
                var format = '<li style="margin-bottom: 15px; padding-left: 0.5em;">' +
                    '<a class="link" href="knowledgebase.php?article={0}">{1}</a>' +
                    '<br>' +
                    '{2}' +
                    '</li>';
                var results = false;
                $.each(data, function() {
                    results = true;
                    $('#kb-suggestion-list').append(format.replace('{0}', this.id).replace('{1}', this.subject).replace('{2}', this.contentPreview));
                });

                if (!results) {
                    $suggestionList.append('<li style="list-style-type: none; margin-left: -15px;">' + noArticlesFoundText + '</li>');
                }
            });
        });
    </script>
    <?php
}