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
                <div class="kb-suggestions boxed">
                    <h6><?php echo $hesklang['sc']; ?>:</h6>
                    <ul id="kb-suggestion-list" class="type--list">
                    </ul>
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
                var format = '<a href="knowledgebase.php?article={0}" class="suggest-preview">' +
                    '<div class="icon-in-circle">' +
                    '<svg class="icon icon-knowledge">' +
                    '<use xlink:href="./theme/hesk3/customer/img/sprite.svg#icon-knowledge"></use>' +
                    '</svg>' +
                    '</div>' +
                    '<div class="suggest-preview__text">' +
                    '<p class="suggest-preview__title">{1}</p>' +
                    '<p>{2}</p>' +
                    '</div>' +
                    '</a>';
                var results = false;
                $.each(data, function() {
                    results = true;
                    $('#kb-suggestion-list').append(format.replace('{0}', this.id).replace('{1}', this.subject).replace('{2}', this.contentPreview));
                });

                if (!results) {
                    $suggestionList.append('<li class="no-articles-found">' + noArticlesFoundText + '</li>');
                }
            });
        });
    </script>
    <?php
}