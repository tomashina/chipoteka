$(function () {

    // čekaj da se DEF filter uopće učita (content_top)
    if (!window.DEFConfig || !$('#digitalElephantFilter_form').length) {
        return;
    }

    // instantiation
    var DEFController = new DigitalElephantFilterController();

    // --- osiguraj hidden def_primary u formi ---
    if (!$('#digitalElephantFilter_form input[name="def_primary"]').length) {
        $('#digitalElephantFilter_form').append('<input type="hidden" name="def_primary" id="def_primary" value=""/>');
    }

    // makni default d-none, dalje kontrolira toggle()
    $('#filter-apply-top').removeClass('d-none');
    $('#filter-clear-top').removeClass('d-none');

    // ✅ helper: sigurno spajanje base URL + query string
    function buildUrl(baseUrl, qs) {
        qs = (qs || '').toString();
        if (!qs) return baseUrl;

        // makni vodeći ? ili &
        qs = qs.replace(/^[?&]+/, '');

        if (baseUrl.indexOf('?') !== -1) {
            if (/[?&]$/.test(baseUrl)) return baseUrl + qs;
            return baseUrl + '&' + qs;
        }

        return baseUrl + '?' + qs;
    }
    function getPanelQS() {
        // trenutno stanje forme (checked + price inputi + def_primary + ajax_digitalElephantFilter)
        var qs = $('#digitalElephantFilter_form').serialize();

        // osiguraj path jer ga forma često nema, a serveru treba za category_id
        if (DEFController.config.categoryPath) {
            qs = upsertQsParam(qs, 'path', DEFController.config.categoryPath);
        }

        // osiguraj ajax flag
        qs = upsertQsParam(qs, 'ajax_digitalElephantFilter', '1');

        // osiguraj def_primary (jer ga server koristi za “primarni kontekst”)
        qs = upsertQsParam(qs, 'def_primary', ($('#def_primary').val() || ''));

        // makni page da ne radi gluposti
        qs = qs.replace(/(^|&)page=\d+(&|$)/g, '$1').replace(/^&+|&+$/g, '');

        qs = DEFController.url.removeEmptyParams(qs);

        return qs;
    }

    // ✅ helper: upsert param u query string (dodaj ili zamijeni)
    function upsertQsParam(qs, key, value) {
        qs = (qs || '').toString().replace(/^[?&]+/, '');

        var escapedKey = key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var re = new RegExp('(^|&)' + escapedKey + '=[^&]*', 'g');
        qs = qs.replace(re, '').replace(/^&+|&+$/g, '').replace(/&&+/g, '&');

        var add = key + '=' + encodeURIComponent(value == null ? '' : String(value));
        return qs ? (qs + '&' + add) : add;
    }

    // --- TOP "Očisti" + badge sync ---
    var defFiltersApplied = false;

    function hasFilterSelectionOrChange() {
        var hasChecked = $('#digitalElephantFilter_form').find('input:checked').length > 0;
        var hasSelectedOption = $('#digitalElephantFilter_form').find('select').filter(function () {
            return ($(this).val() || '') !== '';
        }).length > 0;

        var minNow = $('#digitalElephantFilter_changing_price_min').val();
        var maxNow = $('#digitalElephantFilter_changing_price_max').val();

        var minPeak = (DEFController.config.peakPrice && DEFController.config.peakPrice.min)
            ? DEFController.config.peakPrice.min
            : minNow;

        var maxPeak = (DEFController.config.peakPrice && DEFController.config.peakPrice.max)
            ? DEFController.config.peakPrice.max
            : maxNow;

        var hasPrice = String(minNow) !== String(minPeak) || String(maxNow) !== String(maxPeak);

        return hasChecked || hasSelectedOption || hasPrice;
    }

    function getFacetKeyFromInput($el) {
        var name = $el.attr('name') || '';

        if (name.indexOf('attribute[') === 0) {
            var m1 = name.match(/attribute\[(\d+)\]/);
            return m1 ? ('attribute:' + m1[1]) : null;
        }

        if (name.indexOf('option[') === 0) {
            var m2 = name.match(/option\[(\d+)\]/);
            return m2 ? ('option:' + m2[1]) : null;
        }

        if (name.indexOf('manufacturers[') === 0) {
            return 'manufacturer';
        }

        if (name.indexOf('warehouse[') === 0) {
            return 'warehouse';
        }

        if (name.indexOf('price[') === 0) {
            return 'price';
        }

        if (name.indexOf('category[') === 0) {
            return 'category';
        }

        return null;
    }

    function ensurePrimaryContext(triggerEl) {
        var $t = $(triggerEl);

        if (!$t.is('input, select')) {
            $t = $t.closest('li, div, label, .widget-filter-item').find('input, select').first();
        }
        if (!$t.length) return;

        if ($t.is('input[type="checkbox"], input[type="radio"]') && !$t.is(':checked')) {
            return;
        }

        var current = $('#def_primary').val();
        if (current) return;

        var facetKey = getFacetKeyFromInput($t);
        if (!facetKey) return;

        $('#def_primary').val(facetKey);
    }

    function resetPrimaryContext() {
        $('#def_primary').val('');
    }

    function markFiltersDirty() {
        defFiltersApplied = false;
    }

    function toggleTopFilterButtons() {
        var hasSelection = hasFilterSelectionOrChange();
        $('#filter-apply-top').toggle(hasSelection && !defFiltersApplied);
        $('#filter-clear-top').toggle(hasSelection || defFiltersApplied);
    }

    function updateTotalFromDEF() {
        // uzmi trenutno stanje forme (checked + price + def_primary + ajax flag + path)
        var qs = getPanelQS();

        // makni page da ne krivo računa
        qs = DEFController.url.cutGetParam(qs, 'page');

        $.ajax({
            url: buildUrl(DEFController.config.action.ajaxRenderQuantityProducts, qs),
            type: 'GET',
            success: function (html) {
                var $tmp = $('<div>').html(html);

                // 1) pokušaj izvući broj iz teksta
                var text = $tmp.text().replace(/\s+/g, ' ');
                var m = text.match(/od\s+(\d+)/i);

                // 2) fallback: izvuci prvi “veći” broj ako regex promaši (ovisno o prijevodu/templateu)
                if (!m || !m[1]) {
                    m = text.match(/(\d{1,6})/);
                }

                if (m && m[1]) {
                    $('#filter-results-badge').text(m[1]);
                }
            }
        });
    }


    // ✅ rerender filter panela (da se disabled/total osvježe)
    function refreshFilterPanel() {
        var primary = $('#def_primary').val() || '';

        var getStr = getPanelQS();
        getStr = DEFController.url.cutGetParam(getStr, 'page');

        // ✅ osiguraj da se def_primary šalje
        getStr = upsertQsParam(getStr, 'def_primary', primary);

        $.ajax({
            url: buildUrl(DEFController.config.action.ajaxRenderPanel, getStr),
            type: 'GET',
            success: function (html) {
                var $tmp = $('<div>').html(html);
                var $newPanel = $tmp.find('#digitalElephantFilter_box');

                if ($newPanel.length && $('#digitalElephantFilter_box').length) {
                    $('#digitalElephantFilter_box').replaceWith($newPanel);

                    // ✅ ponovno dodaj hidden u NOVU formu + vrati vrijednost
                    var $form = $('#digitalElephantFilter_form');
                    if ($form.length) {
                        if (!$form.find('input[name="def_primary"]').length) {
                            $form.append('<input type="hidden" name="def_primary" id="def_primary" value=""/>');
                        }
                        $('#def_primary').val(primary);
                    }

                    initSliderPrice(DEFController);
                }
            }
        });
    }

    // --- debounce helper ---
    function debounce(fn, wait) {
        var t;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function () {
                fn.apply(ctx, args);
            }, wait);
        };
    }

    // --- refresh ONLY panel (bez proizvoda) ---
    var refreshFilterPanelOnly = debounce(function () {
        var primary = $('#def_primary').val() || '';
        var qs = getPanelQS();

        $.ajax({
            url: buildUrl(DEFController.config.action.ajaxRenderPanel, qs),
            type: 'GET',
            success: function (html) {
                var $tmp = $('<div>').html(html);
                var $newPanel = $tmp.find('#digitalElephantFilter_box');

                if ($newPanel.length && $('#digitalElephantFilter_box').length) {
                    $('#digitalElephantFilter_box').replaceWith($newPanel);

                    // vrati def_primary jer se DOM zamijenio
                    var $form = $('#digitalElephantFilter_form');
                    if ($form.length && !$form.find('input[name="def_primary"]').length) {
                        $form.append('<input type="hidden" name="def_primary" id="def_primary" value=""/>');
                    }
                    $('#def_primary').val(primary);

                    initSliderPrice(DEFController);
                    toggleTopFilterButtons();
                }
            }
        });
    }, 200);

    var updateTotalPreview = debounce(function () {
        updateTotalFromDEF();
    }, 200);


    // wrapaj ajaxGetProducts da nakon svakog refresh-a updatea badge + applied state + panel
    var _ajaxGetProducts = DEFController.containerProducts.ajaxGetProducts.bind(DEFController.containerProducts);

    DEFController.containerProducts.ajaxGetProducts = function ($_GET_string, isShowMore) {
        var ret = _ajaxGetProducts($_GET_string, isShowMore);

        var afterApplied = function () {
            defFiltersApplied = hasFilterSelectionOrChange();
            toggleTopFilterButtons();
            updateTotalFromDEF();
            refreshFilterPanel();
        };

        if (ret && typeof ret.always === 'function') {
            ret.always(afterApplied);
        } else {
            setTimeout(afterApplied, 400);
        }

        return ret;
    };

    // inicijalno
    updateTotalFromDEF();
    initSliderPrice(DEFController);

    defFiltersApplied = DEFController.url.isFilterParamsInUrl();
    toggleTopFilterButtons();

    // TOP "Očisti"
    $(document).on('click', '#filter-clear-top', function () {
        resetPrimaryContext();
        defFiltersApplied = false;
        toggleTopFilterButtons();
        $('#digitalElephantFilter_button_clear').trigger('click');
    });

    // TOP "Filtriraj"
    $(document).on('click', '#filter-apply-top', function () {
        $('#digitalElephantFilter_button_apply').trigger('click');
    });

    // checkbox/radio direktno
    $(document).on(
        'click',
        '#digitalElephantFilter_form input[type="checkbox"], #digitalElephantFilter_form input[type="radio"]',
        function () {
            var el = this;

            setTimeout(function () {
                if ($(el).is(':checked')) {
                    ensurePrimaryContext(el);
                }

                markFiltersDirty();

                // 🔥 odmah sakrij sekundarne u panelu
                refreshFilterPanelOnly();
                updateTotalPreview();

                toggleTopFilterButtons();
            }, 0);
        }
    );

    // select + ostali inputi (npr. price min/max)
    $(document).on(
        'change',
        '#digitalElephantFilter_form select, #digitalElephantFilter_form input:not([type="checkbox"]):not([type="radio"])',
        function () {
            ensurePrimaryContext(this);
            markFiltersDirty();
            refreshFilterPanelOnly();
            updateTotalPreview();
            toggleTopFilterButtons();
        }
    );

    // original apply
    $(document).on('click', '#digitalElephantFilter_button_apply', function () {
        setTimeout(toggleTopFilterButtons, 50);
    });

    // original clear
    $(document).on('click', '#digitalElephantFilter_button_clear', function () {
        resetPrimaryContext();
        defFiltersApplied = false;
        toggleTopFilterButtons();
        setTimeout(toggleTopFilterButtons, 50);
    });

    // CONTAINER PRODUCT REQUIRE STYLE
    $(DEFController.containerProducts.selector).css('position', 'relative');

    // toggle section open/close
    $(document).on('click', DEFController.filterPanel.selectorElementTitle, function () {
        var $this = this;
        $(this).next('div').slideToggle(500, function () {
            DEFController.filterPanel.setStateSection($this);
        });
    });

    // ajax get products
    if (DEFController.config.isButtonApply) {
        $(document).on('click', DEFController.filterPanel.selectorButtonApply, function () {
            DEFController.changeFilterData();
        });
    } else {
        $(document).on('change', DEFController.filterPanel.selectorControlElements, function () {
            DEFController.changeFilterData();
        });
    }

    // pagination
    $(document).on('click', DEFController.pagination.selectorLink, function () {
        var linkWidthNewPage = $(this).attr('href');
        DEFController.changePagination(linkWidthNewPage);
        return false;
    });

    // page reload
    if (DEFController.url.isFilterParamsInUrl()) {
        DEFController.pageReloaded();
        refreshFilterPanel();
    }

    // change limit
    $(DEFController.limit.selector).attr('onchange', '');
    $(DEFController.limit.selector).change(function () {
        DEFController.changeLimit($(this).val());
    });

    // change sort
    $(DEFController.sort.selector).attr('onchange', '');
    $(DEFController.sort.selector).change(function () {
        DEFController.changeSort($(this).val());
    });

    // reset (original clear)
    $(document).on('click', DEFController.filterPanel.selectorButtonClear, function () {
        resetPrimaryContext();
        defFiltersApplied = false;
        toggleTopFilterButtons();
        DEFController.reset();
    });

    // show more
    if (DEFController.buttonShowMore.isset()) {
        if (!DEFController.url.isFilterParamsInUrl()) {
            DEFController.buttonShowMore.render(DEFController.$_GET_string(), true);
        }

        $(document).on('click', DEFController.buttonShowMore.selector, function () {
            DEFController.clickOnButtonShowMore(this);
        });
    }
});

function initSliderPrice(DEFController) {
    if (DEFController.sliderPrice.isset()) {

        DEFController.sliderPrice.initValues();

        $(DEFController.sliderPrice.selector).slider({
            slide: function () {
                DEFController.sliderPrice.setChangePriceMin($(DEFController.sliderPrice.selector).slider("values", 0));
                DEFController.sliderPrice.setChangePriceMax($(DEFController.sliderPrice.selector).slider("values", 1));
            }
        });

        $(DEFController.sliderPrice.selector).slider({
            stop: function () {
                DEFController.sliderPrice.setChangePriceMin($(DEFController.sliderPrice.selector).slider("values", 0));
                DEFController.sliderPrice.setChangePriceMax($(DEFController.sliderPrice.selector).slider("values", 1));

                if (!DEFController.config.isButtonApply) {
                    DEFController.changeFilterData();
                }
            }
        });

        $(document).on('change', DEFController.sliderPrice.selectorPriceMin, function () {
            DEFController.sliderPrice.changeMinSlideOnKeyword();
        });

        $(document).on('change', DEFController.sliderPrice.selectorPriceMax, function () {
            DEFController.sliderPrice.changeMaxSlideOnKeyword();
        });
    }
}
